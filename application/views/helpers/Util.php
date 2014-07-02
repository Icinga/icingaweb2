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

/**
 * Class Zend_View_Helper_Util
 */
class Zend_View_Helper_Util extends Zend_View_Helper_Abstract
{
    public function util() {
        return $this;
    }

    public static function showTimeSince($timestamp)
    {
        if (! $timestamp) return 'unknown';
        $duration = time() - $timestamp;
        if ($duration > 3600 * 24 * 3) {
            if (date('Y') === date('Y', $timestamp)) {
                return date('d.m.', $timestamp);
            }
            return date('m.Y', $timestamp);
        }
        return self::showHourMin($duration);
    }

    public static function showHourMin($sec)
    {
        $min = floor($sec / 60);
        if ($min < 60) {
            return $min . 'm ' . ($sec % 60) . 's';
        }
        $hour = floor($min / 60);
        if ($hour < 24) {
            return date('H:i', time() - $sec);
        }
        return floor($hour / 24) . 'd ' . ($hour % 24) . 'h';
    }

    public static function showSeconds($sec)
    {
        // Todo: localization
        if ($sec < 1) {
            return round($sec * 1000) . 'ms';
        }
        if ($sec < 60) {
            return $sec . 's';
        }
        return floor($sec / 60) . 'm ' . ($sec % 60) . 's';
    }

    public static function showTime($timestamp)
    {
        // Todo: localization
        if ($timestamp < 86400) {
            return 'undef';
        }
        if (date('Ymd') === date('Ymd', $timestamp)) {
            return date('H:i:s', $timestamp);
        }
        if (date('Y') === date('Y', $timestamp)) {
            return date('H:i d.m.', $timestamp);
        }
        return date('H:i d.m.Y', $timestamp);
    }

    public static function getHostStateClassName($state)
    {
        $class = 'unknown';
        switch ($state) {
            case null:
                $class = 'error';
                break;
            case 0:
                $class = 'ok';
                break;
            case 1:
            case 2:
                $class = 'error';
                break;
        }
        return $class;
    }

    public static function getHostStateName($state)
    {
        $states = array(
            0 => 'UP',
            1 => 'DOWN',
            2 => 'UNREACHABLE',
            3 => 'UNKNOWN',
            4 => 'PENDING', // fake
            99 => 'PENDING' // fake
        );
        if (isset($states[$state])) {
            return $states[$state];
        }
        return sprintf('OUT OF BOUNDS (%s)', var_export($state, 1));
    }

    public static function getServiceStateName($state)
    {
        if ($state === null) { $state = 3; } // really?
        $states = array(
            0 => 'OK',
            1 => 'WARNING',
            2 => 'CRITICAL',
            3 => 'UNKNOWN',
            4 => 'PENDING', // fake
            99 => 'PENDING' // fake
        );
        if (isset($states[$state])) {
            return $states[$state];
        }
        return sprintf('OUT OF BOUND (%d)' . $state, (int) $state);
    }
}
