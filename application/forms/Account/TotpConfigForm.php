<?php

namespace Icinga\Forms\Account;

use Icinga\Authentication\IcingaTotp;
use Icinga\Common\Database;
use Icinga\Model\TotpModel;
use Icinga\User;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use ipl\Stdlib\Filter;

/**
 * Form for enabling and disabling TOTP or creating and updating the TOTP secret
 *
 * This form is used to manage the TOTP settings of a user account.
 */
class TotpConfigForm extends Form
{
    use Database;

    /** @var User|null The user to work with */
    protected ?User $user = null;

    /** @var IcingaTotp The TOTP instance to work with */
    protected IcingaTotp $totp;

    /** @const Label for the button to verify the totp secret */
    protected const VERIFY_LABEL = 'Verify TOTP Secret';

    /** @const Label for the button to remove the totp secret */
    protected const REMOVE_LABEL = 'Remove TOTP Secret';

    public function init(): void
    {
        $this->setName('form_totp');
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
     * Set the TOTP instance to work with
     *
     * @param IcingaTotp $totp
     *
     * @return $this
     */
    public function setTotp(IcingaTotp $totp): static
    {
        $this->totp = $totp;

        return $this;
    }

    public function createElements(array $formData): void
    {
        if (! IcingaTotp::hasDbSecret($this->getDb(), $this->user->getUsername())) {
            $this->addElement(
                'checkbox',
                'enabled_2fa',
                [
                    'autosubmit'  => true,
                    'label'       => $this->translate('Enable TOTP 2FA'),
                    'description' => $this->translate(
                        'This option allows you to enable or to disable the two factor authentication via TOTP.'
                    ),
                ]
            );
        }

        if ($dbTotp = IcingaTotp::loadFromDb($this->getDb(), $this->user->getUsername())) {
            $this->addElement(
                'text',
                'user_totp_secret',
                [
                    'value' => $dbTotp->getSecret()
                ]
            );

            $this->setSubmitLabel(static::REMOVE_LABEL);
            $this->setProgressLabel($this->translate('Removing'));
        } elseif (isset($formData['enabled_2fa']) && $formData['enabled_2fa']) {
            // Keep the same secret if the validation fails, otherwise the user had to scan a new QR code every time.
            if (isset($formData['totp_secret'])) {
                $this->totp = IcingaTotp::createFromSecret($formData['totp_secret'], $this->user->getUsername());
            }

            $this->addElement(
                'hidden',
                'totp_qr_code',
                [
                    'decorators' => [
                        [
                            'HtmlTag',
                            [
                                'tag'   => 'img',
                                'src'   => $this->totp->createQRCode(),
                                'class' => 'totp-qr-code'
                            ]
                        ]
                    ]
                ]
            );

            $this->addElement(
                'textarea',
                'totp_manual_token_url',
                [
                    'ignore'   => true,
                    'disabled' => true,
                    'label'    => $this->translate('Manual Token URL'),
                    'value'    => $this->totp->getTotpAuthUrl()
                ]
            );

            $this->addElement(
                'number',
                'totp_verification_code',
                array(
                    'label'       => $this->translate('Verification Code'),
                    'description' => $this->translate(
                        'Please enter the code from your authenticator app to verify your setup.'
                    ),
                    'min'         => 0,
                    'max'         => 999999,
                    'step'        => 1
                )
            );

            $this->setSubmitLabel(static::VERIFY_LABEL);
            $this->setProgressLabel($this->translate('Verifying'));
        }

        $this->addElement(
            'hidden',
            'totp_secret',
            [
                'value' => $this->totp->getSecret()
            ]
        );
    }

    public function onSuccess(): bool
    {
        $shouldRedirect = true;

        if ($this->getElement('btn_submit')) {
            switch ($this->getValue('btn_submit')) {
                case static::VERIFY_LABEL:
                    $totp = IcingaTotp::createFromSecret(
                        $this->getValue('totp_secret'),
                        $this->user->getUsername()
                    );

                    if ($totp->verify($this->getValue('totp_verification_code'))) {
                        $totp = IcingaTotp::createFromSecret(
                            $this->getValue('totp_secret'),
                            $this->user->getUsername()
                        );
                        $totp->saveToDb();

                        Notification::success($this->translate('TOTP 2FA has been configured successfully.'));
                    } else {
                        Notification::error($this->translate('The verification code is invalid. Please try again.'));
                        $shouldRedirect = false;
                    }
                    break;
                case static::REMOVE_LABEL:
                    $totp = IcingaTotp::createFromSecret(
                        $this->getValue('totp_secret'),
                        $this->user->getUsername()
                    );
                    $totp->removeFromDb();

                    Notification::success($this->translate('TOTP 2FA secret has been removed.'));
                    break;
            }
        } elseif ($this->getElement('enabled_2fa')) {
            if ($this->getValue('enabled_2fa')) {
                Session::getSession()->set('enabled_2fa', true);
            } else {
                Session::getSession()->delete('enabled_2fa');
            }
        }

        return $shouldRedirect;
    }

    /**
     * {@inheritdoc}
     */
    public function onRequest(): void
    {
        $enabledTemporary = Session::getSession()->get('enabled_2fa');

        $totpQuery = TotpModel::on($this->getDb())->filter(Filter::equal('username', $this->user->getUsername()));
        $dbTotp = $totpQuery->first();

        $this->populate([
            'enabled_2fa' => isset($dbTotp->secret) || $enabledTemporary,
        ]);

        Session::getSession()->delete('enabled_2fa');
    }
}
