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

ANALYZE TABLE icinga_notifications;
ALTER TABLE icinga_notifications ENGINE=InnoDB;

ANALYZE TABLE icinga_contactnotifications;
ALTER TABLE icinga_contactnotifications ENGINE=InnoDB;
