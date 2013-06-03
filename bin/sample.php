#!/usr/bin/php
<?php

set_include_path(
    realpath(dirname(__FILE__) . '/../library/')
  . ':' . get_include_path()
);

require_once 'Icinga/Application/Cli.php';
use Icinga\Application\Cli,
    Icinga\Util\Format;
$app = Cli::start();

echo Format::bytes(10930423) . "\n";

