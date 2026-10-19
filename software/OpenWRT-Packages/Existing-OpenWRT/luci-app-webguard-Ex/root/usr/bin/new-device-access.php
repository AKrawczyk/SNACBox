<?php
// Include config file
include __DIR__ .  "/config.php";

NewDeviceAccess();

function NewDeviceAccess()
{
    // Define variables and initialize with empty values
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL ^ E_NOTICE);
    $Status = "";
    $devicejsonPath = '/www/html/web-rated-device.json';

    // Executing curl OpenWRT Login

    $username = get_webguard_option('web_username');
    $password = get_webguard_option('web_password');

    if (empty($username) || empty($password)) 
    {
        // Output HTML error block and exit
        echo <<<HTML
    <div class="cbi-map" id="map">
        <div class="cbi-section">
            <div class="left">
                <h3>Device Access Status - Error</h3>
                <div class="error">Authentication error: missing username or password in config.</div>
                <div class="info">Please open the Configuration section, and provide the credentials.</div>
            </div>
        </div>
    </div>
    HTML;
        exit;
    }
    
    $OWRT_hostname = "127.0.0.1";
    $OWRT_cookie_path = LoginOpenWRTAPI($OWRT_hostname, $username, $password);
    
    //$Status .= JSONUpdateDevice_Active($Status) . "\n";

    //Add active DHCP leases only
    //$Status .= JSONActiveDevices($Status, $OWRT_hostname, $OWRT_cookie_path) . "\n";

    $Status .= AddNewDevicetoFirewall($Status, $OWRT_hostname, $OWRT_cookie_path) . "\n";

    $Status .= deleteCookies($OWRT_cookie_path) . "\n";

    echo $Status;
}

function parse_dhcp_leases($file_path)
{
	$result = [];
	$lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$ln = 1;
	
	foreach ($lines as $line)
	{
		$fields = preg_split('/\s+/', $line);
		if (count($fields) >= 4)
		{
			$index = $ln++;
			$hostname = $fields[3];
			$ip = $fields[2];
			$mac = $fields[1];
			$result[] = ['index' => $index, 'hostname' => $hostname, 'ip' => $ip, 'mac' => $mac];
		}
	}
	return $result;
}

function AddNewDevicetoFirewall($status, $OWRT_hostname, $OWRT_cookie_path)
{
    $OWRTFirewall = openWRTFirewallJSONtoArray($OWRT_hostname, $OWRT_cookie_path);
    $dhcp_leases_file = '/tmp/dhcp.leases';
    $DHCPLeases = parse_dhcp_leases($dhcp_leases_file);

    // Loop through every single live lease found on the system
    foreach($DHCPLeases as $DHCPLease)
    {
        $DoesNotExists = true;
        
        // FIX 1: Convert the target lease MAC address to strict lowercase
        $targetMac = trim(strtolower($DHCPLease['mac']));

        foreach($OWRTFirewall as $ipset)
        {
            if(isset($ipset['src_mac']))
            {
                // Normalize any format (Array or String) into a clean, flat PHP array
                $raw_entries = is_array($ipset['src_mac']) ? $ipset['src_mac'] : [$ipset['src_mac']];
                
                // FIX 2: Standardize all firewall MAC entries to strict lowercase for precise matching
                $ipset_entry_lowercase = array_map(function($mac) {
                    return trim(strtolower($mac));
                }, $raw_entries);
                
                // Cross-check the clean lowercase values
                if(in_array($targetMac, $ipset_entry_lowercase)) 
                {
                    $status .= "Firewall Check: MAC " . $targetMac . " is already registered in active rules. Skipping.\n";
                    $DoesNotExists = false;
                    break; // Break the firewall loops early since we verified it exists
                }
            }
        }
        
        // If the MAC address was nowhere to be found in the active rules, commit it
        if ($DoesNotExists === true)
        {
            $response_info = AddIDtoFirewalldIPSet($DHCPLease['mac'], "none", $OWRT_hostname, $OWRT_cookie_path);
            
            if($response_info === false) 
            {
                $status .= 'Failed to append missing MAC (' . $DHCPLease['mac'] . ') to OpenWRT rules.' . "\n";  
            }
            else
            {
                $status .= 'Successfully registered new device (' . $DHCPLease['mac'] . ') into active rules.' . "\n";
            }
        }
    }
    return $status;
}
?>
