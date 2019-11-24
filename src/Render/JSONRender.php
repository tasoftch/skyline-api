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


use Skyline\API\Error\Deprecated;
use Skyline\API\Error\Exception;
use Skyline\API\Error\Fatal;
use Skyline\API\Error\Warning;
use Skyline\API\Render\Model\APIModelInterface;
use Symfony\Component\HttpFoundation\Response;

class JSONRender extends AbstractOutputRender
{
    const RENDER_NAME = 'json-render';

    public function getContentType(): string
    {
        return "application/json";
    }

    protected function renderModel(APIModelInterface $model)
    {
        $data = [];
        foreach($model->yieldValue() as $key => $value)
            $data[$key] = $value;

        $data["errors"] = [];
        $data["success"] = true;

        foreach($model->getErrors() as $error) {
            $data["errors"][] = [
                'level' => (function() use ($error) {
                    switch (get_class($error)) {
                        case Exception::class:
                            return "exception";
                        case Fatal::class:
                            return "error";
                        case Warning::class:
                            return "warning";
                        case Deprecated::class:
                            return "deprecated";
                        default:
                            return "notice";
                    }
                })(),
                'code' => $error->getCode(),
                'message' => $error->getMessage(),
                'file' => $error->getFile(),
                "line" => $error->getLine()
            ];
            if($error instanceof Fatal)
                $data["success"] = false;
        }

        $response = $this->getResponse() ?: new Response();
        $response->setContent( json_encode( $data, $this->getJSONEncodingFlags() ) );
        $this->setResponse($response);
    }

    protected function getJSONEncodingFlags(): int {
        return JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES;
    }
}