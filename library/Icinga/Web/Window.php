<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Web\Session\SessionNamespace;

class Window
{
    const UNDEFINED = 'undefined';

    /** @var Window */
    protected static $window;

    /** @var string */
    protected $id;

    /** @var string */
    protected $containerId;

    public function __construct($id)
    {
        $parts = explode('_', $id, 2);
        if (isset($parts[1])) {
            $this->id = $parts[0];
            $this->containerId = $id;
        } else {
            $this->id = $id;
        }
    }

    /**
     * Get whether the window's ID is undefined
     *
     * @return bool
     */
    public function isUndefined()
    {
        return $this->id === self::UNDEFINED;
    }

    /**
     * Get the window's ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the container's ID
     *
     * @return string
     */
    public function getContainerId()
    {
        return $this->containerId ?: $this->id;
    }

    /**
     * Return a window-aware session by using the given prefix
     *
     * @param   string      $prefix     The prefix to use
     * @param   bool        $reset      Whether to reset any existing session-data
     *
     * @return  SessionNamespace
     */
    public function getSessionNamespace($prefix, $reset = false)
    {
        $session = Session::getSession();

        $identifier = $prefix . '_' . $this->getId();
        if ($reset && $session->hasNamespace($identifier)) {
            $session->removeNamespace($identifier);
        }

        $namespace = $session->getNamespace($identifier);
        $nsUndef = $prefix . '_' . self::UNDEFINED;

        if (! $reset && ! $this->isUndefined() && $session->hasNamespace($nsUndef)) {
            // We may not have any window-id on the very first request. Now we add
            // all values from the namespace, that has been created in this case,
            // to the new one and remove it afterwards.
            foreach ($session->getNamespace($nsUndef) as $name => $value) {
                $namespace->set($name, $value);
            }

            $session->removeNamespace($nsUndef);
        }

        return $namespace;
    }

    /**
     * Generate a random string
     *
     * @return string
     */
    public static function generateId()
    {
        $letters = 'abcefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($letters), 0, 12);
    }

    /**
     * @return Window
     */
    public static function getInstance()
    {
        if (! isset(static::$window)) {
            $id = Icinga::app()->getRequest()->getHeader('X-Icinga-WindowId');
            if (empty($id) || $id === static::UNDEFINED || ! preg_match('/^\w+$/', $id)) {
                Icinga::app()->getResponse()->setOverrideWindowId();
                $id = static::generateId();
            }

            static::$window = new Window($id);
        }

        return static::$window;
    }
}
