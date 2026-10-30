Requirements:
OpenWRT 25.12+
Min 2GB storage for Docker containers
2 x Network Ports for WAN and LAN connectivity

Software:
    resize-openwrt-ext4-storage.sh resizes OpenWRT EXT4 storage to max size of SD card.
    luci-theme-openwrt-snacbox installs the SNACBox theme.
    luci-app-adguardhome-plugin installs AdGuardhome with pre-set configurations, makes it the default DNS server and creates a luci dashboard for AdGuardhome.
    luci-app-adguardhome-plugin-Ex installs AdGuardhome with a luci dashboard for AdGuardhome.
    luci-app-webguard-Ex installs the webguard components and adds disabled firewall rules.
    new-adguradhome-settings.sh enables the plugin and makes sure the adguardhome configuration is enabled.
    mod-adguardhome-settings.sh adds configurations to adguardhome


Optional:
    Install luci-theme-openwrt-snacbox
    Install parted losetup resize2fs blkid
    Run only on EXT4 Storage resize-openwrt-ext4-storage.sh

Setup Options:
1. Esisting OpenWRT Router setup
    Install luci-app-adguardhome-plugin
    Install luci-app-webguard-Ex
    Run /etc/webguard/new-adguardhome-settings.sh

2. Existing OpenWRT Router and Existing DNS setup
    Install luci-app-adguardhome-plugin-Ex
    Goto http://<ROUTER IP>:3000
    Setup AdGuardhome as secondary DNS server by change the port numbers eg. WWW = 3080, DNS = 54
    Install luci-app-webguard-Ex
    Run /etc/webguard/new-adguardhome-settings.sh

3. Existing OpenWRT Router setup with existing AdGuardhome setup
    Install luci-app-adguardhome-plugin-Ex
    Install luci-app-webguard-Ex
    Run /etc/webguard/mod-adguardhome-settings.sh