/*
WARNING
You should only apply the following IDO schema changes if you're using Icinga 2 in combination w/ Icinga Web 2.
The aim of the changes is to boost query performance of Icinga 2 and Icinga Web 2.
Query performance of other applications using IDO MAY DROP. Applying all changes may take some time.
Future updates to the IDO schema provided by Icinga 2 may fail.
You have been warned.

For using optimized queries in Web 2 matching the optimized schema you have to modify your backend configuration in:
/etc/icingaweb2/modules/monitoring/backends.ini

[backend-name]
...
use_optimized_queries=1
*/

/*
Optimized Views:

* Hosts
    * Service Status Summary
    * Unhandled Services Count
    * ORDER BY
        * Hostname (idx_hosts_display_name)
        * Severity (idx_hoststatus_severity, idx_hoststatus_severity_asc for MySQL 8+)
        * Current State (using soft states) (idx_hostsatus_state, idx_hostsatus_state_desc for MySQL 8+)
        * Last State Change (idx_hoststatus_last_state_change)
        * Last Check (idx_hoststatus_last_check)
* Host Detail
    * Host Services
    * PROBLEMS
        * Contacts, Contact Groups and Host Groups specify a GROUP BY clause which may not be necessary
        * History is missing
* Services
* Service Detail
* Host Groups
* Service Groups
* Comments
* Downtimes
* Notifications


General Problems:

* MySQL prefers idx_objects_count over suitable indices for ORDER BY ... LIMIT if there not that many rows in the database.
 */

################
# DROP INDICES #
################

/*
Why?
Some indices are created twice.
They don't follow any naming scheme.
Most indices are useless.
Most indices are on low cardinality columns.
Better indices relevant for Web 2 and Icinga 2 will be re-added.
New indices will be introduced.
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
CALL m_drop_table_indices('icinga_contactnotifications');
CALL m_drop_table_indices('icinga_contactgroups');
CALL m_drop_table_indices('icinga_contactgroup_members');
CALL m_drop_table_indices('icinga_host_contacts');
CALL m_drop_table_indices('icinga_host_contactgroups');
CALL m_drop_table_indices('icinga_service_contacts');
CALL m_drop_table_indices('icinga_service_contactgroups');
CALL m_drop_table_indices('icinga_comments');
CALL m_drop_table_indices('icinga_notifications');
CALL m_drop_table_indices('icinga_scheduleddowntime');


####################
# JOIN PERFORMANCE #
####################

/*
The best possible join type in MySQL is `eq_ref` which is used when all parts of an index are used by the join and
the index is a PRIMARY KEY or UNIQUE NOT NULL index.
The IDO schema already has some UNIQUE indices for joins but lacks NOT NULL in the column definitions. Fix it.
In addition, this script modifies columns to NOT NULL where appropriate for the following reasons
a) NOT NULL enables MySQL to efficiently use indices
b) NOT NULL requires less space
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
CALL m_create_index('icinga_hostgroup_members', 'idx_icinga_hostgroup_members', 'hostgroup_id, host_object_id');
CALL m_create_index('icinga_hostgroup_members', 'idx_icinga_hostgroup_host', 'host_object_id, hostgroup_id');

ALTER TABLE icinga_services MODIFY service_object_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_services MODIFY host_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_services', 'idx_services_service_object_id', 'service_object_id');
CALL m_create_index('icinga_services', 'idx_services_host', 'host_object_id, service_object_id');

ALTER TABLE icinga_servicestatus MODIFY service_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_servicestatus', 'idx_servicestatus_service_object_id', 'service_object_id');

ALTER TABLE icinga_servicegroups MODIFY servicegroup_object_id bigint(20) unsigned NOT NULL;
CALL m_create_unique_index('icinga_servicegroups', 'idx_servicegroups_servicegroup_object_id', 'servicegroup_object_id');

ALTER TABLE icinga_servicegroup_members MODIFY servicegroup_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_servicegroup_members MODIFY service_object_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_servicegroup_members', 'idx_icinga_servicegroup_members', 'servicegroup_id, service_object_id');
CALL m_create_index('icinga_servicegroup_members', 'idx_icinga_servicegroup_service', 'service_object_id, servicegroup_id');

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

ALTER TABLE icinga_notifications MODIFY object_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_notifications', 'icinga_notifications_object_id', 'object_id');

ALTER TABLE icinga_contactnotifications MODIFY contact_object_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_contactnotifications', 'idx_contactnotifications_notifications', 'notification_id, contact_object_id');

ALTER TABLE icinga_contacts MODIFY host_timeperiod_object_id bigint(20) unsigned NULL DEFAULT NULL;
ALTER TABLE icinga_contacts MODIFY service_timeperiod_object_id bigint(20) unsigned NULL DEFAULT NULL;
CALL m_create_index('icinga_contacts', 'idx_contacts_host_timeperiod_object_id', 'host_timeperiod_object_id');
CALL m_create_index('icinga_contacts', 'idx_contacts_service_timeperiod_object_id', 'service_timeperiod_object_id');

ALTER TABLE icinga_contactgroup_members MODIFY contactgroup_id bigint(20) unsigned NOT NULL;
ALTER TABLE icinga_contactgroup_members MODIFY contact_object_id bigint(20) unsigned NOT NULL;
CALL m_create_index('icinga_contactgroup_members', 'idx_icinga_contactgroup_members', 'contactgroup_id, contact_object_id');
CALL m_create_index('icinga_contactgroup_members', 'idx_icinga_contactgroup_contact', 'contact_object_id, contactgroup_id');


###################
# VIRTUAL COLUMNS #
###################

/*
At the moment it's impossible for Web 2 queries which filter for host or service state to use indices because they
respect the virtual state PENDING. A host or service is PENDING if it has not been checked yet. Instead of calculating
the state on query execution, we store it for every record taking PENDING into consideration. This reduces database
load and enables MySQL to use possible indices.
*/

