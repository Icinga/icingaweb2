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

############################
# display_name performance #
############################

# Icinga Web 2's queries which filter for or order by host and service display_name are performed in a case-insensitive
# manner. Unfortunately, IDO's collation is case sensitive by default which renders possible indices useless.
# Let's fix that.

ALTER TABLE icinga_hosts MODIFY display_name VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
ALTER TABLE icinga_hosts ADD INDEX idx_hosts_display_name (display_name);
ANALYZE TABLE icinga_hosts;

ALTER TABLE icinga_services MODIFY display_name VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci;
ALTER TABLE icinga_services ADD INDEX idx_services_display_name (display_name);
ANALYZE TABLE icinga_services;



