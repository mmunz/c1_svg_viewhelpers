<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class SymbolViewHelperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        '../../Tests/Fixtures/Extensions/c1_svg_viewhelpers_test',
        '../../../c1_svg_viewhelpers',
    ];

    protected array $defaultArguments = [
        'id' => 1,
    ];

    protected $backupGlobals = true;

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'enforceValidation' => false,
            ],
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(ORIGINAL_ROOT . '/../../Tests/Fixtures/Database/pages.csv');

        // SiteWriter was extracted from SiteConfiguration in TYPO3 v13.
        $siteWriterClass = class_exists(SiteWriter::class) ? SiteWriter::class : SiteConfiguration::class;
        $siteConfiguration = $this->get($siteWriterClass);

        $identifier = 'default';
        $configuration = [
            'rootPageId' => 1,
            'base' => 'https://website.local',
        ];

        try {
            // ensure no previous site configuration influences the test
            GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites/' . $identifier, true);
            $siteConfiguration->write($identifier, $configuration);
        } catch (\Exception $exception) {
            self::markTestSkipped($exception->getMessage());
        }

        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => [
                    'EXT:c1_svg_viewhelpers/Configuration/Sets/Default/constants.typoscript',
                ],
                'setup' => [
                    'EXT:c1_svg_viewhelpers/Configuration/Sets/Default/setup.typoscript',
                    'EXT:c1_svg_viewhelpers_test/Configuration/TypoScript/Basic.typoscript',
                ],
            ],
        );
        $this->addTypoScriptToTemplateRecord(
            1,
            'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.file = EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg' . LF,
        );
    }

    /**
     * Public web path of a sprite in Tests/Fixtures/, including the cache buster the
     * ViewHelper appends. Derived from the fixture itself rather than hard coded, so
     * that touching a fixture does not break every expectation at once.
     */
    private static function sprite(string $fixtureFile): string
    {
        $hash = md5_file(__DIR__ . '/../../Fixtures/' . $fixtureFile);
        if ($hash === false) {
            throw new \RuntimeException('Missing test fixture Tests/Fixtures/' . $fixtureFile);
        }
        return self::spriteWithoutCacheBuster($fixtureFile) . '?cb=' . $hash;
    }

    /**
     * Public web path of a sprite in Tests/Fixtures/, without any cache buster.
     */
    private static function spriteWithoutCacheBuster(string $fixtureFile): string
    {
        return '/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/' . $fixtureFile;
    }

    public static function renderSymbolDataProvider(): array
    {
        $default = self::sprite('sprite-default.svg');
        $alternative = self::sprite('sprite-alternative.svg');

        return [
            'default' => [
                ['identifier' => 'house'],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="' . $default . '#house" /></svg></span>',
                ],
                [
                    '<link rel="preload" href="' . self::spriteWithoutCacheBuster('sprite-default.svg'),
                ],

            ],
            'exception on missing identifier' => [
                [],
                '',
                [
                    // Fluid's TemplateParser rejects the missing required argument. The test
                    // fixture catches it and renders it as "<message> (<code>)". Assert the
                    // message too, not just the code: 1237823699 is raised for *any* missing
                    // required argument, so on its own it would not prove that it was the
                    // identifier that was rejected.
                    'Required argument "identifier" was not supplied.',
                    '1237823699',
                ],
            ],
            'identifier is mail' => [
                ['identifier' => 'mail'],
                '',
                [
                    '<span class="icon-default icon-default-mail icon-default-mail-dims"><svg role="graphics-symbol"><use xlink:href="' . $default . '#mail" /></svg></span>',
                ],
            ],
            'custom symbolFile' => [
                [
                    'identifier' => 'house',
                    'symbolFile' => 'EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-alternative.svg',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="' . $alternative . '#house" /></svg></span>',
                ],
            ],
            'custom symbolFile from settings' => [
                [
                    'identifier' => 'house',
                ],
                'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.file=EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-alternative.svg',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="' . $alternative . '#house" /></svg></span>',
                ],
            ],
            'custom symbolFile where vieHelper argument overwrites preset from settings' => [
                [
                    'identifier' => 'house',
                    'symbolFile' => 'EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-alternative.svg',
                ],
                'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.file=EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-notexists.svg',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="' . $alternative . '#house" /></svg></span>',
                ],
            ],
            'no_cache_buster' => [
                [
                    'identifier' => 'house',
                    'cacheBuster' => '0',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="' . self::spriteWithoutCacheBuster('sprite-default.svg') . '#house" /></svg></span>',
                ],
            ],
            // The ViewHelper reads cacheBuster as a plain truthy check and preload through
            // filter_var(). Those disagree on the string "false", which is truthy in PHP.
            // They never see it: Fluid wraps boolean-typed tag arguments in a BooleanNode,
            // which resolves "false" to false first. These two cases pin that, so the
            // assumption is verified rather than relied upon.
            'string "false" disables the cache buster' => [
                [
                    'identifier' => 'house',
                    'cacheBuster' => 'false',
                ],
                '',
                [
                    '<use xlink:href="' . self::spriteWithoutCacheBuster('sprite-default.svg') . '#house" />',
                ],
                [
                    '?cb=',
                ],
            ],
            'string "false" disables preloading' => [
                [
                    'identifier' => 'house',
                    'preload' => 'false',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims">',
                ],
                [
                    '<link rel="preload"',
                ],
            ],
            'role set to img' => [
                [
                    'identifier' => 'house',
                    'role' => 'img',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="img"><use xlink:href="' . $default . '#house" /></svg></span>',
                ],
            ],
            'with ariaLabel' => [
                [
                    'identifier' => 'house',
                    'ariaLabel' => 'my aria label',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg aria-label="my aria label" role="graphics-symbol"><use xlink:href="' . $default . '#house" /></svg></span>',
                ],
            ],
            'with custom baseClass from viewhelper arguments' => [
                [
                    'identifier' => 'house',
                    'baseClass' => 'myicon',
                ],
                '',
                [
                    '<span class="myicon myicon-house myicon-house-dims"><svg role="graphics-symbol"><use xlink:href="' . $default . '#house" /></svg></span>',
                ],
            ],
            'with custom baseClass from settings' => [
                [
                    'identifier' => 'house',
                ],
                'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.baseClass=myicon',
                [
                    '<span class="myicon myicon-house myicon-house-dims"><svg role="graphics-symbol"><use xlink:href="' . $default . '#house" /></svg></span>',
                ],
            ],
            'with title' => [
                [
                    'identifier' => 'house',
                    'title' => 'myicontitle',
                ],
                '',
                [
                    '<span title="myicontitle" class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="' . $default . '#house" /></svg></span>',
                ],
            ],
            'with extra css class' => [
                [
                    'identifier' => 'house',
                    'class' => 'mycustomclass',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims mycustomclass"><svg role="graphics-symbol"><use xlink:href="' . $default . '#house" /></svg></span>',
                ],
            ],
            'enable preload by vh argument' => [
                [
                    'identifier' => 'house',
                    'preload' => '1',
                ],
                '',
                [
                    '<link rel="preload" href="' . $default . '" as="image" fetchpriority="high" />',
                ],
            ],
            'enable preload by settings' => [
                [
                    'identifier' => 'house',
                ],
                'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.preload=1',
                [
                    '<link rel="preload" href="' . $default . '" as="image" fetchpriority="high" />',
                ],
            ],
            'with universal tag attribute dir' => [
                [
                    'identifier' => 'house',
                    'dir' => 'ltr',
                ],
                '',
                [
                    '<span dir="ltr" class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="' . $default . '#house" /></svg></span>',
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderSymbolDataProvider')]
    public function renderSymbol(
        array $arguments,
        string $typoScript,
        array $expectedStrings,
        array $notExpectedStrings = []
    ): void {
        $requestArguments = array_merge(
            $this->defaultArguments,
            $arguments
        );

        if ($typoScript !== '') {
            $this->addTypoScriptToTemplateRecord(
                1,
                $typoScript . LF,
            );
        }

        $response = $this->fetchFrontendResponse($requestArguments);

        foreach ($expectedStrings as $expected) {
            self::assertStringContainsString($expected, (string)$response->getBody());
        }

        foreach ($notExpectedStrings as $notExpected) {
            self::assertStringNotContainsString($notExpected, (string)$response->getBody());
        }
    }

    protected function fetchFrontendResponse(array $requestArguments): \TYPO3\CMS\Core\Http\Response
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://website.local/'))->withQueryParameters($requestArguments)
        );

        return $response;
    }
}
