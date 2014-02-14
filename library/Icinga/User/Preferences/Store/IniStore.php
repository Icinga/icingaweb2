<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\User\Preferences\Store;

use Zend_Config_Ini;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Config\PreservingIniWriter;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use Icinga\User;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\File;

/**
 * Load and save user preferences from and to INI files
 *
 * Usage example:
 * <code>
 * <?php
 *
 * use Zend_Config;
 * use Icinga\User\Preferences;
 * use Icinga\User\Preferences\PreferencesStore;
 * use Icinga\User\Preferences\Store\IniStore;
 *
 * // Create the store from the factory (prefered approach)
 * $store = new PreferencesStore(
 *     new Zend_Config(
 *         'type'       => 'ini',
 *         'configPath' => '/path/to/preferences'
 *     ),
 *     $user // Instance of Icinga\User
 * );
 *
 * // Create the store directly
 * $store = new IniStore(
 *     new Zend_Config(
 *         'configPath' => '/path/to/preferences'
 *     ),
 *     $user // Instance of Icinga\User
 * );
 *
 * $preferences = new Preferences($store->load());
 * $prefereces->aPreference = 'value';
 * $store->save($preferences);
 * </code>
 */
class IniStore extends PreferencesStore
{
    /**
     * Preferences file of the given user
     *
     * @var string
     */
    private $preferencesFile;

    /**
     * Stored preferences
     *
     * @var Zend_Config_Ini
     */
    private $config;

    /**
     * Writer which stores the preferences
     *
     * @var PreservingIniWriter
     */
    private $writer;

    /**
     * Initialize the store
     *
     * @throws NotReadableError When the preferences INI file of the given user is not readable
     */
    public function init()
    {
        $this->preferencesFile = sprintf(
            '%s/%s.ini',
            IcingaConfig::resolvePath($this->getStoreConfig()->configPath),
            $this->getUser()->getUsername()
        );
        if (file_exists($this->preferencesFile)) {
            if (!is_readable($this->preferencesFile)) {
                throw new NotReadableError('Preferences INI file ' . $this->preferencesFile . ' for user '
                    . $this->getUser()->getUsername() . ' is not readable');
            } else {
                $this->config = new Zend_Config_Ini($this->preferencesFile);
            }
        }
    }

    /**
     * Load preferences from source
     *
     * @return array
     */
    public function load()
    {
        return $this->config !== null ? $this->config->toArray() : array();
    }

    /**
     * Create, update and delete the given preferences
     *
     * @param   array $newPreferences       Key-value array of preferences to create
     * @param   array $updatedPreferences   Key-value array of preferences to update
     * @param   array $deletedPreferences   An array of preference names to delete
     *
     * @throws  NotWritableError            When either the path to the preferences INI files is not writable or the
     *                                      preferences INI file for the given user is not writable
     */
    public function cud($newPreferences, $updatedPreferences, $deletedPreferences)
    {
        if ($this->config === null) {
            // Preferences INI file does not yet exist
            if (!is_writable($this->getStoreConfig()->configPath)) {
                throw new NotWritableError('Path to the preferences INI files ' . $this->getStoreConfig()->configPath
                    . ' is not writable');
            }
            File::create($this->preferencesFile);
            $this->config = new Zend_Config_Ini($this->preferencesFile);
        }
        foreach ($newPreferences as $name => $value) {
            $this->config->{$name} = $value;
        }
        foreach ($updatedPreferences as $name => $value) {
            $this->config->{$name} = $value;
        }
        foreach ($deletedPreferences as $name) {
            unset($this->config->{$name});
        }
        if ($this->writer === null) {
            $this->writer = new PreservingIniWriter(
                array('config' => $this->config)
            );
        }
        if (!is_writable($this->preferencesFile)) {
            throw new NotWritableError('Preferences INI file ' . $this->preferencesFile . ' for user '
                . $this->getUser()->getUsername() . ' is not writable');
        }
        $this->writer->write();
    }
}
