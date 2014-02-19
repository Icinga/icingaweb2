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

use Icinga\Chart\PieChart;
use Icinga\Application\Loader;


/*
 * Use only autoloader and do not bootstrap EmbeddedWeb to improve performance
 * of svg rendering
 */
require_once dirname(__FILE__) . '/../../library/Icinga/Application/Loader.php';
$loader = new Loader();
$loader->registerNamespace('Icinga', dirname(__FILE__) . '/../../library/Icinga');
$loader->register();



// TODO: move functionality into separate class

function sanitizeStringArray(array $arr)
{
    $sanitized = array();
    foreach ($arr as $key => $value) {
        $sanitized[$key] = htmlspecialchars($value);
    }
    return $sanitized;
}

if (!array_key_exists('data', $_GET)) {
    die;
}
header('Content-Type: image/svg+xml');
$data = explode(',', $_GET['data']);
foreach ($data as $key => $value) {
    $data[$key] = (int)$value;
}
$labels = array();
for ($i = 0; $i < sizeof($data); $i++) {
    $labels[] = '';
}

if (array_key_exists('colors', $_GET)) {
    $colors = sanitizeStringArray(explode(',', $_GET['colors']));
} else {
    $colors = array(
        '#00FF00', // OK
        '#FFFF00', // Warning
        '#FF0000', // Critical
        '#E066FF'  // Unreachable
    );
    while (sizeof($colors) < sizeof($data)) {
        $colors[] = '#FEFEFE';
    }
}
$width = 15;
if (array_key_exists('width', $_GET)) {
    $width = (int)$_GET['width'];
}
$height = 15;
if (array_key_exists('height', $_GET)) {
    $height = (int)$_GET['height'];
}
$pie = new PieChart();
$pie->drawPie(array(
    'data' => $data, 'colors' => $colors, 'labels' => $labels
));
$pie->setWidth($width)->setHeight($height);
echo $pie->render();
