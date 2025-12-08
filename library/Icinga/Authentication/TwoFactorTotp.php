<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Authentication;

use DateTime;
use Endroid\QrCode\Builder\Builder;
use Icinga\Common\Database;
use Icinga\Exception\ConfigurationError;
use ipl\Sql\Connection;
use ipl\Sql\Delete;
use ipl\Sql\Insert;
use ipl\Sql\Select;
use OTPHP\TOTP;
use PDOException;
use Throwable;

/**
 * This TwoFactorTotp class provides methods to generate, store and retrieve 2FA TOTP secrets.
 * Additionally, it provides a method to verify TOTP tokens.
 */
class TwoFactorTotp
{
    use Database;

    /** @var TOTP The 2FA TOTP instance */
    protected TOTP $totp;

    /** @var string The user for whom the 2FA TOTP instance is generated */
    protected string $user;

    private function __construct(string $user)
    {
        $this->user = $user;
    }

    /**
     * Generate a new 2FA TOTP instance for the given user.
     *
     * @return $this
     */
    public static function generate(string $user): static
    {
        $twoFactor = new static($user);
        $twoFactor->totp = TOTP::generate(new PsrClock());

        return $twoFactor;
    }

    /**
     * Create a 2FA TOTP instance from a secret for the given user.
     *
     * @param string $secret The secret to use for the TOTP instance.
     * @param string $user   The user for whom the TOTP instance is generated.
     *
     * @return $this
     */
    public static function createFromSecret(string $secret, string $user): static
    {
        $twoFactor = new static($user);
        $twoFactor->totp = TOTP::createFromSecret($secret, new PsrClock());

        return $twoFactor;
    }

    /**
     * Load a 2FA TOTP instance from the database secret for the given user.
     *
     * @param Connection $db   The database connection to use.
     * @param string     $user The user for whom the TOTP instance is loaded.
     *
     * @return $this|null
     */
    public static function loadFromDb(Connection $db, string $user): ?static
    {
        $select = (new Select())
            ->from('icingaweb_2fa')
            ->columns('secret')
            ->where(['LOWER(username) = ?' => strtolower($user)]);

        $dbTwoFactor = $db->select($select)->fetch();

        if (! $dbTwoFactor) {
            return null;
        }

        return self::createFromSecret($dbTwoFactor->secret, $user);
    }

    /**
     * Check whether a 2FA TOTP secret exists for the given user in the database.
     *
     * @param Connection $db   The database connection to use.
     * @param string     $user The user to check for.
     *
     * @return bool
     */
    public static function hasDbSecret(Connection $db, string $user): bool
    {
        try {
            $select = (new Select())
                ->from('icingaweb_2fa')
                ->columns('username')
                ->where(['LOWER(username) = ?' => strtolower($user)]);

            return ! empty($db->select($select)->fetchAll());
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * Get the 2FA TOTP secret.
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->totp->getSecret();
    }

    /**
     * Verify a 2FA TOTP token.
     *
     * @param string $otp
     *
     * @return bool
     */
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
        return (new Builder(data: $this->getTotpAuthUrl()))->build()->getDataUri();
    }

    /**
     * Get the URL for the 2FA TOTP authenticator app.
     *
     * @return string
     */
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
        try {
            $this->getDb()->prepexec(
                (new Insert())
                    ->into('icingaweb_2fa')
                    ->values([
                        'username' => $this->user,
                        'secret'   => $this->getSecret(),
                        'ctime'    => (int) (new DateTime())->format("Uv"),
                    ])
            );
        } catch (Throwable $e) {
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
        try {
            $this->getDb()->prepexec(
                (new Delete())
                    ->from('icingaweb_2fa')
                    ->where(['LOWER(username) = ?' => strtolower($this->user)])
            );
        } catch (Throwable $e) {
            throw new ConfigurationError(
                sprintf('Failed to remove 2FA TOTP secret for user %s: %s', $this->user, $e->getMessage()),
            );
        }
    }
}
