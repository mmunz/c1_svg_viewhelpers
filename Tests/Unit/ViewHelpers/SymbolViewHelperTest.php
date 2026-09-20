<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\Tests\Unit\ViewHelpers;

use C1\SvgViewHelpers\ViewHelpers\SymbolViewHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for the argument/TypoScript resolution logic of the symbol ViewHelper.
 *
 * These cover the pure decision logic (which base class, which symbol file, whether
 * to preload) in isolation. Rendering against real sprite files and real EXT: path
 * resolution is covered by the functional test of the same name.
 */
final class SymbolViewHelperTest extends UnitTestCase
{
    /**
     * Builds the settings array as it arrives from TypoScript, with the given
     * values merged into the "default" preset.
     *
     * @param array<string, mixed> $preset
     * @return array<string, mixed>
     */
    private function settingsWithDefaultPreset(array $preset): array
    {
        return [
            'svg' => [
                'symbol' => [
                    'presets' => [
                        'default' => $preset,
                    ],
                ],
            ],
        ];
    }

    /**
     * Renders the ViewHelper with the given arguments and TypoScript settings.
     *
     * Arguments are merged onto the registered defaults the same way Fluid's
     * invoker would do it, so that hasArgument() behaves as in a real render.
     *
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $settings
     */
    private function render(array $arguments, array $settings, ?PageRenderer $pageRenderer = null): string
    {
        $viewHelper = new SymbolViewHelper();
        $viewHelper->injectPageRenderer($pageRenderer ?? $this->createMock(PageRenderer::class));

        $resolved = [];
        foreach ($viewHelper->prepareArguments() as $name => $definition) {
            $resolved[$name] = $definition->getDefaultValue();
        }
        $viewHelper->setArguments(array_merge($resolved, $arguments));
        $viewHelper->initialize($settings);

        return $viewHelper->render();
    }

    /**
     * Extracts the class attribute of the outer span.
     */
    private function classAttributeOf(string $renderedTag): string
    {
        self::assertSame(1, preg_match('/<span[^>]*\sclass="([^"]*)"/', $renderedTag, $matches));
        return $matches[1];
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>, 2: string}>
     */
    public static function baseClassResolutionDataProvider(): array
    {
        return [
            'baseClass argument wins over the preset' => [
                ['identifier' => 'house', 'baseClass' => 'myicon'],
                ['baseClass' => 'preseticon'],
                'myicon myicon-house myicon-house-dims',
            ],
            'baseClass comes from the preset when no argument is given' => [
                ['identifier' => 'house'],
                ['baseClass' => 'preseticon'],
                'preseticon preseticon-house preseticon-house-dims',
            ],
            'baseClass falls back to icon-default when neither is set' => [
                ['identifier' => 'house'],
                [],
                'icon-default icon-default-house icon-default-house-dims',
            ],
            'additional class argument is appended' => [
                ['identifier' => 'house', 'class' => 'mycustomclass'],
                [],
                'icon-default icon-default-house icon-default-house-dims mycustomclass',
            ],
            'empty class argument is not appended' => [
                ['identifier' => 'house', 'class' => ''],
                [],
                'icon-default icon-default-house icon-default-house-dims',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $preset
     */
    #[Test]
    #[DataProvider('baseClassResolutionDataProvider')]
    public function baseClassIsResolvedFromArgumentPresetOrFallback(
        array $arguments,
        array $preset,
        string $expectedClasses
    ): void {
        $rendered = $this->render($arguments, $this->settingsWithDefaultPreset($preset));

        self::assertSame($expectedClasses, $this->classAttributeOf($rendered));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>, 2: bool}>
     */
    public static function preloadResolutionDataProvider(): array
    {
        return [
            'preload argument wins over the preset' => [
                ['identifier' => 'house', 'preload' => false],
                ['preload' => true],
                false,
            ],
            'preload argument enables preloading against a disabled preset' => [
                ['identifier' => 'house', 'preload' => true],
                ['preload' => false],
                true,
            ],
            'preload comes from the preset when no argument is given' => [
                ['identifier' => 'house'],
                ['preload' => true],
                true,
            ],
            'preload is disabled by the preset' => [
                ['identifier' => 'house'],
                ['preload' => false],
                false,
            ],
            'preload defaults to true when neither is set' => [
                ['identifier' => 'house'],
                [],
                true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $preset
     */
    #[Test]
    #[DataProvider('preloadResolutionDataProvider')]
    public function preloadHeaderIsAddedOnlyWhenPreloadingIsEnabled(
        array $arguments,
        array $preset,
        bool $expectPreloadHeader
    ): void {
        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects($expectPreloadHeader ? $this->once() : $this->never())
            ->method('addHeaderData');

        $this->render($arguments, $this->settingsWithDefaultPreset($preset), $pageRenderer);
    }

    /**
     * The preload argument is typed as boolean, but TypoScript delivers strings.
     * setPreload() runs the value through filter_var(), so "0" must disable it.
     *
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function preloadStringValuesDataProvider(): array
    {
        return [
            '"1" enables preloading' => ['1', true],
            '"0" disables preloading' => ['0', false],
            '"true" enables preloading' => ['true', true],
            '"false" disables preloading' => ['false', false],
        ];
    }

    #[Test]
    #[DataProvider('preloadStringValuesDataProvider')]
    public function preloadArgumentAcceptsStringBooleans(mixed $preload, bool $expectPreloadHeader): void
    {
        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects($expectPreloadHeader ? $this->once() : $this->never())
            ->method('addHeaderData');

        $this->render(
            ['identifier' => 'house', 'preload' => $preload],
            $this->settingsWithDefaultPreset([]),
            $pageRenderer
        );
    }

    #[Test]
    public function symbolFileArgumentMatchingAPresetKeyUsesThePresetFile(): void
    {
        $rendered = $this->render(
            ['identifier' => 'house', 'symbolFile' => 'default'],
            $this->settingsWithDefaultPreset(['file' => 'fileadmin/preset-sprite.svg'])
        );

        self::assertStringContainsString('preset-sprite.svg#house', $rendered);
    }

    #[Test]
    public function symbolFileArgumentNotMatchingAPresetKeyIsUsedAsPath(): void
    {
        $rendered = $this->render(
            ['identifier' => 'house', 'symbolFile' => 'fileadmin/explicit-sprite.svg'],
            $this->settingsWithDefaultPreset(['file' => 'fileadmin/preset-sprite.svg'])
        );

        self::assertStringContainsString('explicit-sprite.svg#house', $rendered);
        self::assertStringNotContainsString('preset-sprite.svg', $rendered);
    }

    #[Test]
    public function ariaLabelAndRoleAreRenderedOnTheSvgTag(): void
    {
        $rendered = $this->render(
            ['identifier' => 'house', 'ariaLabel' => 'my aria label', 'role' => 'img'],
            $this->settingsWithDefaultPreset([])
        );

        self::assertStringContainsString('<svg aria-label="my aria label" role="img">', $rendered);
    }

    #[Test]
    public function titleIsRenderedOnTheOuterSpanAndNotOnTheSvg(): void
    {
        $rendered = $this->render(
            ['identifier' => 'house', 'title' => 'myicontitle'],
            $this->settingsWithDefaultPreset([])
        );

        self::assertStringContainsString('<span title="myicontitle"', $rendered);
        self::assertStringNotContainsString('<svg title=', $rendered);
    }
}
