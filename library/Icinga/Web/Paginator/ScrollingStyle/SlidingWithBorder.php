<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

/**
 * @see Zend_Paginator_ScrollingStyle_Interface
 */
class Icinga_Web_Paginator_ScrollingStyle_SlidingWithBorder implements Zend_Paginator_ScrollingStyle_Interface
{
    /**
     * Returns an array of "local" pages given a page number and range.
     *
     * @param  Zend_Paginator $paginator
     * @param  integer $pageRange (Optional) Page range
     * @return array
     */
    public function getPages(Zend_Paginator $paginator, $pageRange = null)
    {
        // This is unused
        if ($pageRange === null) {
            $pageRange = $paginator->getPageRange();
        }

        $pageNumber = $paginator->getCurrentPageNumber();
        $pageCount = count($paginator);
        $range = array();

        if ($pageCount < 10) {
            // Show all pages if we have less than 10.

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
            if ($pageNumber < 6) {
                // We are on page 1-5 from
                for ($i = 1; $i <= 7; $i++) {
                    $range[$i] = $i;
                }
            } else {
                // Current page > 5
                $range[] = '...';

                // Less than 5 pages left
                if (($pageCount - $pageNumber) < 5) {
                    $start = 5 - ($pageCount - $pageNumber);
                } else {
                    $start = 1;
                }

                for ($i = $pageNumber - $start; $i < ($pageNumber + (4 - $start)); $i++) {
                    if ($i > $pageCount) {
                        break;
                    }
                    $range[$i] = $i;
                }
            }
            if ($pageNumber < ($pageCount - 2)) {
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
