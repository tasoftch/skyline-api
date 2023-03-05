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

namespace Skyline\API\Render\Model;

class HTMLModelAdapter
{
	/** @var APIModel */
	private $model;

	/**
	 * @param APIModel $model
	 */
	public function __construct(APIModel $model)
	{
		$this->model = $model;
	}

	/**
	 * @return APIModel
	 */
	public function getModel(): APIModel
	{
		return $this->model;
	}

	public function addContent(string $html): static {
		$c = $this->model["content"] ?? "";
		$c .= $html;
		$this->model["content"] = $c;
		return $this;
	}

	public function setContent(string $html): static {
		$this->model["content"] = $html;
		return $this;
	}

	public function addHeader($key, $content): static {
		$h = $this->model["headers"] ?: [];
		$h[$key] = $content;
		$this->model["headers"] = $h;
		return $this;
	}

	public function makeAutoIncludeHeader(): void
	{
		$this->setContent("## SKY_AUTOLOAD ##");
	}

	public function beginOutputCapture(): void
	{
		ob_start();
	}

	public function stopOutputCapture(): void
	{
		$this->addContent( ob_get_contents() );
		ob_end_clean();
	}
}