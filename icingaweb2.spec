# Icinga Web 2 | (c) 2013-2016 Icinga Development Team | GPLv2+

%define revision 1

Name:           icingaweb2
Version:        2.4.1
Release:        %{revision}%{?dist}
Summary:        Icinga Web 2
Group:          Applications/System
License:        GPLv2+ and MIT and BSD
URL:            https://icinga.com
Source0:        https://github.com/Icinga/%{name}/archive/v%{version}.tar.gz
BuildArch:      noarch
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}
Packager:       Icinga Team <info@icinga.com>

%if 0%{?fedora} || 0%{?rhel} || 0%{?amzn}
%define php             php
%define php_cli         php-cli
%define wwwconfigdir    %{_sysconfdir}/httpd/conf.d
%define wwwuser         apache
%endif

%if 0%{?suse_version}
%define wwwconfigdir    %{_sysconfdir}/apache2/conf.d
%define wwwuser         wwwrun
%if 0%{?suse_version} == 1110
%define php php53
Requires: apache2-mod_php53
%else
%define php php5
Requires: apache2-mod_php5
%endif
%endif

%{?amzn:Requires(pre):          shadow-utils}
%{?fedora:Requires(pre):        shadow-utils}
%{?rhel:Requires(pre):          shadow-utils}
%{?suse_version:Requires(pre):  pwdutils}
Requires:                       %{name}-common = %{version}-%{release}
Requires:                       php-Icinga = %{version}-%{release}
Requires:                       %{name}-vendor-dompdf = 0.7.0-1%{?dist}
Requires:                       %{name}-vendor-HTMLPurifier = 4.8.0-1%{?dist}
Requires:                       %{name}-vendor-JShrink = 1.1.0-1%{?dist}
Requires:                       %{name}-vendor-lessphp = 0.4.0-1%{?dist}
Requires:                       %{name}-vendor-Parsedown = 1.6.0-1%{?dist}

%if "%{_vendor}" == "redhat" && !(0%{?el5} || 0%{?rhel} == 5 || "%{?dist}" == ".el5" || 0%{?el6} || 0%{?rhel} == 6 || "%{?dist}" == ".el6")
%define selinux 1
%define selinux_variants mls targeted
%{!?_selinux_policy_version: %define _selinux_policy_version %(sed -e 's,.*selinux-policy-\\([^/]*\\)/.*,\\1,' /usr/share/selinux/devel/policyhelp 2>/dev/null)}
%endif

%define basedir         %{_datadir}/%{name}
%define bindir          %{_bindir}
%define configdir       %{_sysconfdir}/%{name}
%define logdir          %{_localstatedir}/log/%{name}
%define phpdir          %{_datadir}/php
%define icingawebgroup  icingaweb2
%define docsdir         %{_datadir}/doc/%{name}


%description
Icinga Web 2


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
Requires:                   %{php} >= 5.3.0
Requires:                   %{php}-gd %{php}-intl
Requires:                   %{name}-vendor-zf1 = 1.12.20-1%{?dist}
%{?amzn:Requires:           %{php}-pecl-imagick}
%{?fedora:Requires:         php-pecl-imagick}
%{?rhel:Requires:           php-pecl-imagick}
%{?suse_version:Requires:   %{php}-gettext %{php}-json %{php}-openssl %{php}-posix}

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


%if 0%{?selinux}
%package selinux
Summary:        SELinux policy for Icinga Web 2
BuildRequires:  checkpolicy, selinux-policy-devel, /usr/share/selinux/devel/policyhelp, hardlink
%if "%{_selinux_policy_version}" != ""
Requires:       selinux-policy >= %{_selinux_policy_version}
%endif
Requires:           %{name} = %{version}-%{release}
Requires(post):     policycoreutils
Requires(postun):   policycoreutils

%description selinux
SELinux policy for Icinga Web 2
%endif


