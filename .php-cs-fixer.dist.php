<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'no_superfluous_phpdoc_tags' => true,
        'ordered_imports' => true,
        'braces_position' => ['functions_opening_brace' => 'next_line_unless_newline_at_signature_end'],
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => false, 'import_functions' => false], // Overrides @Symfony
    ])
    ->setFinder($finder)
;
