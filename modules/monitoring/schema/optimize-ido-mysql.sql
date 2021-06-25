#################
# DROP INDICES  #
#################

/*
* Some indices are created twice
* They don't follow any naming scheme
* Most indices are useless
* Most indices are on low cardinality columns
* Better indices relevant for Web and Icinga 2 will be re-added
* New indices will be introduced
*/

CALL m_drop_table_indices('icinga_objects');
CALL m_drop_table_indices('icinga_hosts');
CALL m_drop_table_indices('icinga_hoststatus');
CALL m_drop_table_indices('icinga_hostgroups');
CALL m_drop_table_indices('icinga_hostgroup_members');
CALL m_drop_table_indices('icinga_services');
CALL m_drop_table_indices('icinga_servicestatus');
CALL m_drop_table_indices('icinga_servicegroups');
CALL m_drop_table_indices('icinga_servicegroup_members');
CALL m_drop_table_indices('icinga_customvariablestatus');
CALL m_drop_table_indices('icinga_contacts');
-- CALL m_drop_table_indices('icinga_contactnotifications');
CALL m_drop_table_indices('icinga_contactgroups');
CALL m_drop_table_indices('icinga_contactgroup_members');
CALL m_drop_table_indices('icinga_host_contacts');
CALL m_drop_table_indices('icinga_host_contactgroups');
CALL m_drop_table_indices('icinga_service_contacts');
CALL m_drop_table_indices('icinga_service_contactgroups');
CALL m_drop_table_indices('icinga_comments');
-- CALL m_drop_table_indices('icinga_notifications');
CALL m_drop_table_indices('icinga_scheduleddowntime');

####################
# JOIN PERFORMANCE #
####################

/*
The best possible join type in MySQL is `eq_ref` which is used when all parts of an index are used by the join and
the index is a PRIMARY KEY or UNIQUE NOT NULL index.
The IDO schema already has some UNIQUE indices for joins but lacks NOT NULL in the column definitions.
In addition, this script modifies columns to NOT NULL where appropriate for the following reasons
a) NOT NULL enables MySQL to efficiently use indices
b) NOT NULL may require less space
c) NOT NULL columns must be not null
*/

ALTER TABLE icinga_hosts MODIFY host_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_hosts', 'idx_hosts_host_object_id', 'host_object_id');

ALTER TABLE icinga_hoststatus MODIFY host_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_hoststatus', 'idx_hoststatus_host_object_id', 'host_object_id');

ALTER TABLE icinga_hostgroups MODIFY hostgroup_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_hostgroups', 'idx_hostgroups_hostgroup_object_id', 'hostgroup_object_id');

ALTER TABLE icinga_hostgroup_members MODIFY hostgroup_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_hostgroup_members MODIFY host_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_hostgroup_members', 'idx_icinga_hostgroup_members', 'hostgroup_id, host_object_id');
CALL m_create_index('icinga_hostgroup_members', 'idx_icinga_hostgroup_of_host', 'host_object_id, hostgroup_id');

ALTER TABLE icinga_services MODIFY service_object_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_services MODIFY host_object_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_services', 'idx_services', 'service_object_id, host_object_id');
-- CALL m_create_unique_index('icinga_services', 'idx_services_service_object_id', 'service_object_id');
-- CALL m_create_index('icinga_services', 'idx_services_of_host', 'host_object_id, service_object_id');

ALTER TABLE icinga_servicestatus MODIFY service_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_servicestatus', 'idx_servicestatus_service_object_id', 'service_object_id');

ALTER TABLE icinga_servicegroups MODIFY servicegroup_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_servicegroups', 'idx_servicegroups_servicegroup_object_id', 'servicegroup_object_id');

ALTER TABLE icinga_servicegroup_members MODIFY servicegroup_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_servicegroup_members MODIFY service_object_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_servicegroup_members', 'idx_icinga_servicegroup_members', 'servicegroup_id, service_object_id');
CALL m_create_index('icinga_servicegroup_members', 'idx_icinga_servicegroup_of_service', 'service_object_id, servicegroup_id');

