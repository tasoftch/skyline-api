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


use Symfony\Component\HttpFoundation\Request;

interface ActionControllerInterface
{
    /**
     * If an incoming request is not an api call, this method is asked to accept it or deny.
     *
     * @param Request $request
     * @return bool
     */
    public function acceptsAnonymousRequest(Request $request): bool;

    /**
     * CORS (Cross Origin Resource Sharing)
     *
     * The api controller must declare, if it will accept a request coming from a different origin
     *
     * @param Request $request
     * @return bool
     */
    public function acceptsCrossOriginRequest(Request $request): bool;

    /**
     * CORS
     *
     * If the controller generally accepts cross origin requests, this method is called if the controller accepts the source origin.
     * If it does not, a wildcard cross origin header is sent to the requesting client.
     * If the requesting client must transmit credentials to the api, set $requireCredentials to true.
     *
     * @param Request $request
     * @param bool $requireCredentials
     * @return bool
     */
    public function acceptOrigin(Request $request, bool &$requireCredentials = false): bool;

    /**
     * CORS
     *
     * If the controller accepts cross origin requests, this method is asked about the http methods it will accept
     * You can return NULL to allow all
     *
     * @param Request $request
     * @return array|null
     */
    public function getAcceptedHTTPMethods(Request $request): ?array;

    /**
     * CORS
     *
     * If the controller accepts cross origin requests, this method is asked about the http headers it will accept
     * You can return NULL to allow all
     *
     * @param Request $request
     * @return array|null
     */
    public function getAcceptedHTTPHeaders(Request $request): ?array;

    /**
     * Determines if the request is a preflight request. (If the HTTP method is OPTIONS)
     *
     * @param Request $request
     * @return bool
     */
    public function isPreflightRequest(Request $request): bool;
}