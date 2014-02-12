<?php
/*
 * run.php
 *
 * This file runs every request to register runtime functionality
 *
 */

$this->registerHook(
    'TopBar',
    'Icinga\\Module\\Monitoring\\Web\\Hook\\TopBar'
);
