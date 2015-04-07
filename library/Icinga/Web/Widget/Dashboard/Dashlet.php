<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Dashboard;

use Zend_Form_Element_Button;
use Icinga\Web\Form;
use Icinga\Web\Url;
use Icinga\Data\ConfigObject;
use Icinga\Exception\IcingaException;

/**
 * A dashboard pane dashlet
 *
 * This is the element displaying a specific view in icinga2web
 *
 */
class Dashlet extends UserWidget
{
    /**
     * The url of this Dashlet
     *
     * @var \Icinga\Web\Url
     */
    private $url;

    /**
     * The title being displayed on top of the dashlet
     * @var
     */
    private $title;

    /**
     * The pane containing this dashlet, needed for the 'remove button'
     * @var Pane
     */
    private $pane;

    /**
     * The disabled option is used to "delete" default dashlets provided by modules
     *
     * @var bool
     */
    private $disabled = false;

    /**
     * The template string used for rendering this widget
     *
     * @var string
     */
    private $template =<<<'EOD'

    <div class="container" data-icinga-url="{URL}">
        <h1><a href="{FULL_URL}" aria-label="{TOOLTIP}" title="{TOOLTIP}" data-base-target="col1">{TITLE}</a></h1>
        <noscript>
            <iframe
                src="{IFRAME_URL}"
                style="height:100%; width:99%"
                frameborder="no"
                title="{TITLE_PREFIX}{TITLE}">
            </iframe>
        </noscript>
    </div>
EOD;

    /**
     * Create a new dashlet displaying the given url in the provided pane
     *
     * @param string $title     The title to use for this dashlet
     * @param Url|string $url   The url this dashlet uses for displaying information
     * @param Pane $pane        The pane this Dashlet will be added to
     */
    public function __construct($title, $url, Pane $pane)
    {
        $this->title = $title;
        $this->pane = $pane;
        if ($url instanceof Url) {
            $this->url = $url;
        } elseif ($url) {
            $this->url = Url::fromPath($url);
        } else {
            throw new IcingaException(
                'Cannot create dashboard dashlet "%s" without valid URL',
                $title
            );
        }
    }

    /**
     * Retrieve the dashlets title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Retrieve the dashlets url
     *
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the dashlets URL
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
     * Set the disabled property
     *
     * @param boolean $disabled
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * Get the disabled property
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Return this dashlet's structure as array
     *
     * @return  array
     */
    public function toArray()
    {
        $array = array(
            'url'   => $this->url->getRelativeUrl(),
            'title' => $this->getTitle()
        );
        if ($this->getDisabled() === true) {
            $array['disabled'] = 1;
        }
        return $array;
    }

    /**
     * @see Widget::render()
     */
    public function render()
    {
        if ($this->disabled === true) {
            return '';
        }

        $view = $this->view();
        $url = clone($this->url);
        $url->setParam('view', 'compact');
        $iframeUrl = clone($url);
        $iframeUrl->setParam('isIframe');

        $searchTokens = array(
            '{URL}',
            '{IFRAME_URL}',
            '{FULL_URL}',
            '{TOOLTIP}',
            '{TITLE}',
            '{TITLE_PREFIX}'
        );

        $replaceTokens = array(
            $url,
            $iframeUrl,
            $url->getUrlWithout(array('view', 'limit')),
            sprintf($view->translate('Show %s', 'dashboard.dashlet.tooltip'), $view->escape($this->getTitle())),
            $view->escape($this->getTitle()),
            $view->translate('Dashlet') . ': '
        );

        return str_replace($searchTokens, $replaceTokens, $this->template);
    }

    /**
     * Create a @see Dashlet instance from the given Zend config, using the provided title
     *
     * @param $title                The title for this dashlet
     * @param ConfigObject $config  The configuration defining url, parameters, height, width, etc.
     * @param Pane $pane            The pane this dashlet belongs to
     *
     * @return Dashlet        A newly created Dashlet for use in the Dashboard
     */
    public static function fromIni($title, ConfigObject $config, Pane $pane)
    {
        $height = null;
        $width = null;
        $url = $config->get('url');
        $parameters = $config->toArray();
        unset($parameters['url']); // otherwise there's an url = parameter in the Url

        $cmp = new Dashlet($title, Url::fromPath($url, $parameters), $pane);
        return $cmp;
    }

    /**
     * @param \Icinga\Web\Widget\Dashboard\Pane $pane
     */
    public function setPane(Pane $pane)
    {
        $this->pane = $pane;
    }

    /**
     * @return \Icinga\Web\Widget\Dashboard\Pane
     */
    public function getPane()
    {
        return $this->pane;
    }
}
