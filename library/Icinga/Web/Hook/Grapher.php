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

namespace Icinga\Web\Hook;

/**
 * Icinga Web Grapher Hook base class
 *
 * Extend this class if you want to integrate your graphing solution nicely into
 * Icinga Web
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Grapher
{
    /**
     * Whether this grapher provides preview images
     *
     * @var bool
     */
    protected $hasPreviews = false;

    /**
     * Constructor must live without arguments right now
     *
     * Therefore the constructor is final, we might change our opinion about
     * this one far day
     */
    final public function __construct()
    {
        $this->init();
    }

    /**
     * Whether this grapher provides preview images
     *
     * @return bool
     */
    public function hasPreviews()
    {
        return $this->hasPreviews;
    }

    /**
     * Overwrite this function if you want to do some initialization stuff
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * Whether a graph for the given host[, service [, plot]] exists
     *
     * @return bool
     */
    public function hasGraph($host, $service = null, $plot = null)
    {
        return false;
    }

    /**
     * Get a preview image for the given host[, service [, plot]] exists
     *
     * WARNING: We are not sure yet whether this will remain as is
     *
     * @return string
     */
    public function getPreviewImage($host, $service = null, $plot = null)
    {
        throw new Exception('This backend has no preview images');
    }

    /**
     * Get URL pointing to the grapher
     *
     * WARNING: We are not sure yet whether this will remain as is
     *
     * @return string
     */
    public function getGraphUrl($host, $service = null, $plot = null)
    {
        throw new Exception('This backend has no images');
    }
}
