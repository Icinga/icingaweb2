# WARNING
# You should only apply the following IDO schema changes if you're using Icinga 2 in combination w/ Icinga Web 2.
# The aim of the changes is to boost query performance of Icinga 2 and Icinga Web 2.
# Query performance of other applications using IDO MAY DROP. Applying the changes may take some time.
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

-- ALTER TABLE icinga_hosts DROP INDEX instance_id;
ALTER TABLE icinga_hosts DROP INDEX host_object_id;
-- ALTER TABLE icinga_hosts DROP INDEX hosts_i_id_idx;
ALTER TABLE icinga_hosts DROP INDEX hosts_host_object_id_idx;

ALTER TABLE icinga_hoststatus DROP INDEX object_id;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_i_id_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_stat_upd_time_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_current_state_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_check_type_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_state_type_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_last_state_chg_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_notif_enabled_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_problem_ack_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_act_chks_en_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_pas_chks_en_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_event_hdl_en_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_flap_det_en_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_is_flapping_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_p_state_chg_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_latency_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_ex_time_idx;
-- ALTER TABLE icinga_hoststatus DROP INDEX hoststatus_sch_downt_d_idx;

-- ALTER TABLE icinga_services DROP INDEX instance_id;
ALTER TABLE icinga_services DROP INDEX service_object_id;
-- ALTER TABLE icinga_services DROP INDEX services_i_id_idx;
ALTER TABLE icinga_services DROP INDEX services_host_object_id_idx;
ALTER TABLE icinga_services DROP INDEX services_combined_object_idx;

ALTER TABLE icinga_servicestatus DROP INDEX object_id;
-- ALTER TABLE icinga_servicestatus DROP INDEX servicestatus_i_id_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_stat_upd_time_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_current_state_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_check_type_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_state_type_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_last_state_chg_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_notif_enabled_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_problem_ack_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_act_chks_en_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_pas_chks_en_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_event_hdl_en_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_flap_det_en_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_is_flapping_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_p_state_chg_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_latency_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_ex_time_idx;
-- ALTER TABLE icinga_servicestatus DROP INDEX srvcstatus_sch_downt_d_idx;

-- ALTER TABLE icinga_hostgroups DROP INDEX instance_id;
ALTER TABLE icinga_hostgroups DROP INDEX hostgroups_i_id_idx;

-- ALTER TABLE icinga_hostgroup_members DROP INDEX hostgroup_members_i_id_idx;
ALTER TABLE icinga_hostgroup_members DROP INDEX hstgrpmbrs_hgid_hoid;

ALTER TABLE icinga_objects DROP INDEX objecttype_id;
ALTER TABLE icinga_objects DROP INDEX objects_objtype_id_idx;
ALTER TABLE icinga_objects DROP INDEX objects_name1_idx;
ALTER TABLE icinga_objects DROP INDEX objects_name2_idx;
-- ALTER TABLE icinga_objects DROP INDEX objects_inst_id_idx;
ALTER TABLE icinga_objects DROP INDEX sla_idx_obj;

-- ALTER TABLE icinga_servicegroups DROP INDEX instance_id;
ALTER TABLE icinga_servicegroups DROP INDEX servicegroups_i_id_idx;

-- ALTER TABLE icinga_servicegroup_members DROP INDEX servicegroup_members_i_id_idx;
ALTER TABLE icinga_servicegroup_members DROP INDEX sgmbrs_sgid_soid;

############################
# DISPLAY_NAME PERFORMANCE #
############################

# Icinga Web 2's queries which filter for or order by host and service display_name are performed in a case-insensitive
# manner. Unfortunately, IDO's collation is case sensitive by default which renders possible indices useless.
# Let's fix that.

ALTER TABLE icinga_hosts MODIFY display_name VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
ALTER TABLE icinga_hosts ADD INDEX idx_hosts_display_name (display_name);

ALTER TABLE icinga_services MODIFY display_name VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
ALTER TABLE icinga_services ADD INDEX idx_services_display_name (display_name);

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
ALTER TABLE icinga_hosts ADD UNIQUE INDEX idx_hosts_host_object_id (host_object_id);

ALTER TABLE icinga_hoststatus MODIFY host_object_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_hoststatus ADD UNIQUE INDEX idx_hoststatus_host_object_id (host_object_id);

ALTER TABLE icinga_services MODIFY service_object_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_services MODIFY host_object_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_services ADD UNIQUE INDEX idx_services_service_object_id (service_object_id, host_object_id);  # Service based joins
ALTER TABLE icinga_services ADD UNIQUE INDEX idx_services_host_object_id (host_object_id, service_object_id);     # Host based joins

ALTER TABLE icinga_servicestatus MODIFY service_object_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_servicestatus ADD UNIQUE INDEX idx_servicestatus_service_object_id (service_object_id);

ALTER TABLE icinga_hostgroups MODIFY hostgroup_object_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_hostgroups ADD UNIQUE INDEX idx_hostgroups_hostgroup_object_id (hostgroup_object_id);

ALTER TABLE icinga_hostgroup_members MODIFY hostgroup_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_hostgroup_members MODIFY host_object_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_hostgroup_members ADD UNIQUE INDEX idx_icinga_hostgroup_members_host_object_id (host_object_id, hostgroup_id);

ALTER TABLE icinga_servicegroups MODIFY servicegroup_object_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_servicegroups ADD UNIQUE INDEX idx_servicegroups_servicegroup_object_id (servicegroup_object_id);

ALTER TABLE icinga_servicegroup_members MODIFY servicegroup_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_servicegroup_members MODIFY service_object_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_servicegroup_members ADD UNIQUE INDEX idx_icinga_servicegroup_members_service_object_id (service_object_id, servicegroup_id);

######################
# FILTER PERFORMANCE #
######################

ALTER TABLE icinga_objects MODIFY objecttype_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE icinga_objects MODIFY is_active TINYINT NOT NULL;
ALTER TABLE icinga_objects MODIFY name1 varchar(128) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL;
ALTER TABLE icinga_objects ADD UNIQUE INDEX idx_objects_objecttype_id (objecttype_id, is_active, name1, name2);

###################
# OPTIMIZE TABLES #
###################

ALTER TABLE icinga_hosts ENGINE=InnoDB;
ANALYZE TABLE icinga_hosts;

ALTER TABLE icinga_services ENGINE=InnoDB;
ANALYZE TABLE icinga_services;

ALTER TABLE icinga_hoststatus ENGINE=InnoDB;
ANALYZE TABLE icinga_hoststatus;

ALTER TABLE icinga_servicestatus ENGINE=InnoDB;
ANALYZE TABLE icinga_servicestatus;

ALTER TABLE icinga_hostgroups ENGINE=InnoDB;
ANALYZE TABLE icinga_hostgroups;

ALTER TABLE icinga_hostgroup_members ENGINE=InnoDB;
ANALYZE TABLE icinga_hostgroup_members;

ALTER TABLE icinga_objects ENGINE=InnoDB;
ANALYZE TABLE icinga_objects;

ALTER TABLE icinga_servicegroups ENGINE=InnoDB;
ANALYZE TABLE icinga_servicegroups;

ALTER TABLE icinga_servicegroup_members ENGINE=InnoDB;
ANALYZE TABLE icinga_servicegroup_members;
