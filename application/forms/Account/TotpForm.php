<?php

namespace Icinga\Forms\Account;

use Exception;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Authentication\Totp;
use Icinga\Data\Filter\Filter;
use Icinga\Forms\PreferenceForm;
use Icinga\User\Preferences;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Session;

/**
 * Form for creating, updating, enable and disable TOTP settings
 *
 * This form is used to manage the TOTP settings of a user account.
 */
class TotpForm extends PreferenceForm
{
    protected Totp $totp;
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_totp');
        $this->setSubmitLabel($this->translate('Save Changes'));
        $this->setProgressLabel($this->translate('Saving'));
    }

    public function setTotp(Totp $totp): self
    {
        $this->totp = $totp;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'checkbox',
            'enabled_2fa',
            [
                'required' => false,
                'autosubmit' => true,
                'label' => $this->translate('Enable TOTP 2FA'),
                'description' => $this->translate(
                    'This option allows you to enable or to disable the second factor authentication via TOTP'
                ),
                'value' => '',
            ]
        );

        if (isset($formData['enabled_2fa']) && $formData['enabled_2fa']) {

            $this->addElement(
                'text',
                'totp_secret',
                [
                    'label' => $this->translate('TOTP Secret:'),
                    'value' => $this->totp->getSecret() ?? $this->translate('No Secret set'),
                    'description' => $this->translate(
                        'If you generate a new TOTP secret, you will need to reconfigure your TOTP application with the new secret. ' .
                        'If you reset the TOTP secret, you will lose access to your TOTP application and will need to set it up again.'
                    ),
                    'disabled' => true,
//                    'decorators' => ['ViewHelper']
                ]
            );

            if ($this->totp->getSecret() !== null) {
                $this->addElement(
                    'submit',
                    'btn_renew_totp',
                    array(
                        'ignore' => true,
                        'label' => $this->translate('Renew TOTP Secret'),
                        'decorators' => array('ViewHelper'),
                    )
                );

                $this->addElement(
                    'submit',
                    'btn_delete_totp',
                    array(
                        'ignore' => true,
                        'label' => $this->translate('Delete TOTP Secret'),
                        'decorators' => array('ViewHelper')
                    )
                );
            } else {
                $this->addElement(
                    'submit',
                    'btn_generate_totp',
                    array(
                        'ignore' => true,
                        'label' => $this->translate('Generate TOTP Secret'),
                        'decorators' => array('ViewHelper')
                    )
                );
            }
        }

        $this->addElement(
            'submit',
            'btn_submit',
            array(
                'ignore' => true,
                'label' => $this->translate('Save to the Preferences'),
                'decorators' => array('ViewHelper'),
                'class' => 'btn-primary'
            )
        );

        $this->addDisplayGroup(
            array('btn_submit', 'btn_delete_totp', 'btn_renew_totp', 'btn_generate_totp'),
            'submit_buttons',
            array(
                'decorators' => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {


        try {
            if ($this->getElement('btn_submit') && $this->getElement('btn_submit')->isChecked()) {
                $this->preferences = new Preferences($this->store ? $this->store->load() : array());
                $webPreferences = $this->preferences->get('icingaweb');
                foreach ($this->getValues() as $key => $value) {
                    if ($value === ''
                        || $value === null
                        || $value === 'autodetect'
                    ) {
                        if (isset($webPreferences[$key])) {
                            unset($webPreferences[$key]);
                        }
                    } else {
                        $webPreferences[$key] = $value;
                    }
                }
                $this->preferences->icingaweb = $webPreferences;
                Session::getSession()->user->setPreferences($this->preferences);
                $this->save();
                Notification::success($this->translate('Submitted btn_submit'));

                return true;
            } elseif ($this->getElement('btn_generate_totp') && $this->getElement('btn_generate_totp')->isChecked()) {
                Notification::success($this->translate('Submitted btn_generate_totp'));

                return true;
            } elseif ($this->getElement('btn_renew_totp') && $this->getElement('btn_renew_totp')->isChecked()) {
                Notification::success($this->translate('Submitted btn_renew_totp'));

                return true;
            } elseif ($this->getElement('btn_delete_totp') && $this->getElement('btn_delete_totp')->isChecked()) {
                Notification::info($this->translate('Submitted btn_delete_totp'));

                return false;
            }
        } catch (Exception $e) {
            Logger::error($e);
            Notification::error($e->getMessage());
        }

        return false;
    }

    /**
     * Populate preferences
     *
     * @see Form::onRequest()
     */
    public function onRequest()
    {
        $auth = Auth::getInstance();
        $values = $auth->getUser()->getPreferences()->get('icingaweb');

        if (!isset($values['enabled_2fa'])) {
            $values['enabled_2fa'] = '0';
        }

        $this->populate($values);
    }

    public function isSubmitted()
    {
        if (
            ($this->getElement('btn_generate_totp') && $this->getElement('btn_generate_totp')->isChecked())
            || ($this->getElement('btn_renew_totp') && $this->getElement('btn_renew_totp')->isChecked())
            || ($this->getElement('btn_delete_totp') && $this->getElement('btn_delete_totp')->isChecked())
            || ($this->getElement('btn_submit') && $this->getElement('btn_submit')->isChecked())
        ) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
//    public function isValid($formData)
//    {
////        $valid = parent::isValid($formData);
////        if (! $valid) {
////            return false;
////        }
////
////        $oldPasswordEl = $this->getElement('old_password');
////
////        if (! $this->backend->authenticate($this->Auth()->getUser(), $oldPasswordEl->getValue())) {
////            $oldPasswordEl->addError($this->translate('Old password is invalid'));
////            $this->markAsError();
////            return false;
////        }
////
////        return true;
//    }
}
