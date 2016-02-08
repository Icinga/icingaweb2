<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\User\Preferences\Store;

use Icinga\Application\Config;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\File\Ini\IniParser;

/**
 * Load and save user preferences from and to INI files
 */
class IniStore extends PreferencesStore
{
    /**
     * Preferences file of the given user
     *
     * @var string
     */
    protected $preferencesFile;

    /**
     * Stored preferences
     *
     * @var array
     */
    protected $preferences = array();

    /**
     * Initialize the store
     */
    protected function init()
    {
        $this->preferencesFile = sprintf(
            '%s/%s/config.ini',
            $this->getStoreConfig()->location,
            strtolower($this->getUser()->getUsername())
        );
    }

    /**
     * Load preferences from source
     *
     * @return  array
     *
     * @throws  NotReadableError    When the INI file of the user exists and is not readable
     */
    public function load()
    {
        if (file_exists($this->preferencesFile)) {
            if (! is_readable($this->preferencesFile)) {
                throw new NotReadableError(
                    'Preferences INI file %s for user %s is not readable',
                    $this->preferencesFile,
                    $this->getUser()->getUsername()
                );
            } else {
                $this->preferences = IniParser::parseIniFile($this->preferencesFile)->toArray();
            }
        }

        return $this->preferences;
    }

    /**
     * Save the given preferences
     *
     * @param   Preferences     $preferences    The preferences to save
     */
    public function save(Preferences $preferences)
    {
        $this->preferences = $preferences->toArray();

        // TODO: Elaborate whether we need to patch the contents
        // $preferences = $preferences->toArray();
        // $this->update(array_diff_assoc($preferences, $this->preferences));
        // $this->delete(array_keys(array_diff_key($this->preferences, $preferences)));

        $this->write();
    }

    /**
     * Write the preferences
     *
     * @throws  NotWritableError    In case the INI file cannot be written
     */
    public function write()
    {
        Config::fromArray($this->preferences)->saveIni($this->preferencesFile);
    }

    /**
     * Add or update the given preferences
     *
     * @param   array   $preferences    The preferences to set
     */
    protected function update(array $preferences)
    {
        foreach ($preferences as $key => $value) {
            $this->preferences[$key] = $value;
        }
    }

    /**
     * Delete the given preferences by name
     *
     * @param   array   $preferenceKeys     The preference names to delete
     */
    protected function delete(array $preferenceKeys)
    {
        foreach ($preferenceKeys as $key) {
            unset($this->preferences[$key]);
        }
    }
}
