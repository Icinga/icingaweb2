<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
        if ($pageRange === null) {
            $pageRange = $paginator->getPageRange();
        }

        $pageNumber = $paginator->getCurrentPageNumber();
        $pageCount = count($paginator);
        $range = array();

        if ($pageCount < 15) {
            for ($i = 1; $i < 15; $i++) {
                if ($i > $pageCount) {
                    break;
                }
                $range[$i] = $i;
            }
        } else {
            foreach (array(1, 2) as $i) {
                $range[$i] = $i;
            }
            if ($pageNumber > 8) {
                $range[] = '...';
                $start = 5;
                if ($pageCount - $pageNumber < 8) {
                    $start = 9 - ($pageCount - $pageNumber);
                }
                for ($i = $pageNumber - $start; $i < $pageNumber + (10 - $start); $i++) {
                    if ($i > $pageCount) {
                        break;
                    }
                    $range[$i] = $i;
                }
            } else {
                for ($i = 3; $i <= 10; $i++) {
                    $range[$i] = $i;
                }
            }
            if ($pageNumber < ($pageCount - 7)) {
                $range[] = '...';
                foreach (array($pageCount - 1, $pageCount) as $i) {
                    $range[$i] = $i;
                }
            }
        }
        if (empty($range)) {
            $range[] = 1;
        }
        return $range;
    }
}

// @codingStandardsIgnoreEnd