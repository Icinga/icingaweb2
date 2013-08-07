<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Class Zend_View_Helper_Img
 */
class Zend_View_Helper_Img extends Zend_View_Helper_Abstract
{
    public function img($url, array $properties = array())
    {
        $attributes = array();
        $has_alt = false;
        foreach ($properties as $key => $val) {
            if ($key === 'alt') $has_alt = true;
            $attributes[] = sprintf(
                '%s="%s"',
                filter_var($key, FILTER_SANITIZE_URL),
                filter_var($val, FILTER_SANITIZE_FULL_SPECIAL_CHARS)
            );
        }
        if (! $has_alt) $attributes[] = 'alt=""';

        return sprintf(
            '<img src="%s"%s />',
            $this->view->baseUrl($url),
            !empty($attributes) ? ' ' . implode(' ', $attributes) : ''
        );
    }
}

// @codingStandardsIgnoreStart
