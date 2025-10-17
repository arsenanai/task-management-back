<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/behaviors',
        __DIR__ . '/commands',
        __DIR__ . '/components',
        __DIR__ . '/config',
        __DIR__ . '/controllers',
        __DIR__ . '/filters',
        __DIR__ . '/mail',
        __DIR__ . '/migrations',
        __DIR__ . '/models',
        __DIR__ . '/services',
        __DIR__ . '/tests',
        __DIR__ . '/web',
    ])
    ->exclude([
        'vendor',
        '_generated',
    ]);

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
