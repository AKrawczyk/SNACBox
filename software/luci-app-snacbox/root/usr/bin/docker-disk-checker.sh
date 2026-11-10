#!/bin/sh

# Function to run the checker but needs to be added to rc.local echo "/usr/bin/docker-disk-checker.sh" >> /etc/rc.local
run_docker_disk_checker() {
    # Check if 'overlay' is present in the output of df -h
    if df -h | grep -q 'overlay'; then
        # If overlay is found, check if the error flag file exists
        if [ -f /etc/docker-disk-checker ]; then
            rm /etc/docker-disk-checker
        fi
    else
        # If 'overlay' is not found, run e2fsck and reboot
        echo "Overlay not found. Running e2fsck and rebooting..."
        touch /etc/docker-disk-checker
        
        # Dynamic partition detection using your provided logic
        MAJMIN="$(awk '$5=="/"{print $3}' /proc/self/mountinfo | tail -n1)"
        BLK_SYS="$(readlink -f "/sys/dev/block/$MAJMIN")"
        ROOT_PART="$(cat "$BLK_SYS/partition")"
        ROOT_DEV="/dev/$(basename "$BLK_SYS")"
        
        # Check if we successfully found a partition
        if [ -n "$ROOT_DEV" ] && [ -n "$ROOT_PART" ]; then
            echo "Detected partition: $ROOT_DEV (Partition: $ROOT_PART)"
            # Run e2fsck on the detected partition
            e2fsck -f -y "$ROOT_DEV"
        else
            echo "Error: Could not detect the partition."
        fi
        
        reboot
    fi
}

# Start the checker in the background
run_docker_disk_checker