<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Application\Icinga;
use Icinga\Common\DataExtractor;
use Icinga\Web\Dashboard\Common\DisableWidget;
use Icinga\Web\Dashboard\Common\ModuleDashlet;
use Icinga\Web\Dashboard\Common\OrderWidget;
use Icinga\Web\Request;
use Icinga\Web\Url;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

/**
 * A dashboard pane dashlet
 *
 * This is the new element being used for the Dashlets view
 */
class Dashlet extends BaseHtmlElement
{
    use DisableWidget;
    use OrderWidget;
    use ModuleDashlet;
    use DataExtractor;

    /** @var string Database table name */
    const TABLE = 'dashlet';

    protected $tag = 'div';

    protected $defaultAttributes = [
        'class'     => 'container widget-sortable',
        'draggable' => 'true'
    ];

    /**
     * The url of this Dashlet
     *
     * @var Url|null
     */
    protected $url;

    /**
     * Not translatable name of this dashlet
     *
     * @var string
     */
    protected $name;

    /**
     * The title being displayed on top of the dashlet
     * @var
     */
    protected $title;

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
     * Unique identifier of this dashlet
     *
     * @var string
     */
    protected $uuid;

    /**
     * The dashlet's description
     *
     * @var string
     */
    protected $description;

    /**
     * Create a new dashlet displaying the given url in the provided pane
     *
     * @param string $title The title to use for this dashlet
     * @param Url|string $url The url this dashlet uses for displaying information
     * @param Pane|null $pane The pane this Dashlet will be added to
     */
    public function __construct($title, $url, Pane $pane = null)
    {
        $this->name = $title;
        $this->title = $title;
        $this->pane = $pane;
        $this->url = $url;
    }

    /**
     * Set the identifier of this dashlet
     *
     * @param string $id
     *
     * @return $this
     */
    public function setUuid($id)
    {
        $this->uuid = $id;

        return $this;
    }

    /**
     * Get the unique identifier of this dashlet
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Setter for this name
     *
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Getter for this name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retrieve the dashlets title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title !== null ? $this->title : $this->getName();
    }

    /**
     * Set the title of this dashlet
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
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
     * Get the dashlet's description
     *
     * @return  string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the dashlet's description
     *
     * @param string $description
     *
     * @return  $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
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

    protected function assemble()
    {
        if (! $this->getUrl()) {
            $this->addHtml(HtmlElement::create('h1', null, t($this->getTitle())));
            $this->addHtml(HtmlElement::create(
                'p',
                ['class' => 'error-message'],
                sprintf(t('Cannot create dashboard dashlet "%s" without valid URL'), t($this->getTitle()))
            ));
        } else {
            $url = $this->getUrl();
            $url->setParam('showCompact', true);

            $this->setAttribute('data-icinga-url', $url);
            $this->setAttribute('data-icinga-dashlet', $this->getName());

            $this->addHtml(new HtmlElement('h1', null, new Link(
                t($this->getTitle()),
                $url->getUrlWithout(['showCompact', 'limit'])->getRelativeUrl(),
                [
                    'aria-label'       => t($this->getTitle()),
                    'title'            => t($this->getTitle()),
                    'data-base-target' => 'col1'
                ]
            )));

            $this->addHtml(HtmlElement::create(
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
    }

    public function toArray()
    {
        return [
            'id'       => $this->getUuid(),
            'pane'     => $this->getPane() ? $this->getPane()->getName() : null,
            'name'     => $this->getName(),
            'url'      => $this->getUrl()->getRelativeUrl(),
            'label'    => $this->getTitle(),
            'order'    => $this->getPriority(),
            'disabled' => (int) $this->isDisabled(),
        ];
    }
}
