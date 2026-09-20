<?php

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
            // Full TypoScript is not reachable from every scope a ViewHelper can render
            // in. TYPO3 v13 throws for a missing request (1721920500), a request without
            // an applicationType (1606222812), and -- the likely one in production --
            // cached Frontend scope, where the setup array is never built (1700841298).
            //
            // Throwable rather than RuntimeException on purpose: NoServerRequestGiven-
            // Exception extends RuntimeException in v13 but Extbase\Exception in v14.
            //
            // An icon must not turn a page into a 500, so fall back to the defaults --
            // but say so, otherwise a misconfigured site fails silently.
            GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->warning(
                'Could not read TypoScript settings, falling back to defaults: {reason}',
                ['reason' => $throwable->getMessage(), 'exception' => $throwable]
            );
            return [];
        }
    }
}
