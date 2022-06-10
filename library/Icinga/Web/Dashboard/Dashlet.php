<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Application\Icinga;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\Common\WidgetState;
use Icinga\Web\Url;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

/**
 * A dashboard pane dashlet
 *
 * This is the new element being used for the Dashlets view
 */
class Dashlet extends BaseDashboard
{
    use WidgetState;

    /** @var string Database table name */
    const TABLE = 'icingaweb_dashlet';

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
     * A flag to identify whether this dashlet widget originates from a module
     *
     * @var bool
     */
    protected $moduleDashlet = false;

    /**
     * The name of the module this dashlet comes from
     *
     * @var string
     */
    protected $module;

    /**
     * Create a new dashlet displaying the given url in the provided pane
     *
     * @param string $name The title to use for this dashlet
     * @param Url|string $url The url this dashlet uses for displaying information
     * @param Pane|null $pane The pane this Dashlet will be added to
     */
    public function __construct(string $name, $url, Pane $pane = null)
    {
        parent::__construct($name);

        $this->pane = $pane;
        $this->url = $url;
    }

    /**
     * Retrieve the dashlets url
     *
     * @return ?Url
     */
    public function getUrl()
    {
        if ($this->url !== null && ! $this->url instanceof Url) {
            if (! Icinga::app()->isCli()) {
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
    public function setUrl($url): self
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
    public function setProgressLabel(string $label): self
    {
        $this->progressLabel = $label;

        return $this;
    }

    /**
     * Return the progress label to use
     *
     * @return  string
     */
    public function getProgressLabel(): string
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
    public function setPane(Pane $pane): self
    {
        $this->pane = $pane;

        return $this;
    }

    /**
     * Get the pane of this dashlet
     *
     * @return ?Pane
     */
    public function getPane()
    {
        return $this->pane;
    }

    /**
     * Get the name of the module which provides this dashlet
     *
     * @return ?string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set the name of the module which provides this dashlet
     *
     * @param string $module
     *
     * @return $this
     */
    public function setModule(string $module): self
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get whether this widget originates from a module
     *
     * @return bool
     */
    public function isModuleDashlet(): bool
    {
        return $this->moduleDashlet;
    }

    /**
     * Set whether this dashlet widget is provided by a module
     *
     * @param bool $moduleDashlet
     *
     * @return $this
     */
    public function setModuleDashlet(bool $moduleDashlet): self
    {
        $this->moduleDashlet = $moduleDashlet;

        return $this;
    }

    /**
     * Generate a html widget for this dashlet
     *
     * @return BaseHtmlElement
     */
    public function getHtml(): BaseHtmlElement
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

            $dashletHtml->setAttribute('data-icinga-url', $url->with('showCompact', true));
            $dashletHtml->addHtml(new HtmlElement('h1', null, new Link(
                t($this->getTitle()),
                $url->without(['limit', 'view'])->getRelativeUrl(),
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

    public function toArray(bool $stringify = true): array
    {
        $pane = $this->getPane();
        return [
            'id'           => $this->getUuid(),
            'pane'         => ! $stringify ? $pane : ($pane ? $pane->getName() : null),
            'name'         => $this->getName(),
            'url'          => $this->getUrl()->getRelativeUrl(),
            'label'        => $this->getTitle(),
            'priority'     => $this->getPriority(),
            'description'  => $this->getDescription()
        ];
    }
}