%package vendor-dompdf
Version:    0.7.0
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library dompdf
Group:      Development/Libraries
License:    LGPLv2.1
Requires:   %{php} >= 5.3.0

%description vendor-dompdf
Icinga Web 2 vendor library dompdf


%package vendor-HTMLPurifier
Version:    4.8.0
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library HTMLPurifier
Group:      Development/Libraries
License:    LGPLv2.1
Requires:   %{php} >= 5.3.0

%description vendor-HTMLPurifier
Icinga Web 2 vendor library HTMLPurifier


%package vendor-JShrink
Version:    1.1.0
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library JShrink
Group:      Development/Libraries
License:    BSD
Requires:   %{php} >= 5.3.0

%description vendor-JShrink
Icinga Web 2 vendor library JShrink


%package vendor-lessphp
Version:    0.4.0
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library lessphp
Group:      Development/Libraries
License:    MIT
Requires:   %{php} >= 5.3.0

%description vendor-lessphp
Icinga Web 2 vendor library lessphp


%package vendor-Parsedown
Version:    1.6.0
Release:    1%{?dist}
Summary:    Icinga Web 2 vendor library Parsedown
Group:      Development/Libraries
License:    MIT
Requires:   %{php} >= 5.3.0

%description vendor-Parsedown
Icinga Web 2 vendor library Parsedown


%package vendor-zf1
Version:    1.12.20
Release:    1%{?dist}
Summary:    Icinga Web 2's fork of Zend Framework 1
Group:      Development/Libraries
License:    BSD
Requires:   %{php} >= 5.3.0
Obsoletes:  %{name}-vendor-Zend

%description vendor-zf1
Icinga Web 2's fork of Zend Framework 1


%prep
%setup -q
%if 0%{?selinux}
mkdir selinux
cp -p packages/selinux/icingaweb2.{fc,if,te} selinux
%endif

%build
%if 0%{?selinux}
cd selinux
for selinuxvariant in %{selinux_variants}
do
  make NAME=${selinuxvariant} -f /usr/share/selinux/devel/Makefile
  mv icingaweb2.pp icingaweb2.pp.${selinuxvariant}
  make NAME=${selinuxvariant} -f /usr/share/selinux/devel/Makefile clean
done
cd -
%endif

%install
rm -rf %{buildroot}
mkdir -p %{buildroot}/{%{basedir}/{modules,library/vendor,public},%{bindir},%{configdir}/modules,%{logdir},%{phpdir},%{wwwconfigdir},%{_sysconfdir}/bash_completion.d,%{docsdir}}
cp -prv application doc %{buildroot}/%{basedir}
cp -pv etc/bash_completion.d/icingacli %{buildroot}/%{_sysconfdir}/bash_completion.d/icingacli
cp -prv modules/{monitoring,setup,doc,translation} %{buildroot}/%{basedir}/modules
cp -prv library/Icinga %{buildroot}/%{phpdir}
cp -prv library/vendor/{dompdf,HTMLPurifier*,JShrink,lessphp,Parsedown,Zend} %{buildroot}/%{basedir}/library/vendor
cp -prv public/{css,font,img,js,error_norewrite.html} %{buildroot}/%{basedir}/public
cp -pv packages/files/apache/icingaweb2.conf %{buildroot}/%{wwwconfigdir}/icingaweb2.conf
cp -pv packages/files/bin/icingacli %{buildroot}/%{bindir}
cp -pv packages/files/public/index.php %{buildroot}/%{basedir}/public
cp -prv etc/schema %{buildroot}/%{docsdir}
cp -prv packages/files/config/modules/{setup,translation} %{buildroot}/%{configdir}/modules
%if 0%{?selinux}
cd selinux
for selinuxvariant in %{selinux_variants}
do
  install -d %{buildroot}%{_datadir}/selinux/${selinuxvariant}
  install -p -m 644 icingaweb2.pp.${selinuxvariant} %{buildroot}%{_datadir}/selinux/${selinuxvariant}/icingaweb2.pp
