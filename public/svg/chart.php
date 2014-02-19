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

use Icinga\Chart\Inline\PieChart;
use Icinga\Application\Loader;


/*
 * Use only autoloader and do not bootstrap EmbeddedWeb to improve performance
 * of svg rendering
 */
require_once dirname(__FILE__) . '/../../library/Icinga/Application/Loader.php';
$loader = new Loader();
$loader->registerNamespace('Icinga', dirname(__FILE__) . '/../../library/Icinga');
$loader->register();

if (!array_key_exists('data', $_GET)) {
    die;
}
header('Content-Type: image/svg+xml');


$pie = new PieChart();
$pie->initFromRequest();
echo $pie->render();

