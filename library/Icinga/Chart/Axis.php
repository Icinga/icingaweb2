<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Chart;

use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\Primitive\Line;
use Icinga\Chart\Primitive\Text;
use Icinga\Chart\Render\RenderContext;

use Icinga\Chart\Unit\AxisUnit;
use Icinga\Chart\Unit\LinearAxis;

/**
 * Axis for both line and bar chart. It defines the unit, scale, label and ticks.
 */
class Axis implements Drawable
{
    private $drawXGrid = true;
    private $drawYGrid = true;
    private $xLabel = "";
    private $yLabel = "";

    /**
     * @var AxisUnit
     */
    private $xUnit = null;
    /**
     * @var AxisUnit
     */
    private $yUnit = null;


    public function addDataset(array $dataset)
    {
        $this->xUnit->addValues($dataset, 0);
        $this->yUnit->addValues($dataset, 1);
    }


    public function setUnitForXAxis(AxisUnit $unit)
    {
        $this->xUnit = $unit;
        return $this;
    }

    public function setUnitForYAxis(AxisUnit $unit)
    {
        $this->yUnit = $unit;
        return $this;
    }

    public function getApproximateLabelSize()
    {
        return strlen($this->getMax());
    }

    public function toSvg(RenderContext $ctx)
    {
        $group = $ctx->getDocument()->createElement('g');
        $this->renderHorizontal($ctx, $group);
        $this->renderVertical($ctx, $group);
        return $group;
    }

    public function getRequiredPadding() {
        return array(5, 10, 5, 10);
    }

    private function renderHorizontal(RenderContext $ctx, \DOMElement $group)
    {
        $line = new Line(0, 100, 100, 100);
        $line->setStrokeWidth(2);
        $group->appendChild($line->toSvg($ctx));
        foreach ($this->xUnit as $label => $pos) {
            $tick = new Line($pos, 100, $pos, 102);
            $group->appendChild($tick->toSvg($ctx));

            $labelField = new Text($pos+0.5, 105, $label);
            $labelField->setAlignment(Text::ALIGN_MIDDLE)
                ->setFontSize('2em');

            $group->appendChild($labelField->toSvg($ctx));

            if ($this->drawYGrid) {
                $bgLine = new Line($pos, 0, $pos, 102);
                $bgLine->setStrokeWidth(0.5)
                    ->setStrokeColor('#232');
                $group->appendChild($bgLine->toSvg($ctx));
            }
        }

        if ($this->xLabel) {
            $label = new Text(50, 110, $this->xLabel);
            $label->setFontSize('1.7em')
                ->setAlignment(Text::ALIGN_MIDDLE);
            $group->appendChild($label->toSvg($ctx));
        }


    }

    private function renderVertical(RenderContext $ctx, \DOMElement $group)
    {
        $line = new Line(0, 0, 0, 100);
        $line->setStrokeWidth(2);
        $group->appendChild($line->toSvg($ctx));

        foreach ($this->yUnit as $label => $pos) {
            $pos = 100 - $pos;
            $tick = new Line(0, $pos, -1, $pos);
            $group->appendChild($tick->toSvg($ctx));

            $labelField = new Text(-0.5, $pos+0.5, $label);
            $labelField->setFontSize('2em')
                ->setAlignment(Text::ALIGN_END);

            $group->appendChild($labelField->toSvg($ctx));
            if ($this->drawXGrid) {
                $bgLine = new Line(0, $pos, 100, $pos);
                $bgLine->setStrokeWidth(0.5)
                    ->setStrokeColor('#343');
                $group->appendChild($bgLine->toSvg($ctx));

            }
        }

        if ($this->yLabel) {
            $label = new Text(-5, 50, $this->yLabel);
            $label->setFontSize('1.7em')
                ->setAdditionalStyle(Text::ORIENTATION_VERTICAL)
                ->setAlignment(Text::ALIGN_MIDDLE);

            $group->appendChild($label->toSvg($ctx));
        }
    }

    public static function createLinearAxis()
    {
        $axis = new Axis();
        $axis->setUnitForXAxis(new LinearAxis());
        $axis->setUnitForYAxis(new LinearAxis());
        return $axis;
    }

    public function setXLabel($label)
    {
        $this->xLabel = $label;
        return $this;
    }

    public function setYLabel($label)
    {
        $this->yLabel = $label;
        return $this;
    }

    public function setXMin($xMin)
    {
        $this->xUnit->setMin($xMin);
        return $this;
    }

    public function setYMin($yMin)
    {
        $this->yUnit->setMin($yMin);
        return $this;
    }

    public function setXMax($xMax)
    {
        $this->xUnit->setMax($xMax);
        return $this;
    }

    public function setYMax($yMax)
    {
        $this->yUnit->setMax($yMax);
        return $this;
    }


    public function transform(array &$dataSet)
    {
        $result = array();
        foreach ($dataSet as &$points) {
            $result[] = array(
                $this->xUnit->transform($points[0]),
                100 - $this->yUnit->transform($points[1])
            );
        }

        return $result;
    }

}

