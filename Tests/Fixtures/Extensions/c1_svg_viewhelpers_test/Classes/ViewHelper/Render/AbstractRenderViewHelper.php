<?php

namespace C1\SvgViewhelpersTest\ViewHelper\Render;

/*
 * This file is part of the FluidTYPO3/Vhs project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ### Base class for all rendering ViewHelpers.
 *
 * If errors occur they can be graciously ignored and
 * replaced by a small error message or the error itself.
 */
abstract class AbstractRenderViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'onError',
            'string',
            'Optional error message to display if error occur while rendering. If NULL, lets the error Exception '
            . 'pass trough (and break rendering)'
        );
        $this->registerArgument(
            'graceful',
            'boolean',
            'If forced to FALSE, errors are not caught but rather "transmitted" as every other error would be',
            false,
            false
        );
    }

    protected static function getPreparedNamespaces(array $arguments): array
    {
        $namespaces = [];
        foreach ((array)$arguments['namespaces'] as $namespaceIdentifier => $namespace) {
            $addedOverriddenNamespace = '{namespace ' . $namespaceIdentifier . '=' . $namespace . '}';
            $namespaces[] = $addedOverriddenNamespace;
        }
        return $namespaces;
    }

    // Parses and renders the template source in the current rendering context, so the
    // variables and the request of the surrounding template stay available. Uses Fluid's
    // own parser instead of StandaloneView, which TYPO3 v14 removed.
    protected function renderSource(string $templateSource): string
    {
        try {
            return (string)$this->renderingContext->getTemplateParser()
                ->parse($templateSource)
                ->render($this->renderingContext);
        } catch (\Exception $error) {
            if (!$this->arguments['graceful']) {
                throw $error;
            }
            return $error->getMessage() . ' (' . $error->getCode() . ')';
        }
    }
}
