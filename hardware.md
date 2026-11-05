# Supported Hardware
<h2>Friendly Elec NanoPi R3S SNACBox (Supported)</h2>
<h3>Friendly Elec</h3> - https://www.friendlyelec.com/index.php?route=product/product&product_id=311<br>
    Mini Dual Gigabit Ethernet Base Board and Metal case designed around the RK3566 Rock Chip Arm SoC.<br>
<img src="screenshots/R3SLTS_03-900x630.jpg" alt="Friednly Elec NanoPi R3S" width="300" height="200"/><br>

<h2>Openwrt 25.12+ Firmware</h2>
Enter chosen hardware "Friendly"<br>
Choose version "25.12.6"
Select based on the Hardware choice made "Friendly Elec NanoPi R3S (64bit)"<br>
Choose "Customize installed packages"<br>
Goto "Installed Packages"<br>
Add to the end of the list<br>

Additional Packages

```bash
 parted losetup resize2fs blkid php8 php8-cli php8-mod-curl luci-mod-rpc luci-ssl at jq adguardhome luci-base luci-theme-openwrt-2020 curl kmod-veth kmod-loop kmod-fuse
```

iPhone Tethering

```bash
kmod-usb-net-ipheth usbmuxd libimobiledevice usbutils libusbmuxd libplist
```

Goto "Script to run on first boot (uci-defaults)"

Add this resize script to resize the storage.

```bash
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
#sed -i "s/PARTUUID=[^ ]*/PARTUUID=${NEW_UUID}/" /boot/cmdline.txt
trap - EXIT

# --- Only neede in Pi CM4 devices ---
#echo 'dtoverlay=dwc2,dr_mode=host' >> /boot/config.txt

reboot
```

Click "Request Build"<br>
Click and download "Factory (Ext4)"<br>

<h2>Raspberry Pi CM4 SNACBox (Supported)</h2>
<h3>Waveshare</h3> - https://www.waveshare.com/cm4-dual-eth-mini.htm?sku=23853<br>
    Mini Dual Gigabit Ethernet Base Board/Mini-Computer Designed for Raspberry Pi Compute Module 4.<br>
<img src="screenshots/cm4-dual-eth-mini-box-1_1_1.jpg" alt="Waveshare CM4" width="300" height="200"/><br>    
<h3>Raspberry Pi</h3> - https://www.raspberrypi.com/products/compute-module-4/?variant=raspberry-pi-cm4001000<br>
    The power of Raspberry Pi 4 in a compact form factor for deeply embedded applications. Raspberry Pi Compute Module 4 incorporates a quad-core ARM Cortex-A72 processor, dual video output, and a wide selection of other interfaces<br>

![Raspberry Pi CM4](screenshots/cm4.webp)<br>

<h2>Openwrt 25.12+ Firmware</h2>
Enter chosen hardware "Raspberry"<br>
Choose version "25.12.6"
Select based on the Hardware choice made "Raspberry Pi (4B/400/CM4) (64bit)"<br>
Choose "Customize installed packages"<br>
Goto "Installed Packages"<br>
Add to the end of the list<br>

Additional Packages

```bash
 parted losetup resize2fs blkid php8 php8-cli php8-mod-curl luci-mod-rpc luci-ssl at jq adguardhome luci-base luci-theme-openwrt-2020 curl kmod-veth kmod-loop kmod-fuse
```
Waveshare and CM4 Kernel Packages

```bash
 kmod-usb-core kmod-usb2 kmod-usb3 kmod-dwc2
```

iPhone Tethering

```bash
kmod-usb-net-ipheth usbmuxd libimobiledevice usbutils libusbmuxd libplist
```

Goto "Script to run on first boot (uci-defaults)"

Add this resize script to resize the storage.

```bash
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
echo 'dtoverlay=dwc2,dr_mode=host' >> /boot/config.txt

reboot
```

Click "Request Build"<br>
Click and download "Factory (Ext4)"<br>

<h2>Raspberry Pi 4B SNACBox (Supported)</h2>
<h3>Waveshare</h3> - https://www.waveshare.com/usb-3.2-gen1-hub-gigabit-eth-hat.htm<br>
    USB 3.2 Gen1 and Gigabit Ethernet HUB HAT for Raspberry Pi, 3x USB 3.2 Gen1, 1x Gigabit Ethernet, driver-free.<br>
<img src="screenshots/usb-3.2-gen1-hub-gigabit-eth-hat-5.jpg" alt="Waveshare CM4" width="300" height="200"/><br>
Or<br>

<h2>Openwrt 25.12+ Firmware</h2>
Enter chosen hardware "Raspberry"<br>
Choose version "25.12.6"
Select based on the Hardware choice made "Raspberry Pi (4B/400/CM4) (64bit)"<br>
Choose "Customize installed packages"<br>
Goto "Installed Packages"<br>
Add to the end of the list<br>

Additional Packages

