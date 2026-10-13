# $${\color{red} Note: Dev-Build-1.0}$$ #
## Mannual Installation of WebGuard ##

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
