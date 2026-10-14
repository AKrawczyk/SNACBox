include $(TOPDIR)/rules.mk

PKG_NAME:=webguard
PKG_VERSION:=1.0.0
PKG_RELEASE:=1

PKG_SOURCE_PROTO:=git
PKG_SOURCE_URL:=https://github.com/AKrawczyk/SNACBox.git
PKG_SOURCE_VERSION:=main
PKG_SOURCE_SUBDIR:=$(PKG_NAME)-$(PKG_VERSION)

include $(INCLUDE_DIR)/package.mk

define Package/luci-app-webguard
  SECTION:=net
  CATEGORY:=Network
  TITLE:=Webguard network and DNS filtering components
  DEPENDS:=+docker +dockerd +php8 +php8-cli +php8-mod-curl +luci-mod-rpc +luci-ssl +at +jq
endef

define Package/luci-app-webguard/description
 Webguard daemon scripts, blacklist tools, and LuCI status views.
endef

define Package/luci-app-webguard/conffiles
/etc/config/firewall
endef

define Package/luci-app-webguard/install
	$(INSTALL_DIR) $(1)/usr/bin $(1)/etc/init.d $(1)/etc/blacklist-ip
	$(INSTALL_BIN) ./root/usr/bin/docker-checker.sh $(1)/usr/bin/docker-checker.sh
	$(INSTALL_DATA) ./root/usr/bin/docker-checker.php $(1)/usr/bin/docker-checker.php
	$(INSTALL_DATA) ./root/usr/bin/web-rated-docker.json $(1)/usr/bin/web-rated-docker.json
	$(INSTALL_BIN) ./root/usr/bin/update-docker-state.sh $(1)/usr/bin/update-docker-state.sh
	$(INSTALL_DATA) ./root/usr/bin/update-docker-state.php $(1)/usr/bin/update-docker-state.php
	$(INSTALL_BIN) ./root/etc/init.d/docker-checker $(1)/etc/init.d/docker-checker

	$(INSTALL_DATA) ./root/etc/config/firewall $(1)/etc/config/firewall

	$(INSTALL_DATA) ./root/blacklist-ip/blacklist-ip.php $(1)/etc/blacklist-ip/blacklist-ip.php
	$(INSTALL_DATA) ./root/blacklist-ip/blacklist-ip-conf.php $(1)/etc/blacklist-ip/blacklist-ip-conf.php
	$(INSTALL_DATA) ./root/blacklist-ip/config.php $(1)/etc/blacklist-ip/config.php

	$(INSTALL_BIN) ./root/usr/bin/new-device-access.sh $(1)/usr/bin/new-device-access.sh
	$(INSTALL_DATA) ./root/usr/bin/config.php $(1)/usr/bin/config.php
	$(INSTALL_DATA) ./root/usr/bin/new-device-access.php $(1)/usr/bin/new-device-access.php
	$(INSTALL_BIN) ./root/etc/init.dnew-device-access $(1)/etc/init.d/new-device-access

	$(INSTALL_DIR) $(1)/www/luci-static/resources/view/status
	$(INSTALL_DATA) ./root/www/luci-static/resources/view/status/config.js $(1)/www/luci-static/resources/view/status/config.js
	$(INSTALL_DATA) ./root/www/luci-static/resources/view/status/luci-access.js $(1)/www/luci-static/resources/view/status/luci-access.js
	$(INSTALL_DATA) ./root/www/luci-static/resources/view/status/scheduleaccess.js $(1)/www/luci-static/resources/view/status/scheduleaccess.js
	$(INSTALL_DATA) ./root/www/luci-static/resources/view/status/web-rated-devices.js $(1)/www/luci-static/resources/view/status/web-rated-devices.js
    $(INSTALL_DATA) ./root/etc/config/webguard $(1)/etc/config/webguard

	$(INSTALL_DIR) $(1)/usr/share/luci/menu.d $(1)/usr/share/rpcd/acl.d
	$(INSTALL_DATA) ./root/usr/share/luci/menu.d/luci-app-device-status.json $(1)/usr/share/luci/menu.d/luci-app-device-status.json
	$(INSTALL_DATA) ./root/usr/share/rpcd/acl.d/luci-app-device-status.json $(1)/usr/share/rpcd/acl.d/luci-app-device-status.json
	$(INSTALL_DATA) ./root/usr/share/luci/menu.d/luci-app-webguard.json $(1)/usr/share/luci/menu.d/luci-app-webguard.json
	$(INSTALL_DATA) ./root/usr/share/rpcd/acl.d/luci-app-webguard.json $(1)/usr/share/rpcd/acl.d/luci-app-webguard.json
	$(INSTALL_DATA) ./root/usr/share/rpcd/acl.d/luci-app-scheduleaccess.json $(1)/usr/share/rpcd/acl.d/luci-app-scheduleaccess.json

    $(INSTALL_DIR) $(1)/etc/webguard
	$(INSTALL_DATA) ./root/etc/webguard/web-rated-docker.json $(1)/etc/webguard/web-rated-docker.json
    $(INSTALL_DATA) ./root/etc/webguard/web-rated-device.json $(1)/etc/webguard/web-rated-device.json
	$(INSTALL_BIN) ./root/etc/webguard/99-WAN-DHCP-Static.sh $(1)/etc/webguard/99-WAN-DHCP-Static.sh
    $(INSTALL_DATA) ./root/etc/webguard $(1)/etc/webguard/firewall

	# Create the uci-defaults script to configure and activate WebGuard.
	$(INSTALL_DIR) $(1)/etc/uci-defaults
	$(INSTALL_BIN) ./root/etc/uci-defaults/30-luci-app-webguard $(1)/etc/uci-defaults/30-luci-app-webguard
endef

$(eval $(call BuildPackage,luci-app-webguard))
