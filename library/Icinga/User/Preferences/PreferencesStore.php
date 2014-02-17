<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\User\Preferences;

use Zend_Config;
use Icinga\User;
use Icinga\Exception\ConfigurationError;
use Icinga\User\Preferences;

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
 * $store = new PreferencesStore(
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
 */
abstract class PreferencesStore
{
    /**
     * Store config
     *
     * @var Zend_Config
     */
    private $config;

    /**
     * Given user
     *
     * @var User
     */
    private $user;

    /**
     * Create a new store
     *
     * @param Zend_Config   $config
     * @param User          $user
     */
    public function __construct(Zend_Config $config, User $user)
    {
        $this->config = $config;
        $this->user = $user;
        $this->init();
    }

    /**
     * Initialize the sore
     */
    public function init()
    {

    }

    /**
     * Getter for the store config
     *
     * @return Zend_Config
     */
    final public function getStoreConfig()
    {
        return $this->config;
    }

    /**
     * Getter for the user
     *
     * @return User
     */
    final public function getUser()
    {
        return $this->user;
    }

    /**
     * Load preferences from source
     *
     * @return array
     */
    abstract public function load();

    /**
     * Save the given preferences
     *
     * @param Preferences $preferences
     */
    public function save(Preferences $preferences)
    {
        $storedPreferences = $this->load();
        $preferences = $preferences->toArray();
        $newPreferences = array_diff_key($preferences, $storedPreferences);
        $updatedPreferences = array_diff_assoc($preferences, $storedPreferences);
        $deletedPreferences = array_keys(array_diff_key($storedPreferences, $preferences));
        if (count($newPreferences) || count($updatedPreferences) || count($deletedPreferences)) {
            $this->cud($newPreferences, $updatedPreferences, $deletedPreferences);
        }
    }

    /**
     * Create, update and delete the given preferences
     *
     * @param   array $newPreferences       Key-value array of preferences to create
     * @param   array $updatedPreferences   Key-value array of preferences to update
     * @param   array $deletedPreferences   An array of preference names to delete
     */
    abstract public function cud($newPreferences, $updatedPreferences, $deletedPreferences);

    /**
     * Create preferences storage adapter from config
     *
     * @param  Zend_Config  $config
     * @param  User         $user
     *
     * @return self
     * @throws ConfigurationError When the configuration defines an invalid storage type
     */
    public static function create(Zend_Config $config, User $user)
    {
        if (($type = $config->type) === null) {
            throw new ConfigurationError(
                'Preferences configuration is missing the type directive'
            );
        }
        $type = ucfirst(strtolower($type));
        $storeClass = 'Icinga\\User\\Preferences\\Store\\' . $type . 'Store';
        if (!class_exists($storeClass)) {
            throw new ConfigurationError(
                'Preferences configuration defines an invalid storage type. Storage type ' . $type . ' not found'
            );
        }
        return new $storeClass($config, $user);
    }
}
