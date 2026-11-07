include $(TOPDIR)/rules.mk

PKG_NAME:=luci-app-snacbox
PKG_VERSION:=1.0.0
PKG_RELEASE:=1

PKG_MAINTAINER:=Aaron Krawczyk <aaron.krawczyk@gmail.com>
PKG_LICENSE:=GNU General Public License v3.0

include $(INCLUDE_DIR)/package.mk

define Package/luci-app-snacbox
  SECTION:=luci
  CATEGORY:=LuCI
  SUBMENU:=3. Applications
  TITLE:=LuCI support for AdGuardHome and Webguard
  DEPENDS:=+luci-base +luci-theme-openwrt-2020 +adguardhome +docker +dockerd +php8 +php8-cli +php8-mod-curl +luci-mod-rpc +luci-ssl +at +jq
  PKGARCH:=all
endef

define Package/luci-app-snacbox/description
  This package provides a LuCI management interface for AdGuardHome and Webguard.
endef

define Build/Compile
	# Nothing to compile conatins static files and scripts
endef

define Package/luci-app-snacbox/conffiles
/etc/config/firewall
endef

define Package/luci-app-snacbox/install
	# Copy the entire root directory structure to the target
	$(INSTALL_DIR) $(1)/
	$(CP) ./root/* $(1)/
	
	# Ensure the executable RPCD script has correct permissions
	$(INSTALL_BIN) ./root/usr/libexec/rpcd/luci.adguardhome $(1)/usr/libexec/rpcd/luci.adguardhome

	# Ensure the webguard executable scripts have correct permissions
	$(INSTALL_BIN) ./root/usr/bin/docker-checker.sh $(1)/usr/bin/docker-checker.sh
	$(INSTALL_BIN) ./root/usr/bin/update-docker-state.sh $(1)/usr/bin/update-docker-state.sh
	$(INSTALL_BIN) ./root/etc/init.d/docker-checker $(1)/etc/init.d/docker-checker
	
	$(INSTALL_BIN) ./root/usr/bin/new-device-access.sh $(1)/usr/bin/new-device-access.sh
	$(INSTALL_BIN) ./root/etc/init.d/new-device-access $(1)/etc/init.d/new-device-access
	$(INSTALL_BIN) ./root/usr/bin/webguard-wan-reset $(1)/usr/bin/webguard-wan-reset
	$(INSTALL_BIN) ./root/usr/bin/webguard-factory-reset $(1)/usr/bin/webguard-factory-reset

	$(INSTALL_BIN) ./root/etc/webguard/99-WAN-DHCP-Static.sh $(1)/etc/webguard/99-WAN-DHCP-Static.sh
	
	# Create the uci-defaults script to activate SNACBox's Theme, AdGuardHome and Webguard components.
	$(INSTALL_DIR) $(1)/etc/uci-defaults
	$(INSTALL_BIN) ./root/etc/uci-defaults/30-luci-app-snacbox $(1)/etc/uci-defaults/
endef

$(eval $(call BuildPackage,luci-app-snacbox))