#!/bin/sh
apk update
apk add docker dockerd
service dockerd enable
service dockerd start

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
