'use strict';
'require rpc';
'require form';
'require view';
'require uci';
'require fs';

return view.extend({
	load: function() {
		return Promise.all([
			uci.load('firewall'),
			uci.load('network'),
                        uci.load('webguard')
		]);
	},
	render: function () {
		var s, o;
		var m = new form.Map('webguard', _('Web Access Configuration'),
			_('This page allows you to configure the WebGuard system settings, including new device access restriction, WAN setup, and factory reset options. Please proceed with caution when making changes.')
		);

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
			var sections = uci.sections('firewall', 'rule').concat(uci.sections('firewall', 'redirect'));
			['none-rated-any', 'blacklist-ip-rated', 'Drop-ping', 'lan', 'drop-webbound'].forEach(function(ruleName) {
				for (var i = 0; i < sections.length; i++) {
					if ((sections[i].name === ruleName || sections[i]['.name'] === ruleName) && sections[i].enabled === '1') {
						enabledCount++;
					}
				}
			});
			return (enabledCount === 5) ? '1' : '0';
		};

		// Apply the toggle state to both firewall rules when saved
		o.write = function(section_id, formvalue) {
			var sections = uci.sections('firewall', 'rule').concat(uci.sections('firewall', 'redirect'));
			['none-rated-any', 'blacklist-ip-rated', 'Drop-ping', 'lan', 'drop-webbound'].forEach(function(ruleName) {
				for (var i = 0; i < sections.length; i++) {
					if (sections[i].name === ruleName || sections[i]['.name'] === ruleName) {
						uci.set('firewall', sections[i]['.name'], 'enabled', formvalue);
					}
				}
			});
			return uci.save();
		};

		s = m.section(form.TypedSection, 'webguard', _('WAN Setup'));                            
		s.anonymous = true;

		if (uci.get('network', 'wan', 'proto') !== 'dhcp')
		{
			o = s.option(form.Button, 'wan_reset_btn', _('WAN Reset Action'), 
				_('Resets the WAN interface to DHCP and installs the static fallback script to /etc/uci-default/.')
			);
			o.inputtitle = _('Perform WAN Reset');
			o.inputstyle = 'btn cbi-button-action important';
			o.onclick = function(section_id) {
				if (!confirm(_('Are you sure you want to reset WAN to DHCP and install the startup script?')))
					return;

				return fs.exec('/usr/bin/webguard-wan-reset').then(function(res) {
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
		}
		else
		{
			o = s.option(form.Button, 'wan_reset_btn', _('WAN Static Action'), 
				_('Sets the WAN interface to Static IP using the DHCP IP address.')
			);
			o.inputtitle = _('Perform WAN Static IP Setup');
			o.inputstyle = 'btn cbi-button-action important';
			o.onclick = function(section_id) {
				if (!confirm(_('Are you sure you want to set the WAN to Static IP?')))
					return;

				return fs.exec('/etc/webguard/99-WAN-DHCP-Static.sh').then(function(res) {
					if (res.code === 0) {
						alert(_('WAN Static IP setup completed successfully. Page will reload.'));
						location.reload();
					} else {
						alert(_('WAN Static IP setup failed with code: ') + res.code);
					}
				}).catch(function(err) {
					alert(_('Error executing WAN Static IP setup: ') + err);
				});
			};
		}

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

			return fs.exec('/usr/bin/webguard-factory-reset').then(function(res) {
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
