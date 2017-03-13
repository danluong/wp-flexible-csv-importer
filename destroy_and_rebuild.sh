#!/bin/bash

# convenience script to destroy any running containers, rebuild (with cache) and output notifications from script watching/syncing source files
sudo docker rm -f wpcsvdevsql
sudo docker rm -f wpcsvdevphp
sudo docker build -t leonstafford/wp-flexible-csv-importer:latest . 
sudo docker run --name wpcsvdevsql -e MYSQL_ROOT_PASSWORD=banana -d mariadb
sudo docker run --name wpcsvdevphp --link wpcsvdevsql:mysql -p 8081:80 -d -v /home/leon/wp-flexible-csv-importer/:/app leonstafford/wp-flexible-csv-importer
sudo docker exec wpcsvdevphp sh /post_launch.sh
sudo docker exec -it wpcsvdevphp sh /watch_source_files.sh
