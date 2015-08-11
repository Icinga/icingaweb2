Fix steps that are always provisioned:

==> default: Notice: /Stage[main]/Icinga2/Icinga2::Feature[statusdata]/Parent_dirs[/etc/icinga2/features-enabled/statusdata.conf]/Exec[parent_dirs-/etc/icinga2/features-enabled/statusdata.conf]/returns: executed successfully
==> default: Notice: /Stage[main]/Icinga2_dev/Icinga2::Config[constants]/Parent_dirs[/etc/icinga2/constants.conf]/Exec[parent_dirs-/etc/icinga2/constants.conf]/returns: executed successfully
==> default: Notice: /Stage[main]/Icinga2_dev/Icinga2::Config[conf.d/commands]/Parent_dirs[/etc/icinga2/conf.d/commands.conf]/Exec[parent_dirs-/etc/icinga2/conf.d/commands.conf]/returns: executed successfully
==> default: Notice: /Stage[main]/Icinga2/Icinga2::Feature[command]/Parent_dirs[/etc/icinga2/features-enabled/command.conf]/Exec[parent_dirs-/etc/icinga2/features-enabled/command.conf]/returns: executed successfully
==> default: Notice: /Stage[main]/Icingaweb2_dev/Pgsql::Database::Populate[icingaweb]/Exec[populate-icingaweb-pgsql-db]/returns: executed successfully
==> default: Notice: /Stage[main]/Php/Exec[php-timezone]/returns: executed successfully
==> default: Notice: /Stage[main]/Icinga2_dev/Icinga2::Config[conf.d/test-config]/Parent_dirs[/etc/icinga2/conf.d/test-config.conf]/Exec[parent_dirs-/etc/icinga2/conf.d/test-config.conf]/returns: executed successfully
==> default: Notice: /Stage[main]/Icingaweb2_dev/Exec[populate-openldap]/returns: executed successfully
==> default: Notice: /Stage[main]/Monitoring_test_config/Git_cmmi[Monitoring-Generator-TestConfig]/Cmmi_dir[Monitoring-Generator-TestConfig]/Exec[configure-Monitoring-Generator-TestConfig]/returns: executed successfully
==> default: Notice: /Stage[main]/Monitoring_test_config/Git_cmmi[Monitoring-Generator-TestConfig]/Cmmi_dir[Monitoring-Generator-TestConfig]/Exec[make-Monitoring-Generator-TestConfig]/returns: executed successfully
==> default: Notice: /Stage[main]/Icinga2/Icinga2::Feature[compatlog]/Parent_dirs[/etc/icinga2/features-enabled/compatlog.conf]/Exec[parent_dirs-/etc/icinga2/features-enabled/compatlog.conf]/returns: executed successfully
==> default: Notice: /Stage[main]/Icinga2_mysql/Icinga2::Feature[ido-mysql]/Parent_dirs[/etc/icinga2/features-enabled/ido-mysql.conf]/Exec[parent_dirs-/etc/icinga2/features-enabled/ido-mysql.conf]/returns: executed successfully
==> default: Notice: /Stage[main]/Apache/Service[httpd]: Triggered 'refresh' from 1 events
==> default: Notice: /Stage[main]/Icingaweb2_dev/Exec[enable-monitoring-module]/returns: executed successfully
==> default: Notice: /Stage[main]/Icingaweb2_dev/Exec[enable-test-module]/returns: executed successfully
==> default: Notice: /Stage[main]/Icinga2_mysql/Icinga2::Feature[ido-mysql]/Icinga2::Config[features-available/ido-mysql]/Parent_dirs[/etc/icinga2/features-available/ido-mysql.conf]/Exec[parent_dirs-/etc/icinga2/features-available/ido-mysql.conf]/returns: executed successfully
==> default: Notice: /Stage[main]/Icinga2_pgsql/Icinga2::Feature[ido-pgsql]/Icinga2::Config[features-available/ido-pgsql]/Parent_dirs[/etc/icinga2/features-available/ido-pgsql.conf]/Exec[parent_dirs-/etc/icinga2/features-available/ido-pgsql.conf]/returns: executed successfully
==> default: Notice: /Stage[main]/Icinga2_pgsql/Icinga2::Feature[ido-pgsql]/Parent_dirs[/etc/icinga2/features-enabled/ido-pgsql.conf]/Exec[parent_dirs-/etc/icinga2/features-enabled/ido-pgsql.conf]/returns: executed successfully

Fix provisioning for CentOS 7
