'use strict';
'require rpc';
'require form';
'require view';

return view.extend({
	render: function () {
		// A basic configuration form; the deviceaccess script and new-device-access daemon that
		// powers the other UI pages needs a username and password to
		// communicate with the OpenWRT REST API.
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

		return m.render();
	},
})
