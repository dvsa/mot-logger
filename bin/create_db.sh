#!/bin/bash
set -o errexit
MyUSER=${1-"root"}
MyPASS=${2-"password"}
MyHOST=${3-"localhost"}
MyDATABASE=${4-${MOT_DATABASE-"dvsa_logger"}}
MyGRANTUSER=${5-"motdbuser"}

echo "$(date) Dropping database $MyDATABASE"
mysql -u $MyUSER -p$MyPASS -h $MyHOST -Bse 'DROP DATABASE IF EXISTS `'$MyDATABASE'`;' 2> >(grep -v 'Using a password on the command line interface can be insecure')

echo "$(date) Creating database $MyDATABASE on host $MyHOST for user $MyGRANTUSER"
mysql -u $MyUSER -p$MyPASS -h $MyHOST -Bse 'CREATE DATABASE `'$MyDATABASE'` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT COLLATE utf8_general_ci;' 2> >(grep -v 'Using a password on the command line interface can be insecure')

echo "$(date) Granting permissions for $MyGRANTUSER on database $MyDATABASE"
mysql -u $MyUSER -p$MyPASS -h $MyHOST $MyDATABASE -Bse 'GRANT CREATE TEMPORARY TABLES, SELECT, UPDATE, INSERT, DELETE, EXECUTE ON `'$MyDATABASE'`.* TO `'$MyGRANTUSER'`' 2> >(grep -v 'Using a password on the command line interface can be insecure')

echo "$(date) Loading schema"
mysql -u $MyUSER -p$MyPASS -h $MyHOST $MyDATABASE < ./data/schema.sql 2> >(grep -v 'Using a password on the command line interface can be insecure')


