<?php
/*
 * BSD 3-Clause License
 *
 * Copyright (c) @today.year, TASoft Applications
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
 */

namespace Skyline\API\Controller;

use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Security\CSRF\CSRFToken;
use Skyline\Security\CSRF\CSRFTokenManager;
use TASoft\Service\ServiceManager;

/**
 * Use this trait by default controllers to ensure secure api calls using a csrf token.
 * The csrf token gets defined in the skyline/component-api package, so the JavaScript window.Skyline.API.post will automatically send the csrf token to the call.
 * The API controller itself also checks if the token is valid before performing the action.
 *
 * @package Skyline\API
 * @property RenderInfoInterface $renderInfo
 */
trait APICallControllerTrait
{
	/**
	 * If you want to call the api with POST requests, the action controller for the main page should invoke this method
	 * to ensure that the csrf token for the api gets rendered.
	 * This method only sets a cookie with the csrf information, because the browsers do not accept setting cookies from components.
	 *
	 * @param string|null $name
	 * @return CSRFToken|null
	 */
	protected function renderApiCsrfToken($name = NULL): ?CSRFToken {
		if(NULL === $name)
			$name = ServiceManager::generalServiceManager()->getParameter("api.js.csrf-token-name");
		$csrf = ServiceManager::generalServiceManager()->get("CSRFManager");
		if($csrf instanceof CSRFTokenManager) {
			// Just set the token
			return $csrf->getToken( $name );
		}
		return NULL;
	}
}