<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Data\Sortable;
use Icinga\Data\QueryInterface;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Web\Controller\ModuleActionController;
use Icinga\Web\Widget\Limiter;
use Icinga\Web\Widget\Paginator;
use Icinga\Web\Widget\SortBox;

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
     * Immediately respond w/ HTTP 404
     *
     * @param   $message
     *
     * @throws  HttpNotFoundException
     */
    public function httpNotFound($message)
    {
        throw new HttpNotFoundException($message);
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
     * @param   int             $itemsPerPage   Default number of items per page
     *
     * @return  $this
     */
    protected function setupLimitControl($itemsPerPage = 25)
    {
        if (! $this->view->compact) {
            $this->view->limiter = new Limiter();
            $this->view->limiter->setDefaultLimit($itemsPerPage);
        }

        return $this;
    }

    /**
     * Apply the given page limit and number on the given query and setup a paginator for it
     *
     * The $itemsPerPage and $pageNumber parameters are only applied if not available in the current request.
     * The paginator is set on the `paginator' view property only if the current view has not been requested as compact.
     *
     * @param   QueryInterface  $query          The query to create a paginator for
     * @param   int             $itemsPerPage   Default number of items per page
     * @param   int             $pageNumber     Default page number
     *
     * @return  $this
     */
    protected function setupPaginationControl(QueryInterface $query, $itemsPerPage = 25, $pageNumber = 0)
    {
        $request = $this->getRequest();
        $limit = $request->getParam('limit', $itemsPerPage);
        $page = $request->getParam('page', $pageNumber);
        $query->limit($limit, $page > 0 ? ($page - 1) * $limit : 0);

        if (! $this->view->compact) {
            $paginator = new Paginator();
            $paginator->setQuery($query);
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
