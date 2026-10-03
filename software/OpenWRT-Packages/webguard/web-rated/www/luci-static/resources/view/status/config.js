'use strict';
'require rpc';
'require form';
'require view';
'require uci';

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

		s = m.section(form.TypedSection, 'webguard', _('Factory Reset'));                           
                s.anonymous = true;

		return m.render();
	},
});
