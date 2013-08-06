<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Util\Dimension;
use Icinga\Web\Url;
use Icinga\Web\Widget\Widget;
use Zend_Config;

/**
 * A dashboard pane component
 *
 * Needs a title and an URL
 *
 */
class Component implements Widget
{
    private $url;
    private $title;
    private $width;
    private $height;

    /**
     * @var Pane
     */
    private $pane;

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

    public function setWidth(Dimension $width = null)
    {
        $this->width = $width;
    }

    public function setHeight(Dimension $height = null)
    {
        $this->height = $height;
    }

    /**
     * Retrieve this components title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Retrieve my url
     *
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set this components URL
     *
     * @param  string|Url $url Component URL
     * @return self
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

    protected function iniPair($key, $val)
    {
        return sprintf(
            "%s = %s\n",
            $key,
            $this->quoteIni($val)
        );
    }

    protected function quoteIni($str)
    {
        return '"' . $str . '"';
    }

    public function toIni()
    {
        $ini = $this->iniPair('url', $this->url->getRelativeUrl());
        foreach ($this->url->getParams() as $key => $val) {
            $ini .= $this->iniPair($key, $val);
        }
        if ($this->height !== null) {
            $ini .= 'height: '.((string) $this->height).'\n';
        }
        if ($this->width !== null) {
            $ini .= 'width: '.((string) $this->width).'\n';
        }
        return $ini;
    }

    public function render(\Zend_View_Abstract $view)
    {
        $url = clone($this->url);
        $url->addParams(array('view' => 'compact'));
        if (isset($_GET['layout'])) {
            $url->addParams(array('layout' => $_GET['layout']));
        }
        $removeUrl = Url::fromPath(
            "/dashboard/removecomponent",
            array(
                "pane" => $this->pane->getName(),
                "component" => $this->getTitle()
            )
        );


        $html = str_replace("{URL}", $url->getAbsoluteUrl(), $this->template);
        $html = str_replace("{REMOVE_URL}", $removeUrl, $html);
        $html = str_replace("{STYLE}", $this->getBoxSizeAsCSS(), $html);
        $html = str_replace("{TITLE}", $view->escape($this->getTitle()), $html);
        return $html;
    }

    private function getBoxSizeAsCSS()
    {
        $style = "";
        if ($this->height) {
            $style .= 'height:'.(string) $this->height.';';
        }
        if ($this->width) {
            $style .= 'width:'.(string) $this->width.';';
        }
    }

    public static function fromIni($title, Zend_Config $config, Pane $pane)
    {
        $height = null;
        $width = null;
        $url = $config->get('url');
        $parameters = $config->toArray();
        unset($parameters["url"]); // otherwise there's an url = parameter in the Url

        if (isset($parameters["height"])) {
            $height = Dimension::fromString($parameters["height"]);
            unset($parameters["height"]);
        }

        if (isset($parameters["width"])) {
            $width = Dimension::fromString($parameters["width"]);
            unset($parameters["width"]);
        }

        $cmp = new Component($title, Url::fromPath($url, $parameters), $pane);
        $cmp->setHeight($height);
        $cmp->setWidth($width);
        return $cmp;
    }
}
