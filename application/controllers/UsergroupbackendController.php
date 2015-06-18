<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Application\Config;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Forms\Config\UserGroup\UserGroupBackendForm;
use Icinga\Web\Controller;
use Icinga\Web\Notification;
use Icinga\Web\Url;

/**
 * Controller to configure user group backends
 */
class UsergroupbackendController extends Controller
{
    /**
     * Initialize this controller
     */
    public function init()
    {
        $this->assertPermission('config/application/usergroupbackend');
    }

    /**
     * Redirect to this controller's list action
     */
    public function indexAction()
    {
        $this->redirectNow('usergroupbackend/list');
    }

    /**
     * Show a list of all user group backends
     */
    public function listAction()
    {
        $this->view->backendNames = Config::app('groups')->keys();
        $this->createListTabs()->activate('usergroupbackend');
    }

    /**
     * Create a new user group backend
     */
    public function createAction()
    {
        $form = new UserGroupBackendForm();
        $form->setRedirectUrl('usergroupbackend/list');
        $form->setTitle($this->translate('Create New User Group Backend'));
        $form->addDescription($this->translate('Create a new backend to associate users and groups with.'));
        $form->setIniConfig(Config::app('groups'));
        $form->setOnSuccess(function (UserGroupBackendForm $form) {
            try {
                $form->add(array_filter($form->getValues()));
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($form->save()) {
                Notification::success(t('User group backend successfully created'));
                return true;
            }

            return false;
        });
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Edit an user group backend
     */
    public function editAction()
    {
        $backendName = $this->params->getRequired('backend');

        $form = new UserGroupBackendForm();
        $form->setAction(Url::fromRequest());
        $form->setRedirectUrl('usergroupbackend/list');
        $form->setTitle(sprintf($this->translate('Edit User Group Backend %s'), $backendName));
        $form->setIniConfig(Config::app('groups'));
        $form->setOnSuccess(function (UserGroupBackendForm $form) use ($backendName) {
            try {
                $form->edit($backendName, array_map(
                    function ($v) {
                        return $v !== '' ? $v : null;
                    },
                    $form->getValues()
                ));
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($form->save()) {
                Notification::success(sprintf(t('User group backend "%s" successfully updated'), $backendName));
                return true;
            }

            return false;
        });

        try {
            $form->load($backendName);
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound(sprintf($this->translate('User group backend "%s" not found'), $backendName));
        }

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Remove a user group backend
     */
    public function removeAction()
    {
        $backendName = $this->params->getRequired('backend');

        $backendForm = new UserGroupBackendForm();
        $backendForm->setIniConfig(Config::app('groups'));
        $form = new ConfirmRemovalForm();
        $form->setRedirectUrl('usergroupbackend/list');
        $form->setTitle(sprintf($this->translate('Remove User Group Backend %s'), $backendName));
        $form->setOnSuccess(function (ConfirmRemovalForm $form) use ($backendName, $backendForm) {
            try {
                $backendForm->delete($backendName);
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($backendForm->save()) {
                Notification::success(sprintf(t('User group backend "%s" successfully removed'), $backendName));
                return true;
            }

            return false;
        });
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Create the tabs for the application configuration
     */
    protected function createListTabs()
    {
        $tabs = $this->getTabs();
        if ($this->hasPermission('config/application/general')) {
            $tabs->add('general', array(
                'title' => $this->translate('Adjust the general configuration of Icinga Web 2'),
                'label' => $this->translate('General'),
                'url'   => 'config/general'
            ));
        }
        if ($this->hasPermission('config/application/resources')) {
            $tabs->add('resource', array(
                'title' => $this->translate('Configure which resources are being utilized by Icinga Web 2'),
                'label' => $this->translate('Resources'),
                'url'   => 'config/resource'
            ));
        }
        if ($this->hasPermission('config/application/userbackend')) {
            $tabs->add('userbackend', array(
                'title' => $this->translate('Configure how users authenticate with and log into Icinga Web 2'),
                'label' => $this->translate('Authentication'),
                'url'   => 'config/userbackend'
            ));
        }
        if ($this->hasPermission('config/application/usergroupbackend')) {
            $tabs->add('usergroupbackend', array(
                'title' => $this->translate('Configure how users are associated with groups by Icinga Web 2'),
                'label' => $this->translate('User Groups'),
                'url'   => 'usergroupbackend/list'
            ));
        }

        return $tabs;
    }
}
