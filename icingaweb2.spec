#/**
# * This file is part of Icinga Web 2.
# *
# * Icinga Web 2 - Head for multiple monitoring backends.
# * Copyright (C) 2014 Icinga Development Team
# *
# * This program is free software; you can redistribute it and/or
# * modify it under the terms of the GNU General Public License
# * as published by the Free Software Foundation; either version 2
# * of the License, or (at your option) any later version.
# *
# * This program is distributed in the hope that it will be useful,
# * but WITHOUT ANY WARRANTY; without even the implied warranty of
# * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# * GNU General Public License for more details.
# *
# * You should have received a copy of the GNU General Public License
# * along with this program; if not, write to the Free Software
# * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
# *
# * @copyright  2014 Icinga Development Team <info@icinga.org>
# * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
# * @author     Icinga Development Team <info@icinga.org>
# *
# */

%define revision 1

%define configdir %{_sysconfdir}/icingaweb
%define sharedir %{_datadir}/icingaweb
%define prefixdir %{_datadir}/icingaweb
%define logdir %{sharedir}/log
%define usermodparam -a -G
%define logdir %{_localstatedir}/log/icingaweb

%if "%{_vendor}" == "suse"
%define phpname php5
%define phpzendname php5-ZendFramework
%define apache2modphpname apache2-mod_php5
%endif
# SLE 11 = 1110
%if 0%{?suse_version} == 1110
%define phpname php53
%define apache2modphpname apache2-mod_php53
%define usermodparam -A
%endif

%if "%{_vendor}" == "redhat"
%define phpname php
%define phpzendname php-ZendFramework
%endif

# el5 requires newer php53 rather than php (5.1)
%if 0%{?el5} || 0%{?rhel} == 5 || "%{?dist}" == ".el5"
%define phpname php53
%endif

%if "%{_vendor}" == "suse"
%define apacheconfdir  %{_sysconfdir}/apache2/conf.d
%define apacheuser wwwrun
%define apachegroup www
%define extcmdfile %{_localstatedir}/run/icinga2/cmd/icinga.cmd
%define livestatussocket %{_localstatedir}/run/icinga2/cmd/livestatus
%endif
%if "%{_vendor}" == "redhat"
%define apacheconfdir %{_sysconfdir}/httpd/conf.d
%define apacheuser apache
%define apachegroup apache
%define extcmdfile %{_localstatedir}/run/icinga2/cmd/icinga.cmd
%define livestatussocket %{_localstatedir}/run/icinga2/cmd/livestatus
%endif

Summary:        Open Source host, service and network monitoring Web UI
Name:           icingaweb2
Version:        0.0.1
Release:        %{revision}%{?dist}
License:        GPLv2
Group:          Applications/System
URL:            http://www.icinga.org
BuildArch:      noarch

%if "%{_vendor}" == "suse"
AutoReqProv:    Off
%endif

Source0:        icingaweb2-%{version}.tar.gz

BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root

BuildRequires:  %{phpname} >= 5.3.0
BuildRequires:  %{phpname}-devel >= 5.3.0
BuildRequires:  %{phpname}-ldap
BuildRequires:  %{phpname}-pdo
BuildRequires:  %{phpzendname}
%if "%{_vendor}" != "suse"
BuildRequires:  %{phpzendname}-Db-Adapter-Pdo
BuildRequires:  %{phpzendname}-Db-Adapter-Pdo-Mysql
BuildRequires:  %{phpzendname}-Db-Adapter-Pdo-Pgsql
%endif

%if "%{_vendor}" == "redhat"
%endif
%if "%{_vendor}" == "suse"
Requires:       %{phpname}-devel >= 5.3.0
BuildRequires:  %{phpname}-json
BuildRequires:  %{phpname}-sockets
BuildRequires:  %{phpname}-dom
%endif

Requires:       %{phpname} >= 5.3.0
Requires:       %{phpzendname}
Requires:       %{phpname}-ldap
Requires:       %{phpname}-pdo
%if "%{_vendor}" == "redhat"
Requires:       %{phpname}-common
Requires:       %{phpzendname}-Db-Adapter-Pdo
Requires:       %{phpzendname}-Db-Adapter-Pdo-Mysql
Requires:       php-pear
%endif
%if "%{_vendor}" == "suse"
Requires:       %{phpname}-pear
Requires:       %{phpname}-dom
Requires:       %{phpname}-tokenizer
Requires:       %{phpname}-gettext
Requires:       %{phpname}-ctype
Requires:       %{phpname}-json
Requires:       %{apache2modphpname}
%endif

