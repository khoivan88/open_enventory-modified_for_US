#!/bin/sh

# change these parameters according to your needs
db_user="backup"
db_pass="xxx"
dest_dir="."
local_days=3

# delete local copies of backups which are older than $local_days
if [ $local_days -gt 0 ]; then
	find $dest_dir -mtime +$local_days -delete
fi

# create individual dumps for each database
for database in `echo SHOW DATABASES\; | mysql -u $db_user -p$db_pass`
do
	if [ "$database" = "Database" ]; then
		# do nothing
		true
	elif [ "$database" = "information_schema" ]; then
		# do nothing
		true
	elif [ "$database" = "performance_schema" ]; then
		# do nothing
		true
	else
		# optimize
		
		# dump
		mysqldump -f -u $db_user -p$db_pass  --databases $database | gzip -c > $dest_dir/`date +%Y%m%d`_$database.sql.gz
		echo $database saved
	fi
done

# put backup commands 
