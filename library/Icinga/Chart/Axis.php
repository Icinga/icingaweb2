<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Chart;

use DOMElement;
use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\Primitive\Line;
use Icinga\Chart\Primitive\Text;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Unit\AxisUnit;
use Icinga\Chart\Unit\CalendarUnit;
use Icinga\Chart\Unit\LinearUnit;

/**
 * Axis class for the GridChart class.
 *
 * Implements drawing functions for the axis and it's labels but delegates tick and label calculations
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
     * If the displayed labels should be aligned horizontally or diagonally
     */
    private $labelRotationStyle = self::LABEL_ROTATE_DIAGONAL;

    /**
     * Set the label rotation style for the horizontal axis
     *
     * <ul>
     *   <li><b>LABEL_ROTATE_HORIZONTAL</b>: Labels will be displayed horizontally </li>
     *   <li><b>LABEL_ROTATE_DIAGONAL</b>: Labels will be rotated by 45° </li>
     * </ul>
     *
     * @param $style    The rotation mode
     */
    public function setHorizontalLabelRotationStyle($style)
    {
        $this->labelRotationStyle = $style;
    }

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
     * @return  self                This Axis Object
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
     * @return  self                This Axis Object
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
        $line = new Line(0, 100, 100, 100);
        $line->setStrokeWidth(2);
        $group->appendChild($line->toSvg($ctx));

        // contains the approximate end position of the last label
        $lastLabelEnd = -1;
        $shift = 0;

        foreach ($this->xUnit as $label => $pos) {
            if ($this->labelRotationStyle === self::LABEL_ROTATE_HORIZONTAL) {
                // If the  last label would overlap this label we shift the y axis a bit
                if ($lastLabelEnd > $pos) {
                    $shift = ($shift + 5) % 10;
                } else {
                    $shift = 0;
                }
            }

            $tick = new Line($pos, 100, $pos, 102);
            $group->appendChild($tick->toSvg($ctx));

            $labelField = new Text($pos + 0.5, ($this->xLabel ? 107 : 105) + $shift, $label);
            if ($this->labelRotationStyle === self::LABEL_ROTATE_HORIZONTAL) {
                $labelField->setAlignment(Text::ALIGN_MIDDLE)
                    ->setFontSize('1.8em');
            } else {
                $labelField->setFontSize('2.5em');
            }

            $labelField = $labelField->toSvg($ctx);

            if ($this->labelRotationStyle === self::LABEL_ROTATE_DIAGONAL) {
                $labelField = $this->rotate($ctx, $labelField, 45);
            }
            $group->appendChild($labelField);

            if ($this->drawYGrid) {
                $bgLine = new Line($pos, 0, $pos, 100);
                $bgLine->setStrokeWidth(0.5)
                    ->setStrokeColor('#232');
                $group->appendChild($bgLine->toSvg($ctx));
            }
            $lastLabelEnd = $pos + strlen($label) * 1.2;
        }

        // render the label for this axis
        if ($this->xLabel) {
            $axisLabel = new Text(50, 104, $this->xLabel);
            $axisLabel->setFontSize('2em')
                ->setFontWeight('bold')
                ->setAlignment(Text::ALIGN_MIDDLE);
            $group->appendChild($axisLabel->toSvg($ctx));
        }
    }

    /**
     * Rotate the given element.
     *
     * @param RenderContext $ctx        The rendering context
     * @param DOMElement    $el         The element to rotate
     * @param               $degrees    The rotation degrees
     *
     * @return DOMElement
     */
    private function rotate(RenderContext $ctx, DOMElement $el, $degrees)
    {
        // Create a box containing the rotated element relative to the original text position
        $container = $ctx->getDocument()->createElement('g');
        $x = $el->getAttribute('x');
        $y = $el->getAttribute('y');
        $container->setAttribute('transform', 'translate(' . $x . ',' . $y . ')');
        $el->removeAttribute('x');
        $el->removeAttribute('y');

        // Create a rotated box containing the text
        $rotate = $ctx->getDocument()->createElement('g');
        $rotate->setAttribute('transform', 'rotate(' . $degrees . ')');
        $rotate->appendChild($el);

        $container->appendChild($rotate);
        return $container;
    }

    /**
     * Render the vertical axis
     *
     * @param RenderContext $ctx    The context to use for rendering
     * @param DOMElement    $group  The DOMElement this axis will be added to
     */
    private function renderVerticalAxis(RenderContext $ctx, DOMElement $group)
    {
        $line = new Line(0, 0, 0, 100);
        $line->setStrokeWidth(2);
        $group->appendChild($line->toSvg($ctx));

        foreach ($this->yUnit as $label => $pos) {
            $pos = 100 - $pos;
            $tick = new Line(0, $pos, -1, $pos);
            $group->appendChild($tick->toSvg($ctx));

            $labelField = new Text(-0.5, $pos+0.5, $label);
            $labelField->setFontSize('1.8em')
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
            $axisLabel = new Text(-8, 50, $this->yLabel);
            $axisLabel->setFontSize('2em')
                ->setAdditionalStyle(Text::ORIENTATION_VERTICAL)
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
     * @return  self            Fluid interface
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
    * @return   self        Fluid interface
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
     * @return  self        Fluid interface
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
     * @return  self        Fluid interface
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
     * @return  self        Fluid interface
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
}
