# $Id$
# Authority: The icinga devel team <icinga-devel at lists.sourceforge.net>
# Upstream: The icinga devel team <icinga-devel at lists.sourceforge.net>
# ExcludeDist: el4 el3

%define revision 0

# FIXME logdir must be set to /var/log/icingaweb
#%define logdir %{_localstatedir}/log/%{name}
%define logdir %{_datadir}/icingaweb/var/log 
%define sharedir %{_datadir}/icingaweb
%define prefixdir %{_datadir}/icingaweb
%define configdir %{_sysconfdir}/icingaweb

%if "%{_vendor}" == "suse"
%define phpname php5
%define phpzendname php5-ZendFramework
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
%define extcmdfile-1x %{_localstatedir}/icinga/rw/icinga.cmd
%define livestatussocket-1x %{_localstatedir}/icinga/rw/live
%endif
%if "%{_vendor}" == "redhat"
%define apacheconfdir %{_sysconfdir}/httpd/conf.d
%define apacheuser apache
%define apachegroup apache
%define extcmdfile-1x %{_localstatedir}/spool/icinga/cmd/icinga.cmd
%define livestatussocket-1x %{_localstatedir}/spool/icinga/cmd/live
%endif

Summary:        Open Source host, service and network monitoring Web UI
Name:           icingaweb
Version:        1.0.0
Release:        %{revision}%{?dist}
License:        GPLv2
Group:          Applications/System
URL:            http://www.icinga.org
BuildArch:      noarch

%if "%{_vendor}" == "suse"
AutoReqProv:    Off
%endif

Source0:        https://downloads.sourceforge.net/project/icinga/%{name}/%{version}/%{name}-%{version}.tar.gz

BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root

BuildRequires:  %{phpname} >= 5.3.0
BuildRequires:  %{phpname}-devel >= 5.3.0
BuildRequires:  %{phpname}-ldap
BuildRequires:  %{phpname}-pdo
BuildRequires:  %{phpzendname}
BuildRequires:  %{phpzendname}-Db-Adapter-Pdo
BuildRequires:  %{phpzendname}-Db-Adapter-Pdo-Mysql
BuildRequires:  %{phpzendname}-Db-Adapter-Pdo-Pgsql

%if "%{_vendor}" == "redhat"
%endif
%if "%{_vendor}" == "suse"
Requires:       %{phpname}-devel >= 5.3.0
BuildRequires:  %{phpname}-json
BuildRequires:  %{phpname}-sockets
BuildRequires:  %{phpname}-dom
%endif

Requires:       %{phpname} >= 5.3.0
Requires:  	%{phpzendname}
Requires:       %{phpname}-ldap
Requires:       %{phpname}-pdo
%if "%{_vendor}" == "redhat"
Requires:       %{phpname}-common
Requires:       php-pear
%endif
%if "%{_vendor}" == "suse"
Requires:       %{phpname}-pear
Requires:       %{phpname}-dom
Requires:       %{phpname}-tokenizer
Requires:       %{phpname}-gettext
Requires:       %{phpname}-ctype
Requires:       %{phpname}-json
Requires:       apache2-mod_php5
%endif

Requires:	%{name}-doc


%description
IcingaWeb for Icinga 2 or Icinga 1.x using status data,
IDOUtils or Livestatus as backend provider.

%package doc
Summary:        documentation for IcingaWeb 
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}


%description doc
Documentation for IcingaWeb.

%package config-internal-mysql
Summary:        config for internal mysql database
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}
Requires:       %{phpzendname}-Db-Adapter-Pdo
Requires:       %{phpzendname}-Db-Adapter-Pdo-Mysql

%description config-internal-mysql
Configuration for internal mysql database.

%package config-internal-pgsql
Summary:        config for internal pgsql database
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}
Requires:       %{phpzendname}-Db-Adapter-Pdo
Requires:       %{phpzendname}-Db-Adapter-Pdo-Pgsql

%description config-internal-pgsql
Configuration for internal pgsql database.

%package config-backend-statusdata-1x
Summary:        Backend config for status data 
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}
Provides:	%{name}-config-statusdata

