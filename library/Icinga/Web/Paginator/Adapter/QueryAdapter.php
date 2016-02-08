<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Paginator\Adapter;

use Zend_Paginator_Adapter_Interface;
use Icinga\Data\QueryInterface;

class QueryAdapter implements Zend_Paginator_Adapter_Interface
{
    /**
     * The query being paginated
     *
     * @var QueryInterface
     */
    protected $query;

    /**
     * Item count
     *
     * @var int
     */
    protected $count;

    /**
     * Create a new QueryAdapter
     *
     * @param   QueryInterface  $query      The query to paginate
     */
    public function __construct(QueryInterface $query)
    {
        $this->setQuery($query);
    }

    /**
     * Set the query to paginate
     *
     * @param   QueryInterface  $query
     *
     * @return  $this
     */
    public function setQuery(QueryInterface $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Return the query being paginated
     *
     * @return  QueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Fetch and return the rows in the given range of the query result
     *
     * @param   int     $offset             Page offset
     * @param   int     $itemCountPerPage   Number of items per page
     *
     * @return  array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        return $this->query->limit($itemCountPerPage, $offset)->fetchAll();
    }

    /**
     * Return the total number of items in the query result
     *
     * @return  int
     */
    public function count()
    {
        if ($this->count === null) {
            $this->count = $this->query->count();
        }

        return $this->count;
    }
}
