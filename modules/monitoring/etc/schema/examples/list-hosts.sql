EXPLAIN SELECT h.icon_image AS host_icon_image, h.icon_image_alt AS
host_icon_image_alt, ho.name1 AS host_name, h.display_name COLLATE
latin1_general_ci AS host_display_name, CASE WHEN hs.has_been_checked = 0
OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END AS
host_state, hs.problem_has_been_acknowledged AS host_acknowledged,
hs.output AS host_output, hs.current_check_attempt || '/' ||
hs.max_check_attempts AS host_attempt, CASE WHEN
(hs.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END AS host_in_downtime,
hs.is_flapping AS host_is_flapping, hs.state_type AS host_state_type, CASE
WHEN (hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth) > 0
THEN 1 ELSE 0 END AS host_handled, UNIX_TIMESTAMP(hs.last_state_change) AS
host_last_state_change, hs.notifications_enabled AS
host_notifications_enabled, hs.active_checks_enabled AS
host_active_checks_enabled, hs.passive_checks_enabled AS
host_passive_checks_enabled FROM icinga_objects AS ho
 INNER JOIN icinga_hosts AS h ON h.host_object_id = ho.object_id AND
ho.is_active = 1 AND ho.objecttype_id = 1
 INNER JOIN icinga_hoststatus AS hs ON hs.host_object_id = ho.object_id
ORDER BY h.display_name COLLATE latin1_general_ci ASC LIMIT 25;

# +------+-------------+-------+--------+----------------------------------------------------------+-----------+---------+--------------------------+------+-----------------------------+
# | id   | select_type | table | type   | possible_keys                                            | key       | key_len | ref                      | rows | Extra                       |
# +------+-------------+-------+--------+----------------------------------------------------------+-----------+---------+--------------------------+------+-----------------------------+
# |    1 | SIMPLE      | h     | ALL    | host_object_id,hosts_host_object_id_idx                  | NULL      | NULL    | NULL                     | 3052 | Using where; Using filesort |
# |    1 | SIMPLE      | ho    | eq_ref | PRIMARY,objecttype_id,objects_objtype_id_idx,sla_idx_obj | PRIMARY   | 8       | icinga2.h.host_object_id |    1 | Using where                 |
# |    1 | SIMPLE      | hs    | ref    | object_id                                                | object_id | 9       | icinga2.h.host_object_id |    1 |                             |
# +------+-------------+-------+--------+----------------------------------------------------------+-----------+---------+--------------------------+------+-----------------------------+

#
# w/ case insensitive collation changes applied
#

EXPLAIN SELECT h.icon_image AS host_icon_image, h.icon_image_alt AS
host_icon_image_alt, ho.name1 AS host_name, h.display_name AS
host_display_name, CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked
IS NULL THEN 99 ELSE hs.current_state END AS host_state,
hs.problem_has_been_acknowledged AS host_acknowledged, hs.output AS
host_output, hs.current_check_attempt || '/' || hs.max_check_attempts AS
host_attempt, CASE WHEN (hs.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END
AS host_in_downtime, hs.is_flapping AS host_is_flapping, hs.state_type AS
host_state_type, CASE WHEN (hs.problem_has_been_acknowledged +
hs.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END AS host_handled,
UNIX_TIMESTAMP(hs.last_state_change) AS host_last_state_change,
hs.notifications_enabled AS host_notifications_enabled,
hs.active_checks_enabled AS host_active_checks_enabled,
hs.passive_checks_enabled AS host_passive_checks_enabled FROM
icinga_objects AS ho
 INNER JOIN icinga_hosts AS h ON h.host_object_id = ho.object_id AND
ho.is_active = 1 AND ho.objecttype_id = 1
 INNER JOIN icinga_hoststatus AS hs ON hs.host_object_id = ho.object_id
ORDER BY h.display_name ASC LIMIT 25;

# +------+-------------+-------+--------+----------------------------------------------------------+------------------------+---------+--------------------------+------+-------------+
# | id   | select_type | table | type   | possible_keys                                            | key                    | key_len | ref                      | rows | Extra       |
# +------+-------------+-------+--------+----------------------------------------------------------+------------------------+---------+--------------------------+------+-------------+
# |    1 | SIMPLE      | h     | index  | host_object_id,hosts_host_object_id_idx                  | idx_hosts_display_name | 258     | NULL                     |   25 | Using where |
# |    1 | SIMPLE      | ho    | eq_ref | PRIMARY,objecttype_id,objects_objtype_id_idx,sla_idx_obj | PRIMARY                | 8       | icinga2.h.host_object_id |    1 | Using where |
# |    1 | SIMPLE      | hs    | ref    | object_id                                                | object_id              | 9       | icinga2.h.host_object_id |    1 |             |
# +------+-------------+-------+--------+----------------------------------------------------------+------------------------+---------+--------------------------+------+-------------+

#
# w/ modifications for join type eq_ref applied (same query as above)
#

# +------+-------------+-------+--------+----------------------------------------------------------+-------------------------------+---------+--------------------------+------+-------------+
# | id   | select_type | table | type   | possible_keys                                            | key                           | key_len | ref                      | rows | Extra       |
# +------+-------------+-------+--------+----------------------------------------------------------+-------------------------------+---------+--------------------------+------+-------------+
# |    1 | SIMPLE      | h     | index  | idx_hosts_host_object_id                                 | idx_hosts_display_name        | 258     | NULL                     |   25 |             |
# |    1 | SIMPLE      | ho    | eq_ref | PRIMARY,objecttype_id,objects_objtype_id_idx,sla_idx_obj | PRIMARY                       | 8       | icinga2.h.host_object_id |    1 | Using where |
# |    1 | SIMPLE      | hs    | eq_ref | idx_hoststatus_host_object_id                            | idx_hoststatus_host_object_id | 8       | icinga2.h.host_object_id |    1 |             |
# +------+-------------+-------+--------+----------------------------------------------------------+-------------------------------+---------+--------------------------+------+-------------+