ALTER TABLE icinga_hoststatus MODIFY current_state tinyint(1) unsigned NOT NULL;
ALTER TABLE icinga_hoststatus MODIFY has_been_checked tinyint(1) unsigned NOT NULL;
CALL m_add_column('icinga_hoststatus', 'is_problem', 'tinyint(1) unsigned NOT NULL AFTER current_state');
CALL m_add_column('icinga_hoststatus', 'is_handled', 'tinyint(1) unsigned NOT NULL AFTER is_problem');
CALL m_add_column('icinga_hoststatus', 'severity', 'smallint unsigned NOT NULL AFTER is_handled');

/* Set host state to PENDING for all hosts that have not been checked */
UPDATE icinga_hoststatus SET current_state = 99 WHERE has_been_checked = 0;

/* Set is_problem for all hosts. Problem hosts have been checked and are not UP */
UPDATE icinga_hoststatus SET is_problem = CASE WHEN current_state > 0 AND current_state != 99 THEN 1 ELSE 0 END;

/* Set is_handled for all hosts. Handled hosts are either in downtime or acknowledged */
UPDATE icinga_hoststatus SET is_handled =
CASE
    WHEN (problem_has_been_acknowledged + scheduled_downtime_depth) > 0
    THEN 1 ELSE 0
END;

/* Set severity for all hosts */
UPDATE icinga_hoststatus SET severity =
CASE current_state
    WHEN 0 THEN 1
    WHEN 1 THEN 64
    WHEN 2 THEN 32
    WHEN 99 THEN 16
    ELSE 256
END
+
CASE
    WHEN scheduled_downtime_depth > 0 THEN 1
    WHEN problem_has_been_acknowledged > 0 THEN 2
    ELSE 256
END;

