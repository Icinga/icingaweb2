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
 *
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query map for comments
 */
class CommentQuery extends AbstractQuery
{
    protected $columnMap = array(
        'comments' => array(
            'comment_objecttype_id'         => 'co.objecttype_id',
            'comment_id'                    => 'cm.internal_comment_id',
            'comment_internal_id'           => 'cm.internal_comment_id',
            'comment_data'                  => 'cm.comment_data',
            'comment_author'                => 'cm.author_name COLLATE latin1_general_ci',
            'comment_timestamp'             => 'UNIX_TIMESTAMP(cm.comment_time)',
            'comment_type'                  => "CASE cm.entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'downtime' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END",
            'comment_is_persistent'         => 'cm.is_persistent',
            'comment_expiration_timestamp'  => 'CASE cm.expires WHEN 1 THEN UNIX_TIMESTAMP(cm.expiration_time) ELSE NULL END'
        ),
        'hosts' => array(
            'host_name' => 'ho.name1 COLLATE latin1_general_ci',
            'host'      => 'ho.name1 COLLATE latin1_general_ci',

        ),
        'services' => array(
            'service_host_name'     => 'so.name1 COLLATE latin1_general_ci',
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_name'          => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2 COLLATE latin1_general_ci',
        )
    );

    protected function joinBaseTables()
    {
        $this->baseQuery = $this->db->select()->from(
            array('cm' => $this->prefix . 'comments'),
            array()
        );

        $this->baseQuery->join(
            array(
                'co' => $this->prefix . 'objects'
            ),
            'cm.object_id = co.' . $this->object_id . ' AND co.is_active = 1'
        );

        $this->joinedVirtualTables = array('comments' => true);
    }

    protected function joinHosts()
    {
        $this->baseQuery->join(
            array('ho' => $this->prefix . 'objects'),
            'co.name1 = ho.name1 AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
    }

    protected function joinServices()
    {
        $this->baseQuery->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'co.name1 = so.name1 AND co.name2 = so.name2 AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
    }
}
