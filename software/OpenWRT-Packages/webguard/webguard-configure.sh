#!/bin/sh

#-------- Docker configuration --------
/etc/init.d/dockerd start
/etc/init.d/dockerd enable

docker pull webrated/e2guardian:latest
docker pull webrated/unboundns:latest
docker pull webrated/webbound:latest

docker network create --driver=bridge --subnet=172.20.0.0/16 --gateway=172.20.0.1 doc1

#-------- Docker network firewall configuration --------

NETWORK_NAME="doc1"
IFACE="doc"
ZONE="doc"

# 1. Determine the bridge device for the docker network
if ! docker network inspect "$NETWORK_NAME" >/dev/null 2>&1; then
    echo "Error: Docker network '$NETWORK_NAME' not found."
    exit 1
fi

# Use a custom bridge name if one was configured, else br-<short id>
BRIDGE_NAME=$(docker network inspect --format='{{index .Options "com.docker.network.bridge.name"}}' "$NETWORK_NAME" 2>/dev/null)
if [ -z "$BRIDGE_NAME" ] || [ "$BRIDGE_NAME" = "<no value>" ]; then
    NET_ID=$(docker network inspect --format='{{.Id}}' "$NETWORK_NAME" | cut -c1-12)
    BRIDGE_NAME="br-${NET_ID}"
fi
echo "Found Docker network '$NETWORK_NAME' with bridge device: $BRIDGE_NAME"

# 2. Verify the bridge exists on the host
if ! ip link show "$BRIDGE_NAME" >/dev/null 2>&1; then
    echo "Error: Bridge device $BRIDGE_NAME does not exist on the system yet."
    exit 1
fi

# 3. Configure the OpenWrt network interface
echo "Configuring OpenWrt network interface '$IFACE'..."
uci set network.$IFACE='interface'
uci set network.$IFACE.proto='none'
uci set network.$IFACE.device="$BRIDGE_NAME"
uci commit network

# 4. Find (or create) firewall zone and add the interface
echo "Configuring firewall zone '$ZONE'..."
ZONE_SECTION=""
for s in $(uci -q show firewall | sed -n 's/^firewall\.\([^.=]*\)=zone$/\1/p'); do
    if [ "$(uci -q get firewall.$s.name)" = "$ZONE" ]; then
        ZONE_SECTION="$s"
        break
    fi
done

if [ -z "$ZONE_SECTION" ]; then
    ZONE_SECTION=$(uci add firewall zone)   # returns the new section id
    uci set firewall.$ZONE_SECTION.name="$ZONE"
    uci set firewall.$ZONE_SECTION.input='ACCEPT'
    uci set firewall.$ZONE_SECTION.output='ACCEPT'
    uci set firewall.$ZONE_SECTION.forward='ACCEPT'
    echo "Created new firewall zone '$ZONE' ($ZONE_SECTION)."
fi

# Add the interface if it isn't already in the zone's network list
FOUND=0
for net in $(uci -q get firewall.$ZONE_SECTION.network); do
    if [ "$net" = "$IFACE" ]; then
        FOUND=1
        break
    fi
done

if [ "$FOUND" -eq 0 ]; then
    uci add_list firewall.$ZONE_SECTION.network="$IFACE"
    echo "Added interface '$IFACE' to firewall zone '$ZONE'."
else
    echo "Interface '$IFACE' is already part of firewall zone '$ZONE'."
fi
uci commit firewall

# 5. Apply changes
echo "Applying changes..."
/etc/init.d/network reload
ifup $IFACE 2>/dev/null
/etc/init.d/firewall reload

echo "Done! Interface '$IFACE' is bound to $BRIDGE_NAME and in firewall zone '$ZONE'."

#-------- Docker Checker Daemon Activation --------

docker run -d --name web-rated-none --net doc1  --ip 172.20.0.101 --restart unless-stopped webrated/webbound:latest

/usr/bin/php8-cli "/usr/bin/docker-checker.php" > /var/log/docker_checker.log
/usr/bin/php8-cli "/usr/bin/docker-checker.php" > /var/log/docker_checker.log
/usr/bin/php8-cli "/usr/bin/docker-checker.php" > /var/log/docker_checker.log

/etc/init.d/docker-checker start
/etc/init.d/docker-checker enable

#-------- Firewall Configuration Activation --------
mv /etc/config/firewall /etc/config/firewall.org
cp /etc/webguard/firewall /etc/config/
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

#-------- LAN IP and DHCP Activation --------

IFACE="lan"
IPADDR="192.168.12.1/24"      # CIDR format (OpenWrt 25.12 style)
START="50"
LIMIT="200"
LEASETIME="120h"
DHCP_OPTION="6,192.168.12.1"

# 0. Make sure the interface exists
if [ "$(uci -q get network.$IFACE)" != "interface" ]; then
    echo "Error: network interface '$IFACE' does not exist."
    exit 1
fi

# 1. Set the IPv4 address on the interface
echo "Setting IPv4 address $IPADDR on '$IFACE'..."
uci set network.$IFACE.proto='static'
uci -q delete network.$IFACE.ipaddr      # clear old value(s), string or list
uci -q delete network.$IFACE.netmask     # not needed with CIDR notation
uci add_list network.$IFACE.ipaddr="$IPADDR"
uci commit network

# 2. Configure the DHCP server for the interface
echo "Configuring DHCP server on '$IFACE'..."
if [ "$(uci -q get dhcp.$IFACE)" != "dhcp" ]; then
    uci set dhcp.$IFACE='dhcp'
fi

uci set dhcp.$IFACE.interface="$IFACE"
uci set dhcp.$IFACE.dhcpv4='server'
uci set dhcp.$IFACE.ignore='0'
uci set dhcp.$IFACE.start="$START"
uci set dhcp.$IFACE.limit="$LIMIT"
uci set dhcp.$IFACE.leasetime="$LEASETIME"
uci set dhcp.$IFACE.force='1'            # Force DHCP even if another DHCP server is detected

# Replace any existing DHCP options (idempotent)
uci -q delete dhcp.$IFACE.dhcp_option
uci add_list dhcp.$IFACE.dhcp_option="$DHCP_OPTION"

uci commit dhcp

# 3. Apply changes
echo "Applying changes..."
/etc/init.d/network reload
/etc/init.d/dnsmasq restart

echo "Done! '$IFACE' = $IPADDR, DHCP pool .$START - .$((START + LIMIT - 1)), lease $LEASETIME, option $DHCP_OPTION, force enabled."

#-------- Webguard New Device Access Daemon Activation --------
/etc/init.d/new-device-access start
/etc/init.d/new-device-access enable

#-------- Webguard Application Configuration --------
mkdir /www/html
/etc/init.d/rpcd restart
/etc/init.d/uhttpd restart

#cp /etc/webguard/99-WAN-DHCP-Static.sh /etc/uci-default/
#chmod +x /etc/uci-default/99-WAN-DHCP-Static.sh
