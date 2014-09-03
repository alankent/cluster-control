FROM ubuntu:trusty
MAINTAINER Alan Kent

# This container requires the following environment variables to be set at run time
# - ETCD_URL
# - PUBLIC_HOST_AND_PORT

# Get Apache, mysql client, PHP etc (subset of a full LAMP stack - no MySQL server)
RUN apt-get update && apt-get install -y apache2 php5 php5-curl curl git zip unzip

# Environment variables from /etc/apache2/apache2.conf
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_RUN_DIR /var/run/apache2
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2/apache2.pid

# Replace index.html with our index.php file.
RUN rm /var/www/html/index.html
ADD index.php /var/www/html/index.php
RUN chmod 644 /var/www/html/index.php

# Add Cluster Control scripts.
RUN mkdir /cluster-control && \
	cd /cluster-control && \
	curl -sS https://getcomposer.org/installer | php && \
	curl -L -O https://github.com/alankent/cluster-control/archive/master.zip && \
	unzip master.zip && \
	mv cluster-control-master/* . && \
	rm cluster-control-master/.gitignore && \
	rmdir cluster-control-master && \
	php composer.phar update

# Add scripts to create cluster-control.conf substituting in appropriate
# envrionment variables.
ADD create-config.php /var/www/create-config.php

# Add the script to start up the servers.
ADD startscript /var/www/startscript
RUN chmod 755 /var/www/startscript

# Expose the web server port
EXPOSE 80

# Start up the Apache server
ENTRYPOINT ["/bin/bash"]
CMD ["-c", "/var/www/startscript"]
