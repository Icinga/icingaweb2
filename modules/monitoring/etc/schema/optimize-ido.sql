# WARNING
# You should only apply the following IDO schema changes if you're using Icinga 2 in combination w/ Icinga Web 2.
# The aim of the changes is to boost query performance of Icinga 2 and Icinga Web 2.
# Query performance of other applications using IDO MAY DROP. Applying all changes may take some time.
# Future updates to the IDO schema provided by Icinga 2 may fail.
# You have been warned.

# For using optimized queries in Web 2 matching the optimized schema you have to add the following configuration in
# /etc/icingaweb2/modules/monitoring/config.ini
# ...
# [ido]
# use_optimized_queries=1

################
# DROP INDICES #
################

# Why?
# Some indices are created twice.
# They don't follow any naming scheme.
# Most indices are useless.
# Most indices are on low cardinality columns.
# Better indices relevant for Web 2 and Icinga 2 will be re-added.
# New indices will be introduced.

-- CALL drop_index('icinga_hosts', 'instance_id');
CALL drop_index('icinga_hosts', 'host_object_id');
-- CALL drop_index('icinga_hosts', 'hosts_i_id_idx');
CALL drop_index('icinga_hosts', 'hosts_host_object_id_idx');

CALL drop_index('icinga_hoststatus', 'object_id');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_i_id_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_stat_upd_time_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_current_state_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_check_type_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_state_type_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_last_state_chg_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_notif_enabled_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_problem_ack_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_act_chks_en_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_pas_chks_en_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_event_hdl_en_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_flap_det_en_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_is_flapping_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_p_state_chg_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_latency_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_ex_time_idx');
-- CALL drop_index('icinga_hoststatus', 'hoststatus_sch_downt_d_idx');

-- CALL drop_index('icinga_services', 'instance_id');
CALL drop_index('icinga_services', 'service_object_id');
-- CALL drop_index('icinga_services', 'services_i_id_idx');
CALL drop_index('icinga_services', 'services_host_object_id_idx');
CALL drop_index('icinga_services', 'services_combined_object_idx');

CALL drop_index('icinga_servicestatus', 'object_id');
-- CALL drop_index('icinga_servicestatus', 'servicestatus_i_id_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_stat_upd_time_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_current_state_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_check_type_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_state_type_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_last_state_chg_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_notif_enabled_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_problem_ack_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_act_chks_en_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_pas_chks_en_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_event_hdl_en_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_flap_det_en_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_is_flapping_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_p_state_chg_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_latency_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_ex_time_idx');
-- CALL drop_index('icinga_servicestatus', 'srvcstatus_sch_downt_d_idx');

-- CALL drop_index('icinga_hostgroups', 'instance_id');
CALL drop_index('icinga_hostgroups', 'hostgroups_i_id_idx');

-- CALL drop_index('icinga_hostgroup_members', 'hostgroup_members_i_id_idx');
CALL drop_index('icinga_hostgroup_members', 'hstgrpmbrs_hgid_hoid');

CALL drop_index('icinga_objects', 'objecttype_id');
CALL drop_index('icinga_objects', 'objects_objtype_id_idx');
CALL drop_index('icinga_objects', 'objects_name1_idx');
CALL drop_index('icinga_objects', 'objects_name2_idx');
-- CALL drop_index('icinga_objects', 'objects_inst_id_idx');
CALL drop_index('icinga_objects', 'sla_idx_obj');

-- CALL drop_index('icinga_servicegroups', 'instance_id');
CALL drop_index('icinga_servicegroups', 'servicegroups_i_id_idx');

-- CALL drop_index('icinga_servicegroup_members', 'servicegroup_members_i_id_idx');
CALL drop_index('icinga_servicegroup_members', 'sgmbrs_sgid_soid');

CALL drop_index('icinga_notifications', 'instance_id');
CALL drop_index('icinga_notifications', 'notification_idx');
CALL drop_index('icinga_notifications', 'notification_object_id_idx');

############################
# DISPLAY_NAME PERFORMANCE #
############################

# Icinga Web 2's queries which filter for or order by host and service display_name are performed in a case-insensitive
# manner. Unfortunately, IDO's collation is case sensitive by default which renders possible indices useless.
# Let's fix that.

ALTER TABLE icinga_hosts MODIFY display_name VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
CALL create_index('icinga_hosts', 'idx_hosts_display_name', 'display_name');

ALTER TABLE icinga_services MODIFY display_name VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
CALL create_index('icinga_services', 'idx_services_display_name', 'display_name');

#####################
# ALIAS PERFORMANCE #
#####################

# Icinga 2.5 already sets alias columns from text to varchar(255). This is a good start. But Web 2's queries filter for
# or order by host and service group alias are performed in a case-insensitive manner. So, let's add the case
# insensitive collation.

ALTER TABLE icinga_hostgroups MODIFY alias VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
ALTER TABLE icinga_servicegroups MODIFY alias VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci;

####################
# JOIN PERFORMANCE #
####################

# The best possible join type in MySQL is `eq_ref` which is used when all parts of an index are used by the join and
# the index is a PRIMARY KEY or UNIQUE NOT NULL index.
# The IDO schema already has some UNIQUE indices for joins but lacks NOT NULL in the column definitions. Fix it.
# In addition, this script modifies columns to NOT NULL where appropriate for the following reasons
# a) NOT NULL enables MySQL to efficiently use indices
# b) NOT NULL requires less space
# c) NOT NULL columns must be not null

