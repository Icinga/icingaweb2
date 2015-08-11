<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart;

use DOMElement;
use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\Primitive\Line;
use Icinga\Chart\Primitive\Text;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Render\Rotator;
use Icinga\Chart\Unit\AxisUnit;
use Icinga\Chart\Unit\CalendarUnit;
use Icinga\Chart\Unit\LinearUnit;

/**
 * Axis class for the GridChart class.
 *
 * Implements drawing functions for the axis and its labels but delegates tick and label calculations
 * to the AxisUnit implementations
 *
 * @see GridChart
 * @see AxisUnit
 */
class Axis implements Drawable
{
    /**
     * Draw the label text horizontally
     */
    const LABEL_ROTATE_HORIZONTAL = 'normal';

    /**
     * Draw the label text diagonally
     */
    const LABEL_ROTATE_DIAGONAL = 'diagonal';

    /**
     * Whether to draw the horizontal lines for the background grid
     *
     * @var bool
     */
    private $drawXGrid = true;

    /**
     * Whether to draw the vertical lines for the background grid
     *
     * @var bool
     */
    private $drawYGrid = true;

    /**
     * The label for the x axis
     *
     * @var string
     */
    private $xLabel = "";

    /**
     * The label for the y axis
     *
     * @var string
     */
    private $yLabel = "";

    /**
     * The AxisUnit implementation to use for calculating the ticks for the x axis
     *
     * @var AxisUnit
     */
    private $xUnit = null;

    /**
     * The AxisUnit implementation to use for calculating the ticks for the y axis
     *
     * @var AxisUnit
     */
    private $yUnit = null;

    /**
     * The minimum amount of units each step must take up
     *
     * @var int
     */
    public $minUnitsPerStep = 80;

    /**
     * The minimum amount of units each tick must take up
     *
     * @var int
     */
    public $minUnitsPerTick = 15;

    /**
     * If the displayed labels should be aligned horizontally or diagonally
     */
    protected $labelRotationStyle = self::LABEL_ROTATE_HORIZONTAL;

    /**
     * Inform the axis about an added dataset
     *
     * This is especially needed when one or more AxisUnit implementations dynamically define
     * their min or max values, as this is the point where they detect the min and max value
     * from the datasets
     *
     * @param array $dataset An dataset to respect on axis generation
     */
    public function addDataset(array $dataset)
    {
        $this->xUnit->addValues($dataset, 0);
        $this->yUnit->addValues($dataset, 1);
    }

    /**
     * Set the AxisUnit implementation to use for generating the x axis
     *
     * @param   AxisUnit $unit      The AxisUnit implementation to use for the x axis
     *
     * @return  $this                This Axis Object
     * @see     Axis::CalendarUnit
     * @see     Axis::LinearUnit
     */
    public function setUnitForXAxis(AxisUnit $unit)
    {
        $this->xUnit = $unit;
        return $this;
    }

    /**
     * Set the AxisUnit implementation to use for generating the y axis
     *
     * @param   AxisUnit $unit      The AxisUnit implementation to use for the y axis
     *
     * @return  $this                This Axis Object
     * @see     Axis::CalendarUnit
     * @see     Axis::LinearUnit
     */
    public function setUnitForYAxis(AxisUnit $unit)
    {
        $this->yUnit = $unit;
        return $this;
    }

    /**
     * Return the padding this axis requires
     *
     * @return array An array containing the padding for all sides
     */
    public function getRequiredPadding()
    {
        return array(10, 5, 15, 10);
    }

