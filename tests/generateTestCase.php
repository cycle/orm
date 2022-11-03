<?php

declare(strict_types=1);

\error_reporting(E_ALL | E_STRICT);
\ini_set('display_errors', '1');

//Composer
require \dirname(__DIR__) . '/vendor/autoload.php';

$integrationDir = __DIR__ . '/ORM/Functional/Driver/Common/Integration';
$caseTemplateDir = __DIR__ . '/ORM/Functional/Driver/Common/Integration/CaseTemplate/';

$cases = 0;

foreach (\scandir($integrationDir) as $dirName) {
    if (\str_starts_with($dirName, 'Case') && $dirName !== 'CaseTemplate') {
        $cases++;
    }
}

$caseName = 'Case' . $cases + 1;

$caseTemplateFiles = [
    'Entity',
    'Entity/Comment.php',
    'Entity/Post.php',
    'Entity/PostTag.php',
    'Entity/Tag.php',
    'Entity/User.php',
    'CaseTest.php',
    'schema.php',
];

$caseDir = $integrationDir . '/' . $caseName;

echo "Creating new test case with name '$caseName'...\n";

\mkdir($caseDir);

foreach ($caseTemplateFiles as $file) {
    $filePath = $caseTemplateDir . $file;
    $copyPath = $caseDir . '/' . $file;

    if (!\file_exists($filePath)) {
        continue;
    }

    if (\is_dir($filePath)) {
        \mkdir($caseDir . '/' . $file);
    } else {
        \copy($filePath, $copyPath);
        \file_put_contents($copyPath, str_replace('CaseTemplate', $caseName, \file_get_contents($copyPath)));
    }
}

if (file_exists(__DIR__ . '/generate.php')) {
    \exec('php tests/generate.php');
}
