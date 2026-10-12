rm /usr/share/luci/menu.d/luci-app-adguardhome.json
rm /usr/share/rpcd/acl.d/luci-app-adguardhome.json
wget -P /usr/share/luci/menu.d/ https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/luci-app-adguardhome-plugin/root/usr/share/luci/menu.d/luci-app-adguardhome.json
wget -P /usr/share/luci/menu.d/ https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/luci-app-adguardhome-plugin/root/usr/share/luci/menu.d/luci-app-webguard.json
wget -P /usr/share/rpcd/acl.d/ https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/luci-app-adguardhome-plugin/root/usr/share/rpcd/acl.d/luci-app-adguardhome.json
wget -P /usr/share/rpcd/acl.d/ https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/luci-app-adguardhome-plugin/root/usr/share/rpcd/acl.d/luci-app-webguard.json

mv /etc/config/adguradhome /etc/config/adguardhome.org
mv /etc/config/adguardhome.txt /etc/config/adguardhome

service adguardhome restart
service rpcd restart
service uhttpd restart