DELIMITER //
DROP TRIGGER IF EXISTS t_insert_hoststatus //
CREATE TRIGGER t_insert_hoststatus BEFORE INSERT ON icinga_hoststatus
FOR EACH ROW
    BEGIN
        IF NEW.has_been_checked = 0 THEN
            SET NEW.current_state = 99;
        END IF;

        IF NEW.current_state > 0 AND NEW.current_state != 99 THEN
            SET NEW.is_problem = 1;
        ELSE
            SET NEW.is_problem = 0;
        END IF;

        IF NEW.problem_has_been_acknowledged + NEW.scheduled_downtime_depth > 0 THEN
            SET NEW.is_handled = 1;
        ELSE
            SET NEW.is_handled = 0;
        END IF;

        IF NEW.current_state = 99 THEN
            SET NEW.severity = 16;
        ELSEIF NEW.current_state = 0 THEN
            SET NEW.severity = 1;
        ELSEIF NEW.current_state = 1 THEN
            SET NEW.severity = 64;
        ELSEIF NEW.current_state = 2 THEN
            SET NEW.severity = 32;
        ELSE
            SET NEW.severity = 256;
        END IF;

        IF NEW.scheduled_downtime_depth > 0 THEN
            SET NEW.severity = NEW.severity + 1;
        ELSEIF NEW.problem_has_been_acknowledged > 0 THEN
            SET NEW.severity = NEW.severity + 2;
        ELSE
            SET NEW.severity = NEW.severity + 256;
        END IF;
    END //
DELIMITER ;

DELIMITER //
DROP TRIGGER IF EXISTS t_update_hoststatus //
CREATE TRIGGER t_update_hoststatus BEFORE UPDATE ON icinga_hoststatus
FOR EACH ROW
    BEGIN
        IF NEW.problem_has_been_acknowledged + NEW.scheduled_downtime_depth > 0 THEN
            SET NEW.is_handled = 1;
        ELSE
            SET NEW.is_handled = 0;
        END IF;

        IF NEW.current_state > 0 AND NEW.current_state != 99 THEN
            SET NEW.is_problem = 1;
        ELSE
            SET NEW.is_problem = 0;
        END IF;

        IF NEW.current_state = 99 THEN
            SET NEW.severity = 16;
        ELSEIF NEW.current_state = 0 THEN
            SET NEW.severity = 1;
        ELSEIF NEW.current_state = 1 THEN
            SET NEW.severity = 64;
        ELSEIF NEW.current_state = 2 THEN
            SET NEW.severity = 32;
        ELSE
            SET NEW.severity = 256;
        END IF;

        IF NEW.scheduled_downtime_depth > 0 THEN
            SET NEW.severity = NEW.severity + 1;
        ELSEIF NEW.problem_has_been_acknowledged > 0 THEN
            SET NEW.severity = NEW.severity + 2;
        ELSE
            SET NEW.severity = NEW.severity + 256;
        END IF;
    END //
DELIMITER ;

ALTER TABLE icinga_servicestatus MODIFY current_state tinyint(1) unsigned NOT NULL;
ALTER TABLE icinga_servicestatus MODIFY has_been_checked tinyint(1) unsigned NOT NULL;
CALL m_add_column('icinga_servicestatus', 'is_problem', 'tinyint(1) unsigned NOT NULL AFTER current_state');
CALL m_add_column('icinga_servicestatus', 'is_handled', 'tinyint(1) unsigned NOT NULL AFTER is_problem');
CALL m_add_column('icinga_servicestatus', 'severity', 'smallint unsigned NOT NULL AFTER is_handled');

/* Set service state to PENDING for all services that have not been checked */
UPDATE icinga_servicestatus SET current_state = 99 WHERE has_been_checked = 0;

/* Set is_problem for all services. Problem services have been checked and are not OK */
UPDATE icinga_servicestatus SET is_problem = CASE WHEN current_state > 0 AND current_state != 99 THEN 1 ELSE 0 END;

/* Set is_handled for all services. Handled services are either in downtime or acknowledged or their hosts have a problem */
UPDATE icinga_servicestatus ss
INNER JOIN icinga_services s ON s.service_object_id = ss.service_object_id
INNER JOIN icinga_hoststatus hs ON hs.host_object_id = s.host_object_id
SET ss.is_handled =
CASE
    WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + hs.is_problem) > 0
    THEN 1 ELSE 0
END;

