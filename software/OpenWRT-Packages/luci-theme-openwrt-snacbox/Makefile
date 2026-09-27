include $(TOPDIR)/rules.mk

PKG_NAME:=luci-theme-openwrt-snacbox
PKG_VERSION:=1.0.0
PKG_RELEASE:=1

PKG_MAINTAINER:=Aaron Krawczyk <aaron.krawczyk@gmail.com>
PKG_LICENSE:=Apache-2.0

include $(INCLUDE_DIR)/package.mk

define Package/luci-theme-openwrt-snacbox
  SECTION:=luci
  CATEGORY:=LuCI
  SUBMENU:=4. Themes
  TITLE:=Snacbox Theme for LuCI
  DEPENDS:=+luci-base +luci-theme-openwrt-2020
  PKGARCH:=all
endef

define Package/luci-theme-openwrt-snacbox/description
  A modified version of OpenWRT 2020 theme.
endef

define Build/Compile
	# Static theme assets, nothing to compile
endef

define Package/luci-theme-openwrt-snacbox/install
	# Create the base directory structure in the target root
	$(INSTALL_DIR) $(1)/
	
	# Copy all files from the local root folder into the IPK build target
	$(CP) ./root/* $(1)/
	
	# 2. Create the uci-defaults script to activate and register SnacBox Theme.
	$(INSTALL_DIR) $(1)/etc/uci-defaults
	$(INSTALL_BIN) ./root/etc/uci-defaults/30-luci-theme-snacbox $(1)/etc/uci-defaults/
endef

$(eval $(call BuildPackage,luci-theme-openwrt-snacbox))
