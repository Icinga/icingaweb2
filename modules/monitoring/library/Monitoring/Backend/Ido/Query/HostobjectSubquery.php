<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;

class HostobjectSubquery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1',
        ),
        'hosts' => array(
            'host'                  => 'ho.name1',
        ),
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1',
        ),
        'services' => array(
            'service'                => 'so.name2',
        )
    );

    /**
     * Create a sub query to join comments into status query
     *
     * @param   int     $entryType
     * @param   string  $alias
     *
     * @return  Zend_Db_Expr
     */
    protected function createLastCommentSubQuery($entryType, $alias)
    {
        $sql = <<<SQL
SELECT
  c.object_id,
  '[' || c.author_name || '] ' || c.comment_data AS $alias
FROM
  icinga_comments c
INNER JOIN
  (
    SELECT
      MAX(comment_id) AS comment_id,
      object_id
    FROM
      icinga_comments
    WHERE
      entry_type = $entryType
    GROUP BY object_id
  ) ec ON ec.comment_id = c.comment_id
SQL;
        return new Zend_Db_Expr('(' . $sql . ')');
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('h' => $this->prefix . 'hosts'),
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = h.host_object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
        $this->joinedVirtualTables['hosts'] = true;
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->select->joinLeft(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = ho.object_id',
            array()
        )->joinLeft(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_id = hgm.hostgroup_id',
            array()
        )->joinLeft(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hg.hostgroup_object_id AND hgo.is_active = 1 AND hgo.objecttype_id = 3',
            array()
        );
        $this->group('ho.name1');
    }

    /**
     * Join host status
     */
    protected function joinHoststatus()
    {
        $this->select->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join last host acknowledgement comment
     */
    protected function joinLasthostackcomment()
    {
        $this->select->joinLeft(
            array('hlac' => $this->createLastCommentSubQuery(4, 'last_ack_data')),
            'hlac.object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join last host comment
     */
    protected function joinLasthostcomment()
    {
        $this->select->joinLeft(
            array('hlc' => $this->createLastCommentSubQuery(1, 'last_comment_data')),
            'hlc.object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join last host downtime comment
     */
    protected function joinLasthostdowntimeComment()
    {
        $this->select->joinLeft(
            array('hldc' => $this->createLastCommentSubQuery(2, 'last_downtime_data')),
            'hldc.object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join last host flapping comment
     */
    protected function joinLasthostflappingcomment()
    {
        $this->select->joinLeft(
            array('hlfc' => $this->createLastCommentSubQuery(3, 'last_flapping_data')),
            'hlfc.object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->requireVirtualTable('services');
        $this->select->joinLeft(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->joinLeft(
            array('sg' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sg.' . $this->servicegroup_id,
            array()
        )->joinLeft(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sg.servicegroup_object_id AND sgo.is_active = 1 AND sgo.objecttype_id = 4',
            array()
        );
        $this->group('ho.name1');
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->requireVirtualTable('hosts');
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
        $this->group('ho.name1');
    }

    /**
     * Join service problem summary
     */
    protected function joinServiceproblemsummary()
    {
        $select = <<<'SQL'
SELECT
    SUM(
        CASE WHEN(ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0
        THEN 0
        ELSE 1
        END
    ) AS unhandled_services_count,
    SUM(
        CASE WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0) ) > 0
        THEN 1
        ELSE 0
        END
    ) AS handled_services_count,
    s.host_object_id
FROM
    icinga_servicestatus ss
    JOIN icinga_objects o ON o.object_id = ss.service_object_id
    JOIN icinga_services s ON s.service_object_id = o.object_id
    JOIN icinga_hoststatus hs ON hs.host_object_id = s.host_object_id
WHERE
    o.is_active = 1
    AND o.objecttype_id = 2
    AND ss.current_state > 0
GROUP BY
    s.host_object_id
SQL;
        $this->select->joinLeft(
            array('sps' => new Zend_Db_Expr('(' . $select . ')')),
            'sps.host_object_id = ho.object_id',
            array()
        );
    }
}