done
cd -
/usr/sbin/hardlink -cv %{buildroot}%{_datadir}/selinux
%endif

%pre
getent group icingacmd >/dev/null || groupadd -r icingacmd
%if 0%{?suse_version} && 0%{?suse_version} < 01200
usermod -A icingacmd,%{icingawebgroup} %{wwwuser}
%else
usermod -a -G icingacmd,%{icingawebgroup} %{wwwuser}
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
%config(noreplace) %{wwwconfigdir}/icingaweb2.conf
%attr(2775,root,%{icingawebgroup}) %dir %{logdir}
%attr(2770,root,%{icingawebgroup}) %config(noreplace) %dir %{configdir}/modules/setup
%attr(0660,root,%{icingawebgroup}) %config(noreplace) %{configdir}/modules/setup/config.ini
%attr(2770,root,%{icingawebgroup}) %config(noreplace) %dir %{configdir}/modules/translation
%attr(0660,root,%{icingawebgroup}) %config(noreplace) %{configdir}/modules/translation/config.ini
%{docsdir}
%docdir %{docsdir}


%pre common
getent group %{icingawebgroup} >/dev/null || groupadd -r %{icingawebgroup}
exit 0

%files common
%defattr(-,root,root)
%{basedir}/application/locale
%dir %{basedir}/modules
%attr(2770,root,%{icingawebgroup}) %config(noreplace) %dir %{configdir}
%attr(2770,root,%{icingawebgroup}) %config(noreplace) %dir %{configdir}/modules


%files -n php-Icinga
%defattr(-,root,root)
%{phpdir}/Icinga


%files -n icingacli
%defattr(-,root,root)
%{basedir}/application/clicommands
%{_sysconfdir}/bash_completion.d/icingacli
%attr(0755,root,root) %{bindir}/icingacli


%if 0%{?selinux}
%post selinux
for selinuxvariant in %{selinux_variants}
do
  %{_sbindir}/semodule -s ${selinuxvariant} -i %{_datadir}/selinux/${selinuxvariant}/icingaweb2.pp &> /dev/null || :
done
%{_sbindir}/restorecon -R %{basedir} &> /dev/null || :
%{_sbindir}/restorecon -R %{configdir} &> /dev/null || :
%{_sbindir}/restorecon -R %{logdir} &> /dev/null || :

%postun selinux
if [ $1 -eq 0 ] ; then
  for selinuxvariant in %{selinux_variants}
  do
     %{_sbindir}/semodule -s ${selinuxvariant} -r icingaweb2 &> /dev/null || :
  done
  [ -d %{basedir} ] && %{_sbindir}/restorecon -R %{basedir} &> /dev/null || :
  [ -d %{configdir} ] && %{_sbindir}/restorecon -R %{configdir} &> /dev/null || :
  [ -d %{logdir} ] && %{_sbindir}/restorecon -R %{logdir} &> /dev/null || :
fi

%files selinux
%defattr(-,root,root,0755)
%doc selinux/*
%{_datadir}/selinux/*/icingaweb2.pp
%endif


%files vendor-dompdf
%defattr(-,root,root)
%{basedir}/library/vendor/dompdf


%files vendor-HTMLPurifier
%defattr(-,root,root)
%{basedir}/library/vendor/HTMLPurifier
%{basedir}/library/vendor/HTMLPurifier.autoload.php
%{basedir}/library/vendor/HTMLPurifier.php


%files vendor-JShrink
%defattr(-,root,root)
%{basedir}/library/vendor/JShrink


%files vendor-lessphp
%defattr(-,root,root)
%{basedir}/library/vendor/lessphp


%files vendor-Parsedown
%defattr(-,root,root)
%{basedir}/library/vendor/Parsedown


%files vendor-zf1
%defattr(-,root,root)
%{basedir}/library/vendor/Zend
