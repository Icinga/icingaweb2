<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Authentication;

use chillerlan\QRCode\QRCode;
use DateTime;
use Icinga\Common\Database;
use Icinga\Exception\ConfigurationError;
use Icinga\Model\TwoFactorModel;
use ipl\Sql\Connection;
use ipl\Sql\Delete;
use ipl\Sql\Insert;
use ipl\Stdlib\Filter;
use OTPHP\TOTP;
use Throwable;

class TwoFactorTotp
{
    use Database;

    protected TOTP $totp;

    protected string $user;

    private function __construct(string $user)
    {
        $this->user = $user;
    }

    protected function setTotp(TOTP $totp): static
    {
        $this->totp = $totp;

        return $this;
    }

    public static function generate(string $user): static
    {
        $twoFactor = new static($user);
        $twoFactor->setTotp(TOTP::generate(new PsrClock()));

        return $twoFactor;
    }

    public static function createFromSecret(string $secret, string $user): static
    {
        $twoFactor = new static($user);
        $twoFactor->setTotp(TOTP::createFromSecret($secret, new PsrClock()));

        return $twoFactor;
    }

    public static function loadFromDb(Connection $db, string $user): ?static
    {
        $query = TwoFactorModel::on($db)->filter(Filter::equal('username', $user));
        $dbTwoFactor = $query->first();
        if ($dbTwoFactor === null) {
            return null;
        }

        return self::createFromSecret($dbTwoFactor->secret, $user);
    }

    public static function hasDbSecret(Connection $db, string $user): bool
    {
        $query = TwoFactorModel::on($db)->filter(Filter::equal('username', $user));

        return $query->first() !== null;
    }

    public function getSecret(): string
    {
        return $this->totp->getSecret();
    }

    public function verify(string $otp): bool
    {
        // The code is valid for 10 seconds before and after the current time to allow some clock drift
        return $this->totp->verify($otp, null, 10);
    }

    /**
     * Creates a QR code for the 2FA TOTP secret.
     * This method generates a QR code that can be scanned by TOTP apps to set up the user's secret.
     *
     * @return string The rendered QR code as a string
     */
    public function createQRCode(): string
    {
        return (new QRCode())->render($this->getTotpAuthUrl());
    }

    public function getTotpAuthUrl(): string
    {
        $this->totp->setIssuer('icingaweb2');
        $this->totp->setLabel($this->user);

        return $this->totp->getProvisioningUri();
    }

    /**
     * Saves the 2FA TOTP secret to the database.
     *
     * This method stores the 2FA TOTP secret associated with the user in the database.
     *
     * @throws ConfigurationError If the database operation fails
     */
    public function saveToDb(): void
    {
        $db = $this->getDb();
        $db->beginTransaction();

        try {
            $db->prepexec(
                (new Insert())
                    ->into('icingaweb_2fa')
                    ->values([
                        'username' => $this->user,
                        'secret'   => $this->getSecret(),
                        'ctime'    => (int) (new DateTime())->format("Uv"),
                    ])
            );
            $db->commitTransaction();
        } catch (Throwable $e) {
            $db->rollBackTransaction();
            throw new ConfigurationError(
                sprintf('Failed to save 2FA TOTP secret for user %s: %s', $this->user, $e->getMessage()),
            );
        }
    }

    /**
     * Removes the 2FA TOTP secret from the database.
     *
     * This method removes the 2FA TOTP secret associated with the user from the database.
     *
     * @throws ConfigurationError If the database operation fails
     */
    public function removeFromDb(): void
    {
        $db = $this->getDb();
        $db->beginTransaction();

        try {
            $db->prepexec(
                (new Delete())
                    ->from('icingaweb_2fa')
                    ->where(['username = ?' => $this->user])
            );
            $db->commitTransaction();
        } catch (Throwable $e) {
            $db->rollBackTransaction();
            throw new ConfigurationError(
                sprintf('Failed to remove 2FA TOTP secret for user %s: %s', $this->user, $e->getMessage()),
            );
        }
    }
}
