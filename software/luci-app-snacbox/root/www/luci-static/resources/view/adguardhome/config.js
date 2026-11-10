'use strict';
'require rpc';
'require form';
'require view';
'require ui';

return view.extend({
	render: function () {
		var s, o;
		var m = new form.Map('adguardhome', _('ADGuardHome Plugin Configuration'),
			_('Enables and configures the AdGuardHome plugin credentials.')
		);

		function generatePasswords() {
			const minLength = 3;
			const maxLength = 4;
			const numWords = 3;
			const numPasswords = 1;
			const specialChars = "!%^*()[]{}_-=?#;:<>.|~";
			const dictionaryPath = L.resource('view/google-10000-english.txt');

			return fetch(dictionaryPath)
				.then(function(response) {
					if (!response.ok) {
						throw new Error('Failed to load local dictionary');
					}
					return response.text();
				})
				.then(function(data) {
					const words = data
						.split(/\r?\n/)
						.map(function(word) { return word.trim(); })
						.filter(function(word) {
							return word && word.length >= minLength && word.length <= maxLength && /^[a-z]+$/.test(word);
						});

					let generated = '';
					for (let p = 0; p < numPasswords; p++) {
						let password = '';
						for (let w = 0; w < numWords; w++) {
							const randomIndex = Math.floor(Math.random() * words.length);
							const word = words[randomIndex] || 'alpha';
							const capitalizedWord = word.charAt(0).toUpperCase() + word.slice(1);
							password += capitalizedWord;
							const randomNumber = Math.floor(Math.random() * 10);
							if (w < numWords - 1) {
								password += randomNumber + specialChars.charAt(Math.floor(Math.random() * specialChars.length));
							} else {
								password += randomNumber;
							}
						}
						generated = password;
					}
					return generated;
				})
				.catch(function(error) {
					console.error('Password generation failed:', error);
					throw error;
				});
		}

		s = m.section(form.NamedSection, 'config', 'adguardhome', _('AdGuardHome Authentication'));

		o = s.option(form.Value, 'web_username', _('Username'));
		o.placeholder = 'root';
		o.readonly = true;
		o.cfgvalue = function() { return 'root'; };
		o.write = function(section_id, value) {
			return 'root';
		};

		o = s.option(form.Value, 'web_password', _('Password'));
		o.password = true;
		o.readonly = true;
		o.placeholder = 'Click "Enable Plugin"';
		o.cfgvalue = function() { return ''; };
		o.write = function(section_id, value) {
			return value;
		};

		var ChangePassword = form.Button.extend({
			set_passwd: rpc.declare({
				object: 'luci.adguardhome',
				method: 'set_passwd',
				params: [ 'username', 'hash' ],
			}),
			onclick: function() {
				var usernameField = document.getElementById("widget.cbid.adguardhome.config.web_username");
				var passwordField = document.getElementById("widget.cbid.adguardhome.config.web_password");
				var username = 'root';
				var self = this;

				return generatePasswords().then(function(password) {
					if (usernameField) {
						usernameField.value = username;
						usernameField.readOnly = true;
					}
					if (passwordField) {
						passwordField.value = password;
						passwordField.readOnly = true;
					}

					if (!username || !password) {
						alert(_('Username and password fields cannot be empty.'));
						return;
					}

					var hash = TwinBcrypt.hashSync(password);
					return m.save().then(function() {
						return self.set_passwd(username, hash);
					}).then(function() {
						return ui.changes.apply(true);
					});
				});
			},
		});
		
		o = s.option(ChangePassword, 'change_password', _('Enable Plugin'));
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