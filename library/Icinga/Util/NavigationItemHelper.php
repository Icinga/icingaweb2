<?php

namespace Icinga\Util;

use Icinga\Application\Config;
use Icinga\Data\Filter\FilterMatchCaseInsensitive;
use Icinga\User;
use Icinga\Web\Navigation\Navigation;

class NavigationItemHelper
{

    protected static $navigationItemCache = null;

    public static function fetchUserNavigationItems(User $user)
    {
        if (self::$navigationItemCache !== null) {
            return self::$navigationItemCache;
        }

        $itemTypeConfig = Navigation::getItemTypeConfiguration();
        $username = $user->getUsername();
        self::$navigationItemCache = array_merge(
            static::fetchSharedNavigationItemConfigs($itemTypeConfig, $username),
            static::fetchUserNavigationItemConfigs($itemTypeConfig, $username)
        );

        return self::$navigationItemCache;
    }

    /**
     * Return all shared navigation item configurations
     *
     * @param   string  $owner  A username if only items shared by a specific user are desired
     *
     * @return  array
     */
    protected static function fetchSharedNavigationItemConfigs($itemTypeConfig, string $owner)
    {
        $configs = array();
        foreach ($itemTypeConfig as $type => $_) {
            $config = Config::navigation($type);
            $config->getConfigObject()->setKeyColumn('name');
            $query = $config->select();
            if ($owner !== null) {
                $query->applyFilter(new FilterMatchCaseInsensitive('owner', '=', $owner));
            }

            foreach ($query as $itemConfig) {
                $configs[] = $itemConfig;
            }
        }

        return $configs;
    }

    /**
     * Return all user navigation item configurations
     *
     * @param   string  $username
     *
     * @return  array
     */
    protected static function fetchUserNavigationItemConfigs($itemTypeConfig, string $username)
    {
        $configs = array();
        foreach ($itemTypeConfig as $type => $_) {
            $config = Config::navigation($type, $username);
            $config->getConfigObject()->setKeyColumn('name');
            foreach ($config->select() as $itemConfig) {
                $configs[] = $itemConfig;
            }
        }

        return $configs;
    }

}