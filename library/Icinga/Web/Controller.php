<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Data\Filterable;
use Icinga\Data\Sortable;
use Icinga\Data\QueryInterface;
use Icinga\Exception\Http\HttpBadRequestException;
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

        if (($sort = $request->getPost('sort')) || ($direction = $request->getPost('dir'))) {
            $url = Url::fromRequest();
            if ($sort) {
                $url->setParam('sort', $sort);
                $url->remove('dir');
            } else {
                $url->setParam('dir', $direction);
            }

            $this->redirectNow($url);
        }
    }

    /**
     * Immediately respond w/ HTTP 400
     *
     * @param   string  $message    Exception message or exception format string
     * @param   mixed   ...$arg     Format string argument
     *
     * @throws  HttpBadRequestException
     */
    public function httpBadRequest($message)
    {
        throw HttpBadRequestException::create(func_get_args());
    }

    /**
     * Immediately respond w/ HTTP 404
     *
     * @param   string  $message    Exception message or exception format string
     * @param   mixed   ...$arg     Format string argument
     *
     * @throws  HttpNotFoundException
     */
    public function httpNotFound($message)
    {
        throw HttpNotFoundException::create(func_get_args());
    }

    /**
     * Render the given form using a simple view script
     *
     * @param   Form    $form
     * @param   string  $tab
     */
    public function renderForm(Form $form, $tab)
    {
        $this->getTabs()->add(uniqid(), array(
            'active'    => true,
            'label'     => $tab,
            'url'       => Url::fromRequest()
        ));
        $this->view->form = $form;
        $this->render('simple-form', null, true);
    }

    /**
     * Create a SortBox widget and apply its sort rules on the given query
     *
     * The widget is set on the `sortBox' view property only if the current view has not been requested as compact
     *
     * @param   array       $columns    An array containing the sort columns, with the
     *                                  submit value as the key and the label as the value
     * @param   Sortable    $query      Query to apply the user chosen sort rules on
     * @param   array       $defaults   An array containing default sort directions for specific columns
     *
     * @return  $this
     */
    protected function setupSortControl(array $columns, Sortable $query = null, array $defaults = null)
    {
        $request = $this->getRequest();
        $sortBox = SortBox::create('sortbox-' . $request->getActionName(), $columns, $defaults);
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
     * Create a FilterEditor widget and apply the user's chosen filter options on the given filterable
     *
     * The widget is set on the `filterEditor' view property only if the current view has not been requested as compact.
     * The optional $filterColumns parameter should be an array of key-value pairs where the key is the name of the
     * column and the value the label to show to the user. The optional $searchColumns parameter should be an array
     * of column names to be used to handle quick searches.
     *
     * If the given filterable is an instance of Icinga\Data\FilterColumns, $filterable->getFilterColumns() and
     * $filterable->getSearchColumns() is called to provide the respective columns if $filterColumns or $searchColumns
     * is not given.
     *
     * @param   Filterable  $filterable         The filterable to create a filter editor for
     * @param   array       $filterColumns      The filter columns to offer to the user
     * @param   array       $searchColumns      The search columns to utilize for quick searches
     * @param   array       $preserveParams     The url parameters to preserve
     *
     * @return  $this
     *
     * @todo    Preserving and ignoring parameters should be configurable (another two method params? property magic?)
     */
    protected function setupFilterControl(
        Filterable $filterable,
        array $filterColumns = null,
        array $searchColumns = null,
        array $preserveParams = null
    ) {
        $defaultPreservedParams = array(
            'limit', // setupPaginationControl()
            'sort', // setupSortControl()
            'dir', // setupSortControl()
            'backend', // Framework
            'view', // Framework
            '_dev' // Framework
        );

        $editor = Widget::create('filterEditor');
        /** @var \Icinga\Web\Widget\FilterEditor $editor */
        call_user_func_array(
            array($editor, 'preserveParams'),
            array_merge($defaultPreservedParams, $preserveParams ?: array())
        );

        $editor
            ->setQuery($filterable)
            ->ignoreParams('page') // setupPaginationControl()
            ->setColumns($filterColumns)
            ->setSearchColumns($searchColumns)
            ->handleRequest($this->getRequest());

        if ($this->view->compact) {
            $editor->setVisible(false);
        }

        $this->view->filterEditor = $editor;

        return $this;
    }
}
