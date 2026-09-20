<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * SymbolViewHelperTest feeds the .typoscript files in by path, bypassing both
 * mechanisms below, so it stays green even when neither is wired up. That is how the
 * missing v12 static template went unnoticed.
 */
final class TypoScriptInclusionTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        '../../Tests/Fixtures/Extensions/c1_svg_viewhelpers_test',
        '../../../c1_svg_viewhelpers',
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
    }

    private function writeSiteConfiguration(array $siteConfiguration = []): void
    {
        // SiteWriter was extracted from SiteConfiguration in TYPO3 v13.
        $siteWriterClass = class_exists(SiteWriter::class) ? SiteWriter::class : SiteConfiguration::class;
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites/default', true);
        $this->get($siteWriterClass)->write('default', array_merge(
            ['rootPageId' => 1, 'base' => 'https://website.local'],
            $siteConfiguration
        ));
    }

    private function renderPlaceholderIcon(): string
    {
        return (string)$this->executeFrontendSubRequest(
            (new InternalRequest('https://website.local/'))
                ->withQueryParameters(['id' => 1, 'identifier' => 'placeholder'])
        )->getBody();
    }

    // The cache buster is the telling part: it only appears when the resolved file
    // exists, so this covers the preset pointing somewhere real too.
    private function assertShippedDefaultPresetWasUsed(string $body): void
    {
        self::assertMatchesRegularExpression(
            '#<use xlink:href="[^"]*/default-symbol\.svg\?cb=[0-9a-f]{32}\#placeholder" />#',
            $body
        );
        self::assertStringNotContainsString('xlink:href="/default#placeholder"', $body);
    }

    // What a v12 integrator selects under "Include static (from extensions)". v12 has
    // no site sets, so without it no preset is reachable there at all.
    #[Test]
    public function presetsAreReachableThroughTheStaticTemplate(): void
    {
        $this->writeSiteConfiguration();
        $this->setUpFrontendRootPage(
            1,
            ['setup' => ['EXT:c1_svg_viewhelpers_test/Configuration/TypoScript/Basic.typoscript']],
            ['include_static_file' => 'EXT:c1_svg_viewhelpers/Configuration/TypoScript/'],
        );

        $this->assertShippedDefaultPresetWasUsed($this->renderPlaceholderIcon());
    }

    #[Test]
    public function presetsAreReachableThroughTheSiteSet(): void
    {
        // Check the feature itself rather than a proxy for the major version.
        if (!class_exists(SetRegistry::class)) {
            self::markTestSkipped('Site sets require TYPO3 v13.1+.');
        }

        $this->writeSiteConfiguration(['dependencies' => ['c1/svg-viewhelpers-default']]);
        $this->setUpFrontendRootPage(
            1,
            ['setup' => ['EXT:c1_svg_viewhelpers_test/Configuration/TypoScript/Basic.typoscript']],
        );
        // setUpFrontendRootPage() hardcodes clear=3 after merging $templateValues, and
        // "clear" discards everything included before the record — which is where the
        // site set sits. Without this the set is silently dropped.
        $this->getConnectionPool()->getConnectionForTable('sys_template')
            ->update('sys_template', ['clear' => 0], ['pid' => 1]);

        $this->assertShippedDefaultPresetWasUsed($this->renderPlaceholderIcon());
    }
}
