<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Session;

use Icinga\Exception\NotImplementedError;

/**
 * Base class for handling sessions
 */
abstract class Session extends SessionNamespace
{
    /**
     * Container for session namespaces
     *
     * @var array
     */
    protected $namespaces = array();

    /**
     * The identifiers of all namespaces removed from this session
     *
     * @var array
     */
    protected $removedNamespaces = array();

    /**
     * Read all values from the underlying session implementation
     */
    abstract public function read();

    /**
     * Persists changes to the underlying session implementation
     */
    public function write()
    {
        throw new NotImplementedError('You are required to implement write() in your session implementation');
    }

    /**
     * Return whether a session exists
     *
     * @return  bool
     */
    abstract public function exists();

    /**
     * Purge session
     */
    abstract public function purge();

    /**
     * Assign a new session id to this session.
     */
    abstract public function refreshId();

    /**
     * Return the id of this session
     *
     * @return  string
     */
    abstract public function getId();

    /**
     * Get or create a new session namespace
     *
     * @param   string      $identifier     The namespace's identifier
     *
     * @return  SessionNamespace
     */
    public function getNamespace($identifier)
    {
        if (!isset($this->namespaces[$identifier])) {
            if (in_array($identifier, $this->removedNamespaces, true)) {
                unset($this->removedNamespaces[array_search($identifier, $this->removedNamespaces, true)]);
            }

            $this->namespaces[$identifier] = new SessionNamespace();
        }

        return $this->namespaces[$identifier];
    }

    /**
     * Return whether the given session namespace exists
     *
     * @param   string      $identifier     The namespace's identifier to check
     *
     * @return  bool
     */
    public function hasNamespace($identifier)
    {
        return isset($this->namespaces[$identifier]);
    }

    /**
     * Remove the given session namespace
     *
     * @param   string      $identifier     The identifier of the namespace to remove
     */
    public function removeNamespace($identifier)
    {
        unset($this->namespaces[$identifier]);
        $this->removedNamespaces[] = $identifier;
    }

    /**
     * Return whether the session has changed
     *
     * @return  bool
     */
    public function hasChanged()
    {
        return parent::hasChanged() || false === empty($this->namespaces) || false === empty($this->removedNamespaces);
    }

    /**
     * Clear all values and namespaces from the session cache
     */
    public function clear()
    {
        parent::clear();
        $this->namespaces = array();
        $this->removedNamespaces = array();
    }
}
