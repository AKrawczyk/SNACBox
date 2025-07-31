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
if [ ! -f "docker-checker.sh" ] || [ ! -f "docker-checker" ]; then
    echo "Source files not found in current directory"
    exit 1
fi

# Copy files
echo "Copying files..."
cp docker-checker.sh /usr/bin/docker-checker.sh
cp docker-checker /etc/init.d/docker-checker
cp docker-checker.php /usr/bin/docker-checker.php
cp web-rated-docker.json /usr/bin/web-rated-docker.json
cp update-docker-state.sh /usr/bin/update-docker-state.sh
cp update-docker-state.php /usr/bin/update-docker-state.php

# Set permissions
echo "Setting permissions..."
chmod +x /usr/bin/docker-checker.sh
chmod +x /etc/init.d/docker-checker

# Create log rotation config
echo "Creating log rotation configuration..."
cat > /etc/logrotate.d/docker-checker << 'EOF'
/var/log/docker-checker.log {
    rotate 7
    daily
    compress
    missingok
    notifempty
    create 0644 root root
}
EOF

# Create log file with proper permissions
touch /var/log/docker-checker.log
chmod 644 /var/log/docker-checker.log

# Enable and start service
echo "Enabling and starting service..."
/etc/init.d/docker-checker enable
/etc/init.d/docker-checker start

# Verify service status
if /etc/init.d/docker-checker status > /dev/null 2>&1; then
    echo "Docker checker service installed and running successfully"
else
    echo "Error: Service failed to start"
    exit 1
fi
