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

use Icinga\User;
use Icinga\Exception\ProgrammingError;
use \Icinga\Application\DbAdapterFactory;
use \Zend_Config;
use \Zend_Db;

/**
 * Create preference stores from zend config
 */
final class StoreFactory
{
    /**
     * Prefix for classes containing namespace
     */
    const CLASS_PREFIX = 'Icinga\\User\\Preferences\\';

    /**
     * Suffix for class
     */
    const CLASS_SUFFIX = 'Store';

    /**
     * Create storage adapter from zend configuration
     *
     * @param  Zend_Config $config
     * @param  User $user
     *
     * @return FlushObserverInterface
     * @throws ProgrammingError
     */
    public static function create(Zend_Config $config, User $user)
    {
        $class = self::CLASS_PREFIX. ucfirst($config->get('type')). self::CLASS_SUFFIX;

        if (class_exists($class)) {
            $store = new $class();

            if (!$store instanceof FlushObserverInterface) {
                throw new ProgrammingError('Not instance of FlushObserverInterface: '. $class);
            }

            $items = $config->toArray();

            if ($items['type'] == 'db') {
                $items['dbAdapter'] = DbAdapterFactory::getDbAdapter($items['resource']);
            }
            unset($items['type']);

            foreach ($items as $key => $value) {
                $setter = 'set'. ucfirst($key);
                if (is_callable(array($store, $setter))) {
                    $store->$setter($value);
                }
            }

            $store->setUser($user);

            return $store;
        }

        throw new ProgrammingError('Could not instantiate class: '. $class);
    }
}
