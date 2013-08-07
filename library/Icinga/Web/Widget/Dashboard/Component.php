<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Util\Dimension;
use Icinga\Web\Url;
use Icinga\Web\Widget\Widget;
use Zend_Config;

/**
 * A dashboard pane component
 *
 * This is the element displaying a specific view in icinga2web
 *
 */
class Component implements Widget
{
    /**
     * The url of this Component
     *
     * @var \Icinga\Web\Url
     */
    private $url;

    /**
     * The title being displayed on top of the component
     * @var
     */
    private $title;

    /**
     * The width of the component, if set
     *
     * @var Dimension|null
     */
    private $width = null;

    /**
     * The height of the component, if set
     *
     * @var Dimension|null
     */
    private $height = null;

    /**
     * The pane containing this component, needed for the 'remove button'
     * @var Pane
     */
    private $pane;

    /**
     * The template string used for rendering this widget
     *
     * @var string
     */
    private $template =<<<'EOD'

    <div class="icinga-container dashboard" icingatitle="{TITLE}" style="{DIMENSION}">
        <a href="{URL}"> {TITLE}</a>
        <a  class="btn btn-danger btn-mini pull-right" href="{REMOVE_URL}" style="color:black">X</a>

        <div class="icinga-container" icingaurl="{URL}">
        <noscript>
            <iframe src="{URL}" style="height:100%; width:99%" frameborder="no"></iframe>
        </noscript>
        </div>
    </div>
EOD;

    /**
     * Create a new component displaying the given url in the provided pane
     *
     * @param string $title     The title to use for this component
     * @param Url|string $url   The url this component uses for displaying information
     * @param Pane $pane        The pane this Component will be added to
     */
    public function __construct($title, $url, Pane $pane)
    {
        $this->title = $title;
        $this->pane = $pane;
        if ($url instanceof Url) {
            $this->url = $url;
        } else {
            $this->url = Url::fromPath($url);
        }
    }

    /**
     * Set the with for this component or use the default width if null is provided
     *
     * @param Dimension|null $width     The width to use or null to use the default width
     */
    public function setWidth(Dimension $width = null)
    {
        $this->width = $width;
    }

    /**
     * Set the with for this component or use the default height if null is provided
     *
     * @param Dimension|null $height     The height to use or null to use the default height
     */
    public function setHeight(Dimension $height = null)
    {
        $this->height = $height;
    }

    /**
     * Retrieve the components title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Retrieve the components url
     *
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the components URL
     *
     * @param  string|Url $url  The url to use, either as an Url object or as a path
     *
     * @return $this
     */
    public function setUrl($url)
    {
        if ($url instanceof Url) {
            $this->url = $url;
        } else {
            $this->url = Url::fromPath($url);
        }
        return $this;
    }

    /**
     * Return this component in a suitable format and encoding for ini files
     *
     * @return string
     */
    public function toIni()
    {
        $ini =  'url = "'.$this->url->getRelativeUrl().'"'.PHP_EOL;
        foreach ($this->url->getParams() as $key => $val) {
            $ini .= $key.' = "'.$val.'"'.PHP_EOL;
        }
        if ($this->height !== null) {
            $ini .= 'height = "'.((string) $this->height).'"'.PHP_EOL;
        }
        if ($this->width !== null) {
            $ini .= 'width = "'.((string) $this->width).'"'.PHP_EOL;
        }
        return $ini;
    }

    /**
     * @see Widget::render()
     */
    public function render(\Zend_View_Abstract $view)
    {
        $url = clone($this->url);
        $url->addParams(array('view' => 'compact'));

        $removeUrl = Url::fromPath(
            '/dashboard/removecomponent',
            array(
                'pane' => $this->pane->getName(),
                'component' => $this->getTitle()
            )
        );

        $html = str_replace('{URL}', $url->getAbsoluteUrl(), $this->template);
        $html = str_replace('{REMOVE_URL}', $removeUrl, $html);
        $html = str_replace('{DIMENSION}', $this->getBoxSizeAsCSS(), $html);
        $html = str_replace('{TITLE}', $view->escape($this->getTitle()), $html);
        return $html;
    }

    /**
     * Return the height and width deifnition (if given) in CSS format
     *
     * @return string
     */
    private function getBoxSizeAsCSS()
    {
        $style = '';
        if ($this->height) {
            $style .= 'height:'.(string) $this->height.';';
        }
        if ($this->width) {
            $style .= 'width:'.(string) $this->width.';';
        }
        return $style;
    }

    /**
     * Create a @see Component instance from the given Zend config, using the provided title
     *
     * @param $title                    The title for this component
     * @param Zend_Config $config       The configuration defining url, parameters, height, width, etc.
     * @param Pane $pane                The pane this component belongs to
     *
     * @return Component                A newly created Component for use in the Dashboard
     */
    public static function fromIni($title, Zend_Config $config, Pane $pane)
    {
        $height = null;
        $width = null;
        $url = $config->get('url');
        $parameters = $config->toArray();
        unset($parameters['url']); // otherwise there's an url = parameter in the Url

        if (isset($parameters['height'])) {
            $height = Dimension::fromString($parameters['height']);
            unset($parameters['height']);
        }

        if (isset($parameters['width'])) {
            $width = Dimension::fromString($parameters['width']);
            unset($parameters['width']);
        }

        $cmp = new Component($title, Url::fromPath($url, $parameters), $pane);
        $cmp->setHeight($height);
        $cmp->setWidth($width);
        return $cmp;
    }
}
