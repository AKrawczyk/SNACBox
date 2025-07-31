#!/bin/sh
#Install OpenWRT tools to enable mobile teathering via USB to allow  mobile wan

opkg update
opkg install kmod-usb-net-rndis kmod-nls-base kmod-usb-core kmod-usb-net kmod-usb-net-cdc-ether kmod-usb2 kmod-usb-net-ipheth usbmuxd libimobiledevice usbutils hub-ctrl

# Call usbmuxd
usbmuxd -v
 
# Add usbmuxd to autostart
sed -i -e "\$i usbmuxd" /etc/rc.local

# Configure Mobile Wan Interface and Firewall
echo "Please configure Mobile WAN interface and Firewall./n"
echo "GUI configuration goto - Network -> Interfaces -> New Interface./n"
echo "Name = mwan, Interface = ethX or usbX, Firewall Zone = wan./n"
echo "Once completed press anykey to continue: -> "

# Wait for a single key press
read -n 1 -s

# Copy files
echo "Copying files..."
cp mwan-watchdog.sh /usr/bin/mwan-watchdog.sh
cp mwan-watchdog /etc/init.d/mwan-watchdog
cp iphone-mwan-watchdog.sh /usr/bin/iphone-mwan-watchdog.sh
mkdir -p /etc/lockdown-backup

# Set permissions
echo "Setting permissions..."
chmod +x /usr/bin/mwan-watchdog.sh
chmod +x /usr/bin/iphone-mwan-watchdog.sh
chmod +x /etc/init.d/mwan-watchdog

# Create log rotation config
echo "Creating log rotation configuration..."
cat > /etc/logrotate.d/mwan-watchdog << 'EOF'
/var/log/mwan-watchdog.log {
    rotate 7
    daily
    compress
    missingok
    notifempty
    create 0644 root root
}
EOF

# Create log file with proper permissions
touch /var/log/mwan-watchdog.log
chmod 644 /var/log/mwan-watchdog.log

# Enable and start service
echo "Enabling and starting service..."
/etc/init.d/mwan-watchdog enable
/etc/init.d/mwan-watchdog start

# Verify service status
if /etc/init.d/mwan-watchdog status > /dev/null 2>&1; then
    echo "Mobile wan watchdog service installed and running successfully"
else
    echo "Error: Mobile wan watchdog service failed to start"
    exit 1
fi

# Create log rotation config
echo "Creating log rotation configuration..."
cat > /etc/logrotate.d/iphone-mwan-watchdog << 'EOF'
/var/log/iphone-mwan-watchdog.log {
    rotate 7
    daily
    compress
    missingok
    notifempty
    create 0644 root root
}
EOF

# Create log file with proper permissions
touch /var/log/iphone-mwan-watchdog.log
chmod 644 /var/log/iphone-mwan-watchdog.log

# Add watchdog script to autostart
sed -i -e "\$i (/usr/bin/iphone-mwan-watchdog.sh) &" /etc/rc.local