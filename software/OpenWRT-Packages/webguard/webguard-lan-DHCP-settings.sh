#!/bin/sh

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
