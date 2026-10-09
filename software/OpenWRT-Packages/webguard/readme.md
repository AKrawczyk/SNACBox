# $${\color{red} Note: Underdevelopment Currently Not Working}$$ #
## Mannual Installation of WebGuard ##

Note: This is the test build of the luci-app-webguard APK package for OpenWRT 25.12+</br>
If you wish to install the WebGuard app mannualy the download the three shell script files.<br>

Run the scripts:

```bash
chmod +x webguard*.sh
chord +x AdGuard*.sh
AdGuard-configure.sh
webguard-install.sh
webguard-configure.sh
```

This will install all the comopnents and configureations needed on a clean install of OpenWRT<br>
Note: only run AdGuard-configure.sh if you install the adguardhome plugin package.
