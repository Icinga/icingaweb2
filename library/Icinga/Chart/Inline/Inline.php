<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Inline;

/**
 * Class to render and inline chart directly from the request params.
 *
 * When rendering huge amounts of inline charts it is too expensive
 * to bootstrap the complete application for ever single chart and
 * we need to be able render Charts in a compact environment without
 * the other Icinga classes.
 *
 * Class Inline
 * @package Icinga\Chart\Inline
 */
class Inline
{

    /**
     * The data displayed in this chart
     *
     * @var array
     */
    protected $data;

    /**
     * The colors used to display this chart
     *
     * @var array
     */
    protected $colors = array(
        '#00FF00', // OK
        '#FFFF00', // Warning
        '#FF0000', // Critical
        '#E066FF'  // Unreachable
    );

    /**
     * The labels displayed on this chart
     *
     * @var array
     */
    protected $labels = array();

    /**
     * The height in percent
     *
     * @var int
     */
    protected $height = 100;

    /**
     * The width in percent
     *
     * @var int
     */
    protected $width = 100;

    protected function sanitizeStringArray(array $arr)
    {
        $sanitized = array();
        foreach ($arr as $key => $value) {
            $sanitized[$key] = htmlspecialchars($value);
        }
        return $sanitized;
    }

    /**
     * Populate the properties from the current request.
     */
    public function initFromRequest()
    {
        $this->data = explode(',', $_GET['data']);
        foreach ($this->data as $key => $value) {
            $this->data[$key] = (int)$value;
        }
        for ($i = 0; $i < count($this->data); $i++) {
            $this->labels[] = '';
        }

        if (array_key_exists('colors', $_GET)) {
            $this->colors = $this->sanitizeStringArray(explode(',', $_GET['colors']));
        }
        while (count($this->colors) < count($this->data)) {
            $this->colors[] = '#FEFEFE';
        }

        if (array_key_exists('width', $_GET)) {
            $this->width = (int)$_GET['width'];
        }
        if (array_key_exists('height', $_GET)) {
            $this->height = (int)$_GET['height'];
        }
    }
}
