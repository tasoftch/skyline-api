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


use DOMDocument;
use DOMElement;
use DOMNode;
use SimpleXMLElement;
use Skyline\API\Render\Model\APIModelInterface;
use Symfony\Component\HttpFoundation\Response;

class XMLRender extends AbstractOutputRender
{
    const RENDER_NAME = 'xml-render';
    const NUMBER_TAG_NAME = 'ITEM';

    public function getContentType(): string
    {
        return "text/xml;charset=utf-8";
    }

    protected function renderModel(APIModelInterface $model)
    {
        $DOM = new DOMDocument("1.0", "UTF-8");
        $DOM->formatOutput = true;

        $CONTENT = $DOM->createElement("response");
        $DOM->appendChild($CONTENT);

        $insertObject = function($key, $value, DOMElement $parent) use (&$insertObject, $DOM) {
            if($value instanceof DOMNode) {
                $parent->appendChild($value);
                return;
            }
            elseif($value instanceof SimpleXMLElement) {
                $parent->appendChild( dom_import_simplexml($value) );
                return;
            }
            elseif (is_numeric($key))
                $key = static::NUMBER_TAG_NAME;

            $element = $DOM->createElement($key);
            if(is_scalar($value))
                $element->textContent = $value;
            elseif(is_iterable($value)) {
                foreach ($value as $k => $v) {
                    $insertObject($k, $v, $element);
                }
            } else {
                trigger_error("Can not render value for key $key into xml response", E_USER_WARNING);
            }
        };

        $CONTENT->setAttribute("success", "YES");

        foreach($model->yieldValue() as $key => $value) {
            $insertObject($key, $value, $CONTENT);
        }



        $response = $this->getResponse() ?: new Response();

        if($errors = $model->getErrors()) {
            $E = $DOM->createElement("errors");
            if($fc = $CONTENT->firstChild)
                $CONTENT->insertBefore($E, $fc);
            else
                $CONTENT->appendChild($E);

            foreach($errors as $error) {
                $ec = explode("\\", get_class($error));
                $ex = $DOM->createElement(strtoupper(array_pop($ec)));

                $ex->setAttribute("code", $error->getCode());
                $ex->setAttribute("file", $error->getFile());
                $ex->setAttribute("line", $error->getLine());
                $ex->textContent = $error->getMessage();

                $E->appendChild($ex);
            }
        }

        $CONTENT->setAttribute("code", $response->getStatusCode());

        $response->setContent( $DOM->saveXML() );
        $this->setResponse($response);
    }


}