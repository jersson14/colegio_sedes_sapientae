<?php
// PHP-CS-Fixer (Fase 1): PSR-12 sobre el código moderno. Lo heredado se formatea
// al migrarlo a src/, nunca en masa (generaría diffs gigantes sin pruebas).
$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/core', __DIR__ . '/tools', __DIR__ . '/tests', __DIR__ . '/database', __DIR__ . '/superadmin'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules(['@PSR12' => true])
    ->setFinder($finder);
