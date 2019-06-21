# change these parameters according to your needs
DB_USER="root"
DB_PASS="password"
MYSQL_PATH="c:\Program files\Xampp\mysql\bin\"
$MYSQL_PATH$\mysqldump -f -u $DB_USER$ -p$DB_PASS$  -a > backup.sql

