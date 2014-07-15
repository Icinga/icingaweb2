<?php
// {{{ICINGA_LICENSE_HEADER}}}
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