    /**
     * Render the horizontal axis
     *
     * @param RenderContext $ctx    The context to use for rendering
     * @param DOMElement    $group  The DOMElement this axis will be added to
     */
    private function renderHorizontalAxis(RenderContext $ctx, DOMElement $group)
    {
        $steps = $this->ticksPerX($this->xUnit->getTicks(), $ctx->getNrOfUnitsX(), $this->minUnitsPerStep);
        $ticks = $this->ticksPerX($this->xUnit->getTicks(), $ctx->getNrOfUnitsX(), $this->minUnitsPerTick);

        // Steps should always be ticks
        if ($ticks !== $steps) {
            $steps = $ticks * 5;
        }

        // Check whether there is enough room for regular labels
        $labelRotationStyle = $this->labelRotationStyle;
        if ($this->labelsOversized($this->xUnit, 6)) {
            $labelRotationStyle = self::LABEL_ROTATE_DIAGONAL;
        }

        /*
        $line = new Line(0, 100, 100, 100);
        $line->setStrokeWidth(2);
        $group->appendChild($line->toSvg($ctx));
        */

        // contains the approximate end position of the last label
        $lastLabelEnd = -1;
        $shift = 0;

        $i = 0;
        foreach ($this->xUnit as $label => $pos) {

            if ($i % $ticks === 0) {
                /*
                $tick = new Line($pos, 100, $pos, 101);
                $group->appendChild($tick->toSvg($ctx));
                */
            }

            if ($i % $steps === 0) {
                if ($labelRotationStyle === self::LABEL_ROTATE_HORIZONTAL) {
                    // If the  last label would overlap this label we shift the y axis a bit
                    if ($lastLabelEnd > $pos) {
                        $shift = ($shift + 5) % 10;
                    } else {
                        $shift = 0;
                    }
                }

                $labelField = new Text($pos + 0.5, ($this->xLabel ? 107 : 105) + $shift, $label);
                if ($labelRotationStyle === self::LABEL_ROTATE_HORIZONTAL) {
                    $labelField->setAlignment(Text::ALIGN_MIDDLE)
                        ->setFontSize('2.5em');
                } else {
                    $labelField->setFontSize('2.5em');
                }

                if ($labelRotationStyle === self::LABEL_ROTATE_DIAGONAL) {
                    $labelField = new Rotator($labelField, 45);
                }
                $labelField = $labelField->toSvg($ctx);

                $group->appendChild($labelField);

                if ($this->drawYGrid) {
                    $bgLine = new Line($pos, 0, $pos, 100);
                    $bgLine->setStrokeWidth(0.5)
                        ->setStrokeColor('#BFBFBF');
                    $group->appendChild($bgLine->toSvg($ctx));
                }
                $lastLabelEnd = $pos + strlen($label) * 1.2;
            }
            $i++;
        }
    }

    /**
     * Render the vertical axis
     *
     * @param RenderContext $ctx    The context to use for rendering
     * @param DOMElement    $group  The DOMElement this axis will be added to
     */
    private function renderVerticalAxis(RenderContext $ctx, DOMElement $group)
    {
        $steps = $this->ticksPerX($this->yUnit->getTicks(), $ctx->getNrOfUnitsY(), $this->minUnitsPerStep);
        $ticks = $this->ticksPerX($this->yUnit->getTicks(), $ctx->getNrOfUnitsY(), $this->minUnitsPerTick);

        // Steps should always be ticks
        if ($ticks !== $steps) {
            $steps = $ticks * 5;
        }
        /*
        $line = new Line(0, 0, 0, 100);
        $line->setStrokeWidth(2);
        $group->appendChild($line->toSvg($ctx));
        */

        $i = 0;
        foreach ($this->yUnit as $label => $pos) {
            $pos = 100 - $pos;

            if ($i % $ticks === 0) {
                // draw a tick
                //$tick = new Line(0, $pos, -1, $pos);
                //$group->appendChild($tick->toSvg($ctx));
            }

            if ($i % $steps === 0) {
                // draw a step
                $labelField = new Text(-0.5, $pos + 0.5, $label);
                $labelField->setFontSize('2.5em')
                    ->setAlignment(Text::ALIGN_END);

                $group->appendChild($labelField->toSvg($ctx));
                if ($this->drawXGrid) {
                    $bgLine = new Line(0, $pos, 100, $pos);
                    $bgLine->setStrokeWidth(0.5)
                        ->setStrokeColor('#BFBFBF');
                    $group->appendChild($bgLine->toSvg($ctx));
                }
            }
            $i++;
        }

        if ($this->yLabel || $this->xLabel) {
            if ($this->yLabel && $this->xLabel) {
                $txt = $this->yLabel . ' / ' . $this->xLabel;
            } else if ($this->xLabel) {
                $txt = $this->xLabel;
            } else {
                $txt = $this->yLabel;
            }

            $axisLabel = new Text(50, -3, $txt);
            $axisLabel->setFontSize('2em')
                ->setFontWeight('bold')
                ->setAlignment(Text::ALIGN_MIDDLE);

            $group->appendChild($axisLabel->toSvg($ctx));
        }
    }

