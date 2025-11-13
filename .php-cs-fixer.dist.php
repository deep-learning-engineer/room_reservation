<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'var',
        'vendor',
        'bin',
    ])
    ->name('*.php')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        
        '@Symfony' => true,

        'concat_space' => ['spacing' => 'one'],
        
        'no_unused_imports' => true,
        
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],

        'no_extra_blank_lines' => [
            'tokens' => [
                'break',
                'continue',
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],
        
        'line_ending' => true,
        
        'single_quote' => true,
        
        'general_phpdoc_annotation_remove' => [
                'annotations' => ['psalm', 'psalm-suppress', 'psalm-param', 'psalm-return', 'psalm-var'],
                'case_sensitive' => false,
        ],

        'declare_strict_types' => true,
        
        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => true,
            'import_constants' => true,
        ],
    ]) 
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setRiskyAllowed(true);
