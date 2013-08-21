<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

use \Icinga\Application\Benchmark;
use \Icinga\Authentication\Manager;
use \Icinga\Web\Controller\BaseConfigController;
use \Icinga\Web\Widget\Tab;
use \Icinga\Web\Url;
use \Icinga\Web\Hook\Configuration\ConfigurationTabBuilder;
use \Icinga\Application\Icinga;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Form\Config\GeneralForm;
use \Icinga\Form\Config\AuthenticationForm;
use \Icinga\Form\Config\Authentication\LdapBackendForm;
use \Icinga\Form\Config\Authentication\DbBackendForm;
use \Icinga\Form\Config\LoggingForm;
use \Icinga\Config\PreservingIniWriter;

/**
 * Application wide controller for application preferences
 */
class ConfigController extends BaseConfigController
{
    /**
     * Create tabs for this configuration controller
     *
     * @return  array
     *
     * @see     BaseConfigController::createProvidedTabs()
     */
    public static function createProvidedTabs()
    {
        return array(
            'index' => new Tab(
                array(
                    'name'      => 'index',
                    'title'     => 'Application',
                    'url'       => Url::fromPath('/config')
                )
            ),

            'authentication' => new Tab(
                array(
                    'name'      => 'auth',
                    'title'     => 'Authentication',
                    'url'       =>  Url::fromPath('/config/authentication')
                )
            ),

            'logging' => new Tab(
                array(
                    'name'      => 'logging',
                    'title'     => 'Logging',
                    'url'       => Url::fromPath('/config/logging')
                )
            ),
            'modules' => new Tab(
                array(
                    'name'      => 'modules',
                    'title'     => 'Modules',
                    'url'       => Url::fromPath('/config/moduleoverview')
                )
            )
        );
    }

    /**
     * Index action, entry point for configuration
     */
    public function indexAction()
    {
        $form = new GeneralForm();

        $form->setConfiguration(IcingaConfig::app());
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            if (!$this->writeConfigFile($form->getConfig(), 'config'))  {
                return false;
            }
            $this->redirectNow('/config');
        }
        $this->view->form = $form;
    }


    /**
     * Form for modifying the logging configuration
     */
    public function loggingAction()
    {
        $form = new LoggingForm();
        $form->setConfiguration(IcingaConfig::app());
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            $config = $form->getConfig();
            if (!$this->writeConfigFile($form->getConfig(), 'config')) {
                return;
            }
            $this->redirectNow('/config/logging');
        }
        $this->view->form = $form;
    }

    /**
     * Display the list of all modules
     */
    public function moduleoverviewAction()
    {
        $this->view->modules = Icinga::app()->getModuleManager()->select()
            ->from('modules')
            ->order('name');
        $this->render('module/overview');
    }

    /**
     * Enable a specific module provided by the 'name' param
     */
    public function moduleenableAction()
    {
        $manager = Icinga::app()->getModuleManager();
        $manager->enableModule($this->_getParam('name'));
        $manager->loadModule($this->_getParam('name'));
        $this->redirectNow('config/moduleoverview?_render=body');
    }

    /**
     * Disable a module specific module provided by the 'name' param
     */
    public function moduledisableAction()
    {
        $manager = Icinga::app()->getModuleManager();
        $manager->disableModule($this->_getParam('name'));
        $this->redirectNow('config/moduleoverview?_render=body');
    }

    /**
     * Action for creating a new authentication backend
     */
    public function authenticationAction()
    {
        $form = new AuthenticationForm();
        $config = IcingaConfig::app('authentication');
        $form->setConfiguration($config);
        $form->setRequest($this->_request);

        if ($form->isSubmittedAndValid()) {
            $modifiedConfig = $form->getConfig();
            if (empty($modifiedConfig)) {
                $form->addError('You need at least one authentication backend.');
            } else if (!$this->writeAuthenticationFile($modifiedConfig)) {
                return;
            } else {
                $this->redirectNow('/config/authentication');
            }
        }
        $this->view->form = $form;
    }

    /**
     * Action for creating a new authentication backend
     */
    public function createauthenticationbackendAction()
    {
        if ($this->getRequest()->getParam('type') === 'ldap') {
            $form = new LdapBackendForm();
        } else {
            $form = new DbBackendForm();
        }
        $form->setRequest($this->getRequest());
        if ($form->isSubmittedAndValid()) {
            $backendCfg = IcingaConfig::app('authentication')->toArray();
            foreach ($form->getConfig() as $backendName => $settings) {
                $backendCfg[$backendName] = $settings;
            }
            if (!$this->writeAuthenticationFile($backendCfg)) {
                return;
            }
            $this->redirectNow('/config/authentication');

        }
        $this->view->form = $form;
        $this->render('authentication/modify');
    }

    /**
     * Write changes to an authentication file
     *
     * This uses the Zend_Config_Writer_Ini implementation for now, as the Preserving ini writer can't
     * handle ordering
     *
     * @param   array $config The configuration changes
     *
     * @return  bool True when persisting succeeded, otherwise false
     *
     * @see     writeConfigFile()
     */
    private function writeAuthenticationFile($config) {
        $writer = new Zend_Config_Writer_Ini(
            array(
                'config' => new Zend_Config($config),
                'filename' => IcingaConfig::app('authentication')->getConfigFile()
            )
        );
        return $this->writeConfigFile($config, 'authentication', $writer);
    }

    /**
     * Write changes to a configuration file $file, using the supplied writer or PreservingIniWriter if none is set
     *
     * @param   array|Zend_Config   $config The configuration to write
     * @param   string              $file   The filename to write to (without .ini)
     * @param   Zend_Config_Writer  $writer An optional writer to use for persisting changes
     *
     * @return  bool True when persisting succeeded, otherwise false
     */
    private function writeConfigFile($config, $file, $writer = null)
    {
        if (is_array($config)) {
            $config = new Zend_Config($config);
        }
        if ($writer === null) {
            $writer = new PreservingIniWriter(
                array(
                    'config' => $config,
                    'filename' => IcingaConfig::app($file)->getConfigFile()
                )
            );
        }
        try {
            $writer->write();
            return true;
        } catch (Exception $exc) {
            $this->view->exceptionMessage = $exc->getMessage();
            $this->view->iniConfigurationString = $writer->render();
            $this->view->file = $file;
            $this->render('show-configuration');
            return false;
        }
    }
}
// @codingStandardsIgnoreEnd
