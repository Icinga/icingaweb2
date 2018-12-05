#!/usr/bin/env php
<?php

function findPhpFiles($dir, &$files = [])
{
    if (substr($dir, -1, 1) !== DIRECTORY_SEPARATOR) {
        $dir .= DIRECTORY_SEPARATOR;
    }
    if ($dh = opendir($dir)) {
        while (($name = readdir($dh)) !== false) {
            $path = $dir . $name;

            if (substr($name, 0, 1) === '.') {
                continue;
            } elseif (is_file($path) && preg_match('~\.ph(p|tml)$~i', $path)) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                findPhpFiles($path, $files);
            }
        }
        closedir($dh);

        return $files;
    } else {
        throw new Exception('Could not read directory: ' . $dir);
    }
}

function stdout($t, $color = '32')
{
    if (posix_isatty(STDOUT) && $color) {
        $t = "\e[${color}m" . $t . "\e[39m";
    }
    fwrite(STDOUT, $t);
}

function stderr($t)
{
    if (posix_isatty(STDERR)) {
        $t = "\e[91m" . $t . "\e[39m";
    }
    fwrite(STDERR, $t);
}

function checkFile($path, &$errors)
{
    $escapedPath = escapeshellarg($path);
    exec("php -l ${escapedPath} 2>&1 >/dev/null", $output, $rc);

    if (! empty($output) || $rc != 0) {
        stdout('E', '91');

        foreach ($output as $line) {
            // remove own name from text
            $line = preg_replace('~ in ' . preg_quote($path) . '~i', '', $line);

            $errors[$path][] = $line;
        }
    } else {
        stdout('.');
    }
}

function usage()
{
    printf("Usage: %s [--verbose] [--exclude file-regex] [path]\n\n", $_SERVER['argv'][0]);
}

function main($argv)
{
    $fileCount = 0;
    $verbose = false;
    $errors = [];
    $excludes = [];
    $searchPaths = [];

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        switch ($arg) {
            case '-h':
            case '--help':
                usage();
                return 1;
            case '-v':
            case '--verbose':
                $verbose = true;
                break;
            case '-e':
            case '--exclude':
                $excludes[] = $argv[++$i];
                break;
            default:
                if (substr($arg, 0, 1) === '-') {
                    stderr("Unknown argument: $arg");
                    return 1;
                } else {
                    $searchPaths[] = $arg;
                }
        }
    }

    if (empty($searchPaths)) {
        $searchPaths = ['.'];
    }

    $files = [];
    foreach ($searchPaths as $basePath) {
        findPhpFiles($basePath, $files);
    }

    foreach ($files as $file) {
        foreach ($excludes as $exclude) {
            if (preg_match("~$exclude~", $file)) {
                continue 2;
            }
        }

        $fileCount++;

        if ($verbose) {
            printf("%s\n", $file);
        }
        checkFile($file, $errors);
    }

    $errorCount = count($errors);
    if ($fileCount === 0) {
        stderr("error: No files found!\n");
        return 2;
    } elseif ($errorCount > 0) {
        stdout("\n");

        foreach ($errors as $file => $errList) {
            stderr("\n$file\n    " . join("\n    ", $errList) . "\n");
        }

        stderr(sprintf("\nFound syntax errors in %d of %d files! \n", $errorCount, $fileCount));
        return 1;
    } else {
        stdout(sprintf("\n\nChecked %d files successfully! \n", $fileCount));
        return 0;
    }
}

exit(main($_SERVER['argv']));
