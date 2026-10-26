#!/bin/bash

# check if Ubuntu or CentOS
if grep -q Ubuntu "/etc/issue.net"
then
	distri="ubuntu"
elif grep -q Mint "/etc/issue.net"
then
	distri="ubuntu"
elif grep -q CentOS "/etc/issue.net"
then 
	distri="centos"
else
	echo "Your Linux distribution could not be detected. This script is designed for (U)buntu, Linux (M)int or (C)entOS."
	printf "Which one do you want to use? Any other key will exit."
	read answer
	case $answer in
		[UuMm]* ) distri="ubuntu";;
		[Cc]* ) distri="centos";;
		* ) exit 1;;
	esac
fi

# check for root
if [ `id -u` -ne 0 ] ; then
	echo "This script must be executed with root privileges."
	exit 1
fi

# install required packages
if [ "$distri" = "ubuntu" ]
then
	apt update
	apt install php-mysql php php-cli php-common php-gd php-mbstring php-pear mariadb-server mariadb-client mariadb-common apache2 ghostscript imagemagick
	# make sure already-installed PHP packages get security fixes (PHP2Shell, Oct 2026)
	apt install --only-upgrade 'php*'
elif [ "$distri" = "centos" ]
then
	yum install php-mysql php php-gd php-mbstring php-pear httpd zlib-devel
	yum update 'php*'
fi

# PHP hardening for CVE-2026-109618/109619 (PHP2Shell), see docs/SECURITY_PHP2SHELL.md
# in https://github.com/khoivan88/open_enventory-modified_for_US. Updating PHP is the fix; this narrows exposure.
oe_php_ini="; Open Enventory: PHP2Shell (CVE-2026-109618/109619) hardening, see INSTALL/99-open_enventory-security.ini
session.upload_progress.enabled = 0
fatal_error_backtraces = 0
expose_php = Off"
if [ "$distri" = "ubuntu" ]
then
	for confdir in /etc/php/*/apache2/conf.d /etc/php/*/fpm/conf.d
	do
		[ -d "$confdir" ] && echo "$oe_php_ini" > "$confdir/99-open_enventory-security.ini"
	done
	service apache2 restart
	for fpm in /etc/init.d/php*-fpm
	do
		[ -x "$fpm" ] && "$fpm" restart
	done
elif [ "$distri" = "centos" ]
then
	echo "$oe_php_ini" > /etc/php.d/99-open_enventory-security.ini
	service httpd restart
	[ -x /etc/init.d/php-fpm ] && service php-fpm restart
fi

php -r 'echo "Installed PHP: ".PHP_VERSION."\n";'
php -r '$v=PHP_VERSION_ID; exit($v<80235 || ($v>=80300 && $v<80336) || ($v>=80400 && $v<80427) || ($v>=80500 && $v<80512) ? 1 : 0);' || \
	echo "WARNING: this PHP version number is older than the PHP2Shell fixes (8.2.35, 8.3.36, 8.4.27, 8.5.12). Distribution packages (Ubuntu, Debian, RHEL) often backport the fix without changing the version number: check your distribution's security advisory for CVE-2026-109618 and CVE-2026-109619 before putting this server on a network."

# generate random mariadb root pw
cd /root
rootpw=`< /dev/urandom tr -dc A-Za-z0-9 |head -c12; echo`
echo "SET PASSWORD = PASSWORD('$rootpw');UPDATE mysql.user SET plugin='mysql_native_password' WHERE User LIKE 'root';FLUSH PRIVILEGES;" > setrootpw.sql
mariadb -u root < setrootpw.sql
# make root login with sudo possible again
service mysql restart

# TODO: modify my.cnf and php.ini if needed; warn user about it

cd /var/www

if [ "$distri" = "centos" -o "$distri" = "ubuntu" ]
then
	cd html
fi

wget https://sciformation-demo.eu/intern/open_enventory_latest.zip 
unzip open_enventory_latest.zip

folder_name=`unzip -l open_enventory_latest.zip |grep -o open_enventory\\[^\\\\\\/\\]\\\\+\\\\\\/\\$`

ln -s "$folder_name" oe

echo "Now open your browser, navigate to http://localhost/oe and create a database by entering the desired name, \"root\" as user name and \"$rootpw\" as password. The root password was stored in /root/setrootpw.sql. Make sure the password stays secret."
