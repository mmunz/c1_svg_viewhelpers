<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\Tests\Unit\ViewHelpers;

use C1\SvgViewHelpers\ViewHelpers\SymbolViewHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Resolution logic in isolation. Real sprite files and EXT: path resolution are
 * covered by the functional test of the same name.
 */
final class SymbolViewHelperTest extends UnitTestCase
{
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

    // Arguments are merged onto the registered defaults the way Fluid's invoker
    // does, so that hasArgument() behaves as in a real render.
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

    private function classAttributeOf(string $renderedTag): string
    {
        if (preg_match('/<span[^>]*\sclass="([^"]*)"/', $renderedTag, $matches) !== 1) {
            self::fail('No span with a class attribute in: ' . $renderedTag);
        }
        return $matches[1];
    }

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

    // Invoking directly bypasses Fluid's BooleanNode coercion, so these pin
    // setPreload()'s own filter_var() handling.
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

    // The preset branch of setPreload() receives raw TypoScript, which is always a
    // string. Assigned straight to a typed bool property, PHP's weak coercion turns
    // every non-empty string true -- "false" included.
    public static function preloadPresetStringValuesDataProvider(): array
    {
        return [
            'preset "1" enables preloading' => ['1', true],
            'preset "0" disables preloading' => ['0', false],
            'preset "true" enables preloading' => ['true', true],
            'preset "false" disables preloading' => ['false', false],
        ];
    }

    #[Test]
    #[DataProvider('preloadPresetStringValuesDataProvider')]
    public function preloadPresetAcceptsStringBooleans(mixed $preload, bool $expectPreloadHeader): void
    {
        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects($expectPreloadHeader ? $this->once() : $this->never())
            ->method('addHeaderData');

        $this->render(
            ['identifier' => 'house'],
            $this->settingsWithDefaultPreset(['preload' => $preload]),
            $pageRenderer
        );
    }

    // Happens when the extension is installed without its set being included.
    #[Test]
    public function renderingWithoutAnyTypoScriptSettingsFallsBackToDefaults(): void
    {
        $rendered = $this->render(['identifier' => 'house'], []);

        self::assertSame(
            'icon-default icon-default-house icon-default-house-dims',
            $this->classAttributeOf($rendered)
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
