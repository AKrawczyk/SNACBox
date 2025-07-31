#!/bin/sh

# Path to the PHP script
SCRIPT_PATH="/usr/bin/docker-checker.php"

sleep 20
# Function to run the checker
run_docker_checker() {
    # Check if 'overlay' is present in the output of df -h
    if df -h | grep -q '/opt/docker/overlay2'; then
        # If 'overlay' is found, run the PHP script every 30 seconds
        while true; do
            /usr/bin/php8-cli "$SCRIPT_PATH" > /var/log/docker_checker.log
            sleep 30
        done
    else
        # If 'overlay' is not found, run e2fsck and reboot
        echo "Overlay not found. Running e2fsck and rebooting..."
        e2fsck -f -y /dev/mmcblk0p2
        reboot
    fi
}

# Start the checker
run_docker_checker &

# Store the PID for later use
echo $! > /var/run/docker-checker.pid