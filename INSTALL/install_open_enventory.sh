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
	apt install php-mysql php php-cli php-common php-gd php-mbstring php-pear mariadb-server mariadb-client mariadb-common apache2 ghostscript imagemagick
elif [ "$distri" = "centos" ]
then
	yum install php-mysql php php-gd php-mbstring php-pear httpd zlib-devel
fi

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
