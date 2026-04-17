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

function JSONUpdateDevice_Active($fStatus)
{
    // Set parameters
    $Param_active = 0;
    $devicejsonPath = '/www/html/web-rated-device.json';
    
    //
    //UPDATE JSON
    //
    try
    {
        // Initialize JSON manager for different Docker Manager JSON files
        $deviceJson = new JsonManager($devicejsonPath);
            
        // Get multiple entries
        $ArrayofResults = $deviceJson->getEntries([
            'Device_Active' => '1'
        ]);
        foreach ($ArrayofResults as $value) 
        {
            $success = $deviceJson->updateEntry($value['ID'], ['Device_Active' => '0']);
            if ($success)
            {
                $fStatus .= "</br>Update Entry: ID " . $value['ID'] . " succesfully updated.</.br>";
            }
            else
            {
                $fStatus .= "</br>Update Entry: ID " . $value['ID'] . " update failed.</.br>";
            }
        }
    }
    catch (Exception $e) 
    {
        $fStatus .= "Error: " . $e->getMessage();
    }
    return $fStatus;
}

function JSONActiveDevices($status, $OWRT_hostname, $OWRT_cookie_path)
{
    $Active = "1";
    $devicejsonPath = '/www/html/web-rated-device.json';
    $resultDevicesselected = [];
    $newId = "";
	 
    $dhcp_leases_file = '/tmp/dhcp.leases';
    $DHCPLeases = 	parse_dhcp_leases($dhcp_leases_file);

    //Create a row of data in table for each item in DHCP lease created from DHCP Lease JSON file
    //Foreach loop to desplay active DHCP leases only
    foreach($DHCPLeases as $DHCPLease)
    {
        try
        {
            // Initialize JSON manager for different Docker Manager JSON files
            $deviceJson = new JsonManager($devicejsonPath);
            
            // Set parameters to serarch for Device_mac
            $jparam_Link_id = $DHCPLease['mac'];
            $resultDevicesSelected = $deviceJson->getEntries(['Device_MAC' => trim($jparam_Link_id, '"\'')]);
            if(sizeof($resultDevicesSelected) == 0)
            {
                // Set parameters
                $newdevice = [
                    "Device_hostname" => $DHCPLease['hostname'],
                    "Device_Description" => "",
                    "Device_ip" => $DHCPLease['ip'],
                    "Device_MAC" => $DHCPLease['mac'],
                    "Device_Rating" => "No Access",
                    "Device_Active" => $Active
                ];
                $newId = $deviceJson->addEntry($newdevice);
                $status .= "Device added successfully with ID: $newId \n";

                //Add MAC Address to Firewalld IPSet
                $response_info = AddIDtoFirewalldIPSet ($DHCPLease['mac'], "none", $OWRT_hostname, $OWRT_cookie_path);
                // Check if data was exported successful
                if($response_info === false) 
                {
                    //Display error to screen
                    $status .= 'Failed to add IP/MAC to OpenWRT Firewall ipsets' . "\n";
                    die('Failed to add IP/MAC to OpenWRT Firewall ipsets');  
                }
            }
            else
            {
                //Loop all results of select statement and create HTML output to table Client Group name
                foreach ($resultDevicesSelected as $rowDevicesSelected)
                {

                    $updatedevice = [
                        "Device_hostname" => $DHCPLease['hostname'],
                        "Device_ip" => $DHCPLease['ip'],
                        "Device_Active" => $Active
                    ];
                    //Update Client Device Table with Hostname, IP and Active
                    $success = $deviceJson->updateMACEntry($rowDevicesSelected['Device_MAC'], $updatedevice);
                    if ($success)
                    {
                        $status .= "Update Entry: MAC " . $rowDevicesSelected['Device_MAC'] . " succesfully updated active devices.\n";
                    }
                    else
                    {
                        $status .= "Update Entry: MAC " . $rowDevicesSelected['Device_MAC'] . " failed to update active devices.\n";
                    }
                }
            }
            // Set parameters to serarch for mac address
            $jparam_Device_mac = $DHCPLease['mac'];
            $resultDevicesselected = $deviceJson->getEntries(['Device_MAC' => trim($jparam_Device_mac, '"\'')]);
        }
        catch (Exception $e) 
        {
            $status .= "Error: " . $e->getMessage() . "\n";
        }
    }
    return $status;
}

function AddNewDevicetoFirewall($status, $OWRT_hostname, $OWRT_cookie_path)
{
    $OWRTFirewall = openWRTFirewallJSONtoArray ($OWRT_hostname, $OWRT_cookie_path);
    $dhcp_leases_file = '/tmp/dhcp.leases';
    $DHCPLeases = 	parse_dhcp_leases($dhcp_leases_file);

    //Create a row of data in table for each item in DHCP lease created from DHCP Lease JSON file
    //Foreach loop to desplay active DHCP leases only
    foreach($DHCPLeases as $DHCPLease)
    {
        $DoesNotExists = true;
        foreach($OWRTFirewall as $ipset)
        {
            if(isset($ipset['src_mac']))
            {
                // Check if $ipset['src_mac'] is an array
                if (is_array($ipset['src_mac'])) 
                {
                    $ipset_entry = $ipset['src_mac'];
                    //echo "</br> Array " . implode(" ", $ipset_entry)  . "</br>";
                } 
                else 
                {
                    //echo "String " . $ipset['src_mac'] . "</br>";
                    $ipset_entry = explode(" ", $ipset['src_mac']);
                }
                
                if(in_array($DHCPLease['mac'], $ipset_entry)) 
                {
                    $status .= "Firewall MACs " . implode(" ", $ipset['src_mac']) . " " . "DHCP MAC " . $DHCPLease['mac'] . " is already part of a Firewall rule.\n";
                    $DoesNotExists = false;
                }
            }
        }
        
        if ($DoesNotExists === true)
        {
            //Add MAC Address to Firewalld IPSet
            $response_info = AddIDtoFirewalldIPSet ($DHCPLease['mac'], "none", $OWRT_hostname, $OWRT_cookie_path);
            // Check if data was exported successful
            if($response_info === false) 
            {
                //Display error to screen
                $status .= 'Failed to add IP/MAC to OpenWRT Firewall ipsets' . "\n";  
            }
            else
            {
                //Display error to screen
                $status .= 'Successfully added IP/MAC to OpenWRT Firewall ipsets' . "\n";
            }
        }
    }
    return $status;
}
?>
