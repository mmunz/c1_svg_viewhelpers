<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\Utilities;

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class TypoScript
{
    public static function getSettings(): array
    {
        try {
            /** @var ConfigurationManager $configurationManager */
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
            /** @var TypoScriptService $typoScriptService */
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);

            $typoScript = $configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT,
                'tx_c1svgviewhelpers',
                null
            );

            return $typoScriptService->convertTypoScriptArrayToPlainArray(
                $typoScript['plugin.']['tx_c1svgviewhelpers.']['settings.'] ?? []
            );
        } catch (\Throwable $throwable) {
            // v13 refuses full TypoScript without a request (1721920500), without an
            // applicationType (1606222812), and in cached Frontend scope (1700841298).
            // Throwable, not RuntimeException: NoServerRequestGivenException changes
            // base class in v14.
            GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->warning(
                'Could not read TypoScript settings, falling back to defaults: {reason}',
                ['reason' => $throwable->getMessage(), 'exception' => $throwable]
            );
            return [];
        }
    }
}
