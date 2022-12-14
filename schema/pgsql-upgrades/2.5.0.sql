/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

ALTER TABLE "icingaweb_group_membership" ALTER COLUMN "username" TYPE character varying(254);
ALTER TABLE "icingaweb_user" ALTER COLUMN "name" TYPE character varying(254);
ALTER TABLE "icingaweb_user_preference" ALTER COLUMN "username" TYPE character varying(254);
