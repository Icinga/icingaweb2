<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Data;

/**
 * Interface for browsing data
 */
interface Browsable
{
    /**
     * Paginate data
     *
     * @param   int $itemsPerPage   Number of items per page
     * @param   int $pageNumber     Current page number
     *
     * @return  Zend_Paginator
     */
    public function paginate($itemsPerPage = null, $pageNumber = null);
}
