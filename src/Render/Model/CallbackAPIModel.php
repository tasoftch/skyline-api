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

namespace Skyline\API\Render\Model;


use Generator;
use Skyline\API\Error\Deprecated;
use Skyline\API\Error\ErrorInterface;
use Skyline\API\Error\Exception;
use Skyline\API\Error\Fatal;
use Skyline\API\Error\Notice;
use Skyline\API\Error\Warning;
use Skyline\Kernel\Service\Error\AbstractErrorHandlerService;

/**
 * This model allows to yield the model values using a callback function.
 * This callback MUST be a generator and further yield the values on demand.
 *
 * @package Skyline\API\Render\Model
 */
class CallbackAPIModel implements APIModelInterface
{
    private $errors;
    private $callback;

    /**
     * CallbackAPIModel constructor.
     * @param $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }


    public function getErrors()
    {
        return $this->errors;
    }

    public function addError(ErrorInterface $error)
    {
        $this->errors[] = $error;
    }

    public function yieldValue(): Generator
    {
        set_error_handler(function(int $code, string $message, $file, $line) {
            if(error_reporting() & $code) {
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

                $this->addError(new $class($message, $code, $file, $line));

                return $level >= AbstractErrorHandlerService::FATAL_ERROR_LEVEL ? false : true;
            }
            return true;
        });

        try {
            yield from call_user_func( $this->getCallback() );
        } catch (\Exception $exception) {
            $error = new Exception($exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine());
            $error->setException($exception);
            $this->addError($error);
        }

        restore_error_handler();
    }

    /**
     * @return callable
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }
}