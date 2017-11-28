<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Redis;

use Exception;
use Icinga\Data\ConfigObject;
use Icinga\Data\Inspectable;
use Icinga\Data\Inspection;
use Icinga\Exception\ConfigurationError;
use Redis;
use RedisException;

/**
 * Encapsulate Redis connections
 */
class RedisConnection implements Inspectable
{
    /**
     * Cached connections
     *
     * Rationale:
     *
     * "This feature is not available in threaded versions. pconnect and popen then working like their non persistent
     * equivalents." – {@link Redis::pconnect()}
     *
     * @var Redis[]
     */
    protected static $persistentClients = array();

    /**
     * The resource configuration
     *
     * @var ConfigObject
     */
    protected $resourceConfig;

    /**
     * This object does the actual work
     *
     * @var Redis
     */
    protected $client;

    /**
     * Constructor
     *
     * @param   ConfigObject    $resourceConfig     The resource configuration
     *
     * @throws  ConfigurationError
     */
    public function __construct(ConfigObject $resourceConfig)
    {
        $this->resourceConfig = clone $resourceConfig;

        if (! isset($this->resourceConfig->host)) {
            throw new ConfigurationError('Host missing');
        }

        foreach ($this->resourceConfig as $key => $value) {
            if ($value === '') {
                unset($this->resourceConfig->$key);
            }
        }
    }

    public function inspect()
    {
        $insp = new Inspection('Redis Connection');

        try {
            $this->bootstrap();
        } catch (Exception $e) {
            return $insp->error(sprintf('Connection failed: %s', $e->getMessage()));
        }

        if (isset($this->resourceConfig->dbindex)) {
            $insp->write(sprintf(
                isset($this->resourceConfig->password)
                    ? 'Authenticated connection to database #%d on %s successful'
                    : 'Connection to database #%d on %s successful',
                (int) $this->resourceConfig->dbindex,
                $this->getHumanReadableEndpoint()
            ));
        } else {
            $insp->write(sprintf(
                isset($this->resourceConfig->password)
                    ? 'Authenticated connection to %s successful'
                    : 'Connection to %s successful',
                $this->getHumanReadableEndpoint()
            ));
        }

        try {
            /** @var string[] $infoServer */
            $infoServer = $this->client->info('SERVER');
        } catch (Exception $e) {
            if ($this->resourceConfig->get('persistent', false)) {
                unset(static::$persistentClients[$this->getPersistentClientId()]);
            }

            return $insp->error(sprintf('INFO SERVER failed: %s', $e->getMessage()));
        }

        $infoInsp = new Inspection('Redis Server Info');
        foreach ($infoServer as $key => $value) {
            $infoInsp->write( "$key: $value");
        }

        $insp->write($infoInsp);

        return $insp;
    }

    public function __call($name, $arguments)
    {
        $this->bootstrap();

        try {
            return call_user_func_array(array($this->client, $name), $arguments);
        } catch (Exception $e) {
            if ($this->resourceConfig->get('persistent', false)) {
                unset(static::$persistentClients[$this->getPersistentClientId()]);
            }

            throw $e;
        }
    }

    /**
     * Bootstraps the Redis connection if not already done
     */
    protected function bootstrap()
    {
        if ($this->client === null) {
            if ($this->resourceConfig->get('persistent', false)) {
                $id = $this->getPersistentClientId();

                if (isset(static::$persistentClients[$id])) {
                    $this->client = static::$persistentClients[$id];
                } else {
                    static::$persistentClients[$id] = $this->client = $this->bootstrapNew();
                }
            } else {
                $this->client = $this->bootstrapNew();
            }
        }
    }

    /**
     * Get the ID of this persistent connection for {@link persistentClients}
     *
     * @return string
     */
    protected function getPersistentClientId()
    {
        $config = $this->resourceConfig->toArray();
        unset($config['name']);
        unset($config['persistent']);
        ksort($config);
        return serialize($config);
    }

    /**
     * Bootstraps a new Redis connection
     *
     * @return Redis
     *
     * @throws RedisException
     */
    protected function bootstrapNew()
    {
        $client = new Redis();

        if (! $client->connect(
            $this->resourceConfig->host,
            isset($this->resourceConfig->port) ? (int) $this->resourceConfig->port : 6379
        )) {
            throw new RedisException('Couldn\'t connect to ' . $this->getHumanReadableEndpoint());
        }

        if (isset($this->resourceConfig->password) && ! $client->auth($this->resourceConfig->password)) {
            throw new RedisException('Couldn\'t authenticate against ' . $this->getHumanReadableEndpoint());
        }

        if (isset($this->resourceConfig->dbindex) && ! $client->select((int) $this->resourceConfig->dbindex)) {
            throw new RedisException(sprintf(
                'Couldn\'t select database #%d on %s',
                $this->resourceConfig->dbindex,
                $this->getHumanReadableEndpoint()
            ));
        }

        return $client;
    }

    /**
     * Get the configured remote endpoint for exception messages
     *
     * @return string
     */
    protected function getHumanReadableEndpoint()
    {
        return isset($this->resourceConfig->port)
            ? var_export($this->resourceConfig->host, true) . ':' . (int) $this->resourceConfig->port
            : var_export($this->resourceConfig->host, true);
    }
}
