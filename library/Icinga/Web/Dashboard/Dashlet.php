<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Application\Icinga;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\Common\ModuleDashlet;
use Icinga\Web\Request;
use Icinga\Web\Url;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

/**
 * A dashboard pane dashlet
 *
 * This is the new element being used for the Dashlets view
 */
class Dashlet extends BaseDashboard
{
    use ModuleDashlet;

    /** @var string Database table name */
    const TABLE = 'dashlet';

    /**
     * The url of this Dashlet
     *
     * @var Url|null
     */
    protected $url;

    /**
     * The pane this dashlet belongs to
     *
     * @var Pane
     */
    protected $pane;

    /**
     * The progress label being used
     *
     * @var string
     */
    protected $progressLabel;

    /**
     * Create a new dashlet displaying the given url in the provided pane
     *
     * @param string $name The title to use for this dashlet
     * @param Url|string $url The url this dashlet uses for displaying information
     * @param Pane|null $pane The pane this Dashlet will be added to
     */
    public function __construct($name, $url, Pane $pane = null)
    {
        parent::__construct($name);

        $this->pane = $pane;
        $this->url = $url;
    }

    /**
     * Retrieve the dashlets url
     *
     * @return Url|null
     */
    public function getUrl()
    {
        if ($this->url !== null && ! $this->url instanceof Url) {
            if (Icinga::app()->isCli()) {
                $this->url = Url::fromPath($this->url, [], new Request());
            } else {
                $this->url = Url::fromPath($this->url);
            }
        }

        return $this->url;
    }

    /**
     * Set the dashlets URL
     *
     * @param string|Url $url The url to use, either as an Url object or as a path
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the progress label to use
     *
     * @param string $label
     *
     * @return  $this
     */
    public function setProgressLabel($label)
    {
        $this->progressLabel = $label;

        return $this;
    }

    /**
     * Return the progress label to use
     *
     * @return  string
     */
    public function getProgressLabel()
    {
        if ($this->progressLabel === null) {
            return $this->progressLabel = t('Loading');
        }

        return $this->progressLabel;
    }

    /**
     * Set the Pane of this dashlet
     *
     * @param Pane $pane
     *
     * @return Dashlet
     */
    public function setPane(Pane $pane)
    {
        $this->pane = $pane;

        return $this;
    }

    /**
     * Get the pane of this dashlet
     *
     * @return Pane
     */
    public function getPane()
    {
        return $this->pane;
    }

    /**
     * Generate a html widget for this dashlet
     *
     * @return HtmlElement
     */
    public function getHtml()
    {
        $dashletHtml = HtmlElement::create('div', ['class' => 'container']);
        if (! $this->getUrl()) {
            $dashletHtml->addHtml(HtmlElement::create('h1', null, t($this->getTitle())));
            $dashletHtml->addHtml(HtmlElement::create(
                'p',
                ['class' => 'error-message'],
                sprintf(t('Cannot create dashboard dashlet "%s" without valid URL'), t($this->getTitle()))
            ));
        } else {
            $url = $this->getUrl();
            $url->setParam('showCompact', true);

            $dashletHtml->setAttribute('data-icinga-url', $url);
            $dashletHtml->setAttribute('data-icinga-dashlet', $this->getName());

            $dashletHtml->addHtml(new HtmlElement('h1', null, new Link(
                t($this->getTitle()),
                $url->getUrlWithout(['showCompact', 'limit'])->getRelativeUrl(),
                [
                    'aria-label'       => t($this->getTitle()),
                    'title'            => t($this->getTitle()),
                    'data-base-target' => 'col1'
                ]
            )));

            $dashletHtml->addHtml(HtmlElement::create(
                'p',
                ['class' => 'progress-label'],
                [
                    $this->getProgressLabel(),
                    HtmlElement::create('span', null, '.'),
                    HtmlElement::create('span', null, '.'),
                    HtmlElement::create('span', null, '.'),
                ]
            ));
        }

        return $dashletHtml;
    }

    public function toArray($stringify = true)
    {
        $pane = $this->getPane();
        return [
            'id'    => $this->getUuid(),
            'pane'  => ! $stringify ? $pane : ($pane ? $pane->getName() : null),
            'name'  => $this->getName(),
            'url'   => $this->getUrl()->getRelativeUrl(),
            'label' => $this->getTitle(),
            'order' => $this->getPriority(),
        ];
    }
}
