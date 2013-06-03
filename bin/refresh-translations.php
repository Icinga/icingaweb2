#!/usr/bin/php
<?php

require_once dirname(__FILE__) . '/../library/Icinga/Application/Cli.php';
use Icinga\Application\Cli,
    Icinga\Application\TranslationHelper;
$bootstrap = Cli::start();

if (count($argv) < 2) {
    die(sprintf(
        "Usage: ./%s lc_LC [module]\n",
        basename($argv[0])
    ));
}

$locale = $argv[1];
if (array_key_exists(2, $argv)) {
    $module = $argv[2];
} else {
    $module = null;
}

$translation = new TranslationHelper($bootstrap, $locale, $module);
$translation->createTemporaryFileList()
            ->extractTexts();


