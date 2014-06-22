<?php

namespace Icinga\Web\Controller;

class ModuleActionController extends ActionController
{
    private $config;

    private $configs = array();

    public function Config($file = null)
    {
        $module = $this->getRequest()->getModuleName();

        $this->moduleName = $module;

        if ($tile === null) {
            if ($this->config === null) {
                $this->config = Config::module($module);
            }
            return $this->config;
        } else {
            if (! array_key_exists($file, $this->configs)) {
                $this->configs[$file] = Config::module($module, $file);
            }
            return $this->configs[$file];
        }
    }
}
