<?php


namespace Skyline\API\Render;


use Exception;
use Skyline\API\Render\Model\APIModelInterface;
use Skyline\Render\AbstractRender;
use Skyline\Render\Context\RenderContextInterface;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Template\TemplateInterface;

class PartialTemplateRender extends AbstractRender implements OutputRenderInterface
{
	const RENDER_NAME = 'partial-template-render';
	private $resolvedModel = [];

	/**
	 * @inheritDoc
	 */
	protected function renderModel(APIModelInterface $model)
	{
		foreach($model->yieldValue() as $key => $value)
			$this->resolvedModel[$key] = $value;
	}

	public function render(RenderInfoInterface $renderInfo)
	{
		$model = $renderInfo->get( RenderInfoInterface::INFO_MODEL );
		if($model instanceof APIModelInterface)
			$this->renderModel($model);

		$template = $renderInfo->get( RenderInfoInterface::INFO_TEMPLATE );
		if($template instanceof TemplateInterface) {
			$model = $this->resolvedModel ?: $renderInfo->get( RenderInfoInterface::INFO_MODEL );

			$ctx = $this->getServiceManager()->get("renderContext");
			if($ctx instanceof RenderContextInterface) {
				$renderInfo->set(RenderInfoInterface::INFO_ADDITIONAL_INFO, $model);
				$ctx->setRenderInfo($renderInfo);
			}

			$this->renderTemplate($template, $model);
		} else
			throw new Exception("Could not resolve template");
	}

	/**
	 * @inheritDoc
	 */
	public function getContentType(): string
	{
		return "text/html; charset=UTF-8";
	}
}