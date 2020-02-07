#!/usr/bin/env bash
set -ex
curl https://packages.microsoft.com/keys/microsoft.asc | sudo apt-key add -
curl https://packages.microsoft.com/config/ubuntu/18.04/prod.list | sudo tee /etc/apt/sources.list.d/mssql-release.list
sudo apt-get update

sudo ACCEPT_EULA=Y apt-get install -qy msodbcsql17 mssql-tools