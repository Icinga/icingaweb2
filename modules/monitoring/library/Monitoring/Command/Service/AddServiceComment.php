<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Service;

use Icinga\Module\Monitoring\Command\Common\Comment;

class AddServiceComment extends Comment
{
    protected $service;

    /**
     * Whether the comment is persistent
     *
     * Persistent comments are not lost the next time the monitoring host restarts.
     */
    protected $persistent;

    public function __construct($service)
    {
        $this->serivce = (string) $service;
    }

    public function getCommand()
    {
        return sprintf(
            'ADD_SVC_COMMENT;%s;%u;%s',
            $this->host,
            $this->persistent,
            parent::getCommand()
        );
    }
}
