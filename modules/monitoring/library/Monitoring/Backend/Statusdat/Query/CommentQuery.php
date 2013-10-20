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

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

/**
 * Query map for comments
 */
class CommentQuery extends StatusdatQuery
{
    public static $mappedParameters = array(
        'comment_id'                    => 'comment_id',
        'comment_internal_id'           => 'comment_id',
        'comment_data'                  => 'comment_data',
        'comment_author'                => 'author',
        'comment_timestamp'             => 'entry_time',
        'comment_is_persistent'         => 'persistent',
        'host_name'                     => 'host_name',
        'host'                          => 'host_name',
    );

    public static $handlerParameters = array(
        'comment_objecttype_id'         => 'getCommentObjectType',
        'comment_type'                  => 'getCommentType',
        'comment_expiration_timestamp'  => 'getExpirationTime',
        'service'                       => 'getServiceDescription',
        'service_name'                  => 'getServiceDescription',
        'service_description'           => 'getServiceDescription'
    );

    public function getServiceDescription(&$obj)
    {
        if (isset($obj->service_description)) {
            return $obj->service_description;
        }
        return '';
    }

    public function getExpirationTime(&$obj)
    {
        if ($obj->expires) {
            return $obj->expire_time;
        } else {
            return null;
        }
    }

    public function getCommentObjectType(&$obj)
    {
        if (isset($obj->service_description)) {
            return 2;
        } else {
            return 1;
        }
    }

    public function getCommentType(&$obj)
    {
        switch ($obj->entry_type) {
            case 1:
                return 'comment';
            case 2:
                return 'downtime';
            case 3:
                return 'flapping';
            case 4:
                return 'ack';
        }
        return '';

    }

    public function getObjectType(&$obj)
    {
        return isset($obj->service_description) ? 'service ': 'host';
    }

    public function selectBase()
    {
        $this->select()->from("comments", array());
    }
}
