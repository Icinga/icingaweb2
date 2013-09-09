<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Chart;

use Icinga\Chart\Chart;
use Icinga\Chart\Axis;
use Icinga\Chart\Graph\BarGraph;
use Icinga\Chart\Graph\LineGraph;
use Icinga\Chart\Primitive\Canvas;
use Icinga\Chart\Primitive\Rect;
use Icinga\Chart\Primitive\Path;
use Icinga\Chart\Render\LayoutBox;
use Icinga\Chart\Render\RenderContext;

/**
 * Base class for grid based charts
 */
class GridChart extends Chart
{
    const TYPE_LINE = "LINE";
    const TYPE_BAR  = "BAR";

    private $graphs = array();
    private $axis = array();

    public function isValidDataFormat()
    {
        foreach ($this->graphs as $axis => $values) {
            foreach ($values as $value) {
                if (!isset($value['data']) || !is_array($value['data'])) {
                    return false;
                }
            }
        }
        return true;
    }


    private function configureAxisFromDatasets()
    {
        foreach ($this->graphs as $axis => &$lines) {
            $axisObj = $this->axis[$axis];
            foreach ($lines as &$line) {
                $axisObj->addDataset($line);
            }
        }
    }

    public function drawLines($axis /*,...*/)
    {
        $this->draw(self::TYPE_LINE, func_get_args());

    }

    public function drawBars($axis)
    {
        $this->draw(self::TYPE_BAR, func_get_args());

    }

    private function draw($type, $data)
    {
        $axisName = 'default';
        if (is_string($data[0])) {
            $axisName =  $data[0];
            array_shift($data);
        }
        foreach ($data as &$graph) {
            $graph['graphType'] = $type;

            $this->graphs[$axisName][] = $graph;
            $this->legend->addDataset($graph);
        }
    }

    public function setAxisLabel($xAxisLabel, $yAxisLabel, $axisName = 'default')
    {
        $this->axis[$axisName]->setXLabel($xAxisLabel)->setYLabel($yAxisLabel);
        return $this;
    }

    protected function build()
    {
        $this->configureAxisFromDatasets();
    }

    protected function init()
    {
        $this->setAxis(Axis::createLinearAxis());
    }

    public function setAxis(Axis $axis, $name = 'default')
    {
        $this->axis = array($name => $axis);
        return $this;
    }

    public function addAxis(Axis $axis, $name)
    {
        $this->axis[$name] = $axis;
        return $this;
    }

    public function setAxisMin($xMin = null, $yMin = null, $axisName = 'default')
    {
        $this->axis[$axisName]->setXMin($xMin)->setYMin($yMin);
        return $this;
    }

    public function setAxisMax($xMax = null, $yMax = null, $axisName = 'default')
    {
        $this->axis[$axisName]->setXMax($xMax)->setYMax($yMax);
        return $this;
    }

    public function toSvg(RenderContext $ctx)
    {
        $outerBox = new Canvas('outerGraph', new LayoutBox(0, 0, 100 , 100));
        $innerBox = new Canvas('graph', new LayoutBox(0, 0, 100, 90));

        $maxPadding = array(0,0,0,0);
        foreach($this->axis as $axis) {
            $padding = $axis->getRequiredPadding();
            for ($i=0; $i < count($padding); $i++) {
                $maxPadding[$i] = max($maxPadding[$i], $padding[$i]);
            }
            $innerBox->addElement($axis);
        }
        $this->renderGraphContent($innerBox);

        $innerBox->getLayout()->setPadding($maxPadding[0], $maxPadding[1], $maxPadding[2], $maxPadding[3]);
        $clipBox = new Canvas('clip', new LayoutBox(0,0,100,100));
        $clipBox->toClipPath();
        $innerBox->addElement($clipBox);

        $clipBox->addElement(new Rect(0,0,100,100));
        $outerBox->addElement($innerBox);
        $outerBox->addElement($this->legend);
        return $outerBox->toSvg($ctx);
    }

    private function renderGraphContent($innerBox)
    {
        foreach ($this->graphs as $axisName => $graphs) {
            $axis = $this->axis[$axisName];
            foreach ($graphs as $graph) {
                switch ($graph['graphType']) {
                    case self::TYPE_BAR:
                        $this->renderBars($axis, $graph, $innerBox);
                        break;
                    case self::TYPE_LINE:
                        $this->renderLines($axis, $graph, $innerBox);
                        break;
                }
            }
        }
    }

    private function renderLines($axis, array $graph, Canvas $innerBox)
    {
        $path = new LineGraph($axis->transform($graph['data']));
        $path->setStyleFromConfig($graph);
        $innerBox->addElement($path);

    }

    private function renderBars($axis, array $graph, Canvas $innerBox)
    {
        $path = new BarGraph($axis->transform($graph['data']));
        $innerBox->addElement($path);
    }
}
