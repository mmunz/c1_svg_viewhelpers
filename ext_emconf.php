<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'C1 SVG Viewhelpers',
    'description' => 'Viewhelpers for SVG in fluid, i.e. allows to reference external SVG from a symbol file',
    'category' => 'frontend',
    'author' => 'Manuel Munz',
    'author_email' => 't3dev@comuno.net',
    'author_company' => 'comuno.net',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '2.0.0',
    'autoload' => [
        'psr-4' => [
            'C1\\SvgViewHelpers\\' => 'Classes',
        ],
    ],
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
