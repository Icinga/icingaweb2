<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\User\Preferences;

use \Zend_Config;
use Icinga\User;
use Icinga\User\Preferences;
use Icinga\Exception\ConfigurationError;

/**
 * Preferences store factory
 *
 * Usage example:
 * <code>
 * <?php
 *
 * use Zend_Config;
 * use Icinga\User\Preferences;
 * use Icinga\User\Preferences\PreferencesStore;
 *
 * // Create a INI store
 * $store = PreferencesStore::create(
 *     new Zend_Config(
 *         'type'       => 'ini',
 *         'configPath' => '/path/to/preferences'
 *     ),
 *     $user // Instance of \Icinga\User
 * );
 *
 * $preferences = new Preferences($store->load());
 * $preferences->aPreference = 'value';
 * $store->save($preferences);
 * </code>
 */
abstract class PreferencesStore
{
    /**
     * Store config
     *
     * @var Zend_Config
     */
    protected $config;

    /**
     * Given user
     *
     * @var User
     */
    protected $user;

    /**
     * Create a new store
     *
     * @param   Zend_Config     $config     The config for this adapter
     * @param   User            $user       The user to which these preferences belong
     */
    public function __construct(Zend_Config $config, User $user)
    {
        $this->config = $config;
        $this->user = $user;
        $this->init();
    }

    /**
     * Getter for the store config
     *
     * @return  Zend_Config
     */
    public function getStoreConfig()
    {
        return $this->config;
    }

    /**
     * Getter for the user
     *
     * @return  User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Initialize the store
     */
    abstract protected function init();

    /**
     * Load preferences from source
     *
     * @return  array
     */
    abstract public function load();

    /**
     * Save the given preferences
     *
     * @param   Preferences     $preferences    The preferences to save
     */
    abstract public function save(Preferences $preferences);

    /**
     * Create preferences storage adapter from config
     *
     * @param   Zend_Config     $config     The config for the adapter
     * @param   User            $user       The user to which these preferences belong
     *
     * @return  self
     *
     * @throws  ConfigurationError          When the configuration defines an invalid storage type
     */
    public static function create(Zend_Config $config, User $user)
    {
        if (($type = $config->type) === null) {
            throw new ConfigurationError(
                'Preferences configuration is missing the type directive'
            );
        }

        $storeClass = 'Icinga\\User\\Preferences\\Store\\' . ucfirst(strtolower($type)) . 'Store';
        if (!class_exists($storeClass)) {
            throw new ConfigurationError(
                'Preferences configuration defines an invalid storage type. Storage type ' . $type . ' not found'
            );
        }

        return new $storeClass($config, $user);
    }
}
