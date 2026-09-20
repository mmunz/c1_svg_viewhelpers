<?php

namespace C1\SvgViewhelpersTest\ViewHelper\Render;

/*
 * This file is part of the FluidTYPO3/Vhs project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

/**
 * ### Render: Inline
 *
 * Render as string containing Fluid as if it were
 * part of the template currently being rendered.
 *
 * Environment (template variables etc.) is cloned
 * but not re-merged after rendering, which means that
 * any and all changes in variables that happen while
 * rendering this inline code will be destroyed after
 * sub-rendering is finished.
 */
class InlineViewHelper extends AbstractRenderViewHelper
{
    /**
     * @var bool
     */
    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('content', 'string', 'Template code to render as Fluid (usually from a variable)');
        $this->registerArgument(
            'namespaces',
            'array',
            'Optional additional/overridden namespaces, ["ns" => "MyVendor\\MyExt\\ViewHelpers"]',
            false,
            []
        );
        parent::initializeArguments();
    }

    /**
     * Makes the "content" argument take precedence over the tag content, the way
     * the removed CompileWithContentArgumentAndRenderStatic trait used to do.
     */
    public function getContentArgumentName(): ?string
    {
        return 'content';
    }

    public function render(): string
    {
        $content = (string)$this->renderChildren();
        $namespaces = static::getPreparedNamespaces($this->arguments);
        $namespaceHeader = implode(LF, $namespaces);
        foreach ($namespaces as $namespace) {
            $content = str_replace($namespace, '', $content);
        }
        $view = static::getPreparedClonedView($this->renderingContext);
        $view->setTemplateSource($namespaceHeader . $content);
        return static::renderView($view, $this->arguments);
    }
}
