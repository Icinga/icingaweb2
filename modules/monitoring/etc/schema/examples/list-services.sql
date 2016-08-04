EXPLAIN SELECT so.name1 AS host_name, h.display_name COLLATE latin1_general_ci AS
host_display_name, CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked
IS NULL THEN 99 ELSE hs.current_state END AS host_state, so.name2 AS
service_description, s.display_name COLLATE latin1_general_ci AS
service_display_name, CASE WHEN ss.has_been_checked = 0 OR
ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END AS
service_state, CASE WHEN (ss.scheduled_downtime_depth = 0 OR
ss.scheduled_downtime_depth IS NULL) THEN 0 ELSE 1 END AS
service_in_downtime, ss.problem_has_been_acknowledged AS
service_acknowledged, CASE WHEN (ss.problem_has_been_acknowledged +
ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0 THEN 1
ELSE 0 END AS service_handled, ss.output AS service_output, ss.perfdata AS
service_perfdata, ss.current_check_attempt || '/' || ss.max_check_attempts
AS service_attempt, UNIX_TIMESTAMP(ss.last_state_change) AS
service_last_state_change, s.icon_image AS service_icon_image,
s.icon_image_alt AS service_icon_image_alt, ss.is_flapping AS
service_is_flapping, ss.state_type AS service_state_type, CASE WHEN
ss.current_state = 0 THEN CASE WHEN ss.has_been_checked = 0 OR
ss.has_been_checked IS NULL THEN 16 ELSE 0 END + CASE WHEN
ss.problem_has_been_acknowledged = 1 THEN 2 ELSE CASE WHEN
ss.scheduled_downtime_depth > 0 THEN 1 ELSE 4 END END ELSE CASE WHEN
ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 16 WHEN
ss.current_state = 1 THEN 32 WHEN ss.current_state = 2 THEN 128 WHEN
ss.current_state = 3 THEN 64 ELSE 256 END + CASE WHEN hs.current_state > 0
THEN 1024 ELSE CASE WHEN ss.problem_has_been_acknowledged = 1 THEN 512 ELSE
CASE WHEN ss.scheduled_downtime_depth > 0 THEN 256 ELSE 2048 END END END
END AS service_severity, ss.notifications_enabled AS
service_notifications_enabled, ss.active_checks_enabled AS
service_active_checks_enabled, ss.passive_checks_enabled AS
service_passive_checks_enabled FROM icinga_objects AS so
 INNER JOIN icinga_services AS s ON s.service_object_id = so.object_id AND
so.is_active = 1 AND so.objecttype_id = 2
 INNER JOIN icinga_hosts AS h ON h.host_object_id = s.host_object_id
 INNER JOIN icinga_hoststatus AS hs ON hs.host_object_id = s.host_object_id
 INNER JOIN icinga_servicestatus AS ss ON ss.service_object_id =
so.object_id ORDER BY s.display_name COLLATE latin1_general_ci ASC,
h.display_name COLLATE latin1_general_ci ASC LIMIT 25;

# +------+-------------+-------+--------+----------------------------------------------------------------------------+-----------------------------+---------+-----------------------------+------+----------------------------------------------+
# | id   | select_type | table | type   | possible_keys                                                              | key                         | key_len | ref                         | rows | Extra                                        |
# +------+-------------+-------+--------+----------------------------------------------------------------------------+-----------------------------+---------+-----------------------------+------+----------------------------------------------+
# |    1 | SIMPLE      | h     | ALL    | host_object_id,hosts_host_object_id_idx                                    | NULL                        | NULL    | NULL                        | 3002 | Using where; Using temporary; Using filesort |
# |    1 | SIMPLE      | hs    | ref    | object_id                                                                  | object_id                   | 9       | icinga2.h.host_object_id    |    1 |                                              |
# |    1 | SIMPLE      | s     | ref    | service_object_id,services_host_object_id_idx,services_combined_object_idx | services_host_object_id_idx | 9       | icinga2.h.host_object_id    |    1 | Using where                                  |
# |    1 | SIMPLE      | ss    | ref    | object_id                                                                  | object_id                   | 9       | icinga2.s.service_object_id |    1 |                                              |
# |    1 | SIMPLE      | so    | eq_ref | PRIMARY,objecttype_id,objects_objtype_id_idx,sla_idx_obj                   | PRIMARY                     | 8       | icinga2.s.service_object_id |    1 | Using where                                  |
# +------+-------------+-------+--------+----------------------------------------------------------------------------+-----------------------------+---------+-----------------------------+------+----------------------------------------------+

#
# w/ case insensitive collation, join performance and order by column reduction changes applied
#

EXPLAIN SELECT so.name1 AS host_name, h.display_name AS host_display_name, CASE
WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE
hs.current_state END AS host_state, so.name2 AS service_description,
s.display_name AS service_display_name, CASE WHEN ss.has_been_checked = 0
OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END AS
service_state, CASE WHEN (ss.scheduled_downtime_depth = 0 OR
ss.scheduled_downtime_depth IS NULL) THEN 0 ELSE 1 END AS
service_in_downtime, ss.problem_has_been_acknowledged AS
service_acknowledged, CASE WHEN (ss.problem_has_been_acknowledged +
ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0 THEN 1
ELSE 0 END AS service_handled, ss.output AS service_output, ss.perfdata AS
service_perfdata, ss.current_check_attempt || '/' || ss.max_check_attempts
AS service_attempt, UNIX_TIMESTAMP(ss.last_state_change) AS
service_last_state_change, s.icon_image AS service_icon_image,
s.icon_image_alt AS service_icon_image_alt, ss.is_flapping AS
service_is_flapping, ss.state_type AS service_state_type, CASE WHEN
ss.current_state = 0 THEN CASE WHEN ss.has_been_checked = 0 OR
ss.has_been_checked IS NULL THEN 16 ELSE 0 END + CASE WHEN
ss.problem_has_been_acknowledged = 1 THEN 2 ELSE CASE WHEN
ss.scheduled_downtime_depth > 0 THEN 1 ELSE 4 END END ELSE CASE WHEN
ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 16 WHEN
ss.current_state = 1 THEN 32 WHEN ss.current_state = 2 THEN 128 WHEN
ss.current_state = 3 THEN 64 ELSE 256 END + CASE WHEN hs.current_state > 0
THEN 1024 ELSE CASE WHEN ss.problem_has_been_acknowledged = 1 THEN 512 ELSE
CASE WHEN ss.scheduled_downtime_depth > 0 THEN 256 ELSE 2048 END END END
END AS service_severity, ss.notifications_enabled AS
service_notifications_enabled, ss.active_checks_enabled AS
service_active_checks_enabled, ss.passive_checks_enabled AS
service_passive_checks_enabled FROM icinga_objects AS so
 INNER JOIN icinga_services AS s ON s.service_object_id = so.object_id AND
so.is_active = 1 AND so.objecttype_id = 2
 INNER JOIN icinga_hosts AS h ON h.host_object_id = s.host_object_id
 INNER JOIN icinga_hoststatus AS hs ON hs.host_object_id = s.host_object_id
 INNER JOIN icinga_servicestatus AS ss ON ss.service_object_id =
so.object_id ORDER BY s.display_name ASC LIMIT 25;

# +------+-------------+-------+--------+----------------------------------------------------------+-------------------------------------+---------+-----------------------------+------+-------------+
# | id   | select_type | table | type   | possible_keys                                            | key                                 | key_len | ref                         | rows | Extra       |
# +------+-------------+-------+--------+----------------------------------------------------------+-------------------------------------+---------+-----------------------------+------+-------------+
# |    1 | SIMPLE      | s     | index  | idx_services_service_object_id                           | idx_services_display_name           | 258     | NULL                        |   25 |             |
# |    1 | SIMPLE      | h     | eq_ref | idx_hosts_host_object_id                                 | idx_hosts_host_object_id            | 8       | icinga2.s.host_object_id    |    1 |             |
# |    1 | SIMPLE      | ss    | eq_ref | idx_servicestatus_service_object_id                      | idx_servicestatus_service_object_id | 8       | icinga2.s.service_object_id |    1 |             |
# |    1 | SIMPLE      | hs    | eq_ref | idx_hoststatus_host_object_id                            | idx_hoststatus_host_object_id       | 8       | icinga2.s.host_object_id    |    1 |             |
# |    1 | SIMPLE      | so    | eq_ref | PRIMARY,objecttype_id,objects_objtype_id_idx,sla_idx_obj | PRIMARY                             | 8       | icinga2.s.service_object_id |    1 | Using where |
# +------+-------------+-------+--------+----------------------------------------------------------+-------------------------------------+---------+-----------------------------+------+-------------+