ALTER TABLE icinga_hosts MODIFY host_object_id BIGINT UNSIGNED NOT NULL;
CALL create_unique_index('icinga_hosts', 'idx_hosts_host_object_id', 'host_object_id');

ALTER TABLE icinga_hoststatus MODIFY host_object_id BIGINT UNSIGNED NOT NULL;
CALL create_unique_index('icinga_hoststatus', 'idx_hoststatus_host_object_id', 'host_object_id');

ALTER TABLE icinga_services MODIFY service_object_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_services MODIFY host_object_id BIGINT UNSIGNED NOT NULL;
CALL create_unique_index('icinga_services', 'idx_services_service_object_id', 'service_object_id, host_object_id');  # Service based joins
CALL create_unique_index('icinga_services', 'idx_services_host_object_id', 'host_object_id, service_object_id');     # Host based joins

ALTER TABLE icinga_servicestatus MODIFY service_object_id BIGINT UNSIGNED NOT NULL;
CALL create_unique_index('icinga_servicestatus', 'idx_servicestatus_service_object_id', 'service_object_id');

ALTER TABLE icinga_hostgroups MODIFY hostgroup_object_id BIGINT UNSIGNED NOT NULL;
CALL create_unique_index('icinga_hostgroups', 'idx_hostgroups_hostgroup_object_id', 'hostgroup_object_id');

ALTER TABLE icinga_hostgroup_members MODIFY hostgroup_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_hostgroup_members MODIFY host_object_id BIGINT UNSIGNED NOT NULL;
CALL create_unique_index('icinga_hostgroup_members', 'idx_icinga_hostgroup_members_host_object_id', 'host_object_id, hostgroup_id');

ALTER TABLE icinga_servicegroups MODIFY servicegroup_object_id BIGINT UNSIGNED NOT NULL;
CALL create_unique_index('icinga_servicegroups', 'idx_servicegroups_servicegroup_object_id', 'servicegroup_object_id');

ALTER TABLE icinga_servicegroup_members MODIFY servicegroup_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_servicegroup_members MODIFY service_object_id BIGINT UNSIGNED NOT NULL;
CALL create_unique_index('icinga_servicegroup_members', 'idx_icinga_servicegroup_members_service_object_id', 'service_object_id, servicegroup_id');

ALTER TABLE icinga_notifications MODIFY instance_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_notifications MODIFY object_id BIGINT UNSIGNED NOT NULL;
CALL create_index('icinga_notifications', 'idx_notifications_instance_id', 'instance_id');
CALL create_index('icinga_notifications', 'idx_notifications_object_id', 'object_id');

######################
# FILTER PERFORMANCE #
######################

ALTER TABLE icinga_objects MODIFY objecttype_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_objects MODIFY is_active TINYINT NOT NULL;
ALTER TABLE icinga_objects MODIFY name1 varchar(128) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL;
CALL create_unique_index('icinga_objects', 'idx_objects_objecttype_id', 'objecttype_id, is_active, name1, name2');

# At the moment it's impossible for Web 2 queries which filter for host or service state to use indices because they
# respect the virtual state PENDING. A host or service is PENDING if it has not been checked yet. Instead of calculating
# the state on query execution, we store it for every record taking PENDING into consideration. This reduces database
# load and enables MySQL to use possible indices.

ALTER TABLE icinga_servicestatus MODIFY current_state TINYINT NOT NULL;
ALTER TABLE icinga_servicestatus MODIFY has_been_checked TINYINT NOT NULL;

# Set service state to PENDING for all services that have not been checked
UPDATE icinga_servicestatus SET current_state = 99 WHERE has_been_checked = 0;

# Create trigger for updating the service state if the service has not been checked yet
DELIMITER //
DROP TRIGGER IF EXISTS t_set_pending_service_state //
CREATE TRIGGER t_set_pending_service_state BEFORE INSERT ON icinga_servicestatus
FOR EACH ROW
  BEGIN
    IF NEW.has_been_checked = 0 THEN
      SET NEW.current_state = 99;
    END IF;
  END //
DELIMITER ;

# Add indices for prominent service list filters, e.g. recently recovered services
CALL create_index('icinga_servicestatus', 'idx_servicestatus_current_state_last_state_change', 'current_state, last_state_change');
CALL create_index('icinga_servicestatus', 'idx_servicestatus_current_state_last_check', 'current_state, last_check');

ALTER TABLE icinga_hoststatus MODIFY current_state TINYINT NOT NULL;
ALTER TABLE icinga_hoststatus MODIFY has_been_checked TINYINT NOT NULL;

# Set host state to PENDING for all hosts that have not been checked
UPDATE icinga_hoststatus SET current_state = 99 WHERE has_been_checked = 0;

# Create trigger for updating the host state if the host has not been checked yet
DELIMITER //
DROP TRIGGER IF EXISTS t_set_pending_host_state //
CREATE TRIGGER t_set_pending_host_state BEFORE INSERT ON icinga_hoststatus
FOR EACH ROW
  BEGIN
    IF NEW.has_been_checked = 0 THEN
      SET NEW.current_state = 99;
    END IF;
  END //
DELIMITER ;

# Add indices for prominent host list filters
CALL create_index('icinga_hoststatus', 'idx_hoststatus_current_state_last_state_change', 'current_state, last_state_change');
CALL create_index('icinga_hoststatus', 'idx_hoststatus_current_state_last_check', 'current_state, last_check');

# Add index for prominent notification filter
CALL create_index('icinga_notifications', 'idx_notifications_start_time', 'start_time');
