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
