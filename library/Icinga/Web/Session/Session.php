<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
    public function write() {
        throw new NotImplementedError('You are required to implement write() in your session implementation');
    }

    /**
     * Purge session
     */
    abstract public function purge();

    /**
     * Assign a new session id to this session.
     */
    abstract public function refreshId();

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
            if (in_array($identifier, $this->removedNamespaces)) {
                unset($this->removedNamespaces[array_search($identifier, $this->removedNamespaces)]);
            }

            $this->namespaces[$identifier] = new SessionNamespace($this);
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
     * Clear all values and namespaces from the session cache
     */
    public function clear()
    {
        $this->values = array();
        $this->removed = array();
        $this->namespaces = array();
        $this->removedNamespaces = array();
    }
}
