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

namespace Icinga\Util;

use Icinga\Exception\ProgrammingError;

class Format
{
    const STANDARD_IEC = 0;
    const STANDARD_SI  = 1;
    protected static $instance;

    protected static $bitPrefix = array(
        array('bit', 'Kibit', 'Mibit', 'Gibit', 'Tibit', 'Pibit', 'Eibit', 'Zibit', 'Yibit'),
        array('bit', 'kbit', 'Mbit', 'Gbit', 'Tbit', 'Pbit', 'Ebit', 'Zbit', 'Ybit'),
    );
    protected static $bitBase = array(1024, 1000);

    protected static $bytePrefix = array(
        array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'),
        array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'),
    );
    protected static $byteBase = array(1024, 1000);

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Format;
        }
        return self::$instance;
    }

    public static function bits($value, $standard = self::STANDARD_SI)
    {
        return self::formatForUnits(
            $value,
            self::$bitPrefix[$standard],
            self::$bitBase[$standard]
        );
    }

    public static function bytes($value, $standard = self::STANDARD_IEC)
    {
        return self::formatForUnits(
            $value,
            self::$bytePrefix[$standard],
            self::$byteBase[$standard]
        );
    }

    public static function duration($duration)
    {
        if ($duration === null || $duration === false) {
            return '-';
        }
        return self::showHourMin($duration);
    }

    protected static function showHourMin($sec)
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

    protected static function smartTimeDiff($diff, $timestamp)
    {
        if ($timestamp === null || $timestamp === false) {
            return '-';
        }
        if (! preg_match('~^\d+$~', $timestamp)) {
            throw new ProgrammingError(sprintf('"%s" is not a number', $timestamp));
        }
        $prefix = '';
        if ($diff < 0) {
            $prefix = '-';
            $diff *= -1;
        }
        if ($diff > 3600 * 24 * 3) {
            if (date('Y') === date('Y', $timestamp)) {
                return date('d.m.', $timestamp);
            }
            return date('m.Y', $timestamp);
        }
        return $prefix . self::showHourMin($diff);
    }

    public static function timeSince($timestamp)
    {
        return self::smartTimeDiff(time() - $timestamp, $timestamp);
    }

    public static function timeUntil($timestamp)
    {
        return self::smartTimeDiff($timestamp - time(), $timestamp);
    }

    protected static function formatForUnits($value, & $units, $base)
    {
        $sign = '';
        if ($value < 0) {
            $value = abs($value);
            $sign = '-';
        }
        $pow = floor(log($value, $base));
        $result =  $value / pow($base, $pow);

        // 1034.23 looks better than 1.03, but 2.03 is fine:
        if ($pow > 0 && $result < 2) {
            $pow--;
            $result =  $value / pow($base, $pow);
        }
        return sprintf(
            '%s%0.2f %s',
            $sign,
            $result,
            $units[$pow]
        );
    }
}
