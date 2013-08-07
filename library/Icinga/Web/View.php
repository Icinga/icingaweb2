<?php

namespace Icinga\Web;

use Zend_View_Abstract as ZfViewAbstract;
use Icinga\Web\Url;
use Icinga\Util\Format;

class View extends ZfViewAbstract
{
    private $_useViewStream = false;

    public function __construct($config = array())
    {
        $this->_useViewStream = (bool) ini_get('short_open_tag') ? false : true;
        if ($this->_useViewStream) {
            if (!in_array('zend.view', stream_get_wrappers())) {
                stream_wrapper_register('zend.view', '\Icinga\Web\ViewStream');
            }
        }
        parent::__construct($config);
    }

    public function init()
    {
        $this->loadGlobalHelpers();
    }

    protected function loadGlobalHelpers()
    {
        $pattern = dirname(__FILE__) . '/View/helpers/*.php';
        $files = glob($pattern);
        foreach ($files as $file) {
            require_once $file;
        }
    }

    protected function _run()
    {
        foreach ($this->getVars() as $k => $v) {
            // Exporting global variables to view scripts:
            $$k = $v;
        }
        if ($this->_useViewStream) {
           include 'zend.view://' . func_get_arg(0);
        } else {
            include func_get_arg(0);
        }
    }

    public function __call($name, $args)
    {
        $namespaced = '\\Icinga\\Web\\View\\' . $name;
        if (function_exists($namespaced)) {
            return call_user_func_array(
                $namespaced,
                $args
            );
        } else {
            return parent::__call($name, $args);
        }
    }
}