ALTER TABLE icinga_customvariablestatus MODIFY object_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_customvariablestatus MODIFY varname varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL;
ALTER TABLE icinga_customvariablestatus MODIFY varvalue text CHARACTER SET latin1 NOT NULL;
CALL m_create_unique_index('icinga_customvariablestatus', 'idx_icinga_customvariablestatus_customvar', 'object_id, varname, varvalue(255)');

ALTER TABLE icinga_contactgroups MODIFY contactgroup_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_contactgroups', 'idx_contactgroups_contactgroup_object_id', 'contactgroup_object_id');

ALTER TABLE icinga_contacts MODIFY contact_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_contacts', 'idx_contacts_contact_object_id', 'contact_object_id');

ALTER TABLE icinga_host_contactgroups MODIFY contactgroup_object_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_host_contactgroups MODIFY host_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_host_contactgroups', 'idx_host_contactgroups_members', 'host_id, contactgroup_object_id');

ALTER TABLE icinga_host_contacts MODIFY contact_object_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_host_contacts MODIFY host_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_host_contacts', 'idx_host_contacts_members', 'host_id, contact_object_id');

ALTER TABLE icinga_service_contactgroups MODIFY contactgroup_object_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_service_contactgroups MODIFY service_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_service_contactgroups', 'idx_service_contactgroups_members', 'service_id, contactgroup_object_id');

ALTER TABLE icinga_service_contacts MODIFY contact_object_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_service_contacts MODIFY service_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_service_contacts', 'idx_service_contacts_members', 'service_id, contact_object_id');

ALTER TABLE icinga_scheduleddowntime MODIFY object_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_scheduleddowntime', 'icinga_scheduleddowntime_object_id', 'object_id');

ALTER TABLE icinga_comments MODIFY object_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_comments', 'icinga_comments_object_id', 'object_id');

ALTER TABLE icinga_timeperiods MODIFY timeperiod_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_timeperiods', 'idx_timperiods_timeperiod_object_id', 'timeperiod_object_id');

-- ALTER TABLE icinga_notifications MODIFY object_id bigint(20) unsigned NOT NULL;
-- CALL m_create_index('icinga_notifications', 'icinga_notifications_object_id', 'object_id');

-- ALTER TABLE icinga_contactnotifications MODIFY contact_object_id bigint(20) unsigned NOT NULL;
-- CALL m_create_index('icinga_contactnotifications', 'idx_contactnotifications_notifications', 'notification_id, contact_object_id');

ALTER TABLE icinga_contacts MODIFY host_timeperiod_object_id bigint(20) unsigned NULL DEFAULT NULL;
ALTER TABLE icinga_contacts MODIFY service_timeperiod_object_id bigint(20) unsigned NULL DEFAULT NULL;
CALL m_create_index('icinga_contacts', 'idx_contacts_host_timeperiod_object_id', 'host_timeperiod_object_id');
CALL m_create_index('icinga_contacts', 'idx_contacts_service_timeperiod_object_id', 'service_timeperiod_object_id');

ALTER TABLE icinga_contactgroup_members MODIFY contactgroup_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_contactgroup_members MODIFY contact_object_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_contactgroup_members', 'idx_icinga_contactgroup_members', 'contactgroup_id, contact_object_id');
CALL m_create_index('icinga_contactgroup_members', 'idx_icinga_contactgroup_contact', 'contact_object_id, contactgroup_id');

################################
# FILTER AND ORDER PERFORMANCE #
################################

/*
Icinga Web's queries which filter for or order by host and service display_name are performed in a case-insensitive
manner. Unfortunately, IDO's collation is case sensitive by default which renders possible indices useless.
*/

ALTER TABLE icinga_hosts MODIFY alias varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci;

ALTER TABLE icinga_hosts MODIFY display_name varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
CALL m_create_index('icinga_hosts', 'idx_hosts_display_name', 'display_name');

ALTER TABLE icinga_services MODIFY display_name varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
CALL m_create_index('icinga_services', 'idx_services_display_name', 'display_name');

