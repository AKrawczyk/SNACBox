'use strict';

// 1. Declare the system modules this utility file needs to do its job
'require fs';
'require uci';

// 2. Return a custom class extended from LuCI's base object framework
return L.Class.extend({
    
    /**
     * Reads and filters the Docker JSON configurations.
     */
    getDockerRatingDropdown: function(ratedDockerJson) {
        var jsonPath = ratedDockerJson; //'/usr/bin/web-rated-docker.json';

        return fs.read(jsonPath).then(function(content) {
            try {
                var data = content ? JSON.parse(content.trim()) : [];
                var filteredResults = data.filter(function(item) {
                    return item.Rateing == '1' || item.Rating == '1';
                });

                var fragment = document.createDocumentFragment();
                filteredResults.forEach(function(value) {
                    var option = E('option', { 'value': value.ID }, value.Name || '');
                    fragment.appendChild(option);
                });

                return fragment;
            } catch (e) {
                console.error("Failed to parse Docker JSON:", e);
                return "Error: " + e.message;
            }
        }).catch(function(err) {
            console.error("Failed to read Docker JSON file:", err);
            return "Error: " + err.message;
        });
    },

    /**
     * Parses the system dhcp leases file, replicating the provided token layout structure
     */
    parseDhcpLeases: function(dhcpLeases) {
        var leaseFilePath = dhcpLeases; //'/tmp/dhcp.leases';
        return fs.read(leaseFilePath).then(function(content) {
            var result = [];
            if (!content) return result;

            var lines = content.split('\n');
            var ln = 1;

            lines.forEach(function(line) {
                var cleanLine = line.trim();
                if (cleanLine === '') return;

                // Split line on contiguous whitespace boundaries
                var fields = cleanLine.split(/\s+/);
                if (fields.length >= 4) {
                    result.push({
                        index: ln++,
                        hostname: fields[3] === '*' ? 'Unknown' : fields[3],
                        ip: fields[2],
                        mac: fields[1]
                    });
                }
            });
            return result;
        }).catch(function(err) {
            console.error("Failed to parse system dhcp leases configuration path:", err);
            return [];
        });
    },

    /**
     * Cross-checks existing database rows against live leases, appending new nodes safely
     */
    /**
     * Cross-checks existing database rows against live leases.
     * Appends new nodes, updates active data, and sets missing devices to inactive (0).
     */
    syncLeasesToDatabase: function(existingDevices, liveLeases, ratedDevicesJson) {
        var self = this;
        var filePath = ratedDevicesJson; //'/www/html/web-rated-device.json';
        var modified = false;

        // 1. Create a lookup map of live DHCP lease MAC addresses for rapid checking
        var liveLeaseMap = {};
        liveLeases.forEach(function(lease) {
            if (lease.mac) {
                liveLeaseMap[lease.mac.toLowerCase()] = lease;
            }
        });

        // 2. Map existing database entries for tracking/updating updates
        var trackMap = {};
        existingDevices.forEach(function(d) {
            if (d.Device_MAC) {
                trackMap[d.Device_MAC.toLowerCase()] = d;
            }
        });

        // 3. Determine maximum ID threshold value to keep auto-incrementing valid
        var maxId = 0;
        existingDevices.forEach(function(d) {
            var currentId = parseInt(d.ID, 10);
            if (!isNaN(currentId) && currentId > maxId) {
                maxId = currentId;
            }
        });

        var firewallTasks = [];

        // --- PHASE A: MARK DROPPED DEVICES AS INACTIVE ---
        // If a device is in the JSON file as active, but not in /tmp/dhcp.leases, turn it off (0)
        existingDevices.forEach(function(device) {
            if (!device.Device_MAC) return;
            var dbMac = device.Device_MAC.toLowerCase();

            if (!liveLeaseMap[dbMac]) {
                // Device is missing from live DHCP leases file
                if (Number(device.Device_Active) !== 0) {
                    device.Device_Active = 0;
                    device.Device_ip = "";
                    modified = true;
                    console.log("Device dropped from DHCP: " + device.Device_MAC + " marked Inactive.");
                }
            }
        });

        // --- PHASE B: UPDATE EXISTING OR ADD NEW LIVE LEASES ---
        liveLeases.forEach(function(lease) {
            var cleanMac = lease.mac.toLowerCase();
            
            if (trackMap[cleanMac]) {
                var existing = trackMap[cleanMac];
                var detailsChanged = (existing.Device_ip !== lease.ip || existing.Device_hostname !== lease.hostname);
                var statusChanged = (Number(existing.Device_Active) !== 1);

                // If device properties changed or a previously inactive device reappeared, update it
                if (detailsChanged || statusChanged) {
                    existing.Device_ip = lease.ip;
                    existing.Device_hostname = lease.hostname;
                    existing.Device_Active = 1; // Explicitly ensure it returns to active status (1)
                    modified = true;
                }
            } else {
                // Device completely new - generate fresh object structure entry
                maxId++;
                var newDevice = {
                    ID: maxId,
                    Device_Description: "",
                    Device_hostname: lease.hostname,
                    Device_ip: lease.ip,
                    Device_MAC: lease.mac,
                    Device_Rating: "None",
                    Device_Active: 1
                };

                existingDevices.push(newDevice);
                trackMap[cleanMac] = newDevice;
                modified = true;

                // Fire corresponding firewall hook registrations
                firewallTasks.push(self.addMacToRule(lease.mac, "none-rated"));
            }
        });

        // --- PHASE C: SAVE BACK TO STORAGE ---
        if (modified) {
            return fs.write(filePath, JSON.stringify(existingDevices, null, 4))
                .then(L.bind(function() {
                    console.log("web-rated-device.json updated successfully.");
                    
                    if (firewallTasks.length > 0) {
                        return Promise.all(firewallTasks).then(function() { return true; });
                    }
                    return true;
                }, self))
                .catch(function(err) {
                    console.error("Failed to save synced device list back to disk storage file:", err);
                    return false;
                });
        }

        return Promise.resolve(true);
    },

    /**
     * Appends a target MAC address to the specified firewall rule list
     */
    addMacToRule: function(macAddress, ruleName) {
        return uci.load('firewall').then(function() {
            var trafficRules = uci.sections('firewall', 'rule') || [];
            var portForwards = uci.sections('firewall', 'redirect') || [];
            var combinedSections = trafficRules.concat(portForwards);
            var targetSections = [];
            
            var cleanMac = macAddress.toLowerCase();
            var searchStr = String(ruleName).toLowerCase();

            combinedSections.forEach(function(section) {
                if (section.name && section.name.toLowerCase().includes(searchStr)) {
                    targetSections.push({
                        id: section['.name'],
                        type: section['.type']
                    });
                }
            });

            if (targetSections.length === 0) {
                console.warn("Could not find any firewall configurations matching '" + ruleName + "' to insert the MAC node.");
                return false;
            }

            var updatesMade = false;

            targetSections.forEach(function(target) {
                var currentMacs = uci.get('firewall', target.id, 'src_mac');
                var macList = [];

                if (Array.isArray(currentMacs)) {
                    macList = currentMacs.map(function(m) { return m.toLowerCase(); });
                } else if (typeof currentMacs === 'string' && currentMacs.trim() !== '') {
                    macList = [currentMacs.toLowerCase()];
                }

                if (macList.indexOf(cleanMac) === -1) {
                    macList.push(cleanMac);
                    uci.set('firewall', target.id, 'src_mac', macList);
                    updatesMade = true;
                }
            });

            if (updatesMade) {
                return uci.save()
                    .then(function() { return true; })
                    .catch(function(err) { return false; });
            }
            return true; 
        });
    },

    /**
     * Deletes a target MAC address from the specified firewall rule list
     */
    removeMacFromRule: function(macAddress, ruleName) {
        return uci.load('firewall').then(function() {
            var trafficRules = uci.sections('firewall', 'rule') || [];
            var portForwards = uci.sections('firewall', 'redirect') || [];
            var combinedSections = trafficRules.concat(portForwards);
            var targetSections = [];

            var cleanMac = macAddress.toLowerCase();
            var searchStr = String(ruleName).toLowerCase();

            combinedSections.forEach(function(section) {
                if (section.name && section.name.toLowerCase().includes(searchStr)) {
                    targetSections.push(section['.name']);
                }
            });

            if (targetSections.length === 0) {
                return false;
            }

            var updatesMade = false;

            targetSections.forEach(function(sectionId) {
                var currentMacs = uci.get('firewall', sectionId, 'src_mac');
                var macList = [];

                if (Array.isArray(currentMacs)) {
                    macList = currentMacs.map(function(m) { return m.toLowerCase(); });
                } else if (typeof currentMacs === 'string' && currentMacs.trim() !== '') {
                    macList = [currentMacs.toLowerCase()];
                }

                var initialLength = macList.length;
                macList = macList.filter(function(m) { return m.toLowerCase() !== cleanMac; });
                
                if (macList.length < initialLength) {
                    // Found and removed at least one instance
                    if (macList.length === 0) {
                        uci.delete('firewall', sectionId, 'src_mac');
                    } else {
                        uci.set('firewall', sectionId, 'src_mac', macList);
                    }
                    updatesMade = true;
                }
            });

            if (updatesMade) {
                return uci.save()
                    .then(function() { return true; })
                    .catch(function(err) { return false; });
            }
            return true;
        });
    },

    /**
     * Updates a device's description and rating directly inside web-rated-device.json.
     */
    updateDeviceData: function(macAddress, newDescription, newRating, ratedDevicesJson) {
        var filePath = ratedDevicesJson; //'/www/html/web-rated-device.json';
        var cleanMac = macAddress.toLowerCase();

        return fs.read(filePath).then(function(content) {
            var devices = [];
            try { devices = content ? JSON.parse(content.trim()) : []; } catch(e) { return false; }
            if (!Array.isArray(devices)) return false;

            var recordFound = false;
            for (var i = 0; i < devices.length; i++) {
                if (devices[i].Device_MAC && devices[i].Device_MAC.toLowerCase() === cleanMac) {
                    devices[i].Device_Description = newDescription;
                    devices[i].Device_Rating = newRating;
                    recordFound = true;
                    break;
                }
            }

            if (!recordFound) return false;

            return fs.write(filePath, JSON.stringify(devices, null, 4)).then(function() { return true; });
        });
    },

    /**
     * Completely removes a device entry from web-rated-device.json based on MAC address.
     */
    deleteDeviceData: function(macAddress, ratedDevicesJson) {
        var filePath = ratedDevicesJson; 
        var cleanMac = macAddress.toLowerCase();

        return fs.read(filePath).then(function(content) {
            var devices = [];
            try { devices = content ? JSON.parse(content.trim()) : []; } catch(e) { return false; }
            if (!Array.isArray(devices)) return false;

            var initialLength = devices.length;
            var updatedDevices = devices.filter(function(device) {
                // FIX: Ensure the JSON property itself is normalized to lowercase before matching
                return !device.Device_MAC || device.Device_MAC.toLowerCase() !== cleanMac;
            });

            if (updatedDevices.length === initialLength) return true;

            return fs.write(filePath, JSON.stringify(updatedDevices, null, 4)).then(function() { return true; });
        });
    }
});