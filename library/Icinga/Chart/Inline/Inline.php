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

namespace Icinga\Chart\Inline;

/**
 * Class to render and inline chart directly from the request params.
 *
 * When rendering huge amounts of inline charts it is too expensive
 * to bootstrap the complete application for ever single chart and
 * we need to be able render Charts in a compact environment without
 * the other Icinga classes.
 *
 * Class Inline
 * @package Icinga\Chart\Inline
 */
class Inline {

    /**
     * The data displayed in this chart
     *
     * @var array
     */
    protected $data;

    /**
     * The colors used to display this chart
     *
     * @var array
     */
    protected $colors = array(
        '#00FF00', // OK
        '#FFFF00', // Warning
        '#FF0000', // Critical
        '#E066FF'  // Unreachable
    );

    /**
     * The labels displayed on this chart
     *
     * @var array
     */
    protected $labels = array();

    /**
     * The height in percent
     *
     * @var int
     */
    protected $height = 20;

    /**
     * The width in percent
     *
     * @var int
     */
    protected $width = 20;

    protected function sanitizeStringArray(array $arr)
    {
        $sanitized = array();
        foreach ($arr as $key => $value) {
            $sanitized[$key] = htmlspecialchars($value);
        }
        return $sanitized;
    }

    /**
     * Populate the properties from the current request.
     */
    public function initFromRequest()
    {
        $this->data = explode(',', $_GET['data']);
        foreach ($this->data as $key => $value) {
            $this->data[$key] = (int)$value;
        }
        for ($i = 0; $i < sizeof($this->data); $i++) {
            $this->labels[] = '';
        }

        if (array_key_exists('this->colors', $_GET)) {
            $this->colors = $this->sanitizeStringArray(explode(',', $_GET['colors']));
        }
        while (sizeof($this->colors) < sizeof($this->data)) {
            $this->colors[] = '#FEFEFE';
        }

        if (array_key_exists('width', $_GET)) {
            $this->width = (int)$_GET['width'];
        }
        if (array_key_exists('height', $_GET)) {
            $this->height = (int)$_GET['height'];
        }

    }
} 