#!/bin/sh
apk update
apk add docker dockerd php8 php8-cli php8-mod-curl luci-mod-rpc luci-ssl at jq

#-------- Docker Checker Daemon Installation --------
# Copy Docker daemon script to /etc/init.d and make it executable
echo "Copying files..."
#cp docker-checker.sh /usr/bin/docker-checker.sh
#cp docker-checker /etc/init.d/docker-checker
#cp docker-checker.php /usr/bin/docker-checker.php
#cp web-rated-docker.json /usr/bin/web-rated-docker.json
#cp update-docker-state.sh /usr/bin/update-docker-state.sh
#cp update-docker-state.php /usr/bin/update-docker-state.php

wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/Daemon/docker-checker-daemon/docker-checker.sh
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/Daemon/docker-checker-daemon/docker-checker
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/Daemon/docker-checker-daemon/docker-checker.php
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/Daemon/docker-checker-daemon/web-rated-docker.json
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/Daemon/docker-checker-daemon/update-docker-state.sh
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/Daemon/docker-checker-daemon/update-docker-state.php
wget -P /etc/init.d https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/Daemon/docker-checker-daemon/docker-checker

# Set permissions
echo "Setting permissions..."
chmod +x /usr/bin/docker-checker.sh
chmod +x /etc/init.d/docker-checker

#-------- Firewall COnfiguration Installation --------
# Download firewall configuration to /etc/config
wget -P /etc/config https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/firewall/firewall

#-------- Blacklist IP Installation --------
# Define source and destination directories for blacklist files
DEST_DIR="/etc/blacklist-ip"

# Create the destination directory if it doesn't exist
if [ ! -d "$DEST_DIR" ]; then
    mkdir -p "$DEST_DIR"
    echo "Created directory $DEST_DIR"
else
    echo "Directory $DEST_DIR already exists."
fi

# Download blacklist files from GitHub
wget -P "$DEST_DIR" https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/cronjob/blacklist-ip/blacklist-ip.php
wget -P "$DEST_DIR" https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/cronjob/blacklist-ip/blacklist-ip-conf.php
wget -P "$DEST_DIR" https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/cronjob/blacklist-ip/config.php

#-------- Webguard Application Installation --------
# Download webguard application files to