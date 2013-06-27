<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Web\Url;

/**
 * A dashboard pane component
 *
 * Needs a title and an URL
 * // TODO: Rename to "Dashboardlet"
 *
 */
class Component
{
    protected $url;
    protected $title;

    public function __construct($title, $url)
    {
        $this->title = $title;
        if ($url instanceof Url) {
            $this->url = $url;
        } else {
            $this->url = Url::create($url);
        }
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
            $this->url = Url::create($url);
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
        $ini = $this->iniPair('base_url', $this->url->getScript());
        foreach ($this->url->getParams() as $key => $val) {
            $ini .= $this->iniPair($key, $val);
        }
        return $ini;
    }

    /**
     * Render this components HTML
     */
    public function __toString()
    {
	    $url = clone($this->url);
        $url->addParams(array('view' => 'compact'));
        if (isset($_GET['layout'])) {
            $url->addParams(array('layout' => $_GET['layout']));
        }

        $htm = '<div class="icinga-container dashboard" icingaurl="'
             . $url
             . '" icingatitle="'
             . htmlspecialchars($this->title)
             . '">'
             . "\n"
             . '<h1><a href="'
             . $this->url
             . '">'
             . htmlspecialchars($this->title)
             . "</a></h1>\n"
             . '<noscript><iframe src="'
             . $url->addParams(array('layout' => 'embedded', 'iframe' => 'true'))
             . '" style="height:100%; width:99%" frameborder="no"></iframe></noscript>'
             . "\n</div>\n";
        return $htm;
    }
}

