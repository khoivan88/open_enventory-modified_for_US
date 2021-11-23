#!/bin/bash

# check if Ubuntu or CentOS
if grep -q Ubuntu "/etc/issue.net"
then
	distri="ubuntu"
elif grep -q Mint "/etc/issue.net"
then
	distri="ubuntu"
elif grep -q CentOS "/etc/centos-release"
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

# just in case
service mariadb stop
service nginx stop

# install required packages
if [ "$distri" = "ubuntu" ]
then
	# temporarily disable, to avoid clash
	systemctl mask unattended-upgrades.service
	systemctl stop unattended-upgrades.service

	# wait until it is finished
	while pgrep unattended
	do
		echo "Waiting until unattended-upgrades is paused"
		sleep 1
	done

	mariadbconf="/etc/mysql/mariadb.conf.d/50-server.cnf"
	apt update
	apt install php-mysql php php-cli php-common php-gd php-mbstring php-pear mariadb-server mariadb-client mariadb-common apache2 ghostscript imagemagick unzip || (echo "Package installation failed" && exit)
	# unzip needed for Ubuntu 20.10

	# re-enable asap
	systemctl unmask unattended-upgrades.service
	systemctl start unattended-upgrades.service
elif [ "$distri" = "centos" ]
then
	mariadbconf="/etc/my.cnf.d/mariadb-server.cnf"
	yum check-update
	yum install -y php php-mysqlnd php-gd php-json php-mbstring php-pear mariadb-server mariadb httpd zlib-devel wget unzip ghostscript epel-release
	yum install -y ImageMagick
fi

# start MariaDB if not done so automatically
service mariadb start
# Ubuntu also accepts mysql, CentOS only mariadb


# generate random mariadb root pw
cd /root
rootpw=`< /dev/urandom tr -dc A-Za-z0-9 |head -c12; echo`
# make root login with sudo possible again
echo "SET PASSWORD = PASSWORD('$rootpw');UPDATE mysql.user SET plugin='mysql_native_password' WHERE User LIKE 'root';FLUSH PRIVILEGES;" > setrootpw.sql
mysql -u root < setrootpw.sql
# Ubuntu also accepts mariadb, CentOS only mysql

# modify my.cnf if needed
if ! sudo grep -q NO_ZERO_IN_DATE "$mariadbconf"
then
	# https://stackoverflow.com/a/7697604
	sudo sed -i.bak '/\[mysqld\]/a # inserted by Sciformation install script\nsql_mode = NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION\nmax_allowed_packet = 64M\n' "$mariadbconf"
	#sql_mode = NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION
	#max_allowed_packet = 64M
	# character_set_server = utf8mb4 is already there by default
fi

service mariadb restart
# Ubuntu also accepts mysql, CentOS only mariadb

# TODO: modify php.ini if needed; warn user about it

cd /var/www

if [ "$distri" = "centos" -o "$distri" = "ubuntu" ]
then
	cd html
fi

wget https://sciformation-demo.eu/intern/open_enventory_latest.zip 
unzip open_enventory_latest.zip

folder_name=`unzip -l open_enventory_latest.zip |grep -o open_enventory\\[^\\\\\\/\\]\\\\+\\\\\\/\\$`

ln -s "$folder_name" oe

if [ "$distri" = "ubuntu" ]
then
	# update /etc/mysql/debian.cnf
	if grep -xq "password = " "/etc/mysql/debian.cnf"
	then
		sed -i.bak "s|password = |password = $rootpw/|g" "/etc/mysql/debian.cnf"
	fi
elif [ "$distri" = "centos" ]
then
	# /etc/php.ini
	# TODO
	# allow connections to suppliers
	setsebool -P httpd_can_network_connect 1

	service httpd start
	# Ubuntu starts Apache automatically
fi

echo "Now open your browser, navigate to http://localhost/oe and create a database by entering the desired name, \"root\" as user name and \"$rootpw\" as password. The root password was stored in /root/setrootpw.sql. Make sure the password stays secret."
