<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\Tests\Functional\ViewHelpers;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
final class SymbolViewHelperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/Extensions/c1_svg_viewhelpers_test',
        'typo3conf/ext/c1_svg_viewhelpers',
    ];

    protected array $defaultArguments = [
        'id' => 1,
    ];

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

        $version = new \TYPO3\CMS\Core\Information\Typo3Version();
        $majorVersion = $version->getMajorVersion();

        if ($majorVersion < 12) {
            $siteConfiguration = new SiteConfiguration(
                $this->instancePath . '/typo3conf/sites/',
                $this->get('cache.core')
            );
        } else {
            $siteConfiguration = new SiteConfiguration(
                $this->instancePath . '/typo3conf/sites/',
                $this->get(EventDispatcherInterface::class),
                $this->get('cache.core')
            );
        }

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
                'EXT:c1_svg_viewhelpers/Configuration/TypoScript/constants.typoscript',
            ],
            'setup' => [
                'EXT:c1_svg_viewhelpers/Configuration/TypoScript/setup.typoscript',
                'EXT:c1_svg_viewhelpers_test/Configuration/TypoScript/Basic.typoscript',
            ],
        ],
        );
        $this->addTypoScriptToTemplateRecord(
            1,
            'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.file = EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg' . LF,
        );
    }

    public static function renderSymbolDataProvider(): array
    {
        return [
            'default' => [
                ['identifier' => 'house'],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
                [
                    '<link rel="preload" href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg',
                ],

            ],
            'exception on missing identifier' => [
                [],
                '',
                [
                    '1237823699',
                ],
            ],
            'identifier is mail' => [
                ['identifier' => 'mail'],
                '',
                [
                    '<span class="icon-default icon-default-mail icon-default-mail-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14#mail" /></svg></span>',
                ],
            ],
            'custom symbolFile' => [
                [
                    'identifier' => 'house',
                    'symbolFile' => 'EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-alternative.svg',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-alternative.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
            'custom symbolFile from settings' => [
                [
                    'identifier' => 'house',
                ],
                'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.file=EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-alternative.svg',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-alternative.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
            'custom symbolFile where vieHelper argument overwrites preset from settings' => [
                [
                    'identifier' => 'house',
                    'symbolFile' => 'EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-alternative.svg',
                ],
                'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.file=EXT:c1_svg_viewhelpers/Tests/Fixtures/sprite-notexists.svg',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-alternative.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
            'no_cache_buster' => [
                [
                    'identifier' => 'house',
                    'cacheBuster' => '0',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg#house" /></svg></span>',
                ],
            ],
            'role set to img' => [
                [
                    'identifier' => 'house',
                    'role' => 'img',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg role="img"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
            'with ariaLabel' => [
                [
                    'identifier' => 'house',
                    'ariaLabel' => 'my aria label',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims"><svg aria-label="my aria label" role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
            'with custom baseClass from viewhelper arguments' => [
                [
                    'identifier' => 'house',
                    'baseClass' => 'myicon',
                ],
                '',
                [
                    '<span class="myicon myicon-house myicon-house-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
            'with custom baseClass from settings' => [
                [
                    'identifier' => 'house',
                ],
                'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.baseClass=myicon',
                [
                    '<span class="myicon myicon-house myicon-house-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
            'with title' => [
                [
                    'identifier' => 'house',
                    'title' => 'myicontitle',
                ],
                '',
                [
                    '<span title="myicontitle" class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
            'with extra css class' => [
                [
                    'identifier' => 'house',
                    'class' => 'mycustomclass',
                ],
                '',
                [
                    '<span class="icon-default icon-default-house icon-default-house-dims mycustomclass"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
            'enable preload by vh argument' => [
                [
                    'identifier' => 'house',
                    'preload' => '1',
                ],
                '',
                [
                    '<link rel="preload" href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14" as="image" fetchpriority="high" />',
                ],
            ],
            'enable preload by settings' => [
                [
                    'identifier' => 'house',
                ],
                'plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default.preload=1',
                [
                    '<link rel="preload" href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14" as="image" fetchpriority="high" />',
                ],
            ],
            'with universal tag attribute dir' => [
                [
                    'identifier' => 'house',
                    'dir' => 'ltr',
                ],
                '',
                [
                    ' <span dir="ltr" class="icon-default icon-default-house icon-default-house-dims"><svg role="graphics-symbol"><use xlink:href="/typo3conf/ext/c1_svg_viewhelpers/Tests/Fixtures/sprite-default.svg?cb=ccb77e624e62a33b420baff0de4eef14#house" /></svg></span>',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderSymbolDataProvider
     */
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

    private function fetchFrontendResponse(array $requestArguments): \TYPO3\CMS\Core\Http\Response
    {
        return $this->executeFrontendSubRequest(
            (new InternalRequest('https://website.local/'))->withQueryParameters($requestArguments)
        );
    }
}
