# Existing Openwrt Setups
These instruction are designed for existing OpenWRT setups.<br>
Use with caution and make backups of important files (/etc/config/adguardhome /etc/config/firewall /etc/adguardhome/adguardhome.yaml).

<h2>Requirements:</h2>
    OpenWRT 25.12+<br>
    Min 2GB storage for Docker containers<br>
    2 x Network Ports for WAN and LAN connectivity<br>

<h2>Software:</h2>
    resize-openwrt-ext4-storage.sh resizes OpenWRT EXT4 storage to max size of SD card.<br>
    luci-theme-openwrt-snacbox installs the SNACBox theme.<br>
    luci-app-adguardhome-plugin installs AdGuardhome with pre-set configurations, makes it the default DNS server and creates a luci dashboard for AdGuardhome.<br>
    luci-app-adguardhome-plugin-Ex installs AdGuardhome with a luci dashboard for AdGuardhome.<br>
    luci-app-webguard-Ex installs the webguard components and adds disabled firewall rules.<br>
    new-adguradhome-settings.sh enables the plugin and makes sure the adguardhome configuration is enabled.<br>
    mod-adguardhome-settings.sh adds configurations to adguardhome.<br>


<h2>Optional:</h2>
    Install luci-theme-openwrt-snacbox<br>
    Install parted losetup resize2fs blkid<br>
    Run only on EXT4 Storage resize-openwrt-ext4-storage.sh<br>

<h2>Install Options:</h2>
<strong>1. Esisting OpenWRT Router setup</strong>
    Install luci-app-adguardhome-plugin<br>
    Install luci-app-webguard-Ex<br>
    Run /etc/webguard/new-adguardhome-settings.sh<br><br>

<strong>2. Existing OpenWRT Router and Existing DNS setup</strong>
    Install luci-app-adguardhome-plugin-Ex<br>
    Goto http://<ROUTER IP>:3000<br>
    Setup AdGuardhome as secondary DNS server by change the port numbers eg. WWW = 3080, DNS = 54<br>
    Install luci-app-webguard-Ex<br>
    Run /etc/webguard/new-adguardhome-settings.sh<br><br>

<strong>3. Existing OpenWRT Router setup with existing AdGuardhome setup</strong>
    Install luci-app-adguardhome-plugin-Ex<br>
    Install luci-app-webguard-Ex<br>
    Run /etc/webguard/mod-adguardhome-settings.sh<br>

<h2>Post Install</h2>
Depending on the install option chosen there will be addition setup steps needed.<br>
All options will require the configuration of Webguard and AdGuardhome plugin.<br>
Goto Webguard -> Web Rated -> COnfiguration<br>
Enter system Username and Password<br><br>
Goto Webguard -> AdGuardHome -> Configuration<br>
Enter AdGuardhome Username and Password<br><br>
Move all firewall drop rule to bottom of rules list.<br>
Enable or modify all firewall rules depending on your existing configuration.<br><br>
Option 2 has additional complications, for the Adguardhome DNS to act as an addition DNS server you will need to configure a forward rule to forward all doc DNS port 53 to the IP of the AdGuardhome DNS server port e.g 54 or what ever was chosen when AdGuardhome was setup.<br>
