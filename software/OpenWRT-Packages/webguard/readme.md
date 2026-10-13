# $${\color{red} Note: Dev-Build-1.0}$$ #
## Mannual Installation of WebGuard ##

<h2>Openwrt 25.12+ Firmware</h2>
Goto https://firmware-selector.openwrt.org<br>
Enter chosen hardware eg. "Raspberry Pi"<br>
Select based on the Hardware choice made e.g "Raspberry Pi 4B/400/CM4 (64bit)"<br>
Choose "Customize installed packages"<br>
Goto "Installed Packages"<br>
Add to the end of the list<br>

All hardware

```bash
 parted losetup resize2fs blkid php8 php8-cli php8-mod-curl luci-mod-rpc luci-ssl at jq adguardhome luci-base luci-theme-openwrt-2020 curl kmod-overlay kmod-fuse
```

iPhone Teathering

```bash
kmod-usb-net-ipheth usbmuxd libimobiledevice usbutils libusbmuxd libplist
```

<h2>SNACBox Installation</h2>
Note: This is the test build of the luci-app-webguard APK package for OpenWRT 25.12+</br>
If you wish to install the WebGuard app mannualy the download the three shell script files.<br>

Run the scripts:

```bash
wget https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/luci-app-adguardhome-plugin-1.0.0-r1.apk
wget https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/luci-theme-openwrt-snacbox-1.0.0-r1.apk
apk add --allow-untrusted luci-app-adguardhome-plugin-1.0.0-r1.apk luci-theme-openwrt-snacbox-1.0.0-r1.apk
wget https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/AdGuard-configure.sh
wget https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/webguard-install.sh
wegt https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/webguard/webguard-configure.sh
chmod +x webguard*.sh
chord +x AdGuard*.sh
./AdGuard-configure.sh
./webguard-install.sh
./webguard-configure.sh
```

This will install all the comopnents and configureations needed on a clean install of OpenWRT<br>
Note: only run AdGuard-configure.sh if you install the adguardhome plugin package.
