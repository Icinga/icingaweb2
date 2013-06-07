<?php

/**
 * Run embedded in other web applications
 *
 * @package Icinga\Application
 */
namespace Icinga\Application;

require_once dirname(__FILE__) . '/ApplicationBootstrap.php';
use Icinga\Exception\ProgrammingError;

/**
 * Use this if you want to make use of Icinga funtionality in other web projects
 *
 * Usage example:
 * <code>
 * use Icinga\Application\EmbeddedWeb;
 * EmbeddedWeb::start();
 * </code>
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Application
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class EmbeddedWeb extends ApplicationBootstrap
{
    protected function bootstrap()
    {
        return $this->loadConfig()
                    ->configureErrorHandling()
                    ->setTimezone()
                    ->loadEnabledModules();
    }
}
