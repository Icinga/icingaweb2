<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Zend_Paginator;
use Icinga\Web\Controller\ModuleActionController;
use Icinga\Web\Widget\SortBox;
use Icinga\Web\Widget\Limiter;

/**
 * This is the controller all modules should inherit from
 * We will flip code with the ModuleActionController as soon as a couple
 * of pending feature branches are merged back to the master.
 */
class Controller extends ModuleActionController
{
    /**
     * Create a SortBox widget at the `sortBox' view property
     *
     * In case the current view has been requested as compact this method does nothing.
     *
     * @param   array   $columns    An array containing the sort columns, with the
     *                               submit value as the key and the label as the value
     *
     * @return  $this
     */
    protected function setupSortControl(array $columns)
    {
        if (! $this->view->compact) {
            $req = $this->getRequest();
            $this->view->sortBox = SortBox::create(
                'sortbox-' . $req->getActionName(),
                $columns
            )->applyRequest($req);
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
