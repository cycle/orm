<?php

declare(strict_types=1);

\error_reporting(E_ALL | E_STRICT);
\ini_set('display_errors', '1');

//Composer
require_once \dirname(__DIR__) . '/vendor/autoload.php';

$integrationDir = __DIR__ . '/ORM/Functional/Driver/Common/Integration';
$caseTemplateDir = __DIR__ . '/ORM/Functional/Driver/Common/Integration/CaseTemplate';

$cases = 0;

foreach (\scandir($integrationDir) as $dirName) {
    if (\sscanf($dirName, 'Case%d', $last) === 1) {
        $cases = \max($cases, $last);
    }
}
++$cases;

$copyDir = $integrationDir . '/Case' . $cases;

echo \sprintf("Generating new test case 'Case%s'... \n", $cases);

\mkdir($copyDir);
$copyDir = \realpath($copyDir);

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($caseTemplateDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($rii as $file) {
    $filePath = $file->getRealPath();
    $target = \substr($filePath, \strlen($caseTemplateDir));

    // creating directory...
    $dirName = dirname($copyDir . $target);
    if (!\is_dir($dirName)) {
        \mkdir($dirName, recursive: true);
    }

    $contents = \str_replace('CaseTemplate', 'Case' . $cases, \file_get_contents($filePath));
    \file_put_contents($copyDir . $target, $contents);
}

require 'generate.php';

echo "Done. New test case is here:\n$copyDir\n";
