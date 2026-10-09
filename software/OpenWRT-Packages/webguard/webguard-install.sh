#!/bin/sh
apk update
apk add docker dockerd php8 php8-cli php8-mod-curl luci-mod-rpc luci-ssl at jq

#-------- Docker Checker Daemon Installation --------
# Copy Docker daemon script to /etc/init.d and make it executable
echo "Copying files..."

wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/docker-checker-daemon/docker-checker.sh
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/docker-checker-daemon/docker-checker.php
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/docker-checker-daemon/web-rated-docker.json
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/docker-checker-daemon/update-docker-state.sh
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/docker-checker-daemon/update-docker-state.php
wget -P /etc/init.d https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/docker-checker-daemon/docker-checker

# Set permissions
echo "Setting permissions..."
chmod +x /usr/bin/docker-checker.sh
chmod +x /etc/init.d/docker-checker

#-------- Firewall COnfiguration Installation --------
# Download firewall configuration to /etc/config
wget -P /etc/config https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/firewall/firewall

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
wget -P "$DEST_DIR" https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/blacklist-ip/blacklist-ip.php
wget -P "$DEST_DIR" https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/blacklist-ip/blacklist-ip-conf.php
wget -P "$DEST_DIR" https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/blacklist-ip/config.php

#-------- Webguard New Device Access Daemon Installation --------
# Download New Device Access Daemon files to
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/new-device-access-daemon/config.php
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/new-device-access-daemon/new-device-access.php
wget -P /usr/bin https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/new-device-access-daemon/new-device-access.sh
wget -P /etc/init.d https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/new-device-access-daemon/new-device-access

# Set permissions
echo "Setting permissions..."
chmod +x /usr/bin/new-device-access.sh
chmod +x /etc/init.d/new-device-access

#-------- Webguard Application Installation --------
# Download webguard application files to
wget -P /www/luci-static/resources/view/status https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/www/luci-static/resources/view/status/config.js
wget -P /www/luci-static/resources/view/status https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/www/luci-static/resources/view/status/luci-access.js
wget -P /www/luci-static/resources/view/status https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/www/luci-static/resources/view/status/scheduleaccess.js
wget -P /www/luci-static/resources/view/status https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/www/luci-static/resources/view/status/web-rated-devices.js

wget -P /www/luci-static/resources/view/status https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/etc/config/webguard

wget -P /usr/share/luci/menu.d https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/usr/share/luci/menu.d/luci-app-device-status.json
wget -P /usr/share/luci/menu.d https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/usr/share/luci/menu.d/luci-app-webguard.json

wget -P /usr/share/rpcd/acl.d https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/usr/share/rpcd/acl.d/luci-app-device-status.json
wget -P /usr/share/rpcd/acl.d https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/usr/share/rpcd/acl.d/luci-app-scheduleaccess.json
wget -P /usr/share/rpcd/acl.d https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/usr/share/rpcd/acl.d/luci-app-webguard.json

wget -P /etc/config https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/etc/config/webguard

mkdir /etc/webguard
wget -P /etc/webguard https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/docker-checker-daemon/web-rated-docker.json
wget -P /etc/webguard https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/firewall/firewall
wget -P /etc/webguard https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/WAN/99-WAN-DHCP-Static.sh
wget -P /etc/webguard https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/web-rated/etc/config/webguard
touch /etc/webguard/web-rated-device.json
