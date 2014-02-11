<?php
/*
 * run.php
 *
 * This file runs every request to register runtime functionality
 *
 */

use Icinga\Application\Icinga;
use Icinga\Authentication\Manager as AuthManager;
use Icinga\Web\Hook;

if (Icinga::app()->isCli()) {
    return;
}

if (AuthManager::getInstance()->isAuthenticated()) {
    Hook::register(
        Hook::TARGET_LAYOUT_TOPBAR,
        'monitoring-topbar',
        'Icinga\\Module\\Monitoring\\Web\\Hook\\TopBar'
    );
}
