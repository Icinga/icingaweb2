<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Legacy;

use Icinga\Application\Config;
use Icinga\User;
use Icinga\Web\Navigation\DashboardPane;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Navigation\NavigationItem;

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
         if (empty($files)) {
            $roles = $user->getRoles();
            if (! empty($roles)) {
               foreach($roles as $role) {
                 if ($handle = @opendir($dashboards)) {
                     while (false !== ($entry = readdir($handle))) {
                         if ($entry[0] === '.' || ! is_dir($dashboards . '/' . $entry)) {
                             continue;
                         }
                         if (strtolower($entry) === strtolower($role->getName())) {
                             $files[] = $dashboards . '/' . $entry . '/dashboard.ini';
                         }
                     }
                     closedir($handle);
                 }
               }
            }
         }
         if (empty($files)) {
           if ($handle = @opendir($dashboards)) {
               while (false !== ($entry = readdir($handle))) {
                   if ($entry[0] === '.' || ! is_dir($dashboards . '/' . $entry)) {
                       continue;
                   }
                   if (strtolower($entry) === 'default') {
                       $files[] = $dashboards . '/default/dashboard.ini';
                   }
               }
               closedir($handle);
           }
         }
         return $files;
     }

    /**
     * {@inheritdoc}
     */
    public function saveIni($filePath = null, $fileMode = 0660)
    {
        // Preprocessing start, ensures that the non-translated names are used to save module dashboard changes
        // TODO: This MUST NOT survive the new dashboard implementation (yes, it's still a thing..)
        $dashboardNavigation = new Navigation();
        $dashboardNavigation->load('dashboard-pane');
        $getDashboardPane = function ($label) use ($dashboardNavigation) {
            foreach ($dashboardNavigation as $dashboardPane) {
                /** @var DashboardPane $dashboardPane */
                if ($dashboardPane->getLabel() === $label) {
                    return $dashboardPane;
                }

                foreach ($dashboardPane->getChildren() as $dashlet) {
                    /** @var NavigationItem $dashlet */
                    if ($dashlet->getLabel() === $label) {
                        return $dashlet;
                    }
                }
            }
        };

        foreach (clone $this->config as $name => $options) {
            if (strpos($name, '.') !== false) {
                list($dashboardLabel, $dashletLabel) = explode('.', $name, 2);
            } else {
                $dashboardLabel = $name;
                $dashletLabel = null;
            }

            $dashboardPane = $getDashboardPane($dashboardLabel);
            if ($dashboardPane !== null) {
                $dashboardLabel = $dashboardPane->getName();
            }

            if ($dashletLabel !== null) {
                $dashletItem = $getDashboardPane($dashletLabel);
                if ($dashletItem !== null) {
                    $dashletLabel = $dashletItem->getName();
                }
            }

            unset($this->config[$name]);
            $this->config[$dashboardLabel . ($dashletLabel ? '.' . $dashletLabel : '')] = $options;
        }
        // Preprocessing end

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
