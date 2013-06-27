<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Benchmark;
use Icinga\Authentication\Manager;
use Icinga\Web\ActionController;
use Icinga\Web\Hook\Configuration\ConfigurationTab;
use Icinga\Web\Hook\Configuration\ConfigurationTabBuilder;

/**
 * Class ConfigurationController
 */
class ConfigurationController extends ActionController
{
    public function init()
    {
        parent::init();
    }


    /**
     * Index action
     */
    public function indexAction()
    {
        $tabBuilder = new ConfigurationTabBuilder(
            $this->widget('tabs')
        );

        $tabBuilder->build();

        $this->view->tabs = $tabBuilder->getTabs();
    }
}

// @codingStandardsIgnoreEnd