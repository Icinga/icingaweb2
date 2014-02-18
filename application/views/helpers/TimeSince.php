<?php
// @codingStandardsIgnoreStart

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
 * Class Zend_View_Helper_TimeSince
 */
class Zend_View_Helper_TimeSince extends Zend_View_Helper_Abstract
{
    public function timeSince($timestamp)
    {

        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        if (!is_numeric($timestamp)) {
            return '?';
        }

        if (! $timestamp) return '-';
        $duration = time() - $timestamp;
        $prefix = '';
        if ($duration < 0) {
            $prefix = '-';
            $duration *= -1;
        }
        if ($duration > 3600 * 24 * 3) {
            if (date('Y') === date('Y', $timestamp)) {
                return date('d.m.', $timestamp);
            }
            return date('m.Y', $timestamp);
        }
        return '<span class="timesince">' . $prefix . $this->showHourMin($duration) . '</span>';
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
}

// @codingStandardsIgnoreStop
