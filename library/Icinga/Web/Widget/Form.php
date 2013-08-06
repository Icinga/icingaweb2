<?php

namespace Icinga\Web\Widget;

use Icinga\Exception\ProgrammingError;
use Icinga\Application\Icinga;

/**
 * A form loader
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Form
{
    protected $form;
    protected $properties = array(
        'name'    => null,
        'options' => null
    );

    public function __call($func, $args)
    {
        return call_user_func_array(array($this->form, $func), $args);
    }

    protected function init()
    {
        // Load form by name given in props:
        $file = null;
        $fparts = array();
        $cparts = array();
        foreach (preg_split('~/~', $this->name, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $fparts[] = $part;
            $cparts[] = ucfirst($part);
        }
        array_push($fparts, ucfirst(array_pop($fparts)));

        $app = Icinga::app();
        $module_name = $this->view()->module_name;
        if ($module_name === 'default') {
            $module_name = null;
        }
        if ($module_name !== null) {
            $fname = $app->getModuleManager()->getModule($module_name)->getBaseDir()
                   . '/application/forms/'
                   . implode('/', $fparts)
                   . 'Form.php';
            if (file_exists($fname)) {
                $file = $fname;
                array_unshift($cparts, ucfirst($module_name));
            }
        }

        if ($file === null) {
            $fname = $app->getApplicationDir('forms/')
                  . implode('/', $fparts)
                  . 'Form.php';
            if (file_exists($fname)) {
                $file = $fname;
            } else {
                throw new ProgrammingError(sprintf(
                    'Unable to load your form: %s',
                    $this->name
                ));
            }
        }
        $class = 'Icinga\\Web\\Form\\' . implode('_', $cparts) . 'Form';
        require_once($file);
        $this->form = new $class($this->options);
    }

    public function render()
    {
        return (string) $this->form;
    }
}
