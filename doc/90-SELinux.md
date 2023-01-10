# SELinux <a id="selinux"></a>

## Introduction <a id="selinux-introduction"></a>

SELinux is a mandatory access control (MAC) system on Linux which adds a fine granular permission system for access
to all resources on the system such as files, devices, networks and inter-process communication.

The most important questions are answered briefly in the [FAQ of the SELinux Project](https://selinuxproject.org/page/FAQ).
For more details on SELinux and how to actually use and administrate it on your systems have a look at
[Red Hat Enterprise Linux 7 - SELinux User's and Administrator's Guide](https://access.redhat.com/documentation/en-US/Red_Hat_Enterprise_Linux/7/html/SELinux_Users_and_Administrators_Guide/index.html).
For a simplified (and funny) introduction download the [SELinux Coloring Book](https://github.com/mairin/selinux-coloring-book).


## Policy <a id="selinux-policy"></a>

Icinga Web 2 is providing its own SELinux policy for RPM-based systems running the targeted policy
which confines Icinga Web 2 with support for all its modules.

The policy for Icinga Web 2 will also require the policy for Icinga 2 which provides access to its interfaces.
It covers only the scenario running Icinga Web 2 in Apache HTTP Server with mod_php.

Use your distribution's package manager to install the `icingaweb2-selinux` package.

## General <a id="selinux-policy-general"></a>

When the SELinux policy package for Icinga Web 2 is installed, it creates its own type of apache content and labels its
configuration `icingaweb2_config_t` to allow confining access to it.

## Types <a id="selinux-policy-types"></a>

The configuration is labeled `icingaweb2_config_t` and other services can request access to it by using the interfaces
`icingaweb2_read_config` and `icingaweb2_manage_config`.
Files requiring read access are labeled `icingaweb2_content_t`. Files requiring write access are labeled
`icingaweb2_rw_content_t`.

## Booleans <a id="selinux-policy-booleans"></a>

SELinux is based on the least level of access required for a service to run. Using booleans you can grant more access in
a defined way. The Icinga Web 2 policy package provides the following booleans.

**httpd_can_manage_icingaweb2_config**

Having this boolean enabled allows httpd to write to the configuration labeled `icingaweb2_config_t`. This is enabled by
default. If not needed, you can disable it for more security. But this will disable all web based configuration of
Icinga Web 2.

### Optional Booleans <a id="selinux-optional-booleans"></a>

The Icinga Web 2 policy package does not enable booleans not required by default. In order to allow these things,
you'll need to enable them manually. (i.e. with the tool `setsebool`)

**Ldap**  
If you want to allow httpd to connect to the ldap port, you must turn on the `httpd_can_connect_ldap` boolean.
Disabled by default.

## Bugreports <a id="selinux-bugreports"></a>

If you experience any problems while running SELinux in enforcing mode try to reproduce it in permissive mode. If the
problem persists, it is not related to SELinux because in permissive mode SELinux will not deny anything.

When filing a bug report please add the following information additionally to the
[common ones](https://icinga.com/icinga/faq/):
* Output of `semodule -l | grep -e icinga2 -e icingaweb2 -e nagios -e apache`
* Output of `semanage boolean -l | grep icinga`
* Output of `ps -eZ | grep httpd`
* Output of `audit2allow -li /var/log/audit/audit.log`

If access to a file is blocked and you can tell which one, please provided the output of `ls -lZ /path/to/file` and the
directory above.

If asked for full audit.log, add `-w /etc/shadow -p w` to `/etc/audit/rules.d/audit.rules` and restart the audit daemon.
Reproduce the problem and add `/var/log/audit/audit.log` to the bug report. The added audit rule includes
the path of files where access was denied.

If asked to provide full audit log with dontaudit rules disabled, execute `semodule -DB` before reproducing the problem.
After that enable the rules again to prevent auditd spamming your logfile by executing `semodule -B`.
