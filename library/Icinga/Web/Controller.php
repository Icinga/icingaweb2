<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Zend_Paginator;
use Icinga\Web\Controller\ModuleActionController;
use Icinga\Web\Widget\SortBox;
use Icinga\Web\Widget\Limiter;
use Icinga\Data\Sortable;

/**
 * This is the controller all modules should inherit from
 * We will flip code with the ModuleActionController as soon as a couple
 * of pending feature branches are merged back to the master.
 */
class Controller extends ModuleActionController
{
    /**
     * @see ActionController::init
     */
    public function init()
    {
        parent::init();
        $this->handleSortControlSubmit();
    }

    /**
     * Check whether the sort control has been submitted and redirect using GET parameters
     */
    protected function handleSortControlSubmit()
    {
        $request = $this->getRequest();
        if (! $request->isPost()) {
            return;
        }

        if (($sort = $request->getPost('sort'))) {
            $url = Url::fromRequest();
            $url->setParam('sort', $sort);
            if (($dir = $request->getPost('dir'))) {
                $url->setParam('dir', $dir);
            } else {
                $url->removeParam('dir');
            }

            $this->redirectNow($url);
        }
    }

    /**
     * Create a SortBox widget and apply its sort rules on the given query
     *
     * The widget is set on the `sortBox' view property only if the current view has not been requested as compact
     *
     * @param   array    $columns    An array containing the sort columns, with the
     *                               submit value as the key and the label as the value
     * @param   Sortable $query      Query to apply the user chosen sort rules on
     *
     * @return  $this
     */
    protected function setupSortControl(array $columns, Sortable $query = null)
    {
        $request = $this->getRequest();
        $sortBox = SortBox::create('sortbox-' . $request->getActionName(), $columns);
        $sortBox->setRequest($request);

        if ($query) {
            $sortBox->setQuery($query);
            $sortBox->handleRequest($request);
        }

        if (! $this->view->compact) {
            $this->view->sortBox = $sortBox;
        }

        return $this;
    }

    /**
     * Create a Limiter widget at the `limiter' view property
     *
     * In case the current view has been requested as compact this method does nothing.
     *
     * @return  $this
     */
    protected function setupLimitControl()
    {
        if (! $this->view->compact) {
            $this->view->limiter = new Limiter();
        }

        return $this;
    }

    /**
     * Set the view property `paginator' to the given Zend_Paginator
     *
     * In case the current view has been requested as compact this method does nothing.
     *
     * @param   Zend_Paginator  $paginator  The Zend_Paginator for which to show a pagination control
     *
     * @return  $this
     */
    protected function setupPaginationControl(Zend_Paginator $paginator)
    {
        if (! $this->view->compact) {
            $this->view->paginator = $paginator;
        }

        return $this;
    }

    /**
     * Set the view property `filterEditor' to the given FilterEditor
     *
     * In case the current view has been requested as compact this method does nothing.
     *
     * @param   Form    $editor    The FilterEditor
     *
     * @return  $this
     */
    protected function setupFilterControl($editor)
    {
        if (! $this->view->compact) {
            $this->view->filterEditor = $editor;
        }

        return $this;
    }
}
