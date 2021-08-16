<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Dashboard;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

/**
 * A dashboard pane dashlet
 *
 * This is the element displaying a specific view in icinga2web
 *
 */
class Dashlet extends BaseHtmlElement
{
    /** @var string Database table name */
    const TABLE = 'dashlet';

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'container dashlet-sortable'];

    /**
     * The url of this Dashlet
     *
     * @var Url
     */
    private $url;

    /**
     * Not translatable name of this dashlet
     *
     * @var string
     */
    private $name;

    /**
     * The title being displayed on top of the dashlet
     * @var
     */
    private $title;

    /**
     * A user this dashlet belongs to
     *
     * @var string
     */
    private $owner;

    /**
     * The pane containing this dashlet, needed for the 'remove button'
     * @var Pane
     */
    private $pane;

    /**
     * The progress label being used
     *
     * @var string
     */
    private $progressLabel;

    /**
     * Unique identifier of this dashlet
     *
     * @var int
     */
    private $dashletId;

    /**
     * The priority order of this dashlet
     *
     * @var int
     */
    private $order;

    /**
     * Create a new dashlet displaying the given url in the provided pane
     *
     * @param string $title     The title to use for this dashlet
     * @param Url|string $url   The url this dashlet uses for displaying information
     * @param Pane $pane        The pane this Dashlet will be added to
     */
    public function __construct($title, $url, Pane $pane)
    {
        $this->name = $title;
        $this->title = $title;
        $this->pane = $pane;
        $this->url = $url;
    }

    /**
     * Set the identifier of this dashlet
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->dashletId = $id;

        return $this;
    }

    /**
     * Get the unique identifier of this dashlet
     *
     * @return int
     */
    public function getId()
    {
        return $this->dashletId;
    }

    /**
     * Set the name of this dashlet
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
     * Get the name of this dashlet
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the title of this dashlet
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the title of this dashlet
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the owner of this dashlet
     *
     * @param string $user
     *
     * @return $this
     */
    public function setOwner($user)
    {
        $this->owner = $user;

        return $this;
    }

    /**
     * Get the owner of this dashlet
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Get the priority order of this dashlet
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the priority order of this dashlet
     *
     * @param $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the url of this dashlet
     *
     * @return Url
     */
    public function getUrl()
    {
        if (! $this->url instanceof Url) {
            $this->url = Url::fromPath($this->url);
        }

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
        $this->url = $url;

        return $this;
    }

    /**
     * Set the progress label to use
     *
     * @param   string  $label
     *
     * @return  $this
     */
    public function setProgressLabel($label)
    {
        $this->progressLabel = $label;

        return $this;
    }

    /**
     * Get the progress label to use
     *
     * @return  string
     */
    public function getProgressLabe()
    {
        if ($this->progressLabel === null) {
            $this->progressLabel = t('Loading');
        }

        return $this->progressLabel;
    }

    /**
     * Get this dashlet's structure as array
     *
     * @return  array
     */
    public function toArray()
    {
        return [
            'url'   => $this->getUrl()->getRelativeUrl(),
            'title' => $this->getTitle(),
            'name'  => $this->getName(),
            'pane'  => $this->getPane()
        ];
    }

    /**
     * @inheritDoc
     */
    protected function assemble()
    {
        if (! $this->url) {
            $this->add(HtmlElement::create('h1', null, $this->getTitle()));
            $this->add(HtmlElement::create(
                'p',
                ['class' => 'error-message'],
                sprintf(t('Cannot create dashboard dashlet "%s" without valid URL'), $this->getTitle())
            ));
        } else {
            $url = $this->getUrl();
            $url->setParam('showCompact', true);

            $this->addAttributes(['data-icinga-url' => $url]);
            $this->add(new HtmlElement('h1', null, new Link(
                $this->getTitle(),
                $url->getUrlWithout(['showCompact', 'limit'])->getRelativeUrl(),
                [
                    'aria-label'        => $this->getTitle(),
                    'title'             => $this->getTitle(),
                    'data-base-target'  => 'col1'
                ]
            )));

            $this->add(HtmlElement::create(
                'p',
                ['class' => 'progress-label'],
                [
                    $this->getProgressLabe(),
                    HtmlElement::create('span', null, '.'),
                    HtmlElement::create('span', null, '.'),
                    HtmlElement::create('span', null, '.'),
                ]
            ));
        }
    }

    /**
     * Set the pane of this dashlet
     *
     * @param \Icinga\Web\Widget\Dashboard\Pane $pane
     */
    public function setPane(Pane $pane)
    {
        $this->pane = $pane;
    }

    /**
     * Get the pane this dashlet belongs to
     *
     * @return \Icinga\Web\Widget\Dashboard\Pane
     */
    public function getPane()
    {
        return $this->pane;
    }
}
