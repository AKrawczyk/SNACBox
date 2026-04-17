#!/bin/sh

# Check if running as root
if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root"
    exit 1
fi

# Check if required packages are installed
if ! which php8-cli >/dev/null; then
    opkg update
    opkg install php8-cli
fi

# Check if source files exist
if [ ! -f "new-device-access.sh" ] || [ ! -f "new-device-access" ]; then
    echo "Source files not found in current directory"
    exit 1
fi

# Copy files
echo "Copying files..."
cp new-device-access.sh /usr/bin/new-device-access.sh
cp new-device-access /etc/init.d/new-device-access
cp new-device-access.php /usr/bin/new-device-access.php
cp web-rated-device.json /usr/bin/web-rated-device.json
cp config.php /usr/bin/config.php

# Set permissions
echo "Setting permissions..."
chmod +x /usr/bin/new-device-access.sh
chmod +x /etc/init.d/new-device-access

# Create log rotation config
echo "Creating log rotation configuration..."
cat > /etc/logrotate.d/new-device-access << 'EOF'
/var/log/new-device-access.log {
    rotate 7
    daily
    compress
    missingok
    notifempty
    create 0644 root root
}
EOF

# Create log file with proper permissions
touch /var/log/new-device-access.log
chmod 644 /var/log/new-device-access.log

# Enable and start service
#echo "Enabling and starting service..."
#/etc/init.d/new-device-access enable
#/etc/init.d/new-device-access start

# Verify service status
#if /etc/init.d/new-device-access status > /dev/null 2>&1; then
#    echo "New device access service installed and running successfully"
#else
#   echo "Error: Service failed to start"
#    exit 1
#fi