/* Set severity for all services */
UPDATE icinga_servicestatus ss
INNER JOIN icinga_services s ON s.service_object_id = ss.service_object_id
INNER JOIN icinga_hoststatus hs ON hs.host_object_id = s.host_object_id
SET ss.severity =
CASE ss.current_state
    WHEN 0 THEN 1
    WHEN 1 THEN 32
    WHEN 2 THEN 128
    WHEN 3 THEN 64
    WHEN 99 THEN 16
    ELSE 256
END
+
CASE
    WHEN ss.scheduled_downtime_depth > 0 THEN 1
    WHEN ss.problem_has_been_acknowledged > 0 THEN 2
    WHEN hs.is_problem > 0 THEN 4
    ELSE 256
END;

DELIMITER //
DROP TRIGGER IF EXISTS t_insert_servicestatus //
CREATE TRIGGER t_insert_servicestatus BEFORE INSERT ON icinga_servicestatus
FOR EACH ROW
  BEGIN
    DECLARE host_problem tinyint unsigned;

    SELECT
        hs.current_state
    FROM
        icinga_hoststatus hs
    INNER JOIN
        icinga_services s
        ON s.host_object_id = hs.host_object_id
        AND s.service_object_id = NEW.service_object_id
    INTO host_problem;

    IF NEW.has_been_checked = 0 THEN
        SET NEW.current_state = 99;
    END IF;

    IF NEW.current_state > 0 AND NEW.current_state != 99 THEN
        SET NEW.is_problem = 1;
    ELSE
        SET NEW.is_problem = 0;
    END IF;

    IF NEW.problem_has_been_acknowledged + NEW.scheduled_downtime_depth + host_problem > 0 THEN
        SET NEW.is_handled = 1;
    ELSE
        SET NEW.is_handled = 0;
    END IF;

    IF NEW.current_state = 99 THEN
        SET NEW.severity = 16;
    ELSEIF NEW.current_state = 0 THEN
        SET NEW.severity = 1;
    ELSEIF NEW.current_state = 1 THEN
        SET NEW.severity = 32;
    ELSEIF NEW.current_state = 2 THEN
        SET NEW.severity = 128;
    ELSEIF NEW.current_state = 3 THEN
        SET NEW.severity = 64;
    ELSE
        SET NEW.severity = 256;
    END IF;

    IF NEW.scheduled_downtime_depth > 0 THEN
        SET NEW.severity = NEW.severity + 1;
    ELSEIF NEW.problem_has_been_acknowledged > 0 THEN
        SET NEW.severity = NEW.severity + 2;
    ELSEIF host_problem > 0 THEN
        SET NEW.severity = NEW.severity + 4;
    ELSE
        SET NEW.severity = NEW.severity + 256;
    END IF;
  END //
DELIMITER ;

DELIMITER //
DROP TRIGGER IF EXISTS t_update_servicestatus //
CREATE TRIGGER t_update_servicestatus BEFORE UPDATE ON icinga_servicestatus
FOR EACH ROW
  BEGIN
    DECLARE host_problem tinyint unsigned;

    SELECT
        hs.current_state
    FROM
        icinga_hoststatus hs
    INNER JOIN
        icinga_services s
        ON s.host_object_id = hs.host_object_id
        AND s.service_object_id = NEW.service_object_id
    INTO host_problem;

    IF NEW.current_state > 0 AND NEW.current_state != 99 THEN
        SET NEW.is_problem = 1;
    ELSE
        SET NEW.is_problem = 0;
    END IF;

    IF NEW.problem_has_been_acknowledged + NEW.scheduled_downtime_depth + host_problem > 0 THEN
        SET NEW.is_handled = 1;
    ELSE
        SET NEW.is_handled = 0;
    END IF;

    IF NEW.current_state = 99 THEN
        SET NEW.severity = 16;
    ELSEIF NEW.current_state = 0 THEN
        SET NEW.severity = 1;
    ELSEIF NEW.current_state = 1 THEN
        SET NEW.severity = 32;
    ELSEIF NEW.current_state = 2 THEN
        SET NEW.severity = 128;
    ELSEIF NEW.current_state = 3 THEN
        SET NEW.severity = 64;
    ELSE
        SET NEW.severity = 256;
    END IF;

    IF NEW.scheduled_downtime_depth > 0 THEN
        SET NEW.severity = NEW.severity + 1;
    ELSEIF NEW.problem_has_been_acknowledged > 0 THEN
        SET NEW.severity = NEW.severity + 2;
    ELSEIF host_problem > 0 THEN
        SET NEW.severity = NEW.severity + 4;
    ELSE
        SET NEW.severity = NEW.severity + 256;
    END IF;
  END //
