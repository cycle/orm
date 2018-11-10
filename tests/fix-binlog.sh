#!/usr/bin/env bash
set -ex
sed -i '/\[mysqld\]/a\
binlog_format = MIXED' /etc/mysql/my.cnf
sudo service mysql restart