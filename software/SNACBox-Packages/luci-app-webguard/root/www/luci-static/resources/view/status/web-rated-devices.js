'use strict';
/* sysauth: admin */

'require fs';
'require ui';
'require view.status.luci-access as access';

return L.view.extend({
    _searchFilter: '',
    _sortColumn: 'Device_ip',
    _sortDirection: 'asc',
    _activeDevicesData: [],
    _masterDropdown: null,
    _ratedDevicesJson: '/www/html/web-rated-device.json',
    _ratedDockerJson: '/usr/bin/web-rated-docker.json',
    _dhcpLeasesFile: '/tmp/dhcp.leases',

    // Resolve structural sync criteria across our files before render maps invoke
    load: function() {
        return Promise.all([
            fs.read(this._ratedDevicesJson).catch(function(err) {
                console.error("Failed to read web-rated-device.json:", err);
                return null;
            }),
            access.parseDhcpLeases(this._dhcpLeasesFile) // Task 2: Call the newly written parser method simultaneously
        ]).then(L.bind(function(results) {
            var content = results[0];
            var liveLeases = results[1] || [];
            var devicesData = [];

            if (content) {
                try { devicesData = JSON.parse(content.trim()); } catch (e) {}
            }

            // Sync lease array indices back into your core JSON document array references
            return access.syncLeasesToDatabase(devicesData, liveLeases, this._ratedDevicesJson).then(function() {
                // Return the updated array to serve as our rendering source
                return devicesData;
            });
        }, this));
    },

    renderActiveTableRows: function(tableBodyElement) {
        tableBodyElement.innerHTML = '';

        var filteredData = this._activeDevicesData.filter(L.bind(function(device) {
            var ipStr = String(device.Device_ip || '').toLowerCase();
            return ipStr.includes(this._searchFilter.toLowerCase());
        }, this));

        var col = this._sortColumn;
        var dir = this._sortDirection === 'asc' ? 1 : -1;

        filteredData.sort(function(a, b) {
            var valA = String(a[col] || '').toLowerCase();
            var valB = String(b[col] || '').toLowerCase();
            return valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' }) * dir;
        });

        if (filteredData.length === 0) {
            tableBodyElement.appendChild(E('tr', {}, [
                E('td', { 'colspan': 6, 'style': 'text-align: center; color: #888; font-style: italic;' }, _('No active devices found.'))
            ]));
            return;
        }

        filteredData.forEach(L.bind(function(device) {
            tableBodyElement.appendChild(E('tr', { 'class': 'tr cbi-section-table-row' }, [
                E('td', { 'class': 'td cbi-section-table-cell' }, device.Device_Description || E('em', {}, _('None'))),
                E('td', { 'class': 'td cbi-section-table-cell' }, device.Device_hostname || ''),
                E('td', { 'class': 'td cbi-section-table-cell' }, device.Device_ip || E('em', {}, _('None'))),
                E('td', { 'class': 'td cbi-section-table-cell' }, '%s'.format(device.Device_MAC)),
                E('td', { 'class': 'td cbi-section-table-cell' }, device.Device_Rating || E('em', {}, _('Unassigned'))),
                E('td', { 'class': 'td cbi-section-table-cell', 'style': 'text-align: right;'}, [
                    E('button', {
                        'class': 'cbi-button cbi-button-action',
                        'click': L.bind(this.renderManageModal, this, device, this._masterDropdown)
                    }, _('Manage'))
                ])
            ]));
        }, this));
    },

    renderManageModal: function(device, dropdownTemplate) {
        var modalDropdown = dropdownTemplate.cloneNode(true);
        
        if (device.Device_Rating) {
            var targetRating = String(device.Device_Rating).trim().toLowerCase();
            var matched = false;

            for (var i = 0; i < modalDropdown.options.length; i++) {
                var opt = modalDropdown.options[i];
                if (opt.value.toLowerCase() === targetRating || opt.text.toLowerCase() === targetRating) {
                    modalDropdown.selectedIndex = i;
                    opt.selected = true;
                    matched = true;
                    break;
                }
            }

            if (!matched && targetRating !== '') {
                var fallbackOption = E('option', { 'value': device.Device_Rating, 'selected': 'selected' }, device.Device_Rating);
                modalDropdown.appendChild(fallbackOption);
                modalDropdown.value = device.Device_Rating;
            }
        } else {
            modalDropdown.value = '';
        }

        var descriptionInput = E('input', {
            'type': 'text',
            'class': 'cbi-input-text',
            'style': 'width: 100%;',
            'value': device.Device_Description || ''
        });

        var modalBody = E('div', { 'class': 'cbi-map' }, [
            E('p', {}, _('Adjust decsription or access for: <strong>%s (IP: %s | MAC: %s)</strong>').format(device.Device_hostname || 'Unknown', device.Device_ip || 'Inactive', device.Device_MAC)),
            E('div', { 'class': 'cbi-section' }, [
                E('div', { 'class': 'cbi-value' }, [
                    E('label', { 'class': 'cbi-value-title' }, _('Device Description')),
                    E('div', { 'class': 'cbi-value-field' }, descriptionInput)
                ]),
                E('div', { 'class': 'cbi-value' }, [
                    E('label', { 'class': 'cbi-value-title' }, _('Device Access Profile')),
                    E('div', { 'class': 'cbi-value-field' }, modalDropdown)
                ])
            ])
        ]);

        ui.showModal(_('Manage Device Access'), [
            modalBody,
            E('div', { 'class': 'right' }, [
                E('button', { 'class': 'cbi-button cbi-button-reset', 'click': ui.hideModal }, _('Cancel')),
                ' ',
                E('button', {
                    'class': 'cbi-button cbi-button-save',
                    'click': L.bind(function() {
                        var selectedRating = modalDropdown.options[modalDropdown.selectedIndex].text;
                        var updatedDescription = descriptionInput.value.trim();

                        if (!selectedRating) {
                            ui.addNotification(null, E('p', {}, _('Please select a valid access profile.')), 'danger');
                            return;
                        }

                        // 1. Show the standard initial "Saving settings..." spinner
                        ui.showModal(_('Saving settings...'), [ E('span', { 'class': 'spinning' }, _('Updating firewall and records...')) ]);

                        Promise.all([
                            access.updateDeviceData(device.Device_MAC, updatedDescription, selectedRating, this._ratedDevicesJson),
                            access.removeMacFromRule(device.Device_MAC, "rated"),
                            access.addMacToRule(device.Device_MAC, selectedRating)
                        ]).then(L.bind(function(results) {
                            
                            if (results[0] && results[1]) {
                                // 2. Clear out the initial text overlay modal
                                ui.hideModal();

                                // 3. triggers the official 90-second rollback countdown screen, and handles the window reload automatically.
                                ui.changes.apply(true);

                            } else {
                                ui.hideModal();
                                ui.addNotification(null, E('p', {}, _('Error applying changes.')), 'danger');
                            }
                        }, this)).catch(function(err) {
                            ui.hideModal();
                            ui.addNotification(null, E('p', {}, _('An unexpected network error occurred.')), 'danger');
                        });
                    }, this)
                }, _('Save and Apply'))
            ])
        ]);
    },

    render: function(devicesData) {
        // devicesData is now safely pre-populated and synced from our load promise sequence loop!
        var container = E('div', { 'class': 'cbi-map' }, [
            E('h2', {}, _('Devices'))
        ]);

        if (!devicesData || !Array.isArray(devicesData) || devicesData.length === 0) {
            container.appendChild(E('p', { 'style': 'color: red;' }, _('No devices found.')));
            return container;
        }

        this._activeDevicesData = devicesData.filter(function(d) { return Number(d.Device_Active) === 1; });
        var inactiveDevices = devicesData.filter(function(d) { return Number(d.Device_Active) === 0; });

        this._masterDropdown = E('select', { 'name': 'docker_ratings', 'class': 'cbi-input-select' }, [
            E('option', { 'value': '' }, _('Choose Rating'))
        ]);

        var searchInput = E('input', {
            'type': 'text',
            'class': 'cbi-input-text',
            'placeholder': _('Search by IP address...'),
            'style': 'max-width: 320px; margin-bottom: 10px; display: block;',
            'value': this._searchFilter
        });

        var activeTableBody = E('tbody');
        
        var setSort = L.bind(function(columnName) {
            if (this._sortColumn === columnName) {
                this._sortDirection = this._sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this._sortColumn = columnName;
                this._sortDirection = 'asc';
            }
            this.renderActiveTableRows(activeTableBody);
        }, this);

        var activeTable = E('table', { 'class': 'table cbi-section-table' }, [
            E('thead', {}, [
                E('tr', { 'class': 'tr cbi-section-table-titles anonymous' }, [
                    E('th', { 'class': 'th cbi-section-table-cell', 'style': 'cursor:pointer; width:15%', 'click': function() { setSort('Device_Description'); } }, [ _('Description'), ' ↕' ]),
                    E('th', { 'class': 'th cbi-section-table-cell', 'style': 'cursor:pointer; width:15%', 'click': function() { setSort('Device_hostname'); } }, [ _('Name'), ' ↕' ]),
                    E('th', { 'class': 'th cbi-section-table-cell', 'style': 'cursor:pointer; width:15%', 'click': function() { setSort('Device_ip'); } }, [ _('IP Address'), ' ↕' ]),
                    E('th', { 'class': 'th cbi-section-table-cell', 'style': 'cursor:pointer; width:25%', 'click': function() { setSort('Device_MAC'); } }, [ _('ID/MAC'), ' ↕' ]),
                    E('th', { 'class': 'th cbi-section-table-cell', 'style': 'cursor:pointer; width:15%', 'click': function() { setSort('Device_Rating'); } }, [ _('Access'), ' ↕' ]),
                    E('th', { 'class': 'th cbi-section-table-cell', 'style': 'width:15%' }, _(''))
                ])
            ]),
            activeTableBody
        ]);

        searchInput.addEventListener('keyup', L.bind(function(ev) {
            this._searchFilter = ev.target.value;
            this.renderActiveTableRows(activeTableBody);
        }, this));

        var inactiveTable = E('table', { 'class': 'table cbi-section-table' }, [
            E('tr', { 'class': 'tr cbi-section-table-titles anonymous' }, [
                E('th', { 'class': 'th cbi-section-table-cell', 'style': 'width:15%' }, _('Description')),
                E('th', { 'class': 'th cbi-section-table-cell', 'style': 'width:15%' }, _('Name')),
                E('th', { 'class': 'th cbi-section-table-cell', 'style': 'width:25%' }, _('ID/MAC')),
                E('th', { 'class': 'th cbi-section-table-cell', 'style': 'width:15%' }, _('Access')),
                E('th', { 'class': 'th cbi-section-table-cell', 'style': 'width:15%' }, _(''))
            ])
        ]);

        return access.getDockerRatingDropdown(this._ratedDockerJson).then(L.bind(function(optionsOrError) {
            if (typeof optionsOrError === 'string') {
                this._masterDropdown.appendChild(E('option', { 'disabled': 'disabled' }, optionsOrError));
            } else {
                this._masterDropdown.appendChild(optionsOrError);
            }

            this.renderActiveTableRows(activeTableBody);

            inactiveDevices.forEach(L.bind(function(device) {
                inactiveTable.appendChild(E('tr', { 'class': 'tr cbi-section-table-row' }, [
                    E('td', { 'class': 'td cbi-section-table-cell' }, device.Device_Description || E('em', {}, _('None'))),
                    E('td', { 'class': 'td cbi-section-table-cell' }, device.Device_hostname || ''),
                    E('td', { 'class': 'td cbi-section-table-cell' }, '%s'.format(device.Device_MAC)),
                    E('td', { 'class': 'td cbi-section-table-cell' }, device.Device_Rating || E('em', {}, _('Unassigned'))),
                    E('td', { 'class': 'td cbi-section-table-cell', 'style': 'text-align: right;' }, [
                        E('button', {
                            'class': 'cbi-button cbi-button-action',
                            'click': L.bind(this.renderManageModal, this, device, this._masterDropdown)
                        }, _('Manage')),
                        E('button', {
                            'class': 'cbi-button cbi-button-remove',
                            'click': L.bind(function(targetDevice, ev) {
                                if (confirm(_('Do you really want to delete the following device: %s (%s)?').format(targetDevice.Device_hostname, targetDevice.Device_MAC))) {
                                    
                                    // 1. Show the initial loading modal overlay
                                    ui.showModal(_('Deleting...'), [ E('span', { 'class': 'spinning' }, _('Removing device and staging firewall adjustments...')) ]);

                                    Promise.all([
                                        access.removeMacFromRule(targetDevice.Device_MAC, "rated"),
                                        access.deleteDeviceData(targetDevice.Device_MAC, this._ratedDevicesJson)
                                    ]).then(L.bind(function(results) {
                                        
                                        // 2. Clear out the initial text overlay modal
                                        ui.hideModal();

                                        if (results[0] && results[1]) {
                                            // 3. This launches the official 90-second countdown screen for deletion changes too.
                                            ui.changes.apply(true);
                                        } else {
                                            ui.addNotification(null, E('p', {}, _('An error occurred during deletion. Inspect system logs.')), 'danger');
                                        }

                                    }, this)).catch(function(err) {
                                        console.error("Deletion promise failed:", err);
                                        ui.hideModal();
                                        ui.addNotification(null, E('p', {}, _('An unexpected network error occurred.')), 'danger');
                                    });
                                }
                            }, this, device)
                        }, _('Delete'))
                    ])
                ]));
            }, this));

            container.appendChild(E('h3', { 'style': 'color: #008000; margin-top:20px;' }, _('Active Devices')));
            container.appendChild(E('div', { 'style': 'margin-bottom: 10px; text-align: left;' }, [
                E('span', { 'style': 'margin-right: 5px; font-weight: bold; text-align: left;' }, _('Filter IP:')),
                searchInput
            ]));
            container.appendChild(activeTable);
            
            container.appendChild(E('br'));
            container.appendChild(E('h3', { 'style': 'color: #800000;' }, _('Inactive Devices')));
            container.appendChild(inactiveTable);

            return container;
        }, this));
    },
    
    handleSaveApply: null,
    handleSave: null,
    handleReset: null
});