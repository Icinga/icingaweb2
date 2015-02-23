<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Graph;

/**
 * A tooltip that stores and aggregates information about displayed data
 * points of a graph and replaces them in a format string to render the description
 * for specific data points of the graph.
 *
 * When render() is called, placeholders for the keys for each data entry will be replaced by
 * the current value of this data set and the formatted string will be returned.
 * The content of the replaced keys can change for each data set and depends on how the data
 * is passed to this class. There are several types of properties:
 *
 * <ul>
 *  <li>Global properties</li>:     Key-value pairs that stay the same every time render is called, and are
 *                                  passed to an instance in the constructor.
 *  <li>Aggregated properties</li>: Global properties that are created automatically from
 *                                  all attached data points.
 *  <li>Local properties</li>:      Key-value pairs that only apply to a single data point and
 *                                  are passed to the render-function.
 * </ul>
 */
class Tooltip
{
    /**
     * The default format string used
     * when no other format is specified
     *
     * @var string
     */
    private $defaultFormat;

    /**
     * All aggregated points
     *
     * @var array
     */
    private $points = array();

    /**
     * Contains all static replacements
     *
     * @var array
     */
    private $data = array(
        'sum' => 0
    );

    /**
     * Used to format the displayed tooltip.
     *
     * @var string
     */
    protected $tooltipFormat;

    /**
     * Create a new tooltip with the specified default format string
     *
     * Allows you to set the global data for this tooltip, that is displayed every
     * time render is called.
     *
     * @param array     $data   Map of global properties
     * @param string    $format The default format string
     */
    public function __construct (
        $data = array(),
        $format = '<b>{title}</b>: {value} {label}'
    ) {
        $this->data = array_merge($this->data, $data);
        $this->defaultFormat = $format;
    }

    /**
     * Add a single data point to update the aggregated properties for this tooltip
     *
     * @param $point  array   Contains the (x,y) values of the data set
     */
    public function addDataPoint($point)
    {
        // set x-value
        if (!isset($this->data['title'])) {
            $this->data['title'] = $point[0];
        }

        // aggregate y-values
        $y = (int)$point[1];
        if (isset($point[2])) {
            // load original value in case value already aggregated
            $y = (int)$point[2];
        }

        if (!isset($this->data['min']) || $this->data['min'] > $y) {
            $this->data['min'] = $y;
        }
        if (!isset($this->data['max']) || $this->data['max'] < $y) {
            $this->data['max'] = $y;
        }
        $this->data['sum'] += $y;
        $this->points[] = $y;
    }

    /**
     * Format the tooltip for a certain data point
     *
     * @param array     $order      Which data set to render
     * @param array     $data       The local data for this tooltip
     * @param string    $format     Use a custom format string for this data set
     *
     * @return mixed|string The tooltip value
     */
    public function render($order, $data = array(), $format = null)
    {
        if (isset($format)) {
            $str = $format;
        } else {
            $str = $this->defaultFormat;
        }
        $data['value'] = $this->points[$order];
        foreach (array_merge($this->data, $data) as $key => $value) {
            $str = str_replace('{' . $key . '}', $value, $str);
        }
        return $str;
    }

    /**
     * Format the tooltip for a certain data point but remove all
     * occurring html tags
     *
     * This is useful for rendering clean tooltips on client without JavaScript
     *
     * @param array     $order      Which data set to render
     * @param array     $data       The local data for this tooltip
     * @param string    $format     Use a custom format string for this data set
     *
     * @return mixed|string The tooltip value, without any HTML tags
     */
    public function renderNoHtml($order, $data, $format)
    {
        return strip_tags($this->render($order, $data, $format));
    }
}
