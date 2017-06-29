# Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+

ALTER TABLE `icingaweb_group_membership` MODIFY COLUMN `username` varchar(254) COLLATE utf8_unicode_ci NOT NULL;
ALTER TABLE `icingaweb_user` MODIFY COLUMN `name` varchar(254) COLLATE utf8_unicode_ci NOT NULL;
ALTER TABLE `icingaweb_user_preference` MODIFY COLUMN `username` varchar(254) COLLATE utf8_unicode_ci NOT NULL;
