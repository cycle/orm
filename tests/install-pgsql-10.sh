#!/usr/bin/env bash
set -ex
# from Doctrine
sudo service postgresql stop
sudo apt-get remove -q 'postgresql-*'
sudo apt-get update -q
sudo apt-get install -q postgresql-10 postgresql-client-10
sudo cp /etc/postgresql/{9.6,10}/main/pg_hba.conf
sudo service postgresql restart