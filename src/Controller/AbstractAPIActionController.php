<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Skyline\API\Controller;


use Exception;
use Skyline\API\Error\Deprecated;
use Skyline\API\Error\Fatal;
use Skyline\API\Error\Notice;
use Skyline\API\Error\Warning;
use Skyline\API\Exception\APIException;
use Skyline\API\Exception\DeniedCrossOriginRequestException;
use Skyline\API\Exception\DeniedRequestException;
use Skyline\API\Render\Model\APIModel;
use Skyline\API\Render\Model\APIModelInterface;
use Skyline\Application\Controller\AbstractActionController;
use Skyline\Application\Exception\ActionCancelledException;
use Skyline\CMS\Security\Exception\CSRFMissmatchException;
use Skyline\Kernel\Service\CORSService;
use Skyline\Kernel\Service\Error\AbstractErrorHandlerService;
use Skyline\Kernel\Service\SkylineServiceManager;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Template\NullTemplate;
use Skyline\Router\Description\ActionDescriptionInterface;
use Skyline\Security\CSRF\CSRFToken;
use Skyline\Security\CSRF\CSRFTokenManager;
use Skyline\Security\Exception\SecurityException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TASoft\Service\ServiceManager;
use Throwable;

abstract class AbstractAPIActionController extends AbstractActionController implements ActionControllerInterface
{
    /**
     * @inheritDoc
     */
    public function acceptsAnonymousRequest(Request $request): bool
    {
        return SkyGetRunModes() > SKY_RUNMODE_PRODUCTION;
    }

    /**
     * @inheritDoc
     */
    public function acceptsCrossOriginRequest(Request $request): bool {
        return CORSService::isRegistered( $request->getHost() );
    }

    /**
     * @inheritDoc
     */
    public function acceptOrigin(Request $request, bool &$requireCredentials = false): bool {
        return CORSService::getAllowedOriginOf($request, $requireCredentials) ? true : false;
    }

    /**
     * @inheritDoc
     */
    public function getAcceptedHTTPMethods(Request $request): ?array {
        return [
            "GET",
            "POST",
            "OPTIONS"
        ];
    }

    /**
     * Specifies a default render name
     *
     * @return string|null
     */
    protected function getDefaultRenderName(): ?string {
        return NULL;
    }

    /**
     * @inheritDoc
     */
    public function getAcceptedHTTPHeaders(Request $request): ?array {
        return NULL;
    }

    /**
     * @inheritDoc
     */
    public function isPreflightRequest(Request $request): bool {
        return strcasecmp( $request->getMethod(), 'OPTIONS' ) === 0;
    }

    /**
     * If inside an api request an exception occurs, the controller may decide how to handle it.
     * Without doing anything, the default behaviour of the Skyline Application will handle it.
     *
     * @param Throwable $exception
     * @param $actionDescription
     * @return bool Skyline will treat a returning true as exception handled, don't do anything and continue.
     */
    protected function handleException(Throwable $exception, $actionDescription): bool {
        $error = new \Skyline\API\Error\Exception($exception->getMessage(), $exception->getCode()*1, $exception->getFile(), $exception->getLine());
        $error->setException($exception);
        $this->getModel()->addError($error);
        return true;
    }

    /**
     * Again if inside an api request an error occurs, the controller may decide before Skyline does.
     *
     * @param int $code
     * @param $message
     * @param $file
     * @param $line
     * @return bool
     * @see AbstractAPIActionController::handleException()
     */
    protected function handleError(int $code, $message, $file, $line): bool {
        $level = AbstractErrorHandlerService::detectErrorLevel($code);
        switch ($level) {
            case AbstractErrorHandlerService::NOTICE_ERROR_LEVEL:
                $class = Notice::class; break;
            case AbstractErrorHandlerService::DEPRECATED_ERROR_LEVEL:
                $class = Deprecated::class; break;
            case AbstractErrorHandlerService::WARNING_ERROR_LEVEL:
                $class = Warning::class; break;
            default:
                $class = Fatal::class;
        }

        $this->getModel()->addError(new $class($message, $code, $file, $line));

        return true;
    }

    /**
     * Gets or creates the API model
     *
     * @return APIModelInterface
     */
    protected function getModel(): APIModelInterface {
        $model = $this->getRenderInfo()->get( RenderInfoInterface::INFO_MODEL );
        if(NULL === $model)
            $model = $this->makeAPIModel();

        if($model instanceof APIModelInterface) {
            $this->getRenderInfo()->set(RenderInfoInterface::INFO_MODEL, $model);
            return $model;
        }
        throw new APIException("Model used by API controller must be of class APIModelInterface");
    }

    /**
     * Subclass this method to make a different api model
     *
     * @return APIModelInterface
     */
    protected function makeAPIModel(): APIModelInterface {
        return new APIModel();
    }

