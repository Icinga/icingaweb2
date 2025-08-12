<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Account;

use Icinga\Application\Config;
use Icinga\Authentication\DefaultPasswordPolicy;
use Icinga\Authentication\User\DbUserBackend;
use Icinga\Data\Filter\Filter;
use Icinga\User;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use ipl\Html\Text;
use Icinga\Authentication\PasswordPolicyInterface;
use Icinga\Forms\Config\GeneralConfigForm;

/**
 * Form for changing user passwords
 */
class ChangePasswordForm extends Form
{
    /**
     * The user backend
     *
     * @var DbUserBackend
     */
    protected $backend;
    /**@var PasswordPolicyInterface */
    protected $passwordPolicy;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setSubmitLabel($this->translate('Update Account'));
    }

    /*
     * Set the password policy
     */
    public function setPasswordPolicy(PasswordPolicyInterface $policy)
    {
        $this->passwordPolicy = $policy;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {

        $passwordPolicy = Config::app()->get('global', 'password_policy');

        if ($passwordPolicy == 1) {
            $this->setPasswordPolicy(new DefaultPasswordPolicy());
            $message = $this->passwordPolicy->displayPasswordPolicy();
            $this->info($this->translate($message));
        }


        $this->addElement(
            'password',
            'old_password',
            array(
                'label' => $this->translate('Old Password'),
                'required' => true
            )
        );
        $this->addElement(
            'password',
            'new_password',
            array(
                'label' => $this->translate('New Password'),
                'required' => true
            )
        );
        $this->addElement(
            'password',
            'new_password_confirmation',
            array(
                'label' => $this->translate('Confirm New Password'),
                'required' => true,
                'validators' => array(
                    array('identical', false, array('new_password'))
                )
            )
        );
    }

    public function checkPasswordPolicy(): bool
    {
        /*
         *  überprüfen ob pw-policy gesetzt
         * wenn gesetzt dann Text anzeigen mit displayPasswordPolicy
         * neues passwort aus element speichern
         * überprüfen ob pwp eingehalten wird mit validatePassword
         * wenn nicht eingehalten wird dann fehlermeldung ausgeben
        */

        $newPassword = $this->getElement('new_password')->getValue();
        $validatePassword = $this->passwordPolicy->validatePassword($newPassword);

        if (!$validatePassword) {
           $message = $this->passwordPolicy->getPolicyViolation($newPassword);
           $this->getElement('new_password')->addError($this->translate($message));
            return false;
        }

        return true;

    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        $backend = $this->getBackend();
        $backend->update(
            $backend->getBaseTable(),
            array('password' => $this->getElement('new_password')->getValue()),
            Filter::where('user_name', $this->Auth()->getUser()->getUsername())
        );
        Notification::success($this->translate('Account updated'));
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($formData)
    {
        $valid = parent::isValid($formData);
        if (!$valid) {
            return false;
        }


        if ($this->passwordPolicy && (! $this->checkPasswordPolicy())) {
            var_dump('test');
            return false;
        }

        $oldPasswordEl = $this->getElement('old_password');

        if (!$this->backend->authenticate($this->Auth()->getUser(), $oldPasswordEl->getValue())) {
            $oldPasswordEl->addError($this->translate('Old password is invalid'));
            $this->markAsError();
            return false;
        }

        return true;
    }

    /**
     * Get the user backend
     *
     * @return  DbUserBackend
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Set the user backend
     *
     * @param DbUserBackend $backend
     *
     * @return  $this
     */
    public function setBackend(DbUserBackend $backend)
    {
        $this->backend = $backend;
        return $this;
    }
}
