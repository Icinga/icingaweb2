<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
     * All searchUrls provided by Modules
     *
     * @var array
     */
    protected $searchUrls = array();

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

        foreach ($manager->getLoadedModules() as $module) {
            $this->addSearchDashletsFromModule($searchString, $module, $pane);
        }

        if ($searchString === '' && $pane->hasDashlets()) {
            $pane->removeDashlets();
            $pane->add('Ready to search', 'search/hint');
            return;
        }
    }

    /**
     * Add available search dashlets to the pane
     *
     * @param string $searchString
     * @param Module $module
     * @param Pane $pane
     */
    protected function addSearchDashletsFromModule($searchString, $module, $pane)
    {
        $searchUrls = $module->getSearchUrls();

        if (! empty($searchUrls)) {
            $this->searchUrls[] = $module->getSearchUrls();
            foreach ($searchUrls as $search) {
                $pane->addDashlet(
                    $search->title . ': ' . $searchString,
                    Url::fromPath($search->url, array('q' => $searchString))
                );
            }
        }
    }
}
