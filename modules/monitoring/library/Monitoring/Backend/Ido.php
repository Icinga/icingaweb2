<?php

namespace Icinga\Module\Monitoring\Backend;

use Icinga\Data\Db\Connection;

/**
 * This class provides an easy-to-use interface to the IDO database
 *
 * You should usually not directly use this class but go through Monitoring\Backend.
 *
 * New MySQL indexes:
 * <code>
 * CREATE INDEX web2_index ON icinga_scheduleddowntime (object_id, is_in_effect);
 * CREATE INDEX web2_index ON icinga_comments (object_id);
 * CREATE INDEX web2_index ON icinga_objects (object_id, is_active); -- (not sure yet)
 * </code>
 *
 * Other possible (history-related) indexes, still subject to tests:
 * CREATE INDEX web2_index ON icinga_statehistory (object_id, state_time DESC);
 * CREATE INDEX web2_index ON icinga_notifications (object_id, instance_id, start_time DESC);
 * CREATE INDEX web2_index ON icinga_downtimehistory (object_id, actual_start_time, actual_end_time);
 *
 * These should be unique:
 * CREATE INDEX web2_index ON icinga_host_contacts (host_id, contact_object_id);
 * CREATE INDEX web2_index ON icinga_service_contacts (service_id, contact_object_id);
 *
 * ...and we should drop a lot's of useless and/or redundant index definitions
 */
class Ido extends AbstractBackend
{
    protected $db;
    protected $prefix = 'icinga_';

    protected function init()
    {
        $this->db = new Connection($this->config);
        if ($this->db->getDbType() === 'oracle') {
            $this->prefix = '';
        }
    }
    
    public function getConnection()
    {
        return $this->db;
    }

    /**
     * Get our IDO table prefix
     *
     * return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}
