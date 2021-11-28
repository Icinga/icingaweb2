<?php

/* Icinga Web 2 | (c) 2013-2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Icinga\Common\DashboardManager;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
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
     * Database table where dashboard home, pane and dashlet relationships
     * with roles or groups are being managed
     *
     * @var string
     */
    const GROUP_ROLE_TABLE = 'group_role_member';

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

    public function __construct()
    {
        $this->initDashboardUsers();
        $this->initDashboardGroups();
        $this->initDashboardRoles();
    }

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
            return  $this->tabs;
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
        $activeHome = $this->getActiveHome();
        $active = null;

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
        $activeHome = $this->getActiveHome();
        $active = $this->getTabs()->getActiveTab();

        if (! $active) {
            if ($active = Url::fromRequest()->getParam($this->tabParam)) {
                if ($activeHome->hasPane($active)) {
                    $this->activate($active);
                } else {
                    throw new ProgrammingError(
                        'Try to get an inexistent pane.'
                    );
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

    protected function assemble()
    {
        $activeHome = $this->getActiveHome();
        if ($activeHome && ! empty($activeHome->getPanes(true))) {
            $dashlets = $this->getActivePane()->getDashlets(true);

            if (empty($dashlets)) {
                $this->setAttribute('class', 'content');
                $dashlets = new HtmlElement('h1', null, Text::create(t('No dashlet added to this pane.')));
            }
        } else {
            $this->setAttribute('class', 'content');
            $format = t(
                'Currently there is no pane available. This might change once you enabled some of the available %s.'
            );

            $dashlets = [
                new HtmlElement('h1', null, Text::create(t('Welcome to Icinga Web 2!'))),
                Html::Sprintf($format, new Link('modules', 'config/modules'))
            ];
        }

        $this->add($dashlets);
    }
}
