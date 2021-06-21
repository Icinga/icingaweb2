<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Application\Icinga;
use Icinga\Web\Url;

/**
 * Class SearchDashboard display multiple search views on a single search page
 */
class SearchDashboard extends Dashboard
{
    /**
     * Name for the search home
     *
     * @var string
     */
    const SEARCH_HOME = 'Search Home';

    /**
     * Name for the search pane
     *
     * @var string
     */
    const SEARCH_PANE = 'search';

    /**
     * This search dashboard's dashboard home instance
     *
     * @var DashboardHome
     */
    protected $dashboardHome;

    public function __construct()
    {
        // Initialize "Search Home" dashboard Home
        $this->dashboardHome = new DashboardHome(self::SEARCH_HOME);
        $this->dashboardHome->setActive();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabs()
    {
        if ($this->tabs === null) {
            $this->tabs = new \ipl\Web\Widget\Tabs();

            $this->tabs->add(
                'search',
                array(
                    'title' => t('Show Search', 'dashboard.pane.tooltip'),
                    'label' => t('Search'),
                    'url'   => Url::fromRequest()
                )
            );
        }

        return $this->tabs;
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveHome()
    {
        return $this->dashboardHome;
    }

    /**
     * Load all available search dashlets from modules
     *
     * @param   string $searchString
     *
     * @return  $this
     */
    public function search($searchString = '')
    {
        $pane = $this->dashboardHome->createPane(self::SEARCH_PANE)->getPane(self::SEARCH_PANE)->setTitle(t('Search'));
        $this->activate(self::SEARCH_PANE);

        $manager = Icinga::app()->getModuleManager();
        $searchUrls = array();

        foreach ($manager->getLoadedModules() as $module) {
            if ($this->dashboardHome->getUser()->can($manager::MODULE_PERMISSION_NS . $module->getName())) {
                $moduleSearchUrls = $module->getSearchUrls();
                if (! empty($moduleSearchUrls)) {
                    if ($searchString === '') {
                        $pane->add(t('Ready to search'), 'search/hint');
                        return $this;
                    }
                    $searchUrls = array_merge($searchUrls, $moduleSearchUrls);
                }
            }
        }

        usort($searchUrls, array($this, 'compareSearchUrls'));

        foreach (array_reverse($searchUrls) as $searchUrl) {
            $pane->createDashlet(
                $searchUrl->title . ': ' . $searchString,
                Url::fromPath($searchUrl->url, array('q' => $searchString))
            )->setProgressLabel(t('Searching'));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function assemble()
    {
        if (! $this->dashboardHome->getPane(self::SEARCH_PANE)->hasDashlets()) {
            throw new HttpNotFoundException(t('Page not found'));
        }

        $this->add($this->dashboardHome->getPane(self::SEARCH_PANE)->getDashlets());
    }

    /**
     * Compare search URLs based on their priority
     *
     * @param   object  $a
     * @param   object  $b
     *
     * @return  int
     */
    private function compareSearchUrls($a, $b)
    {
        if ($a->priority === $b->priority) {
            return 0;
        }
        return ($a->priority < $b->priority) ? -1 : 1;
    }

    public function __get($name)
    {
        return $this->$name;
    }
}
