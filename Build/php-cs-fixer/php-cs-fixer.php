<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->getFinder()
    ->ignoreVCSIgnored(true)
    ->in([
        __DIR__ . '/../../Build/',
        __DIR__ . '/../../Classes/',
        __DIR__ . '/../../Configuration/',
        __DIR__ . '/../../Tests/',
    ])
    ->notPath([
        'Build/php-cs-fixer/php-cs-fixer.php',
        'Build/phpunit/',
    ]);
return $config;
