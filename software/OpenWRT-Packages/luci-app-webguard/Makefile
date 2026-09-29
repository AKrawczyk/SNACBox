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
	$(INSTALL_BIN) ./root/docker-checker-daemon/docker-checker.sh $(1)/usr/bin/docker-checker.sh
	$(INSTALL_DATA) ./root/docker-checker-daemon/docker-checker.php $(1)/usr/bin/docker-checker.php
	$(INSTALL_DATA) ./root/docker-checker-daemon/web-rated-docker.json $(1)/usr/bin/web-rated-docker.json
	$(INSTALL_BIN) ./root/docker-checker-daemon/update-docker-state.sh $(1)/usr/bin/update-docker-state.sh
	$(INSTALL_DATA) ./root/docker-checker-daemon/update-docker-state.php $(1)/usr/bin/update-docker-state.php
	$(INSTALL_BIN) ./root/docker-checker-daemon/docker-checker $(1)/etc/init.d/docker-checker

	$(INSTALL_DATA) ./root/firewall/firewall $(1)/etc/config/firewall

	$(INSTALL_DATA) ./root/blacklist-ip/blacklist-ip.php $(1)/etc/blacklist-ip/blacklist-ip.php
	$(INSTALL_DATA) ./root/blacklist-ip/blacklist-ip-conf.php $(1)/etc/blacklist-ip/blacklist-ip-conf.php
	$(INSTALL_DATA) ./root/blacklist-ip/config.php $(1)/etc/blacklist-ip/config.php

	$(INSTALL_BIN) ./root/new-device-access-daemon/new-device-access.sh $(1)/usr/bin/new-device-access.sh
	$(INSTALL_DATA) ./root/new-device-access-daemon/config.php $(1)/usr/bin/config.php
	$(INSTALL_DATA) ./root/new-device-access-daemon/new-device-access.php $(1)/usr/bin/new-device-access.php
	$(INSTALL_BIN) ./root/new-device-access-daemon/new-device-access $(1)/etc/init.d/new-device-access

	$(INSTALL_DIR) $(1)/www/luci-static/resources/view/status
	$(INSTALL_DATA) ./root/web-rated/www/luci-static/resources/view/status/config.js $(1)/www/luci-static/resources/view/status/config.js
	$(INSTALL_DATA) ./root/web-rated/www/luci-static/resources/view/status/luci-access.js $(1)/www/luci-static/resources/view/status/luci-access.js
	$(INSTALL_DATA) ./root/web-rated/www/luci-static/resources/view/status/scheduleaccess.js $(1)/www/luci-static/resources/view/status/scheduleaccess.js
	$(INSTALL_DATA) ./root/web-rated/www/luci-static/resources/view/status/web-rated-devices.js $(1)/www/luci-static/resources/view/status/web-rated-devices.js

	$(INSTALL_DIR) $(1)/usr/share/luci/menu.d $(1)/usr/share/rpcd/acl.d
	$(INSTALL_DATA) ./root/web-rated/usr/share/luci/menu.d/luci-app-device-status.json $(1)/usr/share/luci/menu.d/luci-app-device-status.json
	$(INSTALL_DATA) ./root/web-rated/usr/share/rpcd/acl.d/luci-app-device-status.json $(1)/usr/share/rpcd/acl.d/luci-app-device-status.json
	$(INSTALL_DATA) ./root/web-rated/usr/share/rpcd/acl.d/luci-app-scheduleaccess.json $(1)/usr/share/rpcd/acl.d/luci-app-scheduleaccess.json

	# Create the uci-defaults script to configure and activate WebGuard.
	$(INSTALL_DIR) $(1)/etc/uci-defaults
	$(INSTALL_BIN) ./root/etc/uci-defaults/30-luci-app-webguard $(1)/etc/uci-defaults/30-luci-app-webguard
endef

$(eval $(call BuildPackage,luci-app-webguard))