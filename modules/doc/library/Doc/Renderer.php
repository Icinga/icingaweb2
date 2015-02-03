<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Doc;

use RecursiveIteratorIterator;
use Zend_View_Helper_Url;
use Icinga\Web\View;

/**
 * Base class for toc and section renderer
 */
abstract class Renderer extends RecursiveIteratorIterator
{
    /**
     * Encode an anchor identifier
     *
     * @param   string $anchor
     *
     * @return  string
     */
    public static function encodeAnchor($anchor)
    {
        return rawurlencode($anchor);
    }

    /**
     * Decode an anchor identifier
     *
     * @param   string $anchor
     *
     * @return  string
     */
    public static function decodeAnchor($anchor)
    {
        return rawurldecode($anchor);
    }

    /**
     * Encode a URL parameter
     *
     * @param   string $param
     *
     * @return  string
     */
    public static function encodeUrlParam($param)
    {
        return str_replace(array('%2F','%5C'), array('%252F','%255C'), rawurlencode($param));
    }

    /**
     * Decode a URL parameter
     *
     * @param   string $param
     *
     * @return  string
     */
    public static function decodeUrlParam($param)
    {
        return str_replace(array('%2F', '%5C'), array('/', '\\'), $param);
    }

    /**
     * Render to HTML
     *
     * Meant to be overwritten by concrete classes.
     *
     * @param   View                    $view
     * @param   Zend_View_Helper_Url    $zendUrlHelper
     *
     * @return  string
     */
    abstract public function render(View $view, Zend_View_Helper_Url $zendUrlHelper);
}
