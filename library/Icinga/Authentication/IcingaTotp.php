<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Authentication;

use chillerlan\QRCode\QRCode;
use Icinga\Common\Database;
use Icinga\Exception\ConfigurationError;
use Icinga\Model\TotpModel;
use ipl\Sql\Connection;
use ipl\Sql\Delete;
use ipl\Sql\Insert;
use ipl\Stdlib\Filter;
use OTPHP\TOTP;
use Throwable;

class IcingaTotp
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
        $totp = new static($user);
        $totp->setTotp(TOTP::generate(new PsrClock()));

        return $totp;
    }

    public static function createFromSecret(string $secret, string $user): static
    {
        $totp = new static($user);
        $totp->setTotp(TOTP::createFromSecret($secret, new PsrClock()));

        return $totp;
    }

    public static function loadFromDb(Connection $db, string $user): ?static
    {
        $totpQuery = TotpModel::on($db)->filter(Filter::equal('username', $user));
        $dbTotp = $totpQuery->first();
        if ($dbTotp === null) {
            return null;
        }

        return self::createFromSecret($dbTotp->secret, $user);
    }

    public static function hasDbSecret(Connection $db, string $user): bool
    {
        $totpQuery = TotpModel::on($db)->filter(Filter::equal('username', $user));

        return $totpQuery->first() !== null;
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
     * Creates a QR code for the TOTP secret.
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
     * Saves the TOTP secret to the database.
     *
     * This method stores the TOTP secret associated with the user in the database.
     *
     * @throws ConfigurationError If the database operation fails
     */
    public function saveToDb(): void
    {
        try {
            $this->getDb()->prepexec(
                (new Insert())
                    ->into('icingaweb_totp')
                    ->values([
                        'username' => $this->user,
                        'secret'   => $this->getSecret(),
                        'ctime'    => date('Y-m-d H:i:s'),
                    ])
            );
        } catch (Throwable $e) {
            throw new ConfigurationError(
                sprintf('Failed to save TOTP secret for user %s: %s', $this->user, $e->getMessage()),
            );
        }
    }

    /**
     * Removes the TOTP secret from the database.
     *
     * This method deletes the TOTP secret associated with the user from the database.
     *
     * @throws ConfigurationError If the database operation fails
     */
    public function removeFromDb(): void
    {
        try {
            $this->getDb()->prepexec(
                (new Delete())
                    ->from('icingaweb_totp')
                    ->where(['username = ?' => $this->user])
            );
        } catch (Throwable $e) {
            throw new ConfigurationError(
                sprintf('Failed to remove TOTP secret for user %s: %s', $this->user, $e->getMessage()),
            );
        }
    }
}
