# Debian Packages

### Ubuntu

``sudo -i``

```bash
apt-get update
apt-get -y install apt-transport-https wget gnupg

wget -O - https://packages.icinga.com/icinga.key | apt-key add -

. /etc/os-release; if [ ! -z ${UBUNTU_CODENAME+x} ]; then DIST="${UBUNTU_CODENAME}"; else DIST="$(lsb_release -c| awk '{print $2}')"; fi; \
 echo "deb https://packages.icinga.com/ubuntu icinga-${DIST} main" > \
 /etc/apt/sources.list.d/${DIST}-icinga.list
 echo "deb-src https://packages.icinga.com/ubuntu icinga-${DIST} main" >> \
 /etc/apt/sources.list.d/${DIST}-icinga.list

apt-get update
```

```bash
apt-get install icingaweb2 libapache2-mod-php icingacli
```

```bash
apt-get install mysql-server
```

```bash
apt-get install icinga2
```

Get the snapshot packages.

```bash
sudo apt-get install ./icinga-php-common/icinga-php-common_1.0.0-1.bionic_all.deb \
./icinga-php-thirdparty/icinga-php-thirdparty_0.10.0-1.bionic_all.deb \
./icinga-php-library/icinga-php-library_0.6.0-1.bionic_all.deb
```

```bash
sudo apt-get install ./php-icinga_2.8.0+470.g9a58e6f43.20210706.1240+bionic-0_all.deb \
 ./icingacli_2.8.0+470.g9a58e6f43.20210706.1240+bionic-0_all.deb \
 ./icingaweb2-common_2.8.0+470.g9a58e6f43.20210706.1240+bionic-0_all.deb \
 ./icingaweb2-module-doc_2.8.0+470.g9a58e6f43.20210706.1240+bionic-0_all.deb \
 ./icingaweb2-module-monitoring_2.8.0+470.g9a58e6f43.20210706.1240+bionic-0_all.deb \
 ./icingaweb2_2.8.0+470.g9a58e6f43.20210706.1240+bionic-0_all.deb
```

Use this command to reset Icinga Web 2 to the 2.8.2 version.

```bash
sudo apt upgrade
```

Unlike RPM packages, all third-party libraries in Icingaweb2/library/vendor doesn't have individually debian packages.

- [ ] During the installation you will get such messages ``The following packages will be DOWNGRADED:`` instead of ``The
following packages will be UPGRADED:``.
- [ ] ipl module is still present, i.e. it is not deleted or deactivated.
- [ ] In Icinga Web 2, there is no version displayed for ``icinga/icinga-php-thirdparty`` and ``icinga/icinga-php-library``.


# RPM Packages

### CENTOS 7

```bash
sudo yum install https://packages.icinga.com/epel/icinga-rpm-release-7-latest.noarch.rpm

sudo yum install epel-release

sudo yum install centos-release-scl
```

```bash
sudo yum install icingaweb2 icingacli
```

```bash
sudo yum install httpd

sudo systemctl start httpd.service
sudo systemctl enable httpd.service
```

```bash
sudo yum install rh-php71-php-fpm

sudo systemctl start rh-php71-php-fpm.service
sudo systemctl enable rh-php71-php-fpm.service
```

```bash
sudo yum install mysql-server

sudo systemctl start mysqld
```

Change temporary generated mysql root password.

```bash
sudo grep 'temporary password' /var/log/mysqld.log
```

Check for SELinux.

```bash
getenforce

If the output is not "Permissive" then run the following command.

sudo setenforce 0
```

Get the snapshot packages.

Please keep in mind that you can' t copy the following commands directly, as the package names may differ.

```bash
sudo yum install ./icinga-php-common/RPMS/noarch/icinga-php-common-1.0.0-1.el7.icinga.noarch.rpm \
      ./icinga-php-thirdparty/RPMS/noarch/icinga-php-thirdparty-0.10.0-1.el7.icinga.noarch.rpm \
      ./icinga-php-libirary/RPMS/noarch/icinga-php-library-0.6.0-1.el7.icinga.noarch.rpm
```

```bash
sudo rpm -i --force ./RPMS/noarch/php-Icinga-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm \
      ./RPMS/noarch/icingacli-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm \
      ./RPMS/noarch/icingaweb2-common-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm \
      ./RPMS/noarch/icingaweb2-vendor-dompdf-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm \
      ./RPMS/noarch/icingaweb2-vendor-HTMLPurifier-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm \
      ./RPMS/noarch/icingaweb2-vendor-JShrink-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm \
      ./RPMS/noarch/icingaweb2-vendor-lessphp-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm \
      ./RPMS/noarch/icingaweb2-vendor-Parsedown-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm \
      ./RPMS/noarch/icingaweb2-vendor-zf1-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm \
      ./RPMS/noarch/icingaweb2-2.8.0.464.g54acf35c6-0.20210702.1711.el7.icinga.noarch.rpm 
```

For RPM packages you have to force install, otherwise it will not install the packages. Reason already installed
packages are more recent than those from the snapshots and there is also some conflicts.

- [ ] ipl module is still present, i.e. it is not deleted or deactivated.
