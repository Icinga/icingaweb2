# Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+

%define revision 4.rc1

Name:           icingaweb2
Version:        2.0.0
Release:        %{revision}%{?dist}
Summary:        Icinga Web 2
Group:          Applications/System
License:        GPLv2+ and MIT and BSD
URL:            https://icinga.org
Source0:        https://github.com/Icinga/%{name}/archive/v%{version}.tar.gz
BuildArch:      noarch
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}
Packager:       Icinga Team <info@icinga.org>

%if 0%{?fedora} || 0%{?rhel} || 0%{?amzn}
%define apache_configdir  %{_sysconfdir}/httpd/conf.d
%define apache_user     apache
%define zend            php-ZendFramework
%define php             php
%define php_cli         php-cli
%endif

%if 0%{?suse_version}
%define apache_configdir  %{_sysconfdir}/apache2/conf.d
%define apache_user     wwwrun
%define zend            php5-ZendFramework
%if 0%{?suse_version} == 1110
%define php php53
%else
%define php php5
%endif
%endif

%{?amzn:Requires(pre):          shadow-utils}
%{?fedora:Requires(pre):        shadow-utils}
%{?rhel:Requires(pre):          shadow-utils}
%{?suse_version:Requires(pre):  pwdutils}
Requires:                       php-fpm
Requires:                       webserver
Requires:                       %{name}-common = %{version}-%{release}
Requires:                       php-Icinga = %{version}-%{release}
Requires:                       %{name}-vendor-dompdf
Requires:                       %{name}-vendor-HTMLPurifier
Requires:                       %{name}-vendor-JShrink
Requires:                       %{name}-vendor-lessphp
Requires:                       %{name}-vendor-Parsedown


%description
Icinga Web 2


%define basedir             %{_datadir}/%{name}
%define bindir              %{_bindir}
%define configdir           %{_sysconfdir}/%{name}
%define docsdir             %{_datadir}/doc/%{name}
%define logdir              %{_localstatedir}/log/%{name}
%define icingaweb_user      icingaweb2
%define icingaweb_group     %{icingaweb_user}
%define nginx_configdir     %{_sysconfdir}/nginx/default.d
%define nginx_user          nginx
%define phpdir              %{_datadir}/php
%define phpfpm_configdir    %{_sysconfdir}/php-fpm.d


%package common
Summary:                        Common files for Icinga Web 2 and the Icinga CLI
Group:                          Applications/System
%{?amzn:Requires(pre):          shadow-utils}
%{?fedora:Requires(pre):        shadow-utils}
%{?rhel:Requires(pre):          shadow-utils}
%{?suse_version:Requires(pre):  pwdutils}

%description common
Common files for Icinga Web 2 and the Icinga CLI


%package -n php-Icinga
Summary:                    Icinga Web 2 PHP library
Group:                      Development/Libraries
Requires:                   %{php}-common >= 5.3.0
Requires:                   %{php}-intl
Requires:                   %{php}-ldap
Requires:                   %{php}-gd
Obsoletes:                  %{name}-vendor-zend
Requires:                   %{zend}-Db-Adapter-Pdo-Mysql
Requires:                   %{zend}-Db-Adapter-Pdo-Pgsql
%{?suse_version:Requires:   %{php}-gettext %{php}-json %{php}-openssl %{php}-posix}
%{?amzn:Requires:           %{php}-pecl-imagick}
%{?fedora:Requires:         php-pecl-imagick}
%{?rhel:Requires:           php-pecl-imagick}
Requires:                   %{zend}
Obsoletes:                  %{name}-vendor-zend
Requires:                   %{zend}-Db-Adapter-Pdo-Mysql
Requires:                   %{zend}-Db-Adapter-Pdo-Pgsql

%description -n php-Icinga
Icinga Web 2 PHP library


%package -n icingacli
Summary:                    Icinga CLI
Group:                      Applications/System
Requires:                   %{name}-common = %{version}-%{release}
Requires:                   php-Icinga = %{version}-%{release}
%{?amzn:Requires:           %{php_cli} >= 5.3.0 bash-completion}
%{?fedora:Requires:         %{php_cli} >= 5.3.0 bash-completion}
%{?rhel:Requires:           %{php_cli} >= 5.3.0 bash-completion}
%{?suse_version:Requires:   %{php} >= 5.3.0}

%description -n icingacli
Icinga CLI


%package vendor-dompdf
Version:    0.6.1
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library dompdf
Group:      Development/Libraries
License:    LGPLv2.1

%description vendor-dompdf
Icinga Web 2 vendor library dompdf


%package vendor-HTMLPurifier
Version:    4.6.0
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library HTMLPurifier
Group:      Development/Libraries
License:    LGPLv2.1

%description vendor-HTMLPurifier
Icinga Web 2 vendor library HTMLPurifier


%package vendor-JShrink
Version:    1.0.1
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library JShrink
Group:      Development/Libraries
License:    BSD

%description vendor-JShrink
Icinga Web 2 vendor library JShrink


%package vendor-lessphp
Version:    0.4.0
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library lessphp
Group:      Development/Libraries
License:    MIT

