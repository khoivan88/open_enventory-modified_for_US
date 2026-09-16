@ECHO OFF

REM Copyright Kieran Whitbread 2009
REM Based on the work of numerous others
REM ============================
REM == SETTINGS ================

SET BACKUPS_DIR=C:\MySQL-Backups
SET MYSQL_DIR=%PROGRAMFILES%\MySQL\MySQL Server 5.1\bin
SET USER="backup_user" 
SET PASSWORD="backup_password"    

REM ============================

SET OLDDIR=%CD%
CD %TEMP%

REM The date in ISO format, to be appended to backup filenames.
REM (Working from a UK formatted date)
SET TODAY=%DATE:~6,4%-%DATE:~3,2%-%DATE:~0,2%

REM get a list of all databases hosted on the server
"%MYSQL_DIR%\mysql" -u %USER% -p%PASSWORD% -B -s -e"show databases" > mysqldblist.tmp

FOR /F %%D IN (mysqldblist.tmp) DO (
    ECHO Creating backup for database ''%%D''
    "%MYSQL_DIR%\mysqldump" -u %USER% -p%PASSWORD% --result-file="%BACKUPS_DIR%\%%D.%TODAY%.sql" "%%D" 
)

DEL mysqldblist.tmp

