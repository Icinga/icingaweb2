<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Inline;

use Icinga\Chart\PieChart as PieChartRenderer;

/**
 * Draw an inline pie-chart directly from the available request parameters.
 */
class PieChart extends Inline
{
    protected function getChart()
    {
        $pie = new PieChartRenderer();
        $pie->alignTopLeft();
        $pie->disableLegend();
        $pie->drawPie(array(
            'data' => $this->data, 'colors' => $this->colors, 'labels' => $this->labels
        ));
        return $pie;
    }

    public function toSvg($output = true)
    {
        if ($output) {
            echo $this->getChart()->render();
        } else {
            return $this->getChart()->render();
        }
    }

    public function toPng($output = true)
    {
        if ($output) {
            echo $this->getChart()->toPng($this->width, $this->height);
        } else {
            return $this->getChart()->toPng($this->width, $this->height);
        }
    }
}
