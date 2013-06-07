<?php

/**
 * Icinga\Application\Loader class
 *
 * @category Icinga\Application
 */
namespace Icinga\Application;

use Icinga\Application\Log;

/**
 * This class provides a simple Autoloader
 *
 * It takes care of loading classes in the Icinga namespace. You shouldn't need
 * to manually instantiate this class, as bootstrapping code will do so for you.
 *
 * Usage example:
 *
 * <code>
 * Icinga\Application\Loader::register();
 * </code>
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Application
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Loader
{
    const NS = '\\';
    protected $moduleDirs = array();

    private static $instance;

    /**
     * Register the Icinga autoloader
     *
     * You could also call getInstance(), this alias function is here to make
     * code look better
     *
     * @return self
     */
    public static function register()
    {
        return self::getInstance();
    }

    /**
     * Singleton
     *
     * Registers the Icinga autoloader if not already been done
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Loader();
            self::$instance->registerAutoloader();
        }
        return self::$instance;
    }

    public function addModule($name, $dir)
    {
        $this->moduleDirs[ucfirst($name)] = $dir;
        return $this;
    }

    /**
     * Class loader
     *
     * Ignores all but classes in the Icinga namespace.
     *
     * @return boolean
     */
    public function loadClass($class)
    {
        if (strpos($class, 'Icinga' . self::NS) === false) {
            return false;
        }
        $file = str_replace(self::NS, '/', $class) . '.php';
        $file = ICINGA_LIBDIR . '/' . $file;
        if (! @is_file($file)) {
            $parts = preg_split('~\\\~', $class);
            array_shift($parts);
            $module = $parts[0];
            if (array_key_exists($module, $this->moduleDirs)) {
                $file = $this->moduleDirs[$module]
                      . '/'
                      . implode('/', $parts) . '.php';
                if (@is_file($file)) {
                    require_once $file;
                    return true;
                }
            }
            // Log::debug('File ' . $file . ' not found');
            return false;
        }
        require_once $file;
        return true;
    }

    /**
     * Effectively registers the autoloader the PHP/SPL way
     *
     * @return void
     */
    protected function registerAutoloader()
    {
        // Not adding ourselves to include_path right now, MAY be faster
        /*set_include_path(implode(PATH_SEPARATOR, array(
            realpath(dirname(dirname(__FILE__))),
            get_include_path(),
        )));*/
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Constructor
     *
     * Singleton usage is enforced, you are also not allowed to overwrite this
     * function
     *
     * @return void
     */
    final private function __construct()
    {
    }
}
