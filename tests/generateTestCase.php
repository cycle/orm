<?php

declare(strict_types=1);

\error_reporting(E_ALL | E_STRICT);
\ini_set('display_errors', '1');

//Composer
require \dirname(__DIR__) . '/vendor/autoload.php';

$dir = __DIR__ . '/ORM/Functional/Driver/Common/Integration';
$caseTemplateDir = __DIR__ . '/ORM/Functional/Driver/Common/Integration/CaseTemplate';

$cases = 0;

foreach (\scandir($dir) as $dirName) {
    if (\str_starts_with($dirName, 'Case') && $dirName !== 'CaseTemplate') {
        $cases++;
    }
}

$caseName = 'Case' . $cases + 1;

$newCaseDst = $dir . '/' . $caseName;

echo \sprintf("Creating new test case with name '%s'... \n", $caseName);

\copyDir($caseTemplateDir, $newCaseDst);

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($newCaseDst));

$files = [];

foreach ($rii as $file) {
    if ($file->isDir()){
        continue;
    }

    $files[] = $file->getPathname();
}

foreach ($files as $file) {
    \file_put_contents($file, str_replace('CaseTemplate', $caseName, \file_get_contents($file)));
}

\exec('php tests/generate.php');

function copyDir(string $src, string $dst): void
{
    $dir = \opendir($src);

    @\mkdir($dst);

    while ($file = \readdir($dir)) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                \copyDir($src . '/' . $file, $dst . '/' . $file);
            } else {
                \copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }

    \closedir($dir);
}
