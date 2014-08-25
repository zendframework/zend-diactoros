<?php
printf("Executing php_cs!!!\n\n");
$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__)
    ->ignoreDotFiles(true)
    ->filter(function (SplFileInfo $file) {
        $path = $file->getPathname();

        switch (true) {
            case (strrpos($path, '/test/Bootstrap.php')):
                return false;
            case (strrpos($path, '/test/Http/TestAsset/Functions.php')):
                return false;
            case (strrpos($path, '/vendor/')):
                return false;
            default:
                return true;
        }
    });

return Symfony\CS\Config\Config::create()
    ->fixers(array(
        'controls_spaces',
        'braces',
        'elseif',
        'eof_ending',
        'function_declaration',
        'include',
        'indentation',
        'linefeed',
        'php_closing_tag',
        'short_tag',
        'trailing_spaces',
        'unused_use',
        'visibility',
    ))
    ->finder($finder);
