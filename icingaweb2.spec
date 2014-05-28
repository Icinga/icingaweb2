# $Id$
# Authority: The icinga devel team <icinga-devel at lists.icinga.org>
# Upstream: The icinga devel team <icinga-devel at lists.icinga.org>
# ExcludeDist: el4 el3

%define revision 0

%define configdir %{_sysconfdir}/icingaweb
%define sharedir %{_datadir}/icingaweb
%define prefixdir %{_datadir}/icingaweb
%define logdir %{sharedir}/log
%define usermodparam -a -G
#%define logdir %{_localstatedir}/log/icingaweb

%if "%{_vendor}" == "suse"
%define phpname php5
%define phpzendname php5-ZendFramework
%define apache2modphpname apache2-mod_php5
%endif
# SLE 11 = 1110
%if 0%{?suse_version} == 1110
%define apache2modphpname apache2-mod_php53
%define usermodparam -A
%endif

%if "%{_vendor}" == "redhat" || 0%{?suse_version} == 1110
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
%define extcmdfile1x %{_localstatedir}/icinga/rw/icinga.cmd
%define livestatussocket1x %{_localstatedir}/icinga/rw/live
%endif
%if "%{_vendor}" == "redhat"
%define apacheconfdir %{_sysconfdir}/httpd/conf.d
%define apacheuser apache
%define apachegroup apache
%define extcmdfile-1x %{_localstatedir}/spool/icinga/cmd/icinga.cmd
%define livestatussocket1x %{_localstatedir}/spool/icinga/cmd/live
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
IcingaWeb for Icinga 2 or Icinga 1.x using status data,
IDOUtils or Livestatus as backend provider.

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
Icinga Web 2 PHP Libraries shared with icingacli.




%prep
#%setup -q -n %{name}-%{version}
%setup -q -n %{name}

%build

cat > README.RHEL.SUSE <<"EOF"
IcingaWeb for RHEL and SUSE
===========================

Please check ./doc/installation.md
for requirements and database setup.
EOF

%install
[ "%{buildroot}" != "/" ] && [ -d "%{buildroot}" ] && rm -rf %{buildroot}

# prepare configuration for sub packages

# install rhel apache config
install -D -m0644 packages/rhel/etc/httpd/conf.d/icingaweb.conf %{buildroot}/%{apacheconfdir}/icingaweb.conf

# install public, library, modules
%{__mkdir} -p %{buildroot}/%{sharedir}
%{__mkdir} -p %{buildroot}/%{logdir}
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/icingaweb/enabledModules

%{__cp} -r application library modules public %{buildroot}/%{sharedir}/

# install index.php, .htaccess
install -m0644 packages/rhel/usr/share/icingaweb/public/index.php %{buildroot}/%{sharedir}/public/index.php
install -m0644 packages/rhel/usr/share/icingaweb/public/.htaccess %{buildroot}/%{sharedir}/public/.htaccess

# use the vagrant config for configuration for now - TODO
%{__cp} -r .vagrant-puppet/files/etc/icingaweb %{buildroot}/%{_sysconfdir}/

# we use the default 'icinga' database
sed -i 's/icinga2/icinga/g' %{buildroot}/%{_sysconfdir}/icingaweb/resources.ini

# enable the monitoring module by default
ln -s %{sharedir}/modules/monitoring %{buildroot}/%{_sysconfdir}/icingaweb/enabledModules/monitoring

# install icingacli
install -D -m0755 bin/icingacli %{buildroot}/usr/bin/icingacli

# install sql schema files as example

# delete all *.in files
rm -f %{buildroot}/%{_datadir}/%{name}/public/index.php.in
rm -f %{buildroot}/%{_datadir}/%{name}/public/.htaccess.in

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

# uncomment if building from git
# %{__rm} -rf %{buildroot}%{_datadir}/icinga2-web/.git

%preun

%post

%clean
[ "%{buildroot}" != "/" ] && [ -d "%{buildroot}" ] && rm -rf %{buildroot}

%files
# main dirs
%defattr(-,root,root)
%doc etc/schema doc packages/rhel/README
%attr(755,%{apacheuser},%{apachegroup}) %{sharedir}/public
%attr(755,%{apacheuser},%{apachegroup}) %{sharedir}/modules
# configs
%defattr(-,root,root)
%config(noreplace) %attr(-,root,root) %{apacheconfdir}/icingaweb.conf
%dir %{configdir}
%config(noreplace) %attr(775,%{apacheuser},%{apachegroup}) %{configdir}
# logs
%attr(2775,%{apacheuser},%{apachegroup}) %dir %{logdir}

%files -n php-Icinga
%attr(755,%{apacheuser},%{apachegroup}) %{sharedir}/application
%attr(755,%{apacheuser},%{apachegroup}) %{sharedir}/library

%files -n icingacli
%attr(0755,root,root) /usr/bin/icingacli

%changelog
* Tue May 11 2014 Michael Friedrich <michael.friedrich@netways.de> - 0.0.1-1
- initial creation

