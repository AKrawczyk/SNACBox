#!/bin/sh
# expand-root.sh - grow the root ext4 partition and filesystem to fill the disk
set -eu

# --- Identify the root device ---
FSTYPE="$(awk '$5=="/"{for(i=6;i<=NF;i++) if($i=="-"){print $(i+1); exit}}' /proc/self/mountinfo | tail -n1)"
MAJMIN="$(awk '$5=="/"{print $3}' /proc/self/mountinfo | tail -n1)"

BLK_SYS="$(readlink -f "/sys/dev/block/$MAJMIN")"
ROOT_PART="$(cat "$BLK_SYS/partition")"
ROOT_DEV="/dev/$(basename "$BLK_SYS")"
ROOT_DISK="/dev/$(basename "$(dirname "$BLK_SYS")")"

# --- Grow the partition (-f also fixes the GPT backup header if the image was written to a larger disk) ---
parted -f -s "$ROOT_DISK" resizepart "$ROOT_PART" 100%

# --- Grow the filesystem online via a loop device ---
LOOP_DEV="$(losetup -f)"
losetup "$LOOP_DEV" "$ROOT_DEV"
trap 'losetup -d "$LOOP_DEV" 2>/dev/null || true' EXIT
resize2fs -f "$LOOP_DEV"
losetup -d "$LOOP_DEV"
NEW_UUID=`blkid ${ROOT_DISK}p${ROOT_PART} | sed -n 's/.*PARTUUID="\([^"]*\)".*/\1/p'`
sed -i "s/PARTUUID=[^ ]*/PARTUUID=${NEW_UUID}/" /boot/cmdline.txt
trap - EXIT

# --- Only neede in Pi CM4 devices ---
#echo 'dtoverlay=dwc2,dr_mode=host' >> /boot/config.txt

reboot