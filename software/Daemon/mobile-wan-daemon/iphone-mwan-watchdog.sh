#!/bin/sh
# A small script to make life with iPhone tethering less cumbersome on OpenWrt
# Petr Vyskocil, Apr 2020
# Public domain

# Modificatied by Aaron Krawczyk, Jan 2025
# With assistance from Caluide AI
 
# After you successfully allow iPhone tethering, copy files with name like
# /var/lib/lockdown/12345678-9ABCDEF012345678.plist to /etc/lockdown/locks.
# That way, you won't have to set up trust again after router reboots.
if [ -e /etc/lockdown-backup/ ]
then
    mkdir -p /var/lib/lockdown
    cp -f /etc/lockdown-backup/* /var/lib/lockdown/
fi
 
# lockdown records restored, now we can launch usbmuxd. Don't launch it sooner!
usbmuxd
 
# We are up and running now. But unfortunately if your carrier signal is weak, iPhone will
# drop connection from time to time and you'd have to unplug and replug USB cable to start tethering
# again. Script below automates that activity.
 
# ... existing code ...
LOGFILE="/var/log/iphone_mwan-watchdog.log"
MAX_ATTEMPTS=5
attempts=0

# Trap SIGTERM to exit gracefully
trap "echo 'Exiting script.' >> \"$LOGFILE\"; exit 0" SIGTERM

# ... existing code ...
 
# If we see iPhone ethernet interface, try to ping iPhone router's address (172.20.10.1).
# When the ping is unsuccessful, rebind iPhone ethernet USB driver and wait for things to settle down
while :
do
    for i in /sys/bus/usb/drivers/ipheth/*:*
    do
        if [ -e "${i}" ] && [ $attempts -lt $MAX_ATTEMPTS ]; then
            if ! ping -w 3 172.20.10.1 &> /dev/null; then
                echo "$(date): Ping failed for ${i##*/}" >> "$LOGFILE"
                echo "${i##*/}" > "${i%/*}"/unbind
                if [ $? -ne 0 ]; then
                    echo "$(date): Failed to unbind ${i##*/}" >> "$LOGFILE"
                fi
                echo "${i##*/}" > "${i%/*}"/bind
                if [ $? -ne 0 ]; then
                    echo "$(date): Failed to bind ${i##*/}" >> "$LOGFILE"
                fi
                sleep 20
                attempts=$((attempts + 1))
                if [ $attempts -ge $MAX_ATTEMPTS ]; then
                    echo "$(date): Waiting for iPhone(s) to be removed..." >> "$LOGFILE"
                fi
            else
                attempts=0  # Reset attempts on successful ping
                cp -f /var/lib/lockdown/* /etc/lockdown-backup/
            fi
        elif [ ! -e "${i}"] && [ $attempts -ge $MAX_ATTEMPTS ]; then
            echo "$(date): iPhone(s) have been to be removed." >> "$LOGFILE"
            attempts=0
        else
            continue
        fi
    done
    sleep 1
done