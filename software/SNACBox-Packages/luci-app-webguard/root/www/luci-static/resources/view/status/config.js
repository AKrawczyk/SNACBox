'use strict';
'require rpc';
'require form';
'require view';
'require uci';
'require fs';

return view.extend({
	load: function() {
		return Promise.all([
			uci.load('webguard'),
			uci.load('firewall')
		]);
	},
	render: function () {
		var s, o;
		var m = new form.Map('webguard', _('New Device Access Configuration'),
			_('The new-device-access service requires access to the OpenWRT firewall to block new devices.' +
			' Enter the username and password used to login to this device.')
		);

		s = m.section(form.TypedSection, 'webguard', _('System Authentication'));
		s.anonymous = true;

		o = s.option(form.Value, 'web_username', _('System Username'), _('Username'));
		o.placeholder = 'webguard';

		o = s.option(form.Value, 'web_password', _('System Password'), _('Password'));
		o.password = true;

		s = m.section(form.TypedSection, 'webguard', _('Restricted Internet Access'));                
		s.anonymous = true;

		// --- Single Master Firewall Toggle ---
		o = s.option(form.Flag, 'enable_all_firewall_rules', _('Enable'), 
			_('Click to enable or disable Restricted Internet Access.')
		);
		o.rmempty = false;

		// Check if both firewall rules are currently enabled
		o.cfgvalue = function(section_id) {
			var enabledCount = 0;
			['none-rated-any', 'blacklist-ip-rated', 'Drop-ping', 'lan', 'drop-webbound'].forEach(function(ruleName) {
				var sections = uci.sections('firewall', 'rule');
				for (var i = 0; i < sections.length; i++) {
					if ((sections[i].name === ruleName || sections[i]['.name'] === ruleName) && sections[i].enabled === '1') {
						enabledCount++;
					}
				}
			});
			return (enabledCount === 2) ? '1' : '0';
		};

		// Apply the toggle state to both firewall rules when saved
		o.write = function(section_id, formvalue) {
			uci.set('webguard', section_id, 'enable_all_firewall_rules', formvalue);
			
			['none-rated-any', 'blacklist-ip-rated', 'Drop-ping', 'lan', 'drop-webbound'].forEach(function(ruleName) {
				var sections = uci.sections('firewall', 'rule');
				for (var i = 0; i < sections.length; i++) {
					if (sections[i].name === ruleName || sections[i]['.name'] === ruleName) {
						uci.set('firewall', sections[i]['.name'], 'enabled', formvalue);
					}
				}
			});
			return uci.save();
		};

		s = m.section(form.TypedSection, 'webguard', _('WAN Reset'));                            
		s.anonymous = true;

		o = s.option(form.Button, 'wan_reset_btn', _('WAN Reset Action'), 
			_('Resets the WAN interface to DHCP and installs the static fallback script to /etc/uci-default/.')
		);
		o.inputtitle = _('Perform WAN Reset');
		o.inputstyle = 'btn cbi-button-action important';
		o.onclick = function(section_id) {
			if (!confirm(_('Are you sure you want to reset WAN to DHCP and install the startup script?')))
				return;

			return fs.exec('/bin/sh', ['-c', 
				'uci set network.wan.proto="dhcp" && ' +
				'uci delete network.wan.ipaddr 2>/dev/null; ' +
				'uci delete network.wan.netmask 2>/dev/null; ' +
				'uci delete network.wan.gateway 2>/dev/null; ' +
				'uci delete network.wan.dns 2>/dev/null; ' +
				'uci commit network && ' +
				'cp /etc/webguard/99-WAN-DHCP-Static.sh /etc/uci-default/99-WAN-DHCP-Static.sh && ' +
				'chmod +x /etc/uci-default/99-WAN-DHCP-Static.sh'
			]).then(function(res) {
				if (res.code === 0) {
					alert(_('WAN Reset completed successfully. Page will reload.'));
					location.reload();
				} else {
					alert(_('WAN Reset failed with code: ') + res.code);
				}
			}).catch(function(err) {
				alert(_('Error executing WAN Reset: ') + err);
			});
		};

		s = m.section(form.TypedSection, 'webguard', _('Factory Reset'));                            
		s.anonymous = true;

		o = s.option(form.Button, 'factory_reset_btn', _('Factory Reset Action'), 
			_('Backs up current configurations, restores originals from /etc/webguard/, and reboots the device.')
		);
		o.inputtitle = _('Perform Factory Reset');
		o.inputstyle = 'btn cbi-button-negative important';
		o.onclick = function(section_id) {
			if (!confirm(_('Are you sure you want to perform a factory reset? This will overwrite current configurations with originals and reboot the router.')))
				return;

			return fs.exec('/bin/sh', ['-c', 
				'cp /etc/config/firewall /etc/config/firewall.bak && ' +
				'cp /usr/bin/web-rated-docker.json /usr/bin/web-rated-docker.json.bak && ' +
				'cp /www/html/web-rated-device.json /www/html/web-rated-device.json.bak && ' +
				'cp /etc/webguard/firewall /etc/config/firewall && ' +
				'cp /etc/webguard/web-rated-docker.json /usr/bin/web-rated-docker.json && ' +
				'cp /etc/webguard/web-rated-device.json /www/html/web-rated-device.json && ' +
				'/sbin/reboot'
			]).then(function(res) {
				if (res.code === 0) {
					alert(_('Factory reset completed. The device is now rebooting...'));
				} else {
					alert(_('Factory reset failed with code: ') + res.code);
				}
			}).catch(function(err) {
				alert(_('Error executing factory reset: ') + err);
			});
		};

		return m.render();
	},
});
