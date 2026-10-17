#!/bin/sh

WAN_IF="wan"

# Exit immediately if already static
[ "$(uci get network.$WAN_IF.proto 2>/dev/null)" = "static" ] && exit 0

# Wait up to 60 seconds for a valid DHCP lease during boot
count=0
while [ $count -lt 30 ]; do
    WAN_JSON=$(ifstatus "$WAN_IF" 2>/dev/null)
    up=$(echo "$WAN_JSON" | jsonfilter -e '@.up' 2>/dev/null)
    
    if [ "$up" = "true" ]; then
        IP=$(echo "$WAN_JSON" | jsonfilter -e '@["ipv4-address"][0].address' 2>/dev/null)
        MASK_BITS=$(echo "$WAN_JSON" | jsonfilter -e '@["ipv4-address"][0].mask' 2>/dev/null)
        GATEWAY=$(echo "$WAN_JSON" | jsonfilter -e '@["route"][?(@.target=="0.0.0.0")].nexthop' 2>/dev/null)
        DNS_SERVERS=$(echo "$WAN_JSON" | jsonfilter -e '@["dns-server"][*]' 2>/dev/null)

        if [ -n "$IP" ] && [ -n "$GATEWAY" ] && [ -n "$MASK_BITS" ]; then
            break
        fi
    fi
    
    sleep 2
    count=$((count + 1))
done

# If it couldn't get a lease in time, exit non-zero so it retries next boot
[ -z "$IP" ] || [ -z "$GATEWAY" ] || [ -z "$MASK_BITS" ] && exit 1

# Convert CIDR bits to netmask function inline
bits_to_netmask() {
    local n=$1 i mask=""
    for i in 1 2 3 4; do
        if [ "$n" -ge 8 ]; then val=255; n=$((n - 8))
        elif [ "$n" -gt 0 ]; then val=$((256 - (2 << (7 - n)))); n=0
        else val=0; fi
        [ -z "$mask" ] && mask="$val" || mask="$mask.$val"
    done
    echo "$mask"
}
NETMASK=$(bits_to_netmask "$MASK_BITS")

# Apply to UCI network configuration
uci set network.$WAN_IF.proto='static'
uci set network.$WAN_IF.ipaddr="$IP"
uci set network.$WAN_IF.netmask="$NETMASK"
uci set network.$WAN_IF.gateway="$GATEWAY"

uci delete network.$WAN_IF.dns 2>/dev/null
for dns in $DNS_SERVERS; do
    uci add_list network.$WAN_IF.dns="$dns"
done

uci commit network
ifup "$WAN_IF"

# Exit 0 tells OpenWrt the task succeeded, triggering automatic self-deletion
exit 0
