#!/bin/sh

#-------- Docker Checker Daemon Activation --------
/etc/init.d/dockerd start
/etc/init.d/dockerd enable

docker pull webrated/e2guardian:latest
docker pull webrated/unboundns:latest
docker pull webrated/webbound:latest

docker network create --driver=bridge --subnet=172.20.0.0/16 --gateway=172.20.0.1 doc1

#-------- Firewall Configuration Activation --------
/etc/init.d/firewall restart

#-------- Blacklist IP Activation --------
# Create a cron job to run the PHP script daily at 6 AM
CRON_JOB="0 6 * * * php8-cli $DEST_DIR/blacklist-ip.php"

# Check if the cron job already exists
if ! crontab -l | grep -q "$CRON_JOB"; then
    (crontab -l; echo "$CRON_JOB") | crontab -
    echo "Cron job added: $CRON_JOB"
else
    echo "Cron job already exists: $CRON_JOB"
fi

#-------- Webguard Application Configuration --------

