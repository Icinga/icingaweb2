<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Legacy;

use Icinga\Application\Config;
use Icinga\User;

/**
 * Legacy dashboard config class for case insensitive interpretation of dashboard config files
 *
 * Before 2.2, the username part in dashboard config files was not lowered.
 *
 * @deprecated(el): Remove. TBD.
 */
class DashboardConfig extends Config
{
    /**
     * User
     *
     * @var User
     */
    protected $user;

    /**
     * Get the user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user
     *
     * @param   User    $user
     *
     * @return  $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }


    /**
     * List all dashboard configuration files that match the given user
     *
     * @param   User    $user
     *
     * @return  string[]
     */
    public static function listConfigFilesForUser(User $user)
    {
        $files = array();
        $dashboards = static::resolvePath('dashboards');
        if ($handle = @opendir($dashboards)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry[0] === '.' || ! is_dir($dashboards . '/' . $entry)) {
                    continue;
                }
                if (strtolower($entry) === strtolower($user->getUsername())) {
                    $files[] = $dashboards . '/' . $entry . '/dashboard.ini';
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function saveIni($filePath = null, $fileMode = 0660)
    {
        parent::saveIni($filePath, $fileMode);
        if ($filePath === null) {
            $filePath = $this->configFile;
        }
        foreach (static::listConfigFilesForUser($this->user) as $file) {
            if ($file !== $filePath) {
                @unlink($file);
            }
        }
    }
}
