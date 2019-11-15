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

/**
 * JSONRenderTest.php
 * skyline-api
 *
 * Created on 2019-11-15 17:29 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\API\Render\JSONRender;
use Skyline\API\Render\Model\APIModel;
use Skyline\Render\Info\RenderInfo;
use Skyline\Render\Info\RenderInfoInterface;
use Symfony\Component\HttpFoundation\Response;

class JSONRenderTest extends TestCase
{
    public function testJSONRender() {
        $render = new JSONRender();

        $this->assertEquals("application/json", $render->getContentType());

        $model = new APIModel();
        $model["test"] = 23;
        $model->addArray( ['other' => 55, "my" => "Hello"] );

        $render->render(
            new RenderInfo([
                RenderInfoInterface::INFO_MODEL => $model
            ])
        );

        $this->assertEmpty($this->getActualOutput());

        $response = $render->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertJson($response->getContent());
        $this->assertEquals(json_encode([
            'test' => 23,
            'other' => 55,
            'my' => 'Hello',
            'errors' => [],
            'success' => true
        ], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), $response->getContent());
    }
}
