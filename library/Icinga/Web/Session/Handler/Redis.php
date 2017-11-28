<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Session\Handler;

use Exception;
use Icinga\Protocol\Redis\RedisConnection;
use SessionHandlerInterface;

/**
 * Stores PHP sessions in a Redis database
 */
class Redis implements SessionHandlerInterface
{
    /**
     * The backend to use
     *
     * @var \Redis
     */
    protected $connection;

    /**
     * Constructor
     *
     * @param   RedisConnection $connection     The backend to use
     */
    public function __construct(RedisConnection $connection)
    {
        $this->connection = $connection;
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($session_id)
    {
        try {
            $sessionData = $this->connection->hGet('{icingaweb2:sessions}:payload', $session_id);
        } catch (Exception $e) {
            return '';
        }

        return $sessionData === false ? '' : $sessionData;
    }

    public function write($session_id, $session_data)
    {
        $c = $this->connection;

        try {
            $c->multi();
            $c->hSet('{icingaweb2:sessions}:payload', $session_id, $session_data);
            $c->zAdd('{icingaweb2:sessions}:ctime', time(), $session_id);
            $c->exec();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function destroy($session_id)
    {
        $c = $this->connection;

        try {
            $c->multi();
            $c->hDel('{icingaweb2:sessions}:payload', $session_id);
            $c->zRem('{icingaweb2:sessions}:ctime', $session_id);
            $c->exec();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function gc($maxlifetime)
    {
        $c = $this->connection;

        try {
            for (;;) {
                $c->unwatch();
                $c->watch('{icingaweb2:sessions}:payload');
                $c->watch('{icingaweb2:sessions}:ctime');

                $sessionIds = $c->zRangeByScore('{icingaweb2:sessions}:ctime', -42, time() - $maxlifetime);

                if (! empty($sessionIds)) {
                    $c->multi();

                    foreach ($sessionIds as $sessionId) {
                        $c->hDel('{icingaweb2:sessions}:payload', $sessionId);
                        $c->zRem('{icingaweb2:sessions}:ctime', $sessionId);
                    }

                    if ($c->exec() === false) {
                        continue;
                    }
                }

                $c->unwatch();
                break;
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
