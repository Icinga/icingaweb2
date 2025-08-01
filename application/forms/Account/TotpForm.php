<?php

namespace Icinga\Forms\Account;

use Exception;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Authentication\Totp;
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
    /**
     * Preference keys that are used in this form
     *
     * @var array
     */
    const PREFERENCE_KEYS = [
        'enabled_2fa',
    ];

    /**
     * The TOTP instance used for managing TOTP secrets
     *
     * @var Totp
     */
    protected Totp $totp;
    /**
     * Whether 2FA is enabled or not
     *
     * @var bool
     */
    protected bool $enabled2FA;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_totp');
        $this->setSubmitLabel($this->translate('Save Changes'));
        $this->setProgressLabel($this->translate('Saving'));
    }

    /**
     * Set the TOTP instance
     *
     * @param Totp $totp
     * @return self
     */
    public function setTotp(Totp $totp): self
    {
        $this->totp = $totp;

        return $this;
    }

    /**
     * Set whether 2FA is enabled or not
     *
     * @param bool $enabled2FA
     * @return self
     */
    public function setEnabled2FA(bool $enabled2FA): self
    {
        $this->enabled2FA = $enabled2FA;

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
                'value' => $this->enabled2FA,
            ]
        );

        if (isset($formData['enabled_2fa']) && $formData['enabled_2fa']
            || $this->enabled2FA) {
            $this->addElement(
                'text',
                'totp_secret',
                [
                    'label' => $this->translate('TOTP Secret:'),
                    'value' => $this->totp->getSecret() ?? $this->translate('No Secret set'),
                    'description' => $this->translate(
                        'If you generate a new TOTP secret, you will need to configure your TOTP application with this secret. '
                    ),
                    'disabled' => true,
                ]
            );

            $this->addElement(
                'text',
                'new_totp_secret',
                [
                    'label' => $this->translate('New TOTP Secret:'),
                    'value' => $this->totp->getTemporarySecret() ?? $this->translate('No Secret set'),
                    'description' => $this->translate(
                        'If you reset the TOTP secret, you will need to set it up again in your TOTP application.'
                    ),
                    'disabled' => true,
                ]
            );

            if ($this->totp->getTemporarySecret() !== null) {
                $this->addElement(
                    'text',
                    'totp_verification_code',
                    [
                        'label' => $this->translate('Enter Code to verify:'),
                        'description' => $this->translate(
                            'Please enter the verification code from your TOTP application to verify the new secret.'
                        ),
                        'class' => 'autofocus content-centered',
                        'style' => 'width: 120px;',
                        'autocomplete' => 'off',
                    ]
                );


                $this->addElement(
                    'submit',
                    'btn_verify_totp',
                    [
                        'ignore' => true,
                        'label' => $this->translate('Verify TOTP Secret'),
                        'decorators' => ['ViewHelper'],
                    ]
                );
                if ($this->totp->isTemporarySecretApproved()) {
                    $this->addElement(
                        'note',
                        'totp_secret_verified',
                        [
                            'value' => $this->translate('Secret is Verified'),
                            'decorators' => ['ViewHelper'],
                            'class' => 'alert alert-warning'
                        ]
                    );
                }


                $this->addElement(
                    'hidden',
                    'qr_code_image',
                    [
                        'required' => false,
                        'ignore' => false,
                        'autoInsertNotEmptyValidator' => false,
                        'decorators' => [
                            [
                                'HtmlTag', [
                                'tag'  => 'img',
                                'src' => $this->totp->createQRCode(),
                            ]
                            ]
                        ]
                    ]
                );

                $this->addDisplayGroup(
                    ['totp_verification_code', 'btn_verify_totp'],
                    'verify_buttons',
                    [
                        'decorators' => [
                            'FormElements',
                            [
                                'HtmlTag',
                                [
                                    'tag' => 'div',
                                    'class' => 'control-group form-controls'
                                ]
                            ]
                        ]
                    ]
                );

                $this->addElement(
                    'submit',
                    'btn_renew_totp',
                    [
                        'ignore' => true,
                        'label' => $this->translate('Renew TOTP Secret'),
                        'decorators' => ['ViewHelper'],
                    ]
                );
            } else {
                $this->addElement(
                    'submit',
                    'btn_generate_totp',
                    [
                        'ignore' => true,
                        'label' => $this->translate('Generate TOTP Secret'),
                        'decorators' => ['ViewHelper']
                    ]
                );
            }

            if ($this->totp->getSecret() !== null) {
                $this->addElement(
                    'submit',
                    'btn_delete_totp',
                    [
                        'ignore' => true,
                        'label' => $this->translate('Delete TOTP Secret'),
                        'decorators' => ['ViewHelper']
                    ]
                );
            }
            $this->addDisplayGroup(
                ['btn_delete_totp', 'btn_renew_totp', 'btn_generate_totp'],
                'change_buttons',
                [
                    'decorators' => [
                        'FormElements',
                        [
                            'HtmlTag',
                            ['tag' => 'div', 'class' => 'control-group form-controls']
                        ]
                    ]
                ]
            );
        }

        if ($this->totp->hasPendingChanges()
        || ($this->preferences->get('icingaweb')['enabled_2fa'] ?? '0') !== $formData['enabled_2fa'] ?? '0'
        ) {
            $this->addElement(
                'note',
                'totp_pending_changes',
                [
                    'value' => $this->translate('Pending Changes'),
                    'description' => $this->translate(
                        'You have pending changes to your TOTP settings. Please verify the new secret before saving.'
                    ),
                    'decorators' => ['ViewHelper'],
                    'class' => 'alert alert-warning'
                ]
            );

            $this->addElement(
                'submit',
                'btn_cancel_totp',
                [
                    'ignore' => true,
                    'label' => $this->translate('Cancel Changes'),
                    'decorators' => ['ViewHelper'],
                    'class' => 'btn-secondary'
                ]
            );

            $this->addDisplayGroup(
                ['totp_pending_changes'],
                'labels',
                [
                    'decorators' => [
                        'FormElements',
                        ['HtmlTag', ['tag' => 'div', 'class' => 'form-controls']]
                    ]
                ]
            );
        }

        $this->addElement(
            'submit',
            'btn_submit',
            [
                'ignore' => true,
                'label' => $this->translate('Save Change'),
                'decorators' => ['ViewHelper'],
                'class' => 'btn-primary'
            ]
        );

        $this->addDisplayGroup(
            ['btn_submit', 'btn_cancel_totp'],
            'submit_buttons',
            [
                'decorators' => [
                    'FormElements',
                    ['HtmlTag', ['tag' => 'div', 'class' => 'control-group form-controls']]
                ]
            ]
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
                if ($this->totp->hasPendingChanges()
                    || $this->getValue('enabled_2fa') !== $webPreferences['enabled_2fa']) {
                    if (!$this->totp->requiresSecretCheck()) {
                        foreach ($this->getValues() as $key => $value) {
                            if (in_array($key, self::PREFERENCE_KEYS, true)) {
                                $webPreferences[$key] = $value;
                            }
                        }
                        $this->totp->makeChangesPermanent();
                        Session::getSession()->delete('enabled_2fa');
                        if ($webPreferences['enabled_2fa'] == 1) {
                            $webPreferences['enabled_2fa'] = $this->totp->userHasSecret() ? '1' : '0';
                        }
                        $this->preferences->icingaweb = $webPreferences;
                        Session::getSession()->user->setPreferences($this->preferences);
                        $this->save();
                        Notification::success($this->translate('Saved Changes.'));

                        return true;
                    } else {
                        Notification::warning(
                            $this->translate('The new secret needs to be verified before saving.')
                        );
                    }
                } else {
                    Notification::info($this->translate('No changes to save.'));
                }
            } elseif ($this->getElement('btn_generate_totp')
                && $this->getElement('btn_generate_totp')->isChecked()) {
                $this->totp->generateSecret()->saveTemporaryInSession();

                return true;
            } elseif ($this->getElement('btn_renew_totp')
                && $this->getElement('btn_renew_totp')->isChecked()) {
                $this->totp->generateSecret()->saveTemporaryInSession();

                return true;
            } elseif ($this->getElement('btn_delete_totp')
                && $this->getElement('btn_delete_totp')->isChecked()) {
                $this->totp->deleteSecrets()->saveTemporaryInSession();
                Notification::info($this->translate('Deleted TOTP Secret'));

                return true;
            } elseif ($this->getElement('btn_verify_totp')
                && $this->getElement('btn_verify_totp')->isChecked()) {
                $verificationCode = $this->getValue('totp_verification_code');
                if ($this->totp->approveTemporarySecret($verificationCode)) {
                    Notification::success($this->translate('TOTP Secret verified successfully.'));

                    return true;
                } else {
                    $this->getElement('totp_verification_code')->addError(
                        $this->translate('Verification code is invalid.')
                    );
                }
            } elseif ($this->getElement('btn_cancel_totp')
                && $this->getElement('btn_cancel_totp')->isChecked()) {
                $this->totp->resetChanges();
                $this->enabled2FA = $this->preferences->get('icingaweb')['enabled_2fa'] === '1';
                Session::getSession()->delete('enabled_2fa');

                return true;
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

        if (($enabled = Session::getSession()->get('enabled_2fa', null)) !== null) {
            $values['enabled_2fa'] = $enabled == 1 ? '1' : '0';
        }

        $this->populate($values);
    }


    public function isSubmitted()
    {
        if (
            ($this->getElement('btn_generate_totp') && $this->getElement('btn_generate_totp')->isChecked())
            || ($this->getElement('btn_renew_totp') && $this->getElement('btn_renew_totp')->isChecked())
            || ($this->getElement('btn_delete_totp') && $this->getElement('btn_delete_totp')->isChecked())
            || ($this->getElement('btn_verify_totp') && $this->getElement('btn_verify_totp')->isChecked())
            || ($this->getElement('btn_cancel_totp') && $this->getElement('btn_cancel_totp')->isChecked())
            || ($this->getElement('btn_submit') && $this->getElement('btn_submit')->isChecked())
        ) {
            return true;
        }

        return false;
    }
}
