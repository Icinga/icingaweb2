<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\User\Preferences\Store;

use Zend_Config;
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
 * // Create the store from the factory (preferred approach)
 * $store = new PreferencesStore(
 *     new Zend_Config(
 *         'type'       => 'ini',
 *         'configPath' => '/path/to/preferences'
 *     ),
 *     $user // Instance of \Icinga\User
 * );
 *
 * // Create the store directly
 * $store = new IniStore(
 *     new Zend_Config(
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
     * @var array
     */
    private $preferences;

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
                $this->preferences = parse_ini_file($this->preferencesFile);
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
        return $this->preferences !== null ? $this->preferences : array();
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
        if ($this->preferences === null) {
            // Preferences INI file does not yet exist
            if (!is_writable($this->getStoreConfig()->configPath)) {
                throw new NotWritableError('Path to the preferences INI files ' . $this->getStoreConfig()->configPath
                    . ' is not writable');
            }
            File::create($this->preferencesFile);
            $this->preferences = array();
        }
        if (!is_writable($this->preferencesFile)) {
            throw new NotWritableError('Preferences INI file ' . $this->preferencesFile . ' for user '
                . $this->getUser()->getUsername() . ' is not writable');
        }
        foreach ($newPreferences as $name => $value) {
            $this->preferences[$name] = $value;
        }
        foreach ($updatedPreferences as $name => $value) {
            $this->preferences[$name] = $value;
        }
        foreach ($deletedPreferences as $name) {
            unset($this->preferences[$name]);
        }
        if ($this->writer === null) {
            $this->writer = new PreservingIniWriter(
                array('config' => new Zend_Config($this->preferences), 'filename' => $this->preferencesFile)
            );
        }
        $this->writer->write();
    }
}