DELIMITER ;


################################
# FILTER AND ORDER PERFORMANCE #
################################

/*
Icinga Web 2's queries which filter for or order by host and service display_name are performed in a case-insensitive
manner. Unfortunately, IDO's collation is case sensitive by default which renders possible indices useless.
Let's fix that.
*/

ALTER TABLE icinga_hosts MODIFY display_name varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
CALL m_create_index('icinga_hosts', 'idx_hosts_display_name', 'display_name');

ALTER TABLE icinga_services MODIFY display_name varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
CALL m_create_index('icinga_services', 'idx_services_display_name', 'display_name');

/*
Icinga 2.5 already sets alias columns from text to varchar(255). This is a good start. But Web 2's queries filter for
or order by host and service group alias are performed in a case-insensitive manner. So, let's add the case
insensitive collation.
*/

ALTER TABLE icinga_hostgroups MODIFY alias varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
ALTER TABLE icinga_servicegroups MODIFY alias varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
ALTER TABLE icinga_contacts MODIFY alias varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
ALTER TABLE icinga_contactgroups MODIFY alias varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci;

CALL m_create_index('icinga_hostgroups', 'idx_hostgroups_alias', 'alias');
CALL m_create_index('icinga_servicegroups', 'idx_servicegroups_alias', 'alias');
CALL m_create_index('icinga_contacts', 'idx_contacts_alias', 'alias');
CALL m_create_index('icinga_contactgroups', 'idx_contactgroups_alias', 'alias');

CALL m_create_index('icinga_objects', 'idx_objects_count', 'objecttype_id, is_active');
CALL m_create_index('icinga_objects', 'idx_objects_filter', 'name1, objecttype_id, is_active, name2');

CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_severity', 'severity DESC, last_state_change DESC');
CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_severity_asc', 'severity ASC, last_state_change DESC');
CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_state', 'current_state ASC, last_state_change DESC');
CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_state_desc', 'current_state DESC, last_state_change DESC');
CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_last_state_change', 'last_state_change');
CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_last_check', 'last_check');
CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_list_problems', 'is_problem, severity, last_state_change');
CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_problems', 'is_handled, current_state');

CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_severity', 'severity DESC, last_state_change DESC');
CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_severity_asc', 'severity ASC, last_state_change DESC');
CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_state', 'current_state ASC, last_state_change DESC');
CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_state_desc', 'current_state DESC, last_state_change DESC');
CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_last_state_change', 'last_state_change');
CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_last_check', 'last_check');
CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_list_problems', 'is_problem, severity, last_state_change');
CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_problems', 'is_handled, current_state');


-- CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_count_problems', 'is_problem, is_handled');
-- CALL m_create_index('icinga_hoststatus', 'idx_hoststatus_recently_recovered', 'current_state, last_state_change');

-- CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_count_problems', 'is_problem, is_handled');
-- CALL m_create_index('icinga_servicestatus', 'idx_servicestatus_recently_recovered', 'current_state, last_state_change');

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

ALTER TABLE icinga_contactnotifications ENGINE=InnoDB;
ANALYZE TABLE icinga_contactnotifications;

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

ALTER TABLE icinga_notifications ENGINE=InnoDB;
ANALYZE TABLE icinga_notifications;

ALTER TABLE icinga_scheduleddowntime ENGINE=InnoDB;
ANALYZE TABLE icinga_scheduleddowntime;
