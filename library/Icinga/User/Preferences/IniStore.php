<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\User\Preferences;

use \SplObserver;
use \SplSubject;
use Icinga\User;
use Icinga\User\Preferences;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use \Zend_Config;
use \Zend_Config_Writer_Ini;

/**
 * Handle preferences in ini files
 *
 * Load and write values from user preferences to ini files
 */
class IniStore implements LoadInterface, FlushObserverInterface
{
    /**
     * Name of preferences section in ini file
     */
    const DEFAULT_SECTION = 'preferences';

    /**
     * Path to ini configuration
     *
     * @var string
     */
    private $configDir;

    /**
     * Specific user file for preferences
     *
     * @var string
     */
    private $preferencesFile;

    /**
     * Config container
     *
     * @var Zend_Config
     */
    private $iniConfig;

    /**
     * Ini writer
     *
     * @var Zend_Config_Writer_Ini
     */
    private $iniWriter;

    /**
     * Current user
     *
     * @var User
     */
    private $user;

    /**
     * Create a new object
     *
     * @param string $configDir
     */
    public function __construct($configDir)
    {
        $this->setConfigDir($configDir);
    }

    /**
     * Setter for config directory
     *
     * @param  string $configDir
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function setConfigDir($configDir)
    {
        if (!is_dir($configDir)) {
            throw new ConfigurationError('Config dir dos not exist: '. $configDir);
        }

        $this->configDir = $configDir;
    }

    /**
     * Setter for user
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        $this->preferencesFile = sprintf(
            '%s/%s.ini',
            $this->configDir,
            $this->user->getUsername()
        );

        if (file_exists($this->preferencesFile) === false) {
            $this->createDefaultIniFile();
        }

        $this->iniConfig = new Zend_Config(
            parse_ini_file($this->preferencesFile),
            true
        );

        $this->iniWriter = new Zend_Config_Writer_Ini(
            array(
                'config'   => $this->iniConfig,
                'filename' => $this->preferencesFile
            )
        );
    }

    /**
     * Helper to create blank ini file
     */
    private function createDefaultIniFile()
    {
        $writer = new Zend_Config_Writer_Ini(
            array(
                'config'   => new Zend_Config(
                    array(
                        self::DEFAULT_SECTION => array()
                    )
                ),
                'filename' => $this->preferencesFile
            )
        );
        $writer->write();
    }

    /**
     * Load preferences from source
     *
     * @return array
     */
    public function load()
    {
        return $this->iniConfig->toArray();
    }

    /**
     * Receive update from subject
     *
     * @link http://php.net/manual/en/splobserver.update.php
     * @param  SplSubject $subject
     * @throws ProgrammingError
     */
    public function update(SplSubject $subject)
    {
        if (!$subject instanceof Preferences) {
            throw new ProgrammingError('Not compatible with '. get_class($subject));
        }

        $changeSet = $subject->getChangeSet();

        foreach ($changeSet->getCreate() as $key => $value) {
            $this->iniConfig->{$key} = $value;
        }

        foreach ($changeSet->getUpdate() as $key => $value) {
            $this->iniConfig->{$key} = $value;
        }

        foreach ($changeSet->getDelete() as $key) {
            unset($this->iniConfig->{$key});
        }

        // Persist changes to disk
        $this->iniWriter->write();
    }
}
