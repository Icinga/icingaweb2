<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart;

use DOMElement;
use Icinga\Chart\Chart;
use Icinga\Chart\Primitive\Canvas;
use Icinga\Chart\Primitive\PieSlice;
use Icinga\Chart\Primitive\RawElement;
use Icinga\Chart\Primitive\Rect;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Render\LayoutBox;

/**
 * Graphing component for rendering Pie Charts.
 *
 * See the graphs.md documentation for further information about how to use this component
 */
class PieChart extends Chart
{
    /**
     * Stack multiple pies
     */
    const STACKED = "stacked";

    /**
     * Draw multiple pies beneath each other
     */
    const ROW = "row";

    /**
     * The drawing stack containing all pie definitions in the order they will be drawn
     *
     * @var array
     */
    private $pies = array();

    /**
     * The composition type currently used
     *
     * @var string
     */
    private $type = PieChart::STACKED;

    /**
     * Disable drawing of captions when set true
     *
     * @var bool
     */
    private $noCaption = false;

    public function __construct()
    {
        $this->title = t('Pie Chart');
        $this->description = t('Contains data in a pie chart.');
        parent::__construct();
    }

    /**
     * Test if the given pies have the correct format
     *
     * @return bool True when the given pies are correct, otherwise false
     */
    public function isValidDataFormat()
    {
        foreach ($this->pies as $pie) {
            if (!isset($pie['data']) || !is_array($pie['data'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Create renderer and normalize the dataset to represent percentage information
     */
    protected function build()
    {
        $this->renderer = new SVGRenderer(($this->type === self::STACKED) ? 1 : count($this->pies), 1);
        foreach ($this->pies as &$pie) {
            $this->normalizeDataSet($pie);
        }
    }

    /**
     * Normalize the given dataset to represent percentage information instead of absolute valuess
     *
     * @param array $pie The pie definition given in the drawPie call
     */
    private function normalizeDataSet(&$pie)
    {
        $total = array_sum($pie['data']);
        if ($total === 100) {
            return;
        }
        if ($total == 0) {
            return;
        }
        foreach ($pie['data'] as &$slice) {
            $slice = $slice/$total * 100;
        }
    }

    /**
     * Draw an arbitrary number of pies in this chart
     *
     * @param   array $dataSet,...  The pie definition, see graphs.md for further details concerning the format
     *
     * @return  $this                Fluent interface
     */
    public function drawPie(array $dataSet)
    {
        $dataSets = func_get_args();
        $this->pies += $dataSets;
        foreach ($dataSets as $dataSet) {
            $this->legend->addDataset($dataSet);
        }
        return $this;
    }

    /**
     * Return the SVG representation of this graph
     *
     * @param RenderContext $ctx    The context to use for drawings
     *
     * @return DOMElement           The SVG representation of this graph
     */
    public function toSvg(RenderContext $ctx)
    {
        $labelBox = $ctx->getDocument()->createElement('g');
        if (!$this->noCaption) {
            // Scale SVG to make room for captions
            $outerBox = new Canvas('outerGraph', new LayoutBox(33, -5, 40, 40));
            $innerBox = new Canvas('graph', new LayoutBox(0, 0, 100, 100));
            $innerBox->getLayout()->setPadding(10, 10, 10, 10);
        } else {
            $outerBox = new Canvas('outerGraph', new LayoutBox(1.5, -10, 124, 124));
            $innerBox = new Canvas('graph', new LayoutBox(0, 0, 100, 100));
            $innerBox->getLayout()->setPadding(0, 0, 0, 0);
        }
        $this->createContentClipBox($innerBox);
        $this->renderPies($innerBox, $labelBox);
        $innerBox->addElement(new RawElement($labelBox));
        $outerBox->addElement($innerBox);

        return $outerBox->toSvg($ctx);
    }

    /**
     * Render the pies in the draw stack using the selected algorithm for composition
     *
     * @param Canvas $innerBox      The canvas to use for inserting the pies
     * @param DOMElement $labelBox  The DOM element to add the labels to (so they can't be overlapped by pie elements)
     */
    private function renderPies(Canvas $innerBox, DOMElement $labelBox)
    {
        if ($this->type === self::STACKED) {
            $this->renderStackedPie($innerBox, $labelBox);
        } else {
            $this->renderPieRow($innerBox, $labelBox);
        }
    }

    /**
     * Return the color to be used for the given pie slice
     *
     * @param array $pie    The pie configuration as provided in the drawPie call
     * @param int $dataIdx  The index of the pie slice in the pie configuration
     *
     * @return string       The hex color string to use for the pie slice
     */
    private function getColorForPieSlice(array $pie, $dataIdx)
    {
        if (isset($pie['colors']) && is_array($pie['colors']) && isset($pie['colors'][$dataIdx])) {
            return $pie['colors'][$dataIdx];
        }
        $type = Palette::NEUTRAL;
        if (isset($pie['palette']) && is_array($pie['palette']) && isset($pie['palette'][$dataIdx])) {
            $type = $pie['palette'][$dataIdx];
        }
        return $this->palette->getNext($type);
    }

    /**
     * Render a row of pies
     *
     * @param Canvas $innerBox      The canvas to insert the pies to
     * @param DOMElement $labelBox  The DOMElement to use for adding label elements
     */
    private function renderPieRow(Canvas $innerBox, DOMElement $labelBox)
    {
        $radius = 50 / count($this->pies);
        $x = $radius;
        foreach ($this->pies as $pie) {
            $labelPos = 0;
            $lastRadius = 0;

            foreach ($pie['data'] as $idx => $dataset) {
                $slice = new PieSlice($radius, $dataset, $lastRadius);
                $slice->setX($x)
                    ->setStrokeColor('#000')
                    ->setStrokeWidth(1)
                    ->setY(50)
                    ->setFill($this->getColorForPieSlice($pie, $idx));
                $innerBox->addElement($slice);
                // add caption if not disabled
                if (!$this->noCaption && isset($pie['labels'])) {
                    $slice->setCaption($pie['labels'][$labelPos++])
                        ->setLabelGroup($labelBox);

                }
                $lastRadius += $dataset;
            }
            // shift right for next pie
            $x += $radius*2;
        }
    }

    /**
     * Render pies in a stacked way so one pie is nested in the previous pie
     *
     * @param Canvas $innerBox      The canvas to insert the pie to
     * @param DOMElement $labelBox  The DOMElement to use for adding label elements
     */
    private function renderStackedPie(Canvas $innerBox, DOMElement $labelBox)
    {
        $radius = 40;
        $minRadius = 20;
        if (count($this->pies) == 0) {
            return;
        }
        $shrinkStep = ($radius - $minRadius) / count($this->pies);
        $x = $radius;

        for ($i = 0; $i < count($this->pies); $i++) {
            $pie = $this->pies[$i];
            // the offset for the caption path, outer caption indicator shouldn't point
            // to the middle of the slice as there will be another pie
            $offset = isset($this->pies[$i+1]) ? $radius - $shrinkStep : 0;
            $labelPos = 0;
            $lastRadius = 0;
            foreach ($pie['data'] as $idx => $dataset) {
                $color = $this->getColorForPieSlice($pie, $idx);
                if ($dataset == 0) {
                    $labelPos++;
                    continue;
                }
                $slice = new PieSlice($radius, $dataset, $lastRadius);
                $slice->setY(50)
                    ->setX($x)
                    ->setStrokeColor('#000')
                    ->setStrokeWidth(1)
                    ->setFill($color)
                    ->setLabelGroup($labelBox);

                if (!$this->noCaption && isset($pie['labels'])) {
                    $slice->setCaption($pie['labels'][$labelPos++])
                        ->setCaptionOffset($offset)
                        ->setOuterCaptionBound(50);
                }
                $innerBox->addElement($slice);
                $lastRadius += $dataset;
            }
            // shrinken the next pie
            $radius -= $shrinkStep;
        }
    }

    /**
     * Set the composition type of this PieChart
     *
     * @param string $type  Either self::STACKED or self::ROW
     *
     * @return $this         Fluent interface
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Hide the caption from this PieChart
     *
     * @return $this         Fluent interface
     */
    public function disableLegend()
    {
        $this->noCaption = true;
        return $this;
    }

    /**
     * Create the content for this PieChart
     *
     * @param Canvas $innerBox      The innerbox to add the clip mask to
     */
    private function createContentClipBox(Canvas $innerBox)
    {
        $clipBox = new Canvas('clip', new LayoutBox(0, 0, 100, 100));
        $clipBox->toClipPath();
        $innerBox->addElement($clipBox);
        $rect = new Rect(0.1, 0, 100, 99.9);
        $clipBox->addElement($rect);
    }
}