/*
Icinga 2.5 already sets alias columns from text to varchar(255). This is a good start. But Web's queries filter for
or order by host and service group alias are performed in a case-insensitive manner. So, let's add the case
insensitive collation.
*/

ALTER TABLE icinga_hostgroups MODIFY alias varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE icinga_servicegroups MODIFY alias varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE icinga_contacts MODIFY alias varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE icinga_contactgroups MODIFY alias varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;

CALL m_create_index('icinga_hostgroups', 'idx_hostgroups_alias', 'alias');
CALL m_create_index('icinga_servicegroups', 'idx_servicegroups_alias', 'alias');
CALL m_create_index('icinga_contacts', 'idx_contacts_alias', 'alias');
CALL m_create_index('icinga_contactgroups', 'idx_contactgroups_alias', 'alias');

/*
Indices for common Icinga Web filters:
 */
CALL m_create_index('icinga_objects', 'idx_objects', 'objecttype_id, is_active, name1, name2');
-- CALL m_create_index('icinga_objects', 'idx_objects_wo_objecttype_id', 'is_active, name1, name2');

CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_last_state_change', 'last_state_change');
CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_last_check', 'last_check');

CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_last_state_change', 'last_state_change');
CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_last_check', 'last_check');

-- CALL m_create_index('icinga_notifications', 'idx_notifications_start_time', 'start_time');

##################
# REBUILD TABLES #
##################

ALTER TABLE icinga_objects ENGINE=InnoDB;
ANALYZE TABLE icinga_objects;

ALTER TABLE icinga_hosts ENGINE=InnoDB;
ANALYZE TABLE icinga_hosts;

ALTER TABLE icinga_hoststatus ENGINE=InnoDB;
ANALYZE TABLE icinga_hoststatus;

ALTER TABLE icinga_hostgroups ENGINE=InnoDB;
ANALYZE TABLE icinga_hostgroups;

ALTER TABLE icinga_hostgroup_members ENGINE=InnoDB;
ANALYZE TABLE icinga_hostgroup_members;

ALTER TABLE icinga_services ENGINE=InnoDB;
ANALYZE TABLE icinga_services;

ALTER TABLE icinga_servicestatus ENGINE=InnoDB;
ANALYZE TABLE icinga_servicestatus;

ALTER TABLE icinga_servicegroups ENGINE=InnoDB;
ANALYZE TABLE icinga_servicegroups;

ALTER TABLE icinga_servicegroup_members ENGINE=InnoDB;
ANALYZE TABLE icinga_servicegroup_members;

ALTER TABLE icinga_customvariablestatus ENGINE=InnoDB;
ANALYZE TABLE icinga_customvariablestatus;

ALTER TABLE icinga_contacts ENGINE=InnoDB;
ANALYZE TABLE icinga_contacts;

-- ALTER TABLE icinga_contactnotifications ENGINE=InnoDB;
-- ANALYZE TABLE icinga_contactnotifications;

ALTER TABLE icinga_contactgroups ENGINE=InnoDB;
ANALYZE TABLE icinga_contactgroups;

ALTER TABLE icinga_contactgroup_members ENGINE=InnoDB;
ANALYZE TABLE icinga_contactgroup_members;

ALTER TABLE icinga_host_contacts ENGINE=InnoDB;
ANALYZE TABLE icinga_host_contacts;

ALTER TABLE icinga_host_contactgroups ENGINE=InnoDB;
ANALYZE TABLE icinga_host_contactgroups;

ALTER TABLE icinga_service_contacts ENGINE=InnoDB;
ANALYZE TABLE icinga_service_contacts;

ALTER TABLE icinga_service_contactgroups ENGINE=InnoDB;
ANALYZE TABLE icinga_service_contactgroups;

ALTER TABLE icinga_comments ENGINE=InnoDB;
ANALYZE TABLE icinga_comments;

-- ALTER TABLE icinga_notifications ENGINE=InnoDB;
-- ANALYZE TABLE icinga_notifications;

ALTER TABLE icinga_scheduleddowntime ENGINE=InnoDB;
ANALYZE TABLE icinga_scheduleddowntime;
