<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
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
            if ($pageNumber < 6 ) {
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

// @codingStandardsIgnoreEnd
