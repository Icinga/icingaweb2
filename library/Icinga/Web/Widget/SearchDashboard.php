<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard\Pane;
use Zend_Controller_Action_Exception as ActionError;

/**
 * Class SearchDashboard display multiple search views on a single search page
 *
 * @package Icinga\Web\Widget
 */
class SearchDashboard extends Dashboard
{
    const SEARCH_PANE = 'search';

    /**
     * Load all available search dashlets from modules
     *
     * @param string $searchString
     * @return Dashboard|SearchDashboard
     */
    public static function search($searchString = '')
    {
        /** @var $dashboard SearchDashboard */
        $dashboard = new static('searchDashboard');
        $dashboard->loadSearchDashlets($searchString);
        return $dashboard;
    }

    /**
     * Renders the output
     *
     * @return string
     * @throws \Zend_Controller_Action_Exception
     */
    public function render()
    {
        if (! $this->getPane(self::SEARCH_PANE)->hasDashlets()) {
            throw new ActionError('Site not found', 404);
        }
        return parent::render();
    }

    /**
     * Loads search dashlets
     *
     * @param string $searchString
     */
    protected function loadSearchDashlets($searchString)
    {
        $pane = $this->createPane(self::SEARCH_PANE)->getPane(self::SEARCH_PANE)->setTitle(t('Search'));
        $this->activate(self::SEARCH_PANE);

        $manager = Icinga::app()->getModuleManager();
        $searchUrls = array();

        foreach ($manager->getLoadedModules() as $module) {
            $moduleSearchUrls = $module->getSearchUrls();
            if (! empty($moduleSearchUrls)) {
                $searchUrls = array_merge($searchUrls, $moduleSearchUrls);
            }
        }

        usort($searchUrls, array($this, 'compareSearchUrls'));

        foreach (array_reverse($searchUrls) as $searchUrl) {
            $pane->addDashlet(
                $searchUrl->title . ': ' . $searchString,
                Url::fromPath($searchUrl->url, array('q' => $searchString))
            );
        }

        if ($searchString === '' && $pane->hasDashlets()) {
            $pane->removeDashlets();
            $pane->add('Ready to search', 'search/hint');
            return;
        }
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
}
