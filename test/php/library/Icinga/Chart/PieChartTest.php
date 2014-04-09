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

namespace Tests\Icinga\Chart;

use \DOMXPath;
use \DOMDocument;

use Icinga\Chart\PieChart;
use Icinga\Test\BaseTestCase;

class PieChartTest extends BaseTestCase
{
    public function testPieChartCreation()
    {
        $chart = new PieChart();
        $chart->drawPie(
            array(
                'label' => 'My bar',
                'color' => 'black', 'green', 'red',
                'data'  => array(50,50,50)
            )
        );
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($chart->render());
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('x', 'http://www.w3.org/2000/svg');
        $path = $xpath->query('//x:path[@data-icinga-graph-type="pieslice"]');
        $this->assertEquals(3, $path->length, 'Assert the correct number of datapoints being drawn as SVG bars');
    }
}