%description config-backend-statusdata-1x
Backend config for status data provided by Icinga 1.x Core.

%package config-backend-ido-mysql-1x
Summary:        Backend config for icinga 1.x ido mysql database
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}
Requires:	%{phpname}-mysql
Provides:	%{name}-config-ido-mysql

%description config-backend-ido-mysql-1x
Backend config for ido mysql database provided by
Icinga 1.x IDOUtils with MySQL.

%package config-backend-ido-pgsql-1x
Summary:        Backend config for icinga 1.x ido pgsql database
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}
Requires:	%{phpname}-pgsql
Provides:	%{name}-config-ido-pgsql

%description config-backend-ido-pgsql-1x
Backend config for ido mysql database provided by
Icinga 1.x IDOUtils with PostgreSQL.

%package config-backend-livestatus-1x
Summary:        Backend config for icinga 1.x livestatus
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}
Provides:	%{name}-config-livestatus

%description config-backend-livestatus-1x
Backend config for livestatus provided by Icinga 1.x
with mk_livestatus NEB module.

%package config-backend-commands-1x
Summary:        Backend config for icinga 1.x commands 
Group:          Applications/System
Requires:       %{name} = %{version}-%{release}
Provides:	%{name}-config-commands

%description config-backend-commands-1x
Backend config for external command pipe provided by
Icinga 1.x

%prep
%setup -q -n %{name}-%{version}

%build
%configure \
    --prefix="%{prefixdir}" \
    --datadir="%{sharedir}" \
    --datarootdir="%{sharedir}" \
    --sysconfdir="%{configdir}" \
    --with-icingaweb-config-path='%{configdir}' \
    --with-web-path='/icingaweb' \
    --with-httpd-config-path=%{apacheconfdir} \
    --with-web-user='%{apacheuser}' \
    --with-web-group='%{apachegroup}' \
    --with-icinga-commandpipe='%{extcmdfile-1x}' \
    --with-livestatus-socket='%{livestatussocket-1x}'
    # TODO --with-log-dir='%{logdir}'

%install
[ "%{buildroot}" != "/" ] && [ -d "%{buildroot}" ] && rm -rf %{buildroot}
%{__mkdir} -p %{buildroot}/%{apacheconfdir}
%{__make} install \
    install-apache-config \
    DESTDIR="%{buildroot}" \
    INSTALL_OPTS="" \
    COMMAND_OPTS="" \
    INSTALL_OPTS_WEB="" \
    INIT_OPTS=""

# prepare configuration for sub packages

%pre
# Add apacheuser in the icingacmd group
# If the group exists, add the apacheuser in the icingacmd group.
# It is not neccessary that icinga2-web is installed on the same system as
# icinga and only on systems with icinga installed the icingacmd
# group exists. In all other cases the user used for ssh access has
# to be added to the icingacmd group on the remote icinga server.
getent group icingacmd > /dev/null

if [ $? -eq 0 ]; then
%{_sbindir}/usermod -a -G icingacmd %{apacheuser}
fi

# uncomment if building from git
# %{__rm} -rf %{buildroot}%{_datadir}/icinga2-web/.git

%preun

%post

%clean
[ "%{buildroot}" != "/" ] && [ -d "%{buildroot}" ] && rm -rf %{buildroot}

%files
# main dirs
%defattr(-,root,root)
%if "%{_vendor}" == "redhat"
%doc etc/schema doc
%endif
%if "%{_vendor}" == "suse"
%doc etc/schema doc
%endif
%{_datadir}/%{name}/application
%{_datadir}/%{name}/library
%{_datadir}/%{name}/public
%{_datadir}/%{name}/modules
# configs
%defattr(-,root,root)
%config(noreplace) %attr(-,root,root) %{apacheconfdir}/icingaweb.conf
%dir %{configdir}
%config(noreplace) %attr(775,%{apacheuser},%{apachegroup}) %{configdir}
# logs
%attr(2775,%{apacheuser},%{apachegroup}) %dir %{logdir}

%files doc
%defattr(-,root,root)
%doc doc

%changelog
* Sun Oct 20 2013 Michael Friedrich <michael.friedrich@netways.de> - 0.0.1
- initial creation

