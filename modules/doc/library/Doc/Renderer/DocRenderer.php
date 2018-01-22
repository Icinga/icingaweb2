<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Renderer;

use Exception;
use Icinga\Exception\IcingaException;
use RecursiveIteratorIterator;
use Icinga\Application\Icinga;
use Icinga\Web\View;

/**
 * Base class for toc and section renderer
 */
abstract class DocRenderer extends RecursiveIteratorIterator
{
    /**
     * URL to images
     *
     * @var string
     */
    protected $imageUrl;

    /**
     * URL to replace links with
     *
     * @var string
     */
    protected $url;

    /**
     * Additional URL parameters
     *
     * @var array
     */
    protected $urlParams = array();

    /**
     * View
     *
     * @var View|null
     */
    protected $view;

    /**
     * Get the URL to images
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Set the URL to images
     *
     * @param   string  $imageUrl
     *
     * @return  $this
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = (string) $imageUrl;
        return $this;
    }
    /**
     * Get the URL to replace links with
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the URL to replace links with
     *
     * @param   string  $url
     *
     * @return  $this
     */
    public function setUrl($url)
    {
        $this->url = (string) $url;
        return $this;
    }

    /**
     * Get additional URL parameters
     *
     * @return array
     */
    public function getUrlParams()
    {
        return $this->urlParams;
    }

    /**
     * Set additional URL parameters
     *
     * @param   array   $urlParams
     *
     * @return  $this
     */
    public function setUrlParams(array $urlParams)
    {
        $this->urlParams = array_map(array($this, 'encodeUrlParam'), $urlParams);
        return $this;
    }

    /**
     * Get the view
     *
     * @return View
     */
    public function getView()
    {
        if ($this->view === null) {
            $this->view = Icinga::app()->getViewRenderer()->view;
        }
        return $this->view;
    }

    /**
     * Set the view
     *
     * @param   View    $view
     *
     * @return  $this
     */
    public function setView(View $view)
    {
        $this->view = $view;
        return $this;
    }

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
     * @return  string
     */
    abstract public function render();

    /**
     * Render to HTML
     *
     * @return  string
     * @see     \Icinga\Module\Doc\Renderer::render() For the render method.
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return $e->getMessage() . ': ' . IcingaException::getConfidentialTraceAsString($e);
        }
    }
}
