'use strict';
/* sysauth: admin */

'require fs';
'require ui';

return L.view.extend({
    dockermgrJsonPath: '/usr/bin/web-rated-docker.json',
    scheduleJsonPath: '/usr/bin/web-rated-schedule.json',
    cronPath: '/etc/crontabs/root',
    cronServicePath: '/etc/init.d/cron',
    dockerStateScript: 'update-docker-state.php',
    dockerStateScriptPath: '/usr/bin/update-docker-state.php',
    phpPath: '/usr/bin/php8-cli',
    atCommandPath: '/usr/bin/at',
    atrmCommandPath: '/usr/bin/atrm',

    load: function() {
        var self = this;
        
        return fs.read(this.scheduleJsonPath)
            .catch(function() {
                return fs.write(self.scheduleJsonPath, '[]').then(function() {
                    return '[]';
                });
            })
            .then(function(scheduleContent) {
                var parsedSchedules;
                try {
                    parsedSchedules = JSON.parse(scheduleContent.trim());
                } catch(e) {
                    parsedSchedules = [];
                }

                return fs.read(self.dockermgrJsonPath)
                    .then(function(dockerContent) {
                        try {
                            return [JSON.parse(dockerContent.trim()), parsedSchedules];
                        } catch(e) {
                            return [[], parsedSchedules];
                        }
                    })
                    .catch(function() { 
                        return [[], parsedSchedules]; 
                    });
            });
    },

    syncCron: function(schedules) {
        var self = this;
        var cronDataArray = [];
        
        if (Array.isArray(schedules)) {
            schedules.forEach(function(item) {
                if (item && String(item.ScheduleSystem) === 'cron') {
                    var schedule = String(item.Schedule || '').trim();
                    var dockerName = String(item.Docker_name || '').trim();
                    var scheduleFunc = String(item.ScheduleFunction || '').trim();
                    if (schedule && dockerName && scheduleFunc) {
                        cronDataArray.push({
                            Schedule: schedule,
                            Docker_name: dockerName,
                            ScheduleFunction: scheduleFunc
                        });
                    }
                }
            });
        }

        return L.resolveDefault(fs.read(self.cronPath), '').then(function(content) {
            var existingLines = content ? content.split('\n').filter(function(line) { return line.trim().length > 0; }) : [];
            var filteredLines = existingLines.filter(function(line) {
                return line.indexOf(self.dockerStateScript) === -1;
            });

            // FIXED: Using absolute lexical self scoping instead of dynamic 'this' mappings inside promises
            var newCronLines = cronDataArray.map(function(item) {
                return item.Schedule + ' ' + self.phpPath + ' ' + self.dockerStateScriptPath + ' ' + item.Docker_name + ' ' + item.ScheduleFunction;
            });
            
            var finalContent = filteredLines.concat(newCronLines).join('\n').trim() + '\n';
            
            return fs.write(self.cronPath, finalContent).then(function() {
                return L.resolveDefault(fs.exec(self.cronServicePath, ['restart']), true);
            });
        });
    },

    render: function(data) {
        var self = this;
        var resolvedData = data || [];
        var dockermgr_entries = resolvedData[0] || [];
        var schedule_entries = resolvedData[1] || [];

        dockermgr_entries = (Array.isArray(dockermgr_entries) ? dockermgr_entries : []).map(function(entry) {
            if (!entry) return {};
            return {
                ID: String(entry.ID !== undefined ? entry.ID : ''),
                Name: String(entry.Name || entry.name || ''),
                Docker_Name: String(entry['Docker Name'] || entry.name || ''),
                Schedule: String(entry.Schedule || ''),
                Rateing: String(entry.Rateing || entry.Rating || '')
            };
        });

        schedule_entries = (Array.isArray(schedule_entries) ? schedule_entries : []).map(function(entry) {
            if (!entry) return {};
            return {
                ID: String(entry.ID !== undefined ? entry.ID : ''),
                ScheduleName: String(entry.ScheduleName || ''),
                ScheduleFriendly: String(entry.ScheduleFriendly || ''),
                ADClient_name: String(entry.ADClient_name || ''),
                ScheduleFunction: String(entry.ScheduleFunction || ''),
                Docker_name: String(entry.Docker_name || ''),
                Schedule: String(entry.Schedule || ''),
                ScheduleSystem: String(entry.ScheduleSystem || ''),
                ScheduleATID: String(entry.ScheduleATID || '')
            };
        });

        var viewTree = E('div', { 'class': 'cbi-map' }, [
            E('h2', {}, _('Create New Access Schedule')),
            E('div', { 'class': 'cbi-map-descr' }, _('Schedule device access based on access rating.'))
        ]);

        var form = E('div', { 'class': 'cbi-section' });

        var descRow = E('div', { 'class': 'cbi-value-field', 'style': 'display: flex; flex-direction: column; gap: 10px; margin-top: 10px; width: 100%;' }, [
            E('label', { 'class': 'cbi-value-title' }, _('Description')),
            E('div', { 'class': 'cbi-value-field' }, [ E('input', { 'type': 'text', 'id': 'schedule_name', 'class': 'cbi-input-text', 'required': 'required' }) ])
        ]);

        var selectRating = E('select', { 'id': 'schedule_rating', 'class': 'cbi-input-select', 'required': 'required' }, [
            E('option', { 'disabled': 'disabled', 'selected': 'selected' }, _('Choose Rating'))
        ]);
        
        dockermgr_entries.forEach(function(entry) {
            if (entry && (entry.Rateing === '1' || entry.Schedule === '1')) {
                selectRating.appendChild(E('option', { 
                    'value': entry.ID, 
                    'data-docker-name': entry.Docker_Name 
                }, entry.Name || entry.Docker_Name));
            }
        });

        var ratingRow = E('div', { 'class': 'cbi-value-field', 'style': 'display: flex; flex-direction: column; gap: 10px; margin-top: 10px; width: 100%;' }, [
            E('label', { 'class': 'cbi-value-title' }, _('Rating')),
            E('div', { 'class': 'cbi-value-field' }, [ selectRating ])
        ]);

        var accessRow = E('div', { 'class': 'cbi-value-field', 'style': 'display: flex; flex-direction: column; gap: 10px; margin-top: 10px; width: 100%;' }, [
            E('label', { 'class': 'cbi-value-title' }, _('Internet Access')),
            E('div', { 'class': 'cbi-value-field' }, [
                E('select', { 'id': 'schedule_access', 'class': 'cbi-input-select', 'required': 'required' }, [
                    E('option', { 'disabled': 'disabled', 'selected': 'selected' }, _('Choose Access')),
                    E('option', { 'value': 'stop' }, _('Off')),
                    E('option', { 'value': 'start' }, _('On'))
                ])
            ])
        ]);

        var typeSelect = E('select', { 'id': 'schedule_type', 'class': 'cbi-input-select' }, [
            E('option', { 'value': 'run_once' }, _('Run Once')),
            E('option', { 'value': 'daily' }, _('Daily')),
            E('option', { 'value': 'weekly' }, _('Weekly')),
            E('option', { 'value': 'monthly' }, _('Monthly'))
        ]);

        var typeRow = E('div', { 'class': 'cbi-value-field', 'style': 'display: flex; flex-direction: column; gap: 10px; margin-top: 10px; width: 100%;' }, [
            E('label', { 'class': 'cbi-value-title' }, _('Repeat')),
            E('div', { 'class': 'cbi-value-field' }, [ typeSelect ])
        ]);

        var optionsContainer = E('div', { 
            'id': 'schedule-options',
            'class': 'cbi-value-field', 
            'style': 'display: flex; flex-direction: column; gap: 10px; margin-top: 10px; width: 100%;' 
        });

        if (typeSelect) {
            var fieldWrapper = typeSelect.closest('.cbi-value-field');
            if (fieldWrapper) {
                fieldWrapper.appendChild(optionsContainer);
            }
        }

        typeSelect.addEventListener('change', function(ev) {
            optionsContainer.innerHTML = '';
            var mode = ev.target.value;
            
            function appendFieldPair(labelText, inputElement) {
                optionsContainer.appendChild(E('label', { 'class': 'cbi-value-title' }, labelText));
                optionsContainer.appendChild(E('div', { 'class': 'cbi-value-field', 'style': 'margin-bottom: 8px;' }, [ inputElement ]));
            }

            var inputStyle = '';

            if (mode === 'run_once') {
                var dtInput = E('input', { 'type': 'datetime-local', 'id': 'run_once_date', 'class': 'cbi-input-text', 'required': 'required', 'style': inputStyle });
                appendFieldPair(_('Date and Time'), dtInput);

            } else if (mode === 'daily') {
                var hourInput = E('input', { 'type': 'number', 'id': 'daily_hour', 'min': 0, 'max': 23, 'class': 'cbi-input-text', 'required': 'required', 'style': inputStyle });
                var minInput = E('input', { 'type': 'number', 'id': 'daily_minute', 'min': 0, 'max': 59, 'class': 'cbi-input-text', 'required': 'required', 'style': inputStyle });
                
                appendFieldPair(_('Hour (0-23)'), hourInput);
                appendFieldPair(_('Minute (0-59)'), minInput);

            } else if (mode === 'weekly') {
                var hourInput = E('input', { 'type': 'number', 'id': 'weekly_hour', 'min': 0, 'max': 23, 'class': 'cbi-input-text', 'required': 'required', 'style': inputStyle });
                var minInput = E('input', { 'type': 'number', 'id': 'weekly_minute', 'min': 0, 'max': 59, 'class': 'cbi-input-text', 'required': 'required', 'style': inputStyle });
                var daySelect = E('select', { 'id': 'weekly_day', 'class': 'cbi-input-select', 'style': inputStyle }, [
                    E('option', { 'value': '0' }, _('Sunday')),
                    E('option', { 'value': '1' }, _('Monday')),
                    E('option', { 'value': '2' }, _('Tuesday')),
                    E('option', { 'value': '3' }, _('Wednesday')),
                    E('option', { 'value': '4' }, _('Thursday')),
                    E('option', { 'value': '5' }, _('Friday')),
                    E('option', { 'value': '6' }, _('Saturday'))
                ]);

                appendFieldPair(_('Hour (0-23)'), hourInput);
                appendFieldPair(_('Minute (0-59)'), minInput);
                appendFieldPair(_('Day of Week'), daySelect);

            } else if (mode === 'monthly') {
                var hourInput = E('input', { 'type': 'number', 'id': 'monthly_hour', 'min': 0, 'max': 23, 'class': 'cbi-input-text', 'required': 'required', 'style': inputStyle });
                var minInput = E('input', { 'type': 'number', 'id': 'monthly_minute', 'min': 0, 'max': 59, 'class': 'cbi-input-text', 'required': 'required', 'style': inputStyle });
                var dayInput = E('input', { 'type': 'number', 'id': 'monthly_day', 'min': 1, 'max': 31, 'class': 'cbi-input-text', 'required': 'required', 'style': inputStyle });

                appendFieldPair(_('Hour (0-23)'), hourInput);
                appendFieldPair(_('Minute (0-59)'), minInput);
                appendFieldPair(_('Day of Month (1-31)'), dayInput);
            }
        });

        var initEvent = document.createEvent('HTMLEvents');
        initEvent.initEvent('change', true, true);
        typeSelect.dispatchEvent(initEvent);

        var submitBtn = E('button', {
            'class': 'cbi-button cbi-button-save',
            // FIXED: Bound 'self' so all 'this.path' structural definitions are preserved
            'click': L.bind(function(ev) {
                var nameEl = document.getElementById('schedule_name');
                var ratingEl = document.getElementById('schedule_rating');
                var accessEl = document.getElementById('schedule_access');
                
                if (!nameEl || !ratingEl || !accessEl || !nameEl.value || ratingEl.selectedIndex === 0 || accessEl.selectedIndex === 0) {
                    alert(_('Please fill out all registration parameters.'));
                    return;
                }

                var selectedRatingOption = ratingEl.options[ratingEl.selectedIndex];
                var mode = typeSelect.value;
                var scheduleString = '';
                var friendlyString = '';
                var sys = 'cron';
                var atTimestamp = '';

                if (mode === 'run_once') {
                    var dateEl = document.getElementById('run_once_date');
                    var dateVal = dateEl ? dateEl.value : null;
                    if (!dateVal) { return alert(_('Valid datetime assignment is required')); }
                    var d = new Date(dateVal);
                    
                    var pad = function(n) { return n < 10 ? '0'+n : n; };
                    var readableTime = pad(d.getHours()) + ':' + pad(d.getMinutes());
                    var readableDate = d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
                    
                    scheduleString = readableTime + ' ' + readableDate;
                    friendlyString = 'Run Once at ' + scheduleString;
                    sys = 'at';
                    
                    atTimestamp = '' + d.getFullYear() + pad(d.getMonth()+1) + pad(d.getDate()) + pad(d.getHours()) + pad(d.getMinutes());
                } else if (mode === 'daily') {
                    var h = document.getElementById('daily_hour').value;
                    var m = document.getElementById('daily_minute').value;
                    scheduleString = m + ' ' + h + ' * * *';
                    friendlyString = 'Run Daily at ' + h + ':' + m;
                } else if (mode === 'weekly') {
                    var h = document.getElementById('weekly_hour').value;
                    var m = document.getElementById('weekly_minute').value;
                    var day = document.getElementById('weekly_day').value;
                    scheduleString = m + ' ' + h + ' * * ' + day;
                    friendlyString = 'Run Weekly at ' + h + ':' + m + ' on weekday ' + day;
                } else if (mode === 'monthly') {
                    var h = document.getElementById('monthly_hour').value;
                    var m = document.getElementById('monthly_minute').value;
                    var dom = document.getElementById('monthly_day').value;
                    scheduleString = m + ' ' + h + ' ' + dom + ' * *';
                    friendlyString = 'Run Monthly at ' + h + ':' + m + ' on day ' + dom;
                }

                var newItem = {
                    ID: String(new Date().getTime()),
                    ScheduleName: String(nameEl.value || ''),
                    ScheduleFriendly: String(friendlyString || ''),
                    ADClient_name: String(selectedRatingOption.text || selectedRatingOption.textContent || ''),
                    ScheduleFunction: String(accessEl.value || ''),
                    Docker_name: String(selectedRatingOption.getAttribute('data-docker-name') || ''),
                    Schedule: String(scheduleString || ''),
                    ScheduleSystem: String(sys || 'cron'),
                    ScheduleATID: ""
                };

                var executeSavePipeline = function() {
                    schedule_entries.push(newItem);
                    return fs.write(self.scheduleJsonPath, JSON.stringify(schedule_entries, null, 4))
                        .then(function() { return self.syncCron(schedule_entries); })
                        .then(function() { location.reload(); });
                };

                if (sys === 'at') {
                    var targetCommandText = this.phpPath + ' ' + this.dockerStateScriptPath + ' ' + newItem.Docker_name + ' ' + newItem.ScheduleFunction + '\n';

                    fs.exec(this.atCommandPath, ['-t', String(atTimestamp)], targetCommandText).then(function(res) {
                        var resStderr = String((res && res.stderr) || '');
                        var jobMatch = resStderr.match ? resStderr.match(/job\s+(\d+)/) : null;
                        if (jobMatch && jobMatch[1]) {
                            newItem.ScheduleATID = String(jobMatch[1]);
                        }
                        executeSavePipeline();
                    }).catch(function(err) {
                        alert(_('Failed executing "at" command: ') + String(err.message || err));
                    });
                } else {
                    executeSavePipeline();
                }
            }, self)
        }, _('Save'));

        form.appendChild(descRow);
        form.appendChild(ratingRow);
        form.appendChild(accessRow);
        form.appendChild(typeRow);
        form.appendChild(optionsContainer);
        form.appendChild(E('div', { 'class': 'cbi-value-field', 'style': 'display: flex; flex-direction: column; justify-content: left; align-items: left; border-bottom: none; padding: 15px 0;' },[ E('label', { 'class': 'cbi-value-title' }), E('div', { 'class': 'cbi-value-field', 'style': 'margin: 0; padding: 0; display: flex; justify-content: left;' }, [ submitBtn ]) ]));
        viewTree.appendChild(form);

        var tableSection = E('div', { 'class': 'cbi-section' }, [ E('h3', {}, _('Access Schedules')) ]);
        var table = E('table', { 'class': 'table cbi-section-table' }, [
            E('tr', { 'class': 'tr cbi-section-table-titles' }, [
                E('th', { 'class': 'th' }, _('Schedule Name')),
                E('th', { 'class': 'th' }, _('Schedule Description')),
                E('th', { 'class': 'th' }, _('Rating')),
                E('th', { 'class': 'th' }, _('Start/Stop Access')),
                E('th', { 'class': 'th cbi-section-table-cell-action' }, _(''))
            ])
        ]);

        schedule_entries.forEach(function(entry) {
            if (!entry) return;
            var deleteBtn = E('button', {
                'class': 'cbi-button cbi-button-remove',
                // FIXED: Bound 'self' so 'this.atrmCommandPath' and file references remain mapped
                'click': L.bind(function(ev) {
                    if (!confirm(_('Are you sure you want to delete this schedule?'))) { return; }

                    var entryID = String(entry.ID || '');
                    var entryScheduleSystem = String(entry.ScheduleSystem || '');
                    var entryScheduleATID = String(entry.ScheduleATID || '');

                    var removePipeline = function() {
                        var updatedList = schedule_entries.filter(function(item) { return item && String(item.ID || '') !== entryID; });
                        return fs.write(self.scheduleJsonPath, JSON.stringify(updatedList, null, 4))
                            .then(function() { return self.syncCron(updatedList); })
                            .then(function() { location.reload(); });
                    };

                    if (entryScheduleSystem === 'at' && entryScheduleATID) {
                        fs.exec(this.atrmCommandPath, [entryScheduleATID]).then(removePipeline).catch(removePipeline);
                    } else {
                        removePipeline();
                    }
                }, self)
            }, _('Delete'));

            table.appendChild(E('tr', { 'class': 'tr' }, [
                E('td', { 'class': 'td' }, String(entry.ScheduleName || '')),
                E('td', { 'class': 'td' }, String(entry.ScheduleFriendly || '')),
                E('td', { 'class': 'td' }, String(entry.ADClient_name || '')),
                E('td', { 'class': 'td' }, String(entry.ScheduleFunction || '')),
                E('td', { 'class': 'td cbi-section-table-cell-action', 'style': 'text-align: right;' }, [ deleteBtn ])
            ]));
        });

        tableSection.appendChild(table);
        viewTree.appendChild(tableSection);
        return viewTree;
    },

    handleSaveApply: null,
    handleSave: null,
    handleReset: null
});
