include $(TOPDIR)/rules.mk

PKG_NAME:=luci-app-adguardhome-plugin
PKG_VERSION:=1.0.0
PKG_RELEASE:=1

PKG_MAINTAINER:=Aaron Krawczyk <aaron.krawczyk@gmail.com>
PKG_LICENSE:=Apache-2.0

include $(INCLUDE_DIR)/package.mk

define Package/luci-app-adguardhome-plugin
  SECTION:=luci
  CATEGORY:=LuCI
  SUBMENU:=3. Applications
  TITLE:=LuCI support for AdGuardHome
  DEPENDS:=+adguardhome
  PKGARCH:=all
endef

define Package/luci-app-adguardhome-plugin/description
  This package provides a LuCI configuration interface for AdGuardHome.
endef

define Build/Compile
	# Nothing to compile for static JS/JSON files
endef

define Package/luci-app-adguardhome-plugin/install
	# Copy the entire root directory structure to the target
	$(INSTALL_DIR) $(1)/
	$(CP) ./root/* $(1)/
	
	# Ensure the executable RPCD script has correct permissions
	$(INSTALL_BIN) ./root/usr/libexec/rpcd/luci.adguardhome $(1)/usr/libexec/rpcd/luci.adguardhome
	
	# 2. Create the uci-defaults script to activate AdGuardHome as default DNS server.
	$(INSTALL_DIR) $(1)/etc/uci-defaults
	$(INSTALL_BIN) ./root/etc/uci-defaults/30-luci-app-adguardhome-plugin $(1)/etc/uci-defaults/
endef

$(eval $(call BuildPackage,luci-app-adguardhome-plugin))
