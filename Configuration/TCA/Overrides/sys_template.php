<?php

defined('TYPO3') or die();

// Offers the static template in the sys_template record, for sites that include
// TypoScript via template records instead of the site set (Configuration/Sets/Default).
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'c1_svg_viewhelpers',
    'Configuration/TypoScript/',
    'SVG Viewhelpers: Default'
);
