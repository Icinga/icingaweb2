<?php

namespace Icinga\Authentication;

use Icinga\Clock\PsrClock;
use Icinga\Common\Database;
use Icinga\Exception\ConfigurationError;
use ipl\Orm\Model;
use ipl\Orm\Query;
use Icinga\Model\Totp as TotpModel;
use ipl\Sql\Insert;
use ipl\Sql\Update;
use ipl\Stdlib\Filter;
use OTPHP\TOTP as extTOTP;

class Totp
{
    use Database {
        getDb as private getWebDb;
    }

    protected string $username;
    protected PsrClock $clock;
    protected extTOTP $totpObject;
    private ?string $secret = null;
    private ?string $temporarySecret = null;


    public function __construct(string $username, ?string $secret = null)
    {
        $this->username = $username;
        $this->clock = new PsrClock();
        $this->temporarySecret = $secret;
        $this->setTotpObject();
    }


    /**
     * Checks if a TOTP secret exists for the current user.
     *
     * @return bool Returns true if a TOTP secret exists, false otherwise
     */
    public function userHasSecret(): bool
    {

        return $this->secret !== null;
    }


    /**
     * Verifies the provided TOTP code against the user's secret.
     *
     * @param string $code The TOTP code to verify
     * @return bool Returns true if the code is valid, false otherwise
     */
    public function verify(string $code): bool
    {
        if ($this->secret === null) {
            return false;
        }
        return $this->totpObject->verify($code);

    }

    public function generateSecret(): self
    {
        $this->temporarySecret = $this->totpObject->getSecret();

        return $this;
    }

    public function setSecretForUser(): self
    {
        if ($this->temporarySecret === null) {
            throw new ConfigurationError('No temporary secret set to apply to user');
        }

        if ($this->secret === null) {
            $this->getWebDb()->prepexec(
                (new Insert())->into('icingaweb_totp')->values(
                    [
                        'username' => $this->username,
                        'secret' => $this->temporarySecret,
                        'created_at' => $this->clock->now(),
                    ]
                )
            );
        } else {
            $this->getWebDb()->prepexec(
                (new Update())->table('icingaweb_totp')
                    ->set([
                        'secret' => $this->temporarySecret,
                        'updated_at' => $this->clock->now(),
                    ])
                    ->where(['username' => $this->username])
                );
        }
        $this->secret = $this->temporarySecret;
        return $this;
    }

    /**
     * Returns a query for the TOTP model.
     * This method is used to fetch TOTP records from the database.
     *
     * @return Query|null
     */
    private function getTotpQuery(): ?Query
    {
        try {
            $query = TotpModel::on($this->getWebDb());
        } catch (ConfigurationError $e) {
            $query = null;
        }

        return $query->count() > 0 ? $query : null;
    }

    /**
     * Fetches the TOTP model for the current user.
     * This method retrieves the TOTP record associated with the username.
     *
     * @return TotpModel|null
     */
    private function getTotpModel(): ?TotpModel
    {
        $query = $this->getTotpQuery();
        if ($query === null) {
            return null;
        }

        $totp = $query
            ->filter(Filter::equal('username', $this->username))
            ->first();
        if ($totp === null) {
            return null;
        }

        try {
            $totp = $this->ensureIsTotpModel($totp);
        } catch (ConfigurationError $e) {
            $totp = null;
        }

        return $totp;
    }

    /**
     * Ensures that the provided model is an instance of TotpModel.
     *
     * @param Model $totp The model to check
     * @throws ConfigurationError
     */
    private function ensureIsTotpModel(Model $totp): ?TotpModel
    {
        if (!$totp instanceof TotpModel) {
            throw new ConfigurationError(sprintf(
                'Expected TotpModel, got %s',
                get_class($totp)
            ));
        }

        return $totp;
    }

    /**
     * Retrieves the TOTP secret for the current user.
     *
     * @return string|null The TOTP secret or null if not found
     */
    public function getSecret(): ?string
    {

        return $this->secret;
    }

    public function getTemporarySecret(): ?string
    {
        return $this->temporarySecret;
    }


    /**
     * Sets the TOTP object based on the user's secret.
     * If the secret is not set, a new TOTP object is generated.
     */
    private function setTotpObject(bool $new = false): void
    {
        if (isset($this->totpObject)) {
            return;
        }

        if (!$new && ($totpModel = $this->getTotpModel()) !== null) {
            $this->secret = $totpModel->secret;
            $this->totpObject = extTOTP::createFromSecret($this->secret, $this->clock);
        } elseif (!$new && $this->temporarySecret !== null) {
            $this->totpObject = extTOTP::createFromSecret($this->temporarySecret, $this->clock);
        } else {
            $this->totpObject = extTOTP::generate($this->clock);
        }
    }

    public function getCurrentCode(): string
    {
        return $this->totpObject->now();
    }
}
