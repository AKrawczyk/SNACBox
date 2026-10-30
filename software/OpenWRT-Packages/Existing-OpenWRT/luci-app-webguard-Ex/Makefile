include $(TOPDIR)/rules.mk

PKG_NAME:=luci-app-webguard-Ex
PKG_VERSION:=1.0.0
PKG_RELEASE:=1

PKG_SOURCE_PROTO:=git
PKG_SOURCE_URL:=https://github.com/AKrawczyk/SNACBox.git
PKG_SOURCE_VERSION:=main
PKG_SOURCE_SUBDIR:=$(PKG_NAME)-$(PKG_VERSION)

include $(INCLUDE_DIR)/package.mk

define Package/luci-app-webguard-Ex
  SECTION:=net
  CATEGORY:=Network
  TITLE:=Webguard network and DNS filtering components
  DEPENDS:=+docker +dockerd +php8 +php8-cli +php8-mod-curl +luci-mod-rpc +luci-ssl +at +jq +adguardhome
endef

define Package/luci-app-webguard-Ex/description
 Webguard daemon scripts, blacklist tools, and LuCI status views for existing OpenWRT configurations.
endef

define Build/Compile
	# Static scripts nothing to compile
endef

define Package/luci-app-webguard-Ex/conffiles
/etc/config/firewall
endef

define Package/luci-app-webguard-Ex/install

	# Copy the entire root directory structure to the target
	$(INSTALL_DIR) $(1)/
	$(CP) ./root/* $(1)/
	
	# Ensure the executable scripts have correct permissions
	$(INSTALL_BIN) ./root/usr/bin/docker-checker.sh $(1)/usr/bin/docker-checker.sh
	$(INSTALL_BIN) ./root/usr/bin/update-docker-state.sh $(1)/usr/bin/update-docker-state.sh
	$(INSTALL_BIN) ./root/etc/init.d/docker-checker $(1)/etc/init.d/docker-checker
	
	$(INSTALL_BIN) ./root/usr/bin/new-device-access.sh $(1)/usr/bin/new-device-access.sh
	$(INSTALL_BIN) ./root/etc/init.d/new-device-access $(1)/etc/init.d/new-device-access
	$(INSTALL_BIN) ./root/etc/adguardhome/update-adguardhome-clients.sh $(1)/etc/adguardhome/update-adguardhome-clients.sh
	
	$(INSTALL_BIN) ./root/etc/webguard/mod-adguardhome-settings.sh $(1)/etc/webguard/mod-adguardhome-settings.sh
	$(INSTALL_BIN) ./root/etc/webguard/new-adguardhome-settings.sh $(1)/etc/webguard/new-adguardhome-settings.sh
	
	# Create the uci-defaults script to configure and activate WebGuard.
	$(INSTALL_DIR) $(1)/etc/uci-defaults
	$(INSTALL_BIN) ./root/etc/uci-defaults/30-luci-app-webguard $(1)/etc/uci-defaults/30-luci-app-webguard
endef

$(eval $(call BuildPackage,luci-app-webguard-Ex))
