<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Chart\Inline;

use Icinga\Chart\PieChart as PieChartRenderer;
use Imagick;
use Exception;
use Icinga\Exception\IcingaException;

/**
 * Draw an inline pie-chart directly from the available request parameters.
 */
class PieChart extends Inline
{

    public function render($output = true)
    {
        $pie = new PieChartRenderer();
        $pie->alignTopLeft();
        $pie->disableLegend();
        $pie->drawPie(array(
            'data' => $this->data, 'colors' => $this->colors, 'labels' => $this->labels
        ));
        if ($output) {
            echo $pie->render();
        } else {
            return $pie->render();
        }
    }

    public function toPng()
    {
        if (! class_exists('Imagick')) {
            // TODO: This is quick & dirty. 404?
            throw new IcingaException('Cannot render PNGs without Imagick');
        }
        $image = new Imagick();
        $image->readImageBlob($this->render(false));
        $image->setImageFormat('png24');
        $image->resizeImage($this->width, $this->height, imagick::FILTER_LANCZOS, 1);
        echo $image;
    }
}
