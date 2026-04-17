#!/bin/sh

# Path to the PHP script
SCRIPT_PATH="/usr/bin/new-device-access.php"

# Function to run the checker
run_device_checker() {
    while true; do
        # Run the PHP script
        /usr/bin/php8-cli "$SCRIPT_PATH" > /var/log/new-device-access.log
        
        # Wait 30 seconds
        sleep 30
    done
}

# Start the checker
run_device_checker &

# Store the PID for later use
echo $! > /var/run/new-device-access.pid