#!/bin/bash

# run wp-cli cmds from wp install path
cd /var/www/html

echo 'awaiting mysql to be reachable'

while ! mysqladmin ping -h wpcsvdevsql --silent; do
    printf "."
    sleep 1
done

# still requires buffer before accessible for wp cli
sleep 5

# get container IP address
containerIP=$(ip route get 1 | awk '{print $NF;exit}')

# install default
wp --allow-root core install --url="$containerIP" --title='WP Flexible CSV Importer' --admin_user=admin --admin_password=admin --admin_email=blah@blah.com --skip-email

. /sync_sources.sh

# activate wp static output plugin
wp --allow-root plugin activate wp-flexible-csv-importer

# OPTIONAL: install latest static plugin from WP plugins site vs local src
#wp --allow-root plugin install static-html-output-plugin --activate

# OPTIONAL: run log apache errors
# tail -f /var/log/apache2/error.log