%description vendor-lessphp
Icinga Web 2 vendor library lessphp


%package vendor-Parsedown
Version:    1.0.0
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library Parsedown
Group:      Development/Libraries
License:    MIT

%description vendor-Parsedown
Icinga Web 2 vendor library Parsedown


%prep
%setup -q

%build

%install
rm -rf %{buildroot}

mkdir -p %{buildroot}/%{basedir}/{modules,library/vendor,public}
mkdir -p %{buildroot}/{%{_sysconfdir}/bash_completion.d,%{bindir},%{configdir}/modules/setup,%{docsdir},%{logdir}}
mkdir -p %{buildroot}/{%{phpdir},%{phpfpm_configdir},%{apache_configdir},%{nginx_configdir}}

cp -prv application doc                                                 %{buildroot}/%{basedir}
cp -pv  etc/bash_completion.d/icingacli                                 %{buildroot}/%{_sysconfdir}/bash_completion.d/icingacli
cp -prv etc/schema                                                      %{buildroot}/%{docsdir}
cp -prv modules/{monitoring,setup,doc,translation}                      %{buildroot}/%{basedir}/modules
cp -prv library/Icinga                                                  %{buildroot}/%{phpdir}
cp -prv library/vendor/{dompdf,HTMLPurifier,JShrink,lessphp,Parsedown}  %{buildroot}/%{basedir}/library/vendor
cp -pv  packages/files/bin/icingacli                                    %{buildroot}/%{bindir}
cp -prv packages/files/config/modules/setup                             %{buildroot}/%{configdir}/modules/
cp -pv  packages/files/httpd/icingaweb2.conf                            %{buildroot}/%{apache_configdir}/icingaweb2.conf
cp -pv  packages/files/nginx/icingaweb2.conf                            %{buildroot}/%{nginx_configdir}/icingaweb2.conf
cp -pv  packages/files/php-fpm/icingaweb2.conf                          %{buildroot}/%{phpfpm_configdir}/icingaweb2.conf
cp -pv  packages/files/public/index.php                                 %{buildroot}/%{basedir}/public
cp -prv public/{css,img,js,error_norewrite.html}                        %{buildroot}/%{basedir}/public

%pre
useradd -r %{icingaweb_user} -N -s /bin/false -g %{icingaweb_group}
if ! getent group icingacmd >/dev/null; then
    groupadd -r icingacmd
fi
%if 0%{?suse_version} && 0%{?suse_version} < 01200
if getent passwd %{apache_user} >/dev/null; then
    usermod -A icingacmd,%{icingaweb_group} %{apache_user}
fi
if getent passwd %{nginx_user} >/dev/null; then
    usermod -A icingacmd,%{icingaweb_group} %{nginx_user}
fi
%else
if getent passwd %{apache_user} >/dev/null; then
    usermod -a -G icingacmd,%{icingaweb_group} %{apache_user}
fi
if getent passwd %{nginx_user} >/dev/null; then
    usermod -a -G icingacmd,%{icingaweb_group} %{nginx_user}
fi
%endif
exit 0

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root)
%{basedir}/application/controllers
%{basedir}/application/fonts
%{basedir}/application/forms
%{basedir}/application/layouts
%{basedir}/application/views
%{basedir}/application/VERSION
%{basedir}/doc
%{basedir}/modules
%{basedir}/public
%config(noreplace) %{apache_configdir}/icingaweb2.conf
%config(noreplace) %{nginx_configdir}/icingaweb2.conf
%config(noreplace) %{phpfpm_configdir}/icingaweb2.conf
%attr(2770,root,%{icingaweb_group}) %config(noreplace) %dir %{configdir}/modules/setup
%attr(0660,root,%{icingaweb_group}) %config(noreplace) %{configdir}/modules/setup/config.ini
%attr(2775,root,%{icingaweb_group}) %dir %{logdir}
%{docsdir}
%docdir %{docsdir}


%pre common
if ! getent group %{icingaweb_group} >/dev/null; then
    groupadd -r %{icingaweb_group}
fi
exit 0

%files common
%defattr(-,root,root)
%{basedir}/application/locale
%dir %{basedir}/modules
%attr(2770,root,%{icingaweb_group}) %config(noreplace) %dir %{configdir}
%attr(2770,root,%{icingaweb_group}) %config(noreplace) %dir %{configdir}/modules


%files -n php-Icinga
%defattr(-,root,root)
%{phpdir}/Icinga


%files -n icingacli
%defattr(-,root,root)
%{basedir}/application/clicommands
%{_sysconfdir}/bash_completion.d/icingacli
%attr(0755,root,root) %{bindir}/icingacli


%files vendor-dompdf
%defattr(-,root,root)
%{basedir}/library/vendor/dompdf


%files vendor-HTMLPurifier
%defattr(-,root,root)
%{basedir}/library/vendor/HTMLPurifier


%files vendor-JShrink
%defattr(-,root,root)
%{basedir}/library/vendor/JShrink


%files vendor-lessphp
%defattr(-,root,root)
%{basedir}/library/vendor/lessphp


%files vendor-Parsedown
%defattr(-,root,root)
%{basedir}/library/vendor/Parsedown
