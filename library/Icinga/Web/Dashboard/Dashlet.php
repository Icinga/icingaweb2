<?php
/* Icinga Web 2 | (c) 2021 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Dashboard;

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
    use UserWidget;

    /** @var string Database table name */
    const TABLE = 'dashlet';

    /** @var string Database overriding table name */
    const OVERRIDING_TABLE = 'dashlet_override';

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'container dashlet-sortable'];

    /**
     * The url of this Dashlet
     *
     * @var Url|null
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
     * The progress label being used
     *
     * @var string
     */
    private $progressLabel;

    /**
     * Unique identifier of this dashlet
     *
     * @var string
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
     * @param Pane|null $pane   The pane this Dashlet will be added to
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
     * @return Dashlet
     */
    public function setDashletId($id)
    {
        $this->dashletId = $id;

        return $this;
    }

    /**
     * Get the unique identifier of this dashlet
     *
     * @return string
     */
    public function getDashletId()
    {
        return $this->dashletId;
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
     * Retrieve the dashlets url
     *
     * @return Url|null
     */
    public function getUrl()
    {
        if ($this->url !== null && ! $this->url instanceof Url) {
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
     * Set the disabled property
     *
     * @param boolean $disabled
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
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
     * Return the progress label to use
     *
     * @return  string
     */
    public function getProgressLabe()
    {
        if ($this->progressLabel === null) {
            return $this->progressLabel = t('Loading');
        }

        return $this->progressLabel;
    }

    /**
     * Return this dashlet's structure as array
     *
     * @return  array
     */
    public function toArray()
    {
        $array = array(
            'url'   => $this->getUrl()->getRelativeUrl(),
            'title' => $this->getTitle()
        );
        if ($this->getDisabled() === true) {
            $array['disabled'] = 1;
        }

        return $array;
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

            $pane = $this->getPane();
            $this->addAttributes([
                'data-icinga-url'       => $url,
                'data-icinga-dashlets'  => $pane->getHome()->getName() . $pane->getName() . $this->getName(),
            ]);
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
     * Set the Pane of this dashlet
     *
     * @param \Icinga\Web\Dashboard\Pane $pane
     */
    public function setPane(Pane $pane)
    {
        $this->pane = $pane;
    }

    /**
     * Get the pane of this dashlet
     *
     * @return \Icinga\Web\Dashboard\Pane
     */
    public function getPane()
    {
        return $this->pane;
    }
}
