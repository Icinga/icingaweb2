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

    const STATE_SECRET_CHECK_REQUIRED = 'secret_check_required';
    const STATE_HAS_PENDING_CHANGES = 'has_pending_changes';
    const STATE_APPROVED_TEMPORARY_SECRET = 'approve_temporary_secret';


    protected string $username;
    protected PsrClock $clock;
    protected ?extTOTP $totpObject = null;
    protected ?extTOTP $temporaryTotpObject;
    protected array $currentStates = [];
    private ?string $secret = null;
    private ?string $temporarySecret = null;


    public function __construct(string $username, ?string $secret = null)
    {
        $this->username = $username;
        $this->clock = new PsrClock();
        $this->temporarySecret = $secret;
        $this->setTotpObjects();
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

    public function hasPendingChanges(): bool
    {
        return in_array(self::STATE_HAS_PENDING_CHANGES, $this->currentStates, true);
    }

    public function requiresSecretCheck(): bool
    {
        return in_array(self::STATE_SECRET_CHECK_REQUIRED, $this->currentStates, true);
    }


    /**
     * Verifies the provided TOTP code against the user's secret.
     *
     * @param string $code The TOTP code to verify
     * @return bool Returns true if the code is valid, false otherwise
     */
    public function verify(string $code): bool
    {
        if ($this->totpObject === null) {

            return false;
        }

        return $this->totpObject->verify($code);
    }

    public function approveTemporarySecret(string $code): bool
    {
        if ($this->temporarySecret !== null & $this->temporaryTotpObject->verify($code)) {
            $this->setState(self::STATE_APPROVED_TEMPORARY_SECRET);
            $this->removeState(self::STATE_SECRET_CHECK_REQUIRED);

            return true;
        }

        return false;
    }

    public function generateSecret(): self
    {
        $this->setTotpObjects(true)
            ->setState(self::STATE_SECRET_CHECK_REQUIRED)
            ->setState(self::STATE_HAS_PENDING_CHANGES)
        ->removeState(self::STATE_APPROVED_TEMPORARY_SECRET);

        return $this;
    }

    public function deleteSecrets(): self
    {
        if ($this->secret !== null || $this->totpObject !== null
            || $this->temporarySecret !== null || $this->temporaryTotpObject !== null) {
            $this->secret = null;
            $this->totpObject = null;
            $this->temporarySecret = null;
            $this->temporaryTotpObject = null;
            $this->setState(self::STATE_HAS_PENDING_CHANGES)
                ->removeState(self::STATE_SECRET_CHECK_REQUIRED)
                ->removeState(self::STATE_APPROVED_TEMPORARY_SECRET);
        }

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

    public function makeChangesPermanent(): self
    {
        $db = $this->getWebDb();
        $db->beginTransaction();

        $dbEntry = $db->prepexec(
            (new Select())
                ->columns(['*'])
                ->from(self::TABLE_NAME)
                ->where([self::COLUMN_USERNAME . ' = ?' => $this->username])
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
                $this->makeTemporaryObjectPermanent();
                $db->commitTransaction();
            } elseif ($this->secret === null && $dbEntry && $dbEntry->secret !== null) {
                $db->prepexec(
                    (new Delete())
                        ->from(self::TABLE_NAME)
                        ->where([self::COLUMN_USERNAME . ' = ?' => $this->username])
                );
                $db->commitTransaction();
            }

            $this->purgeStates();
            $this->saveTemporaryInSession();
        } catch (\Exception $e) {
            $db->rollBackTransaction();
            throw new ConfigurationError(
                sprintf(
                    'Failed to persist TOTP state for user %s: %s',
                    $this->username,
                    $e->getMessage()
                ), 0, $e
            );
        }


        return $this;
    }

    public function getCurrentCode(): string
    {
        return $this->totpObject->now();
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

    public function getSecretToDisplay(): ?string
    {
        return $this->temporarySecret ?? $this->secret;
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
            throw new ConfigurationError(
                sprintf(
                    'Expected TotpModel, got %s',
                    get_class($totp)
                )
            );
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
    private function setTotpObjects(bool $new = false): self
    {
        if (!$new) {
            if ($this->secret === null && ($totpModel = $this->getTotpModel()) !== null) {
                $this->secret = $totpModel->secret;
            }

            if (isset($this->totpObject)) {
                return $this;
            }
            $this->temporaryTotpObject = $this->temporarySecret !== null
                ? extTOTP::createFromSecret($this->temporarySecret, $this->clock)
                : null;

            $this->totpObject = $this->secret !== null
                ? extTOTP::createFromSecret($this->secret, $this->clock)
                : null;
        } else {
            $this->temporaryTotpObject = extTOTP::generate($this->clock);
            $this->temporarySecret = $this->temporaryTotpObject->getSecret();
        }

        return $this;
    }

    private function makeTemporaryObjectPermanent(): self
    {
        if ($this->temporaryTotpObject !== null) {
            $this->totpObject = $this->temporaryTotpObject;
            $this->secret = $this->totpObject->getSecret();
            $this->temporarySecret = null;
            $this->temporaryTotpObject = null;
        }

        return $this;
    }

    private function setState(string $key): self
    {
        if (! in_array($key, $this->currentStates, true)) {
            $this->currentStates[] = $key;
        }

        return $this;
    }

    private function removeState(string $key): self
    {
        $this->currentStates = array_filter(
            $this->currentStates,
            function ($state) use ($key) {
                return $state !== $key;
            }
        );

        return $this;
    }

    private function purgeStates(): self
    {
        $this->currentStates = [];

        return $this;
    }

}
