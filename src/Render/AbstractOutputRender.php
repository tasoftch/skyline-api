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

namespace Skyline\API\Render;


use Skyline\API\Render\Model\APIModelInterface;
use Skyline\Render\AbstractRender;
use Skyline\Render\Exception\RenderException;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Template\TemplateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TASoft\Service\ServiceForwarderTrait;

abstract class AbstractOutputRender implements OutputRenderInterface
{
    const RENDER_NAME = '';

    use ServiceForwarderTrait;

    /** @var Request */
    private $request;
    /** @var Response|null */
    private $response;

    /** @var AbstractRender|null */
    private static $currentRender;

    /**
     * @return AbstractRender|null
     */
    public static function getCurrentRender(): AbstractRender
    {
        if(!self::$currentRender) {
            $e = new RenderException("Calling current render when not in render context is not allowed");
            throw $e;
        }
        return self::$currentRender;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        if(!$this->request)
            return $this->getServiceManager()->get("request");
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @return Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * @param Response|null $response
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function renderTemplate(TemplateInterface $template, $additionalInfo = NULL)
    {
        // Not used in API render environment
    }

    public function render(RenderInfoInterface $renderInfo)
    {
        $model = $renderInfo->get( RenderInfoInterface::INFO_MODEL );
        if($model instanceof APIModelInterface)
            $this->renderModel($model);
    }

    /**
     * Use this method to render the model into a valid response.
     *
     * @param APIModelInterface $model
     * @return void
     */
    abstract protected function renderModel(APIModelInterface $model);
}