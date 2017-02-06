<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Data\Paginatable;
use Icinga\Exception\ProgrammingError;

/**
 * Paginator
 */
class Paginator extends AbstractWidget
{
    /**
     * The query the paginator widget is created for
     *
     * @var Paginatable
     */
    protected $query;

    /**
     * The view script in use
     *
     * @var string|array
     */
    protected $viewScript = array('mixedPagination.phtml', 'default');

    /**
     * Set the query to create the paginator widget for
     *
     * @param   Paginatable      $query
     *
     * @return  $this
     */
    public function setQuery(Paginatable $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Set the view script to use
     *
     * @param   string|array    $script
     *
     * @return  $this
     */
    public function setViewScript($script)
    {
        $this->viewScript = $script;
        return $this;
    }

    /**
     * Render this paginator
     */
    public function render()
    {
        if ($this->query === null) {
            throw new ProgrammingError('Need a query to create the paginator widget for');
        }

        $itemCountPerPage = $this->query->getLimit();
        if (! $itemCountPerPage) {
            return ''; // No pagination required
        }

        $totalItemCount = count($this->query);
        $pageCount = (int) ceil($totalItemCount / $itemCountPerPage);
        $currentPage = $this->query->hasOffset() ? ($this->query->getOffset() / $itemCountPerPage) + 1 : 1;
        $pagesInRange = $this->getPages($pageCount, $currentPage);
        $variables = array(
            'totalItemCount'    => $totalItemCount,
            'pageCount'         => $pageCount,
            'itemCountPerPage'  => $itemCountPerPage,
            'first'             => 1,
            'current'           => $currentPage,
            'last'              => $pageCount,
            'pagesInRange'      => $pagesInRange,
            'firstPageInRange'  => min($pagesInRange),
            'lastPageInRange'   => max($pagesInRange)
        );

        if ($currentPage > 1) {
            $variables['previous'] = $currentPage - 1;
        }

        if ($currentPage < $pageCount) {
            $variables['next'] = $currentPage + 1;
        }

        if (is_array($this->viewScript)) {
            if ($this->viewScript[1] !== null) {
                return $this->view()->partial($this->viewScript[0], $this->viewScript[1], $variables);
            }

            return $this->view()->partial($this->viewScript[0], $variables);
        }

        return $this->view()->partial($this->viewScript, $variables);
    }

    /**
     * Returns an array of "local" pages given the page count and current page number
     *
     * @return  array
     */
    protected function getPages($pageCount, $currentPage)
    {
        $range = array();

        if ($pageCount < 10) {
            // Show all pages if we have less than 10
            for ($i = 1; $i < 10; $i++) {
                if ($i > $pageCount) {
                    break;
                }

                $range[$i] = $i;
            }
        } else {
            // More than 10 pages:
            foreach (array(1, 2) as $i) {
                $range[$i] = $i;
            }

            if ($currentPage < 6) {
                // We are on page 1-5 from
                for ($i = 1; $i <= 7; $i++) {
                    $range[$i] = $i;
                }
            } else {
                // Current page > 5
                $range[] = '...';

                if (($pageCount - $currentPage) < 5) {
                    // Less than 5 pages left
                    $start = 5 - ($pageCount - $currentPage);
                } else {
                    $start = 1;
                }

                for ($i = $currentPage - $start; $i < ($currentPage + (4 - $start)); $i++) {
                    if ($i > $pageCount) {
                        break;
                    }

                    $range[$i] = $i;
                }
            }

            if ($currentPage < ($pageCount - 2)) {
                $range[] = '...';
            }

            foreach (array($pageCount - 1, $pageCount) as $i) {
                $range[$i] = $i;
            }
        }

        if (empty($range)) {
            $range[] = 1;
        }

        return $range;
    }
}
