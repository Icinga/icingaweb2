<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Dashboard\Common\DashboardManager;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Form;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Tabs;

/**
 * Dashboards display multiple views on a single page
 *
 * The terminology is as follows:
 * - Dashlet:           A single view showing a specific url
 * - Pane:              Aggregates one or more dashlets on one page, displays its title as a tab
 * - Dashboard/Home:    Shows all panes belonging to this home
 *
 */
class Dashboard extends BaseHtmlElement
{
    use DashboardManager;

    /**
     * Base path of our new dashboards controller
     *
     * @var string
     */
    const BASE_ROUTE = 'dashboards';

    /**
     * System dashboards are provided by the modules in PHP code
     * and are available to all users
     *
     * @var string
     */
    const SYSTEM = 'system';

    /**
     * Public dashboards are created by authorized users and are available
     * to specific users, groups, roles or everybody
     *
     * @var string
     */
    const PUBLIC_DS = 'public';

    /**
     * Private dashboards are created by any user and are only
     * available to this user
     *
     * @var string
     */
    const PRIVATE_DS = 'private';

    /**
     * Shared dashboards are available to users who have accepted a share or who
     * have been assigned the dashboard by their Admin
     *
     * @var string
     */
    const SHARED = 'shared';

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'dashboard content'];

    /**
     * The @see Tabs object for displaying displayable panes
     *
     * @var Tabs
     */
    protected $tabs;

    /**
     * The parameter that will be added to identify panes
     *
     * @var string
     */
    private $tabParam = 'pane';

    /**
     * A welcome form rendered when there is no dashboard panes
     *
     * @var Form
     */
    private $welcomeForm;

    /**
     * Set the given tab name as active
     *
     * @param string $name The tab name to activate
     *
     */
    public function activate($name)
    {
        $this->getTabs()->activate($name);
    }

    /**
     * Set this dashboard's tabs
     *
     * @param Tabs $tabs
     *
     * @return $this
     */
    public function setTabs(Tabs $tabs)
    {
        $this->tabs = $tabs;

        return $this;
    }

    /**
     * Return the tab object used to navigate through this dashboard
     *
     * @return Tabs
     */
    public function getTabs()
    {
        $activeHome = $this->getActiveHome();
        if ($activeHome && $activeHome->getName() !== DashboardHome::DEFAULT_HOME) {
            $url = Url::fromPath(self::BASE_ROUTE . '/home')->getUrlWithout(['home', $this->tabParam]);
            $url->addParams(['home' => $activeHome->getName()]);
        } else {
            $url = Url::fromPath(self::BASE_ROUTE)->getUrlWithout($this->tabParam);
        }

        if ($this->tabs === null) {
            $this->tabs = new Tabs();
        }

        $this->tabs->disableLegacyExtensions();
        if (! $activeHome || $activeHome->isDisabled()) {
            return $this->tabs;
        }

        foreach ($activeHome->getPanes() as $key => $pane) {
            if ($pane->isDisabled()) {
                continue;
            }

            if (! $this->tabs->get($key)) {
                $this->tabs->add(
                    $key,
                    [
                        'title' => sprintf(
                            t('Show %s', 'dashboard.pane.tooltip'),
                            $pane->getTitle()
                        ),
                        'label'     => $pane->getTitle(),
                        'url'       => clone($url),
                        'urlParams' => [$this->tabParam => $key]
                    ]
                );
            }
        }

        return $this->tabs;
    }

    /**
     * Activate the default pane of this dashboard and returns its name
     *
     * @return ?int|string
     */
    private function setDefaultPane()
    {
        $active = null;
        $activeHome = $this->getActiveHome();

        foreach ($activeHome->getPanes() as $key => $pane) {
            if ($pane->isDisabled() === false) {
                $active = $key;
                break;
            }
        }

        if ($active !== null) {
            $this->activate($active);
        }

        return $active;
    }

    /**
     * @see determineActivePane()
     */
    public function getActivePane()
    {
        return $this->determineActivePane();
    }

    /**
     * Determine the active pane either by the selected tab or the current request
     *
     * @throws \Icinga\Exception\ConfigurationError
     * @throws \Icinga\Exception\ProgrammingError
     *
     * @return Pane The currently active pane
     */
    public function determineActivePane()
    {
        $active = $this->getTabs()->getActiveTab();
        $activeHome = $this->getActiveHome();

        if (! $active) {
            if ($active = Url::fromRequest()->getParam($this->tabParam)) {
                if ($activeHome->hasPane($active)) {
                    $this->activate($active);
                } else {
                    throw new ProgrammingError('Try to get an inexistent pane.');
                }
            } else {
                $active = $this->setDefaultPane();
            }
        } else {
            $active = $active->getName();
        }

        $panes = $activeHome->getPanes();
        if (isset($panes[$active])) {
            return $panes[$active];
        }

        throw new ConfigurationError('Could not determine active pane');
    }

    public function setWelcomeForm(Form $form)
    {
        $this->welcomeForm = $form;

        return $this;
    }

    protected function assemble()
    {
        $activeHome = $this->getActiveHome();
        if (! $activeHome || $activeHome->getName() === DashboardHome::DEFAULT_HOME && ! $activeHome->hasPanes()) {
            $this->setAttribute('class', 'content welcome-view');
            $wrapper = HtmlElement::create('div', ['class' => 'dashboard-introduction']);

            $wrapper->addHtml(HtmlElement::create('h1', null, t('Welcome to Icinga Web 2!')));
            $wrapper->addHtml(HtmlElement::create(
                'p',
                null,
                t('You will see this screen every time you log in and haven\'t created any dashboards yet.')
            ));

            $message = t('At the moment this view is empty, but you can populate it with small portions of information called Dashlets.');
            $wrapper->addHtml(HtmlElement::create('p', null, $message));

            $message = t(
                'Now you can either customize which dashlets to display, or use the system default dashlets.'
                . ' You will be always able to edit them afterwards.'
            );
            $wrapper->addHtml(HtmlElement::create('p', null, $message));

            $wrapper->addHtml($this->welcomeForm);
            $this->addHtml($wrapper);
        } elseif (! empty($activeHome->getPanes(true))) {
            $dashlets = $this->getActivePane()->getDashlets();
            $this->setAttribute('data-icinga-pane', $activeHome->getName() . '|' . $this->getActivePane()->getName());

            if (empty($dashlets)) {
                $this->setAttribute('class', 'content');
                $dashlets = HtmlElement::create('h1', null, t('No dashlet added to this pane.'));
            }

            $this->add($dashlets);
        } else {
            // TODO: What to do with dashboard homes without any dashboards??
            exit(0);
        }
    }
}
