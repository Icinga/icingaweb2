<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Web\Session;

class Window
{
    const UNDEFINED = 'undefined';

    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function isUndefined()
    {
        return $this->id === self::UNDEFINED;
    }

    public function getId()
    {
        return $this->id;
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

        $identifier = $prefix . '_' . $this->id;
        if ($reset && $session->hasNamespace($identifier)) {
            $session->removeNamespace($identifier);
        }
        $namespace = $session->getNamespace($identifier);
        $nsUndef = $prefix . '_' . self::UNDEFINED;

        if (!$reset && $this->id !== self::UNDEFINED && $session->hasNamespace($nsUndef)) {
            // We do not have any window-id on the very first request. Now we add
            // all values from the namespace, that has been created in this case,
            // to the new one and remove it afterwards.
            foreach ($session->getNamespace($nsUndef) as $name => $value) {
                $namespace->set($name, $value);
            }
            $session->removeNamespace($nsUndef);
        }

        return $namespace;
    }

    public static function generateId()
    {
        $letters = 'abcefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($letters), 0, 12);
    }
}
