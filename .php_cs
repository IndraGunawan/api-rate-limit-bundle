<?php

$header = <<<'EOF'
This file is part of the ApiRateLimitBundle

(c) Indra Gunawan <hello@indra.my.id>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        // one should use PHPUnit methods to set up expected exception instead of annotations
        'general_phpdoc_annotation_remove' => ['annotations' => ['expectedException', 'expectedExceptionMessage', 'expectedExceptionMessageRegExp']],
        'header_comment' => ['header' => $header],
        'no_extra_blank_lines' => ['tokens' => ['break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block']],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => true,
        'php_unit_strict' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'psr_autoloading' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'constant_case' => true,
        'lowercase_keywords' => true,
        'visibility_required' => true,
    ])
    ->setFinder(PhpCsFixer\Finder::create()->in(__DIR__))
;