```bash
 parted losetup resize2fs blkid php8 php8-cli php8-mod-curl luci-mod-rpc luci-ssl at jq adguardhome luci-base luci-theme-openwrt-2020 curl kmod-veth kmod-loop kmod-fuse
```
Waveshare and Pi 4B Kernel Packages

```bash
 kmod-usb-core kmod-usb3 kmod-usb-net-rtl8152 kmod-usb-ohci kmod-usb-uhci kmod-usb2
```

iPhone Tethering

```bash
kmod-usb-net-ipheth usbmuxd libimobiledevice usbutils libusbmuxd libplist
```

Goto "Script to run on first boot (uci-defaults)"

Add this resize script to resize the storage.

```bash
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
```

Click "Request Build"<br>
Click and download "Factory (Ext4)"<br>

<h3>USB Ethernet Dongle</h3>
    Needs to be a USB-A Ethernet Gigabit dongle.
<h3>Raspberry Pi</h3> - https://www.raspberrypi.com/products/raspberry-pi-4-model-b/<br>
    Your tiny, dual-display, desktop computer …and robot brains, smart home hub, media centre, networked AI core, factory controller, and much more<br>

![Raspberry Pi 4B](screenshots/raspberry-pi-4-labelled-f5e5dcdf6a34223235f83261fa42d1e8.png)


<h2>Raspberry Pi CM5 SNACBox (Expermental)</h2>
<h3>Waveshare</h3> - https://www.waveshare.com/cm5-dual-eth-mini.htm?sku=31075<br>
    Mini Dual Gigabit Ethernet Base Board/Mini-Computer Designed for Raspberry Pi Compute Module 5.<br>
<img src="screenshots/cm5-dual-eth-mini-box-1_1_1.jpg" alt="Waveshare CM5" width="300" height="300"/>    
<h3>Raspberry Pi</h3> - https://www.raspberrypi.com/products/compute-module-5/?variant=cm5-104032<br>
    Compute Module 5 is a powerful and scalable system on module with a 64-bit Arm processor @ 2.4GHz, an I/O controller, video and PCIe interfaces, and a range of wireless, SDRAM and eMMC options

<img src="screenshots/compute-module-5-2.jpg" alt="Raspberry Pi CM5" width="300" height="300"/>

<h2>Raspberry Pi 5 SNACBox (Expermental)</h2>
<h3>Waveshare</h3> - https://www.waveshare.com/pcie-to-gigabit-eth-usb3.2-hat-plus.htm<br>
    PCIe To Gigabit Ethernet And USB 3.2 Gen1 HAT For Raspberry Pi 5, 3x USB 3.2 Gen1, 1x Gigabit Ethernet, Driver-Free, Plug And Play, Raspberry Pi 5 PCIe HAT.<br>
<img src="screenshots/PCIe-TO-Gigabit-ETH-USB3.2-HAT-Plus-details-3.jpg" alt="Waveshare CM4" width="300" height="200"/><br>
Or<br>

<h3>USB Ethernet Dongle</h3>
    Needs to be a USB-A Ethernet Gigabit dongle.
<h3>Raspberry Pi</h3> - https://www.raspberrypi.com/products/raspberry-pi-5/<br>
    The everything computer. Optimised. With 2–3× the speed of the previous generation, and featuring silicon designed in‑house for the best possible performance, we’ve redefined the Raspberry Pi experience.<br>

![Raspberry Pi 5](screenshots/rp5.webp)

<h2>GL iNet Brume 2 (GL-MT2500) (Expermental)</h2>
<h3>Brume 2</h3> - https://store-eu.gl-inet.com/products/brume-2-gl-mt2500-vpn-security-gateway<br>
    Brume 2 (GL-MT2500/GL-MT2500A) is a lightweight and powerful VPN Gateway that runs on OpenWrt v21.02 operating system. It is compactly designed to host a VPN server at home, or run SD-WAN (Site-to-Site) for small and medium-sized enterprises.<br>
<br>Note: will need Luci installed to work.
    
<img src="screenshots/ABS-mt2500_main_700x700.webp" alt="GL iNet Brume 2 (GL-MT2500) - ABS" width="300" height="300"/><br>
<img src="screenshots/ALU-mt2500a_main_700x700.webp" alt="GL iNet Brume 2 (GL-MT2500) - ALU" width="300" height="300"/><br>

<h2>OpenWRT One SNACBox (Expermental)</h2>
<h3>Banana Pi</h3> - https://docs.banana-pi.org/en/OpenWRT-One/BananaPi_OpenWRT-One<br>
    The “OpenWrt One/AP-24.XY” router board based on MediaTek MT7981B (Filogic 820) SoC and MediaTek MT7976C dual-band WiFi 6 chipset.It is the first official development board of the OpenWRT open source community<br>
<h3>M.2 NVME SSD M Key</h3>
    Needs a small 2230 or 2242 NVME SSD to handle the larger apps like Docker and ADGuardHome
    Note: This box has not been fully tested yet.

![Banana Pi OpenWRT One](screenshots/banana_pi_openwrt_one_case_9.jpg)