Requires:       php-Icinga


%description
Icinga Web 2 for Icinga 2 or Icinga 1.x using multiple backends
for example DB IDO.

%package -n icingacli
Summary:        Icinga CLI
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}
Requires:       php-Icinga

%description -n icingacli
Icinga CLI using php-Icinga Icinga Web 2 backend.

%package -n php-Icinga
Summary:        Icinga Web 2 PHP Libraries
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}
Requires:       %{phpname} >= 5.3.0
Requires:       %{phpzendname}


%description -n php-Icinga
Icinga Web 2 PHP Libraries required by the web frontend and cli tool.


%prep
#VERSION=0.0.1; git archive --format=tar --prefix=icingaweb2-$VERSION/ HEAD | gzip >icingaweb2-$VERSION.tar.gz
%setup -q -n %{name}-%{version}

%build

%install
[ "%{buildroot}" != "/" ] && [ -d "%{buildroot}" ] && rm -rf %{buildroot}

# prepare configuration for sub packages

# install rhel apache config
install -D -m0644 packages/rpm/etc/httpd/conf.d/icingaweb.conf %{buildroot}/%{apacheconfdir}/icingaweb.conf

# install public, library, modules
%{__mkdir} -p %{buildroot}/%{sharedir}
%{__mkdir} -p %{buildroot}/%{logdir}
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/icingaweb
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/dashboard
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/icingaweb/modules
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/icingaweb/modules/monitoring
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/icingaweb/enabledModules

%{__cp} -r application library modules public %{buildroot}/%{sharedir}/

## config
# authentication is db only
install -D -m0644 packages/rpm/etc/icingaweb/authentication.ini %{buildroot}/%{_sysconfdir}/icingaweb/authentication.ini
# custom resource paths
install -D -m0644 packages/rpm/etc/icingaweb/resources.ini %{buildroot}/%{_sysconfdir}/icingaweb/resources.ini
# monitoring module (icinga2)
install -D -m0644 packages/rpm/etc/icingaweb/modules/monitoring/backends.ini %{buildroot}/%{_sysconfdir}/icingaweb/modules/monitoring/backends.ini
install -D -m0644 packages/rpm/etc/icingaweb/modules/monitoring/instances.ini %{buildroot}/%{_sysconfdir}/icingaweb/modules/monitoring/instances.ini

# enable the monitoring module by default
ln -s %{sharedir}/modules/monitoring %{buildroot}/%{_sysconfdir}/icingaweb/enabledModules/monitoring
## config

# install icingacli
install -D -m0755 packages/rpm/usr/bin/icingacli %{buildroot}/usr/bin/icingacli

%pre
# Add apacheuser in the icingacmd group
# If the group exists, add the apacheuser in the icingacmd group.
# It is not neccessary that icinga2-web is installed on the same system as
# icinga and only on systems with icinga installed the icingacmd
# group exists. In all other cases the user used for ssh access has
# to be added to the icingacmd group on the remote icinga server.
getent group icingacmd > /dev/null

if [ $? -eq 0 ]; then
%{_sbindir}/usermod %{usermodparam} icingacmd %{apacheuser}
fi

%preun

%post

%clean
[ "%{buildroot}" != "/" ] && [ -d "%{buildroot}" ] && rm -rf %{buildroot}

%files
# main dirs
%defattr(-,root,root)
%doc etc/schema doc packages/rpm/README.md
%attr(755,%{apacheuser},%{apachegroup}) %{sharedir}/public
%attr(755,%{apacheuser},%{apachegroup}) %{sharedir}/modules
# configs
%defattr(-,root,root)
%config(noreplace) %attr(-,root,root) %{apacheconfdir}/icingaweb.conf
%config(noreplace) %attr(-,%{apacheuser},%{apachegroup}) %{configdir}
# logs
%attr(2775,%{apacheuser},%{apachegroup}) %dir %{logdir}

%files -n php-Icinga
%attr(755,%{apacheuser},%{apachegroup}) %{sharedir}/application
%attr(755,%{apacheuser},%{apachegroup}) %{sharedir}/library

%files -n icingacli
%attr(0755,root,root) /usr/bin/icingacli

%changelog
