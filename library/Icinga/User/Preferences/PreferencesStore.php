<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\User\Preferences;

use Icinga\Application\Config;
use Icinga\User;
use Icinga\User\Preferences;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Data\Db\DbConnection;

/**
 * Preferences store factory
 *
 * Usage example:
 * <code>
 * <?php
 *
 * use Icinga\Data\ConfigObject;
 * use Icinga\User\Preferences;
 * use Icinga\User\Preferences\PreferencesStore;
 *
 * // Create a INI store
 * $store = PreferencesStore::create(
 *     new ConfigObject(
 *         'store'       => 'ini',
 *         'config_path' => '/path/to/preferences'
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
     * @var ConfigObject
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
     * @param   ConfigObject    $config     The config for this adapter
     * @param   User            $user       The user to which these preferences belong
     */
    public function __construct(ConfigObject $config, User $user)
    {
        $this->config = $config;
        $this->user = $user;
        $this->init();
    }

    /**
     * Getter for the store config
     *
     * @return  ConfigObject
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
     * @param   ConfigObject    $config     The config for the adapter
     * @param   User            $user       The user to which these preferences belong
     *
     * @return  self
     *
     * @throws  ConfigurationError          When the configuration defines an invalid storage type
     */
    public static function create(ConfigObject $config, User $user)
    {
        $type = ucfirst(strtolower($config->get('store', 'ini')));
        $storeClass = 'Icinga\\User\\Preferences\\Store\\' . $type . 'Store';
        if (!class_exists($storeClass)) {
            throw new ConfigurationError(
                'Preferences configuration defines an invalid storage type. Storage type %s not found',
                $type
            );
        }

        if ($type === 'Ini') {
            $config->location = Config::resolvePath('preferences');
        } elseif ($type === 'Db') {
            $config->connection = new DbConnection(ResourceFactory::getResourceConfig($config->resource));
        }

        return new $storeClass($config, $user);
    }
}
