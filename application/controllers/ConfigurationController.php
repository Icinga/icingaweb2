<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Icinga\Web\ActionController;

/**
 * Class ConfigurationController
 */
class ConfigurationController extends ActionController
{
    /**
     * Index action
     */
    public function indexAction()
    {
        $this->view->tabs = $this->createTabs();
    }

    /**
     * @return \Icinga\Web\Widget
     */
    protected function createTabs()
    {
        $tabs = $this->widget('tabs')->add(
                'configuration',
                array(
                    'title' => $this->translate('Overview'),
                    'url'   => 'configuration/index',
            )
        );

        return $tabs;
    }
}

// @codingStandardsIgnoreEnd