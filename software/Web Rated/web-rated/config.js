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
		var m = new form.Map('webguard', _('Web Access Configuration'),
			_('This application requires the username and password that were configured when you set up OpenWRT, ' +
			'as the REST API for OpenWRT is password protected. The deviceaccess and new-device-access require access to OpenWRT API. The credentials supplied here will ' +
			'be stored unencrypted in /etc/config/webguard on your device.')
		);

		s = m.section(form.TypedSection, 'webguard', _('General settings'));
		s.anonymous = true;

		o = s.option(form.Value, 'web_username', _('Username for OpenWRT'), _('The username you configured when you set up OpenWRT'));
		o.placeholder = 'webguard';

		o = s.option(form.Value, 'web_password', _('Password for OpenWRT'), _('The password you configured when you set up OpenWRT'));
		o.password = true;

		return m.render();
	},
})
