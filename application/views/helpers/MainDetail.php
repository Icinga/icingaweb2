<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}


class Zend_View_Helper_MainDetail extends Zend_View_Helper_Abstract
{
    private static $tpl = <<<'EOT'
    <div id='icingamain' class='{{MAIN_CLASS}}'>
        {{MAIN_CONTENT}}
    </div>

    <div id='icingadetail' class='{{DETAIL_CLASS}}'>
        {{DETAIL_CONTENT}}
    </div>
EOT;

    private static $expanded = array(
        'xs'    => 12,
        'sm'    => 12,
        'md'    => 12,
        'lg'    => 5
    );

    public function mainDetail($mainContent, $detailContent = '')
    {
        $detailCls = 'hidden';
        $mainCls = 'col-md-12 col-lg-12 col-xs-12 col-sm-12';

        if ($detailContent != '') {
            $detailCls = '';
            $mainCls = '';
            foreach (self::$expanded as $type=>$size) {
                $detailCls .= 'col-' . $type . '-' . ($size == 12 ? 'push-' : '') . $size . ' ';
                $mainCls .= 'col-' . $type . '-' . ($size == 12 ? 'pull-' : '') . ($size < 12 ? (12-$size) : 12). ' ';
            }
        }

        $html = str_replace('{{MAIN_CLASS}}', $mainCls, self::$tpl);
        $html = str_replace('{{DETAIL_CLASS}}', $detailCls, $html);
        $html = str_replace('{{MAIN_CONTENT}}', $mainContent, $html);
        $html = str_replace('{{DETAIL_CONTENT}}', $detailContent, $html);
        return $html;
    }
}