    /**
     * Factory method, create an Axis instance using Linear ticks as the unit
     *
     * @return  Axis        The axis that has been created
     * @see     LinearUnit
     */
    public static function createLinearAxis()
    {
        $axis = new Axis();
        $axis->setUnitForXAxis(self::linearUnit());
        $axis->setUnitForYAxis(self::linearUnit());
        return $axis;
    }

    /**
     * Set the label for the x axis
     *
     * An empty string means 'no label'.
     *
     * @param   string $label   The label to use for the x axis
     *
     * @return  $this           Fluid interface
     */
    public function setXLabel($label)
    {
        $this->xLabel = $label;
        return $this;
    }

    /**
     * Set the label for the y axis
     *
     * An empty string means 'no label'.
     *
     * @param   string $label   The label to use for the y axis
     *
     * @return  $this            Fluid interface
     */
    public function setYLabel($label)
    {
        $this->yLabel = $label;
        return $this;
    }

    /**
    * Set the labels minimum value for the x axis
    *
    * Setting the value to null let's the axis unit decide which value to use for the minimum
    *
    * @param    int $xMin   The minimum value to use for the x axis
    *
    * @return   $this        Fluid interface
    */
    public function setXMin($xMin)
    {
        $this->xUnit->setMin($xMin);
        return $this;
    }

    /**
     * Set the labels minimum value for the y axis
     *
     * Setting the value to null let's the axis unit decide which value to use for the minimum
     *
     * @param   int $yMin   The minimum value to use for the x axis
     *
     * @return  $this        Fluid interface
     */
    public function setYMin($yMin)
    {
        $this->yUnit->setMin($yMin);
        return $this;
    }

    /**
     * Set the labels maximum value for the x axis
     *
     * Setting the value to null let's the axis unit decide which value to use for the maximum
     *
     * @param   int $xMax   The minimum value to use for the x axis
     *
     * @return  $this        Fluid interface
     */
    public function setXMax($xMax)
    {
        $this->xUnit->setMax($xMax);
        return $this;
    }

    /**
     * Set the labels maximum value for the y axis
     *
     * Setting the value to null let's the axis unit decide which value to use for the maximum
     *
     * @param   int $yMax   The minimum value to use for the y axis
     *
     * @return  $this        Fluid interface
     */
    public function setYMax($yMax)
    {
        $this->yUnit->setMax($yMax);
        return $this;
    }

    /**
     * Transform all coordinates of the given dataset to coordinates that fit the graph's coordinate system
     *
     * @param   array $dataSet  The absolute coordinates as provided in the draw call
     *
     * @return  array           A graph relative representation of the given coordinates
     */
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

    /**
     * Create an AxisUnit that can be used in the axis to represent timestamps
     *
     * @return CalendarUnit
     */
    public static function calendarUnit()
    {
        return new CalendarUnit();
    }

    /**
     * Create an AxisUnit that can be used in the axis to represent a dataset as equally distributed
     * ticks
     *
     * @param   int $ticks
     * @return  LinearUnit
     */
    public static function linearUnit($ticks = 10)
    {
        return new LinearUnit($ticks);
    }

    /**
     * Return the SVG representation of this object
     *
     * @param   RenderContext $ctx The context to use for calculations
     *
     * @return  DOMElement
     * @see     Drawable::toSvg
     */
    public function toSvg(RenderContext $ctx)
    {
        $group = $ctx->getDocument()->createElement('g');
        $this->renderHorizontalAxis($ctx, $group);
        $this->renderVerticalAxis($ctx, $group);
        return $group;
    }

    protected function ticksPerX($ticks, $units, $min)
    {
        $per = 1;
        while ($per * $units / $ticks < $min) {
            $per++;
        }
        return $per;
    }

    /**
     * Returns whether at least one label of the given Axis
     * is bigger than the given maxLength
     *
     * @param   AxisUnit    $axis   The axis that contains the labels that will be checked
     *
     * @return  boolean             Whether at least one label is bigger than maxLength
     */
    private function labelsOversized(AxisUnit $axis, $maxLength = 5)
    {
        $oversized = false;
        foreach ($axis as $label => $pos) {
            if (strlen($label) > $maxLength) {
                $oversized = true;
            }
        }
        return $oversized;
    }
}
