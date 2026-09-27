<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
// var/ is created by the functional tests
$config->getFinder()->in(__DIR__)->exclude(['var']);
return $config;
