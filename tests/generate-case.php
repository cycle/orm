<?php

declare(strict_types=1);

\error_reporting(E_ALL | E_STRICT);
\ini_set('display_errors', '1');

//Composer
require_once \dirname(__DIR__) . '/vendor/autoload.php';

$integrationDir = __DIR__ . '/ORM/Functional/Driver/Common/Integration';

function defaultCaseName(string $integrationDir): string
{
    $cases = 0;

    foreach (\scandir($integrationDir) as $dirName) {
        if (\sscanf($dirName, 'Case%d', $last) === 1) {
            $cases = \max($cases, $last);
        }
    }
    ++$cases;

    return $integrationDir . '/Case' . $cases;
}

function copyTemplateFiles(string $copyDir, string $caseTemplateDirName): void
{
    $caseTemplateDir = __DIR__ . '/ORM/Functional/Driver/Common/Integration/' . $caseTemplateDirName;
    if (!file_exists($caseTemplateDir) || !is_dir($caseTemplateDir)) {
        echo "Error. template folder '$caseTemplateDir' does not exists\n";
        exit(1);
    }

    echo \sprintf("Using template files from '%s'...\n", $caseTemplateDir);

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

        $contents = \str_replace($caseTemplateDirName, basename($copyDir), \file_get_contents($filePath));
        \file_put_contents($copyDir . $target, $contents);
    }
}

$options = getopt('', [
    'case-name:',
    'template:',
]);

$copyDir = isset($options['case-name'])
    ? $integrationDir . DIRECTORY_SEPARATOR . $options['case-name']
    : defaultCaseName($integrationDir);

if (file_exists($copyDir)) {
    echo "Error. Tests folder `$copyDir` already exists\n";
    exit(1);
}

echo \sprintf("Generating new test case '%s'... \n", basename($copyDir));

\mkdir($copyDir);
$copyDir = \realpath($copyDir);

copyTemplateFiles($copyDir, $options['template'] ?? 'CaseTemplate');

require 'generate.php';

echo "Done. New test case is here:\n$copyDir\n";
