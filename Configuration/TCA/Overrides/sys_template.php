<?php

defined('TYPO3') or die();

// Offers the static template in the sys_template record. TYPO3 v13.1+ should use the
// site set (Configuration/Sets/Default) instead; this exists for v12, which has none.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'c1_svg_viewhelpers',
    'Configuration/TypoScript/',
    'SVG Viewhelpers: Default'
);
