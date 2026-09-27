'use strict';
'require rpc';
'require form';
'require view';
'require ui';

return view.extend({
	render: function () {
		var s, o;
		var m = new form.Map('adguardhome', _('ADGuardHome Plugin Configuration'),
			_('Setup the AdGuardHome login credentials and access for the AdGuardHome plugin.')
		);

		s = m.section(form.NamedSection, 'config', 'adguardhome', _('AdGuardHome Authentication'));

		o = s.option(form.Value, 'web_username', _('Username'));
		o.placeholder = 'adguard';

		o = s.option(form.Value, 'web_password', _('Password'));
		o.password = true; // Fixed: changed from p.password to o.password

		var ChangePassword = form.Button.extend({
			set_passwd: rpc.declare({
				object: 'luci.adguardhome',
				method: 'set_passwd',
				params: [ 'username', 'hash' ],
			}),
			onclick: function() {
				var username = document.getElementById("widget.cbid.adguardhome.config.web_username").value;
				var password = document.getElementById("widget.cbid.adguardhome.config.web_password").value;
				
				if (!username || !password) {
					alert(_('Username and password fields cannot be empty.'));
					return;
				}

				var hash = TwinBcrypt.hashSync(password);
				var self = this;

				// 1. Stage the text inputs natively inside the LuCI Map framework
				return m.save().then(function() {
					// 2. Fire the custom backend RPC to update adguardhome.yaml
					return self.set_passwd(username, hash);
				}).then(function() {
					// 3. Flush all changes down to /etc/config/adguardhome simultaneously
					return ui.changes.apply(true);
				});
			},
		});
		
		o = s.option(ChangePassword, 'change_password', _('Save & Apply'));
		o.inputstyle = 'apply';

		var BCryptInclude = form.DummyValue.extend({
			renderWidget: function(section_id, option_index, cfgvalue) {
				return E('script', { 'type':'text/javascript', 'src':'/luci-static/resources/view/twin-bcrypt.min.js' });
			}
		});
		o = s.option(BCryptInclude, 'misc');

		return m.render();
	},

	handleSaveApply: null,
    handleSave: null,
    handleReset: null
})