	/**
	 * This method gets called to verify if the request is valid against a csrf token.
	 * By default this method requires csrf check for all requests except GET and OPTIONS
	 *
	 * @param Request $request
	 * @return bool
	 */
    protected function enableCsrfCheck(Request $request): bool {
    	if(stripos($request->getMethod(), 'GET') === false)
    		return true;
    	return false;
	}

    /**
     * If the request is a preflight, this method gets called how to handle it
     *
     * @param ActionDescriptionInterface $actionDescription
     * @param RenderInfoInterface $renderInfo
     * @param Request $request
     */
    protected function handlePreflightRequest(Request $request, ActionDescriptionInterface $actionDescription, RenderInfoInterface $renderInfo) {
        /** @var Response $response */
        $response = $this->response;

        $eventManager = SkylineServiceManager::getEventManager();
        $eventManager->trigger(SKY_EVENT_TEAR_DOWN);

        $response->prepare( $request );
        if($response->isNotModified( $request ))
            $response->sendHeaders();
        else
            $response->send();

        exit();
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws Throwable
     */
    public function performAction(ActionDescriptionInterface $actionDescription, RenderInfoInterface $renderInfo)
    {
        try {
            /** @var Request $request */
            $this->renderInfo = $renderInfo;
            if($rnd = $this->getDefaultRenderName())
                $this->preferRender($rnd);

            set_error_handler(function(int $code, string $message, $file, $line) {
                if(error_reporting() & $code) {
                    if(!$this->handleError($code, $message, $file, $line)) {
                        $level = AbstractErrorHandlerService::detectErrorLevel($code);
                        if($level > AbstractErrorHandlerService::WARNING_ERROR_LEVEL)
                            return false;
                    }
                }
                return true;
            });

            $request = $this->request;

            $origin = CORSService::getOriginOfRequest($request, $originHost);
            if(!$origin && !$this->acceptsAnonymousRequest($request)) {
                $e = new DeniedRequestException("Anonymous requests are not allowed for the required action", 403);
                $e->setRequest($request);
                throw $e;
            }

			$serverHost = CORSService::getHostOfRequest($request);
            if($originHost != $serverHost) {
                // Resolve cross origin request
                if($this->acceptsCrossOriginRequest($request)) {
                    /** @var Response $response */
                    $response = $this->response;

                    if($response instanceof Response) {
                        $requireCredentials = false;
                        $theOrigin = "*";

                        if($this->acceptOrigin($request, $requireCredentials)) {
                            $theOrigin = $origin;
                        }

                        $response->headers->set("Access-Control-Allow-Origin", $theOrigin, true);
                        if($requireCredentials)
                            $response->headers->set("Access-Control-Allow-Credentials", 'true', true);
                        if($methods = $this->getAcceptedHTTPMethods($request)) {
                            $methods = array_map(function($a) { return strtoupper($a); }, $methods);
                            $response->headers->set("Access-Control-Allow-Methods", implode(",", $methods), true);
                        }
                        if($headers = $this->getAcceptedHTTPHeaders($request)) {
                            $headers = array_map(function($a) { return strtoupper($a); }, $headers);
                            $response->headers->set("Access-Control-Allow-Headers", implode(",", $headers), true);
                        }
                    } else {
                        throw new APIException("Response expected in service management", 500);
                    }
                } else {
                    $e = new DeniedCrossOriginRequestException("Cross origin requests are not permitted for this action", 403);
                    $e->setRequest($request);
                    throw $e;
                }
            }

			if( $this->isPreflightRequest($request) ) {
				$this->handlePreflightRequest($request, $actionDescription, $renderInfo);
				exit();
			}

			// Make the render info renderable
			$renderInfo->set(RenderInfoInterface::INFO_TEMPLATE, new NullTemplate());

			if($this->enableCsrfCheck($request)) {
				$csrf = ServiceManager::generalServiceManager()->get("CSRFManager");
				$tn = ServiceManager::generalServiceManager()->getParameter("api.js.csrf-token-name");
				if($csrf instanceof CSRFTokenManager) {
					if(!$csrf->isTokenValid( $tk = new CSRFToken($tn, $request->request->get($tn)) )) {
						$e = new CSRFMissmatchException("Request is invalid", 403);
						$e->setToken($tk);
						throw $e;
					}
				} else {
					throw new SecurityException("No CSRF management defined in service manager, can not validate request", 403);
				}
			}

            parent::performAction($actionDescription, $renderInfo);
        } catch (ActionCancelledException $exception) {
        } catch (Throwable $exception) {
            if(!$this->handleException($exception, $actionDescription))
                throw $exception;
        } finally {
            restore_error_handler();
        }
    }
}