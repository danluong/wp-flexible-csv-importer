#!/bin/bash
echo 'syncing source files to plugin directory'

# run wp-cli cmds from wp install path
cd /var/www/html

# copy plugin source files to WP path (else changing ownership will change on host)
# ignore file ownership as we modify this post sync; ignore git folder
rsync -av --no-owner --exclude '.git/' --exclude '*.swp' --delete  /app/ wp-content/plugins/wp-flexible-csv-importer/

# www-data to own plugin src files 
chown -R www-data:www-data wp-content/plugins/wp-flexible-csv-importer
chown -R www-data:www-data wp-content/uploads

