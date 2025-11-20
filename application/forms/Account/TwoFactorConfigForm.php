<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Account;

use Icinga\Authentication\TwoFactorTotp;
use Icinga\Common\Database;
use Icinga\User;
use Icinga\Web\Form;
use Icinga\Web\Notification;

/**
 * Form for enabling and disabling 2FA or creating and updating the 2FA TOTP secret
 *
 * This form is used to manage the 2FA settings of a user account.
 */
class TwoFactorConfigForm extends Form
{
    use Database;

    /** @var User|null The user to work with */
    protected ?User $user = null;

    /** @var TwoFactorTotp The TwoFactorTotp instance to work with */
    protected TwoFactorTotp $twoFactor;

    /** @const Label for the button to verify the 2FA TOTP secret */
    protected const VERIFY_2FA_LABEL = 'Verify 2FA TOTP Secret';

    /** @const Label for the button to remove the 2FA TOTP secret, which disables 2FA */
    protected const DISABLE_2FA_LABEL = 'Disable 2FA';

    public function init(): void
    {
        $this->setName('form_2fa');
    }

    /**
     * Set the user to work with
     *
     * @param User $user The user to work with
     *
     * @return $this
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the TwoFactorTotp instance to work with
     *
     * @param TwoFactorTotp $twoFactor
     *
     * @return $this
     */
    public function setTwoFactor(TwoFactorTotp $twoFactor): static
    {
        $this->twoFactor = $twoFactor;

        return $this;
    }

    public function createElements(array $formData): void
    {
        if (TwoFactorTotp::hasDbSecret($this->getDb(), $this->user->getUsername())) {
            $this->setSubmitLabel(static::DISABLE_2FA_LABEL);
            $this->setProgressLabel($this->translate('Disabling'));
        } else {
            $this->addElement(
                'checkbox',
                'enabled_2fa',
                [
                    'autosubmit'  => true,
                    'label'       => $this->translate('Enable 2FA (TOTP)'),
                    'description' => $this->translate(
                        'This option allows you to enable or to disable the two factor authentication via TOTP.'
                    ),
                ]
            );

            if (isset($formData['enabled_2fa']) && $formData['enabled_2fa']) {
                // Keep the same secret if validation fails, otherwise the user had to scan a new QR code every time.
                if (isset($formData['2fa_totp_secret'])) {
                    $this->twoFactor = TwoFactorTotp::createFromSecret(
                        $formData['2fa_totp_secret'],
                        $this->user->getUsername()
                    );
                }

                $this->addElement(
                    'hidden',
                    '2fa_totp_qr_code',
                    [
                        'decorators' => [
                            [
                                'HtmlTag',
                                [
                                    'tag'   => 'img',
                                    'src'   => $this->twoFactor->createQRCode(),
                                    'class' => 'two-factor-totp-qr-code'
                                ]
                            ]
                        ]
                    ]
                );

                $this->addElement(
                    'textarea',
                    '2fa_manual_auth_url',
                    [
                        'ignore'   => true,
                        'disabled' => true,
                        'label'    => $this->translate('Manual Auth URL'),
                        'value'    => $this->twoFactor->getTotpAuthUrl()
                    ]
                );

                $this->addElement(
                    'number',
                    '2fa_verification_token',
                    array(
                        'label'       => $this->translate('Verification Token'),
                        'description' => $this->translate(
                            'Please enter the token from your authenticator app to verify your setup.'
                        ),
                        'min'         => 0,
                        'max'         => 999999,
                        'step'        => 1
                    )
                );

                $this->setSubmitLabel(static::VERIFY_2FA_LABEL);
                $this->setProgressLabel($this->translate('Verifying'));
            }
        }

        $this->addElement(
            'hidden',
            '2fa_totp_secret',
            [
                'value' => $this->twoFactor->getSecret()
            ]
        );
    }

    public function onSuccess(): bool
    {
        $shouldRedirect = true;

        if ($this->getElement('btn_submit')) {
            $twoFactor = TwoFactorTotp::createFromSecret($this->getValue('2fa_totp_secret'), $this->user->getUsername());

            switch ($this->getValue('btn_submit')) {
                case static::VERIFY_2FA_LABEL:
                    if ($twoFactor->verify($this->getValue('2fa_verification_token'))) {
                        $twoFactor->saveToDb();
                        Notification::success($this->translate('2FA via TOTP has been configured successfully.'));
                    } else {
                        $shouldRedirect = false;
                        Notification::error($this->translate('The verification token is invalid. Please try again.'));
                    }

                    break;
                case static::DISABLE_2FA_LABEL:
                    $twoFactor->removeFromDb();
                    Notification::success($this->translate('2FA TOTP secret has been removed.'));

                    break;
            }
        }

        return $shouldRedirect;
    }
}
