#Install ADGuardHome
opkg update
opkg install adguardhome
/etc/init.d/adguardhome start
/etc/init.d/adguardhome enable  # Auto-start on boot

# 1. Move dnsmasq to port 54.
# 2. Set local domain to "lan".
# 3. Add local '/lan/' to make sure all queries *.lan are resolved in dnsmasq;
# 4. Add expandhosts '1' to make sure non-expanded hosts are expanded to ".lan";
# 5. Disable dnsmasq cache size as it will only provide PTR/rDNS info, making sure queries are always up to date (even if a device internal IP change after a DHCP lease renew).
# 6. Disable reading /tmp/resolv.conf.d/resolv.conf.auto file (which are your ISP nameservers by default), you don't want to leak any queries to your ISP.
# 7. Delete all forwarding servers from dnsmasq config.
uci set dhcp.@dnsmasq[0].port="54"
uci set dhcp.@dnsmasq[0].domain="web-rated.home"
uci set dhcp.@dnsmasq[0].local="/web-rated.home/"
uci set dhcp.@dnsmasq[0].expandhosts="1"
uci set dhcp.@dnsmasq[0].cachesize="0"
uci set dhcp.@dnsmasq[0].noresolv="1"
uci -q del dhcp.@dnsmasq[0].server

uci commit dhcp
service dnsmasq restart
service odhcpd restart

#AdGuard-Home yaml
cp adguard/*.yaml /etc/
/etc/init.d/adguardhome restart
