#!/bin/sh

# Log file
LOG_FILE="/var/log/mwan-watchdog.log"

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "${LOG_FILE}"
}

# Fetch WAN gateway
. /lib/functions/network.sh
network_flush_cache
network_find_wan NET_IF
network_get_gateway NET_GW "${NET_IF}"

# Check WAN connectivity
TRIES=0
MAX_TRIES=5

# Trap SIGTERM to exit gracefully
trap "log_message 'Exiting script.'; exit 0" SIGTERM

while true; do
    sleep 60
    if ! (ping -c 1 -w 3 "${NET_GW}" &> /dev/null); then 
        ((TRIES++))
        log_message "Ping failed. Attempt ${TRIES}."
    else
        TRIES=0  # Reset TRIES if ping is successful
    fi

    if [ "${TRIES}" -gt "${MAX_TRIES}" ]; then
        log_message "Connectivity lost for ${MAX_TRIES} attempts. Restarting network."
        TRIES=0
        
        # Restart network
        if /etc/init.d/network stop; then
            hub-ctrl -h 0 -P 1 -p 0
            sleep 1
            hub-ctrl -h 0 -P 1 -p 1
            if /etc/init.d/network start; then
                log_message "Network restarted successfully."
            else
                log_message "Error: Failed to start network."
            fi
        else
            log_message "Error: Failed to stop network."
        fi
    fi
done
 
