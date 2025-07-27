<?php

namespace Icinga\Authentication;

use Icinga\Clock\PsrClock;
use Icinga\Common\Database;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Session;
use ipl\Orm\Model;
use ipl\Orm\Query;
use Icinga\Model\Totp as TotpModel;
use ipl\Sql\Delete;
use ipl\Sql\Insert;
use ipl\Sql\Select;
use ipl\Sql\Update;
use ipl\Stdlib\Filter;
use OTPHP\TOTP as extTOTP;
use Zend_Db_Expr;

class Totp
{
    use Database {
        getDb as private getWebDb;
    }

    /**
     * Table name for TOTP records
     */
    const TABLE_NAME = 'icingaweb_totp';
    /**
     * Column name for secret
     */
    const COLUMN_USERNAME = 'username';
    /**
     * Column name for secret
     */
    const COLUMN_SECRET = 'secret';

    /**
     * Column name for created time
     */
    const COLUMN_CREATED_TIME = 'ctime';

    /**
     * Column name for modified time
     */
    const COLUMN_MODIFIED_TIME = 'mtime';

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
        $newSecret = $this->totpObject->getSecret();
        if ($newSecret !== $this->secret && $newSecret !== $this->temporarySecret) {
            $this->temporarySecret = $this->totpObject->getSecret();
        }

        return $this;
    }

    public function renewSecret(): self
    {
        if (!($this->secret === null && $this->temporarySecret === null)) {
            if (
                ($currentSecret = $this->totpObject->getSecret())
                && $currentSecret !== $this->temporarySecret
                && $currentSecret !== $this->secret
            ) {
                $this->temporarySecret = $this->totpObject->getSecret();
            } else {
                $this->setTotpObject(true)->generateSecret();
            }
        }

        return $this;
    }

    public function deleteSecret(): self
    {
        $this->secret = null;
        $this->temporarySecret = null;

        return $this;
    }

    public function saveTemporaryInSession(): self
    {
        Session::getSession()->set(
            'icingaweb_totp',
            $this
        );

        return $this;
    }
    public function makeStatePersistent(): self
    {
        $db = $this->getWebDb();
        $db->beginTransaction();

        $dbEntry = $db->prepexec(
            (new Select())
                ->columns(['*'])
                ->from('icingaweb_totp')
                ->where(['username = ?' => $this->username])
        )->getIterator()->current();

        try {
            if ($this->temporarySecret !== null) {
                if (!$dbEntry) {
                    $db->prepexec(
                        (new Insert())
                            ->into(self::TABLE_NAME)
                            ->values(
                                [
                                    self::COLUMN_USERNAME => $this->username,
                                    self::COLUMN_SECRET => $this->temporarySecret,
                                    self::COLUMN_CREATED_TIME => date('Y-m-d H:i:s'),
                                    self::COLUMN_MODIFIED_TIME => date('Y-m-d H:i:s'),
                                ]
                            )
                    );
                } else {
                    $db->prepexec(
                        (new Update())
                            ->table(self::TABLE_NAME)
                            ->set([
                                self::COLUMN_SECRET => $this->temporarySecret,
                                self::COLUMN_MODIFIED_TIME => date('Y-m-d H:i:s'),
                            ])
                            ->where([self::COLUMN_USERNAME . ' = ?' => $this->username])
                    );
                }
                $this->secret = $this->temporarySecret;
                $this->temporarySecret = null;
            } elseif ($this->secret === null && $dbEntry->secret !== null) {
                $db->prepexec(
                    (new Delete())
                        ->from(self::TABLE_NAME)
                        ->where([self::COLUMN_USERNAME . ' = ?' => $this->username])
                );
                $this->setTotpObject(true);
            }

            $db->commitTransaction();
        } catch (\Exception $e) {
            $db->rollBackTransaction();
            throw new ConfigurationError(sprintf(
                'Failed to persist TOTP state for user %s: %s',
                $this->username,
                $e->getMessage()
            ), 0, $e);
        }


        return $this;
    }

    /**
     * Retrieves the TOTP secret for the current user.
     *
     * @return string|null The TOTP secret or null if not found
     */


    public function getCurrentCode(): string
    {
        return $this->totpObject->now();
    }
    public function getSecret(): ?string
    {

        return $this->temporarySecret ?? $this->secret;
    }

    public function getTemporarySecret(): ?string
    {
        return $this->temporarySecret;
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
     * Sets the TOTP object based on the user's secret.
     * If the secret is not set, a new TOTP object is generated.
     *
     * @param bool $new If true, a new TOTP object is created regardless of the existing secret
     * @return self Returns the current instance for method chaining
     */
    private function setTotpObject(bool $new = false): self
    {
        $totpModel = null;
        if ($this->secret === null && ($totpModel = $this->getTotpModel()) !== null) {
            $this->secret = $totpModel->secret;
        }

        if (!$new) {
            if (isset($this->totpObject)) {

                return $this;
            } elseif ($this->temporarySecret !== null) {
                $this->totpObject = extTOTP::createFromSecret($this->temporarySecret, $this->clock);

                return $this;
            } elseif ($totpModel !== null) {
                $this->totpObject = extTOTP::createFromSecret($this->secret, $this->clock);

                return $this;
            }
        }

        $this->totpObject = extTOTP::generate($this->clock);

        return $this;
    }
}
