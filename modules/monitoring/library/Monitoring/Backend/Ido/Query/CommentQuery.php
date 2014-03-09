<?php
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

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query map for comments
 */
class CommentQuery extends IdoQuery
{
    protected $columnMap = array(
        'comments' => array(
            'comment_objecttype'            => "CASE WHEN ho.object_id IS NOT NULL THEN 'host' ELSE CASE WHEN so.object_id IS NOT NULL THEN 'service' ELSE NULL END END",
            'comment_internal_id'           => 'cm.internal_comment_id',
            'comment_data'                  => 'cm.comment_data',
            'comment_author'                => 'cm.author_name COLLATE latin1_general_ci',
            'comment_timestamp'             => 'UNIX_TIMESTAMP(cm.comment_time)',
            'comment_type'                  => "CASE cm.entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'downtime' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END",
            'comment_is_persistent'         => 'cm.is_persistent',
            'comment_expiration_timestamp'  => 'CASE cm.expires WHEN 1 THEN UNIX_TIMESTAMP(cm.expiration_time) ELSE NULL END',
        ),
        'hosts' => array(
            'host_name' => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END COLLATE latin1_general_ci',
            'host'      => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END COLLATE latin1_general_ci',
        ),
        'services' => array(
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

        /*
        $this->baseQuery->join(
            array(
                'co' => $this->prefix . 'objects'
            ),
            'cm.object_id = co.' . $this->object_id . ' AND co.is_active = 1'
        );*/

        $this->joinedVirtualTables = array('comments' => true);
        $this->joinVirtualTable('hosts');
        $this->joinVirtualTable('services');
    }

    protected function joinHosts()
    {
        // $this->conflictsWithVirtualTable('services');
        $this->baseQuery->joinLeft(
            array('ho' => $this->prefix . 'objects'),
            'cm.object_id = ho.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
    }

    protected function joinServices()
    {
        // $this->conflictsWithVirtualTable('hosts');
        $this->baseQuery->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'cm.object_id = so.object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
    }
}
