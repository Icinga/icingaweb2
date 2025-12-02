<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Account;

use Icinga\Authentication\TwoFactorTotp;
use Icinga\Common\Database;
use Icinga\User;
use Icinga\Web\Form\Element\FakeFormElement;
use Icinga\Web\Form\Validator\TotpTokenValidator;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;
use ipl\Web\Widget\CopyToClipboard;

/**
 * Form for enabling and disabling 2FA or creating and updating the 2FA TOTP secret
 *
 * This form is used to manage the 2FA settings of a user account.
 */
class TwoFactorConfigForm extends CompatForm
{
    use CsrfCounterMeasure;
    use Database;
    use FormUid;

    /** @var User|null The user to work with */
    protected ?User $user = null;

    /** @var TwoFactorTotp The TwoFactorTotp instance to work with */
    protected TwoFactorTotp $twoFactor;

    /** @var string The submit button to verify the 2FA TOTP secret */
    protected const SUBMIT_VERIFY = 'btn_submit_verify';

    /** @var string The submit button to disable 2FA */
    protected const SUBMIT_DISABLE = 'btn_submit_disable';

    public function __construct()
    {
        $this->setAttribute('name', 'form_config_2fa');
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

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure(Session::getSession()->getId());
        $this->addElement($this->createUidElement());

        if (TwoFactorTotp::hasDbSecret($this->getDb(), $this->user->getUsername())) {
            $this->addElement(
                'submit',
                static::SUBMIT_DISABLE,
                [
                    'label'               => $this->translate('Disable 2FA'),
                    'data-progress-label' => $this->translate('Disabling')
                ]
            );
        } else {
            $this->addElement(
                'checkbox',
                'enabled_2fa',
                [
                    'class'       => 'autosubmit',
                    'label'       => $this->translate('Enable 2FA (TOTP)'),
                    'description' => $this->translate(
                        'This option allows you to enable or to disable the two factor authentication via TOTP.'
                    ),
                ]
            );

            if ($this->getPopulatedValue('enabled_2fa') === 'y') {
                // Keep the secret after form submission, otherwise every form submission would generate a new secret.
                // This would result in the following:
                // - Users would have to scan a new QR code every time the verification fails.
                // - Token verification would fail every time because the secret would have changed.
                if ($secret = $this->getPopulatedValue('2fa_totp_secret')) {
                    $this->twoFactor = TwoFactorTotp::createFromSecret($secret, $this->user->getUsername());
                }

                $this->addHtml(new FakeFormElement(
                    HtmlElement::create('img', Attributes::create([
                        'class' => 'two-factor-totp-qr-code',
                        'src'   => $this->twoFactor->createQRCode()
                    ])),
                    $this->translate('QR Code'),
                    $this->translate('Use your authenticator app to scan the QR code.')
                ));

                $manualAuthUrl = HtmlElement::create(
                    'div',
                    Attributes::create(['class' => 'two-factor-totp-auth-url']),
                    new Text($this->twoFactor->getTotpAuthUrl()),
                );
                CopyToClipboard::attachTo($manualAuthUrl);
                $this->addHtml(new FakeFormElement(
                    $manualAuthUrl,
                    $this->translate('Manual Auth URL'),
                    $this->translate('If you have no camera to scan the QR code you can enter the auth URL manually.')
                ));

                $this->addElement(
                    'text',
                    '2fa_verification_token',
                    [
                        'required'    => true,
                        'label'       => $this->translate('Verification Token'),
                        'description' => $this->translate(
                            'Please enter the token from your authenticator app to verify your setup.'
                        ),
                        'validators'  => [new TotpTokenValidator()]
                    ]
                );

                $this->addElement(
                    'submit',
                    static::SUBMIT_VERIFY,
                    [
                        'label'               => $this->translate('Verify 2FA TOTP Secret'),
                        'data-progress-label' => $this->translate('Verifying')
                    ]
                );
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

    protected function onSuccess(): void
    {
        $twoFactor = TwoFactorTotp::createFromSecret($this->getValue('2fa_totp_secret'), $this->user->getUsername());

        switch ($this->getPressedSubmitElement()?->getName()) {
            case static::SUBMIT_VERIFY:
                $token = $this->getValue('2fa_verification_token');
                if ($token && $twoFactor->verify($token)) {
                    $twoFactor->saveToDb();
                    Notification::success($this->translate('2FA via TOTP has been configured successfully.'));
                } else {
                    Notification::error($this->translate('The verification token is invalid. Please try again.'));

                    // Don't redirect in this case, as the user might want to try again.
                    return;
                }

                break;
            case static::SUBMIT_DISABLE:
                $twoFactor->removeFromDb();
                Notification::success($this->translate('2FA TOTP secret has been removed.'));

                break;
        }

        $this->setRedirectUrl(Url::fromRequest());
    }
}
