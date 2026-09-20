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
 * Covers how an integrator actually gets the extension's TypoScript into a site.
 *
 * SymbolViewHelperTest feeds the .typoscript files into the template record by path,
 * which bypasses both mechanisms below — so it would stay green even if neither were
 * wired up. That is exactly how the missing v12 static template went unnoticed.
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

    /**
     * @param array<string, mixed> $siteConfiguration
     */
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

    /**
     * Asserts the shipped default preset arrived. The cache buster is the telling part:
     * getCacheBuster() only emits one when the resolved file actually exists, so this
     * also covers the preset pointing somewhere real.
     */
    private function assertShippedDefaultPresetWasUsed(string $body): void
    {
        self::assertMatchesRegularExpression(
            '#<use xlink:href="[^"]*/default-symbol\.svg\?cb=[0-9a-f]{32}\#placeholder" />#',
            $body
        );
        self::assertStringNotContainsString('xlink:href="/default#placeholder"', $body);
    }

    /**
     * The static template is what a v12 integrator selects under "Include static (from
     * extensions)". v12 has no site sets, so without this the presets are unreachable
     * there — the ViewHelper falls back to the literal argument default and emits
     * <use xlink:href="/default#house">.
     */
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

    /**
     * The site set is the v13.1+ mechanism and the one the README should point at.
     */
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
