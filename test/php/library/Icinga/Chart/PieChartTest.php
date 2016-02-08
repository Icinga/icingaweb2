<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Chart;

use DOMXPath;
use DOMDocument;
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
