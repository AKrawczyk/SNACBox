<?php
// Include config file
include "config.php";

// Check if the correct number of arguments is provided
if ($argc == 2) //and ($argc !=4))
{
    // Get the input values from command line arguments
    $aidcli = $argv[1];
    DeviceAccess("CLI", null, null, null, null);
}
elseif ($argc == 4) 
{
    // Get the input values from command line arguments
    DeviceAccess("CLI", $argv[1], $argv[2], $argv[3], null);
}
elseif ($argc == 3) 
{
    // Get the input values from command line arguments
    DeviceAccess("CLI", null, null, $argv[2], $argv[1]);
}
else
{
    DeviceAccess("CLI", null, null, null, null);
}

function DeviceAccess($inputtype, $descriptioncli, $accesscli, $maccli, $idcli)
{
    // Define variables and initialize with empty values
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL ^ E_NOTICE);
    $i = 1;
    $Status = "";
    $cookie_path = "";
    $dockermgrjsonpath = '/usr/bin/web-rated-docker.json';
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

    if ($inputtype == "CLI" and isset($maccli))
    {
        $argv1 = $descriptioncli;
        $argv2 = $accesscli;
        $argv3 = $maccli;
        $argv4 = $idcli;
        $Status = JSONProcessArgc($Status, $OWRT_hostname, $OWRT_cookie_path, $argv1, $argv2, $argv3, $argv4);
        $Status = $Status . "<br>" . $argv1 . "<br>" . $argv2 . "<br>" . $argv3 . "<br>";
    }
    /*else
    {
        $Status = $Status . "Did not enter ifelse." . $descriptioncli . "<br>" . $accesscli . "<br>" . $maccli . "<br>";
    }*/

    try
    {
        // Initialize JSON manager for different Docker Manager JSON files
        $dockermgrJson = new JsonManager($dockermgrjsonpath);
        
        // Get multiple entries
        $ArrayofResults = $dockermgrJson->getEntries([
            'Rateing' => '1'
        ]);

        //Dropdown list of Rateings
        $SelectRatings = "";
        foreach ($ArrayofResults as $value) 
        {
            $SelectRatings = $SelectRatings . '<option value="' . htmlentities($value['ID']). '">' . htmlentities($value['Name']) . "</option>";
        }
    } 
    catch (Exception $e) 
    {
        $Status = "Error: " . $e->getMessage();
    }
    
    $Status .= JSONUpdateDevice_Active($Status) . "</br>";

    //Display active DHCP leases only
    $Stint = JSONActiveDevices($Status, $i, $SelectRatings);
    $Status = $Stint[1];
    $i = $Stint[0];

    //Display inactive DHCP leases only
    $Status = JSONInActiveDevices($Status, $i, $SelectRatings, $OWRT_hostname, $OWRT_cookie_path) . "</br>";

    $Status = $Status . deleteCookies($OWRT_cookie_path) . "</br>";

    //Display Status information for debugging
    //echo StatusError($Status) . "</br>";
    //echo $Status;
}

function StatusError($data)
{
  $StatusOutput = "";
  if($data == "")
  {
      $StatusOutput = "";
  }
  else
  {
      $StatusOutput = $data;
  }
  return $StatusOutput;
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

function JSONProcessArgc($status, $OWRT_hostname, $OWRT_cookie_path, $argv1, $argv2, $argv3, $argv4)
{
    $Post_Access = $argv2;
    $Post_Description = $argv1;
    $Post_mac = $argv3;
    $Post_id = $argv4;
    $dockermgrjsonpath = '/usr/bin/web-rated-docker.json';
    $devicejsonPath = '/www/html/web-rated-device.json';

    //Updates Client Device Table and Firewalld ipset to match posted rating
    if(isset($Post_Access) && isset($Post_mac) && ($Post_Access !== "00") && ($Post_Access !== ""))
    {
        try
        {
            // Initialize JSON manager for different Docker Manager JSON files
            $dockermgrJson = new JsonManager($dockermgrjsonpath);
            // Set parameters to serarch for Link_id
            $jparam_Link_id = (int)$Post_Access;
            $rowRatingselected = $dockermgrJson->getEntry($jparam_Link_id);
            $Param_rating = strtolower($rowRatingselected['Name']);

            // Initialize JSON manager for different Docker Manager JSON files
            $deviceJson = new JsonManager($devicejsonPath);
            // Set parameters
            $Param_mac = $Post_mac;
            
            //Add MAC Address to Firewalld IPSet
            $response_info = AddIDtoFirewalldIPSet ($Param_mac, $Param_rating, $OWRT_hostname, $OWRT_cookie_path);
            // Check if data was exported successful
            if($response_info === false) 
            {
                //Display error to screen
                $status .= 'Failed to add IP/MAC to OpenWRT Firewall ipsets' . "</br>";
                die('Failed to add IP/MAC to OpenWRT Firewall ipsets');  
            }
        
            //Update Client Device Table with Link_id and Rating
            $success = $deviceJson->updateMACEntry($Param_mac, ['Device_Rating' => $Param_rating]);
            if ($success)
            {
                $status .= "</br>Update Entry: MAC " . $Param_mac . " succesfully updated rating to " . $Param_rating . ".</.br>";
            }
            else
            {
                $status .= "</br>Update Entry: MAC " . $Param_mac . " failed to update rating to " . $Param_rating . ".</.br>";
            }
        }
        catch (Exception $e) 
        {
            $status .= "Error: " . $e->getMessage();
        }
    }

    //Updates Client Device Table and Firewalld ipset to zero rating 
    else if(isset($Post_Access) && isset($Post_mac) && ($Post_Access === "00") && ($Post_Access !== ""))
    {
        try
        {
            // Initialize JSON manager for different Docker Manager JSON files
            $deviceJson = new JsonManager($devicejsonPath);
            
            // Set parameters
            $Param_mac = $Post_mac;
            
            //Removes MAC Address from  Firewalld IPSet
            $response_info = RemoveIDfromFirewalldIPSet($Param_mac, $OWRT_hostname, $OWRT_cookie_path);
            // Check if data was exported successful
            if($response_info === false) 
            {
                //Display error to screen
                $status .= 'Failed to remove IP/MAC from OpenWRT Firewall ipsets' . "</br>";
                die('Failed to remove IP/MAC from OpenWRT Firewall ipsets');  
            }
        
            //Update Client Device Table with no Rating
            $success = $deviceJson->updateMACEntry($Param_mac, ['Device_Rating' => '']);
            if ($success)
            {
                $fStatus .= "</br>Update Entry: MAC " . $Param_mac . " succesfully updated rating to None.</.br>";
            }
            else
            {
                $fStatus .= "</br>Update Entry: MAC " . $Param_mac . " failed to update rating to None.</.br>";
            }
        }
        catch (Exception $e) 
        {
            $status .= "Error: " . $e->getMessage();
        }
    }

    //Updates Client Device table with device Description
    if(isset($Post_Description) && ($Post_Description !== "") && isset($Post_mac))
    {
        try
        {
            // Initialize JSON manager for different Docker Manager JSON files
            $deviceJson = new JsonManager($devicejsonPath);
            
            // Set parameters
            $Param_Description = $Post_Description;
            $Param_mac = $Post_mac;
        
            //Update Client Device Table with Description
            $success = $deviceJson->updateMACEntry($Param_mac, ['Device_Description' => $Param_Description]);
            if ($success)
            {
                $fStatus .= "</br>Update Entry: MAC " . $Param_mac . " succesfully updated rating to None.</.br>";
            }
            else
            {
                $fStatus .= "</br>Update Entry: MAC " . $Param_mac . " failed to update rating to None.</.br>";
            }
        }
        catch (Exception $e) 
        {
            $status .= "Error: " . $e->getMessage();
        }
    }

    //Delete Inactive Devices
    if(isset($Post_id) && ($Post_id !== "") && isset($Post_mac))
    {
        $status = $status . JSONDeleteInactiveDevice($Post_id, $Post_mac, $OWRT_hostname, $OWRT_cookie_path) . "</br>";
    }
    return $status;
}

function JSONActiveDevices($status, $i, $SelectRatings)
{
    $Active = 1;
    $devicejsonPath = '/www/html/web-rated-device.json';
    $resultDevicesselected = [];
    $newId = "";
    $DataType = "JSON";
    $mysqli = "";
	 
    $dhcp_leases_file = '/tmp/dhcp.leases';
    $DHCPLeases = 	parse_dhcp_leases($dhcp_leases_file);

    //HTML Client Active information Table
    echo "<p><H3 style='color: #008000;'>Active Devices</H3></p>";
    echo "<table class='table cbi-section-table'>";
    echo "<tr class='tr cbi-section-table-titles anonymous'>";
    echo "<th class='th cbi-section-table-cell' style='width:10%' data-widget='dvalue'>Description</th>";
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">Name</th>';
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">IP</th>';
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">ID/MAC</th>';
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">Access</th>';
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">Configuration</th>';
    echo "</tr>";

    //Create a row of data in table for each item in DHCP lease created from DHCP Lease JSON file
    //Foreach loop to desplay active DHCP leases only
    foreach($DHCPLeases as $DHCPLease)
    {
        
        //Creates a unique ID for each dropdown list
        $Post_Access = "Access" . $i;
        $Post_Description = "Description" . $i;
        $HsID = "HostID" . $i;
        $Param_Description = "";
        $Param_Access = "00";
        $Post_mac = "mac" . $i;
        $Param_rating = "";
        $Post_AID = "AID" . $i;
        
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
                    "Device_Rating" => "",
                    "Device_Active" => $Active
                ];
                $newId = $deviceJson->addEntry($newdevice);
                $status .= "Device added successfully with ID: $newId";
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
                        $status .= "</br>Update Entry: MAC " . $rowDevicesSelected['Device_MAC'] . " succesfully updated active devices.</.br>";
                    }
                    else
                    {
                        $status .= "</br>Update Entry: MAC " . $rowDevicesSelected['Device_MAC'] . " failed to update active devices.</.br>";
                    }
                }
            }
            // Set parameters to serarch for mac address
            $jparam_Device_mac = $DHCPLease['mac'];
            $resultDevicesselected = $deviceJson->getEntries(['Device_MAC' => trim($jparam_Device_mac, '"\'')]);
        }
        catch (Exception $e) 
        {
            $status .= "Error: " . $e->getMessage();
        }

        //Loop all results of select statement and create HTML output to table Client Group name
        foreach ($resultDevicesselected as $rowDevicesselected)
        {
            if ($rowDevicesselected['Device_Description'] == null)
            {
                $Param_Description = "";
            }
            else
            {
                $Param_Description = htmlspecialchars($rowDevicesselected['Device_Description']);
            }
            if ($rowDevicesselected['Device_Rating'] == null)
            {
                $Param_Access = "";
            }
            else
            {
                $Param_Access = htmlspecialchars($rowDevicesselected['Device_Rating']);
            }
            
            $Param_Hostname = htmlspecialchars($rowDevicesselected['Device_hostname']);
            $Param_IP = htmlspecialchars($rowDevicesselected['Device_ip']);
        }

        // Outputting ADGuardHome DHCP lease JSON data in Decoded format and displays in HTML table format
        echo '<tr class="tr cbi-section-table-row cbi-rowstyle-1">';
        if($Param_Description !== "")
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="Description">' . $Param_Description . "</td>";
        }
        else
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue">' . $Param_Description . "</td>";
        }
        if($Param_Hostname !== "")
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="Name">' . $Param_Hostname . "</td>";
        }
        else
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue">' . $Param_Hostname . "</td>";
        }
        echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="IP">' . $Param_IP . "</td>";
        echo '<td style="color: #008000;" class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="ID/MAC">' . $DHCPLease['mac'] . "</td>";
        if($Param_Access !== "")
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="Access">' . $Param_Access . "</td>";
        }
        else
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue">' . $Param_Access . "</td>";
        }
        echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="Configuration">';

        //Create HTML form
        echo '<form id=' . $HsID . ' method="post">';
        echo "<input type='hidden' name='form_id' value=" . $i . ">";
        //Creates HTML Hidden Value
        echo "<input type='hidden' id=" . $Post_mac . " name=" . $Post_mac . " value=" . $DHCPLease['mac'] . ">";
        //Creates HTML Hidden Value to determine which Post Active or inActive devices should be processed
        echo "<input type='hidden' id=" . $Post_AID . " name=" . $Post_AID . " value=" . "AD" . ">";
        //Creates HTML Text input
        echo '<input type="text" name=' . $Post_Description . ' placeholder="Host Deccription"><br>';
        //Create HTML dropdown list
        echo "<select class='cbi-input-select' name=" . $Post_Access . ">";
        echo "<option selected disabled>Choose Rating</option>";
        echo $SelectRatings;
        echo "</select></br>";
        
        //Creates HTML Button
        echo '<input class="btn" type="submit" value="Submit">';
        echo "</form>";
        echo "</td>";
        echo "</tr>";
        
        $i++;
    }
    //Finish Device Active HTML Table
    echo "</table></br>";

    $stint = array($i, $status);
    return $stint;
}

function JSONInActiveDevices($status, $i, $SelectRatings, $OWRT_hostname, $OWRT_cookie_path)
{
    $InActive = 0;
    $devicejsonPath = '/www/html/web-rated-device.json';
    $resultDevicesselected = [];
    $DataType = "JSON";
    $mysqli = "";

    try
    {
        // Initialize JSON manager for different Docker Manager JSON files
        $deviceJson = new JsonManager($devicejsonPath);

        // Set parameters to serarch for device active
        $jparam_Device_Active = 0;
        $resultDevices = $deviceJson->getEntries(['Device_Active' => trim($jparam_Device_Active, '"\'')]);
    }
    catch (Exception $e) 
    {
        $status .= "Error: " . $e->getMessage();
    }

    //HTML Client InActive information Table
    //echo "<p><H3 style='color:#800000'>Inactive Devices</H3></p>";
    echo "<p><H3 style='color: #800000;'>Inactive Devices</H3></p>";
    echo "<table class='table cbi-section-table'>";
    echo "<tr class='tr cbi-section-table-titles anonymous'>";
    echo "<th class='th cbi-section-table-cell' style='width:10%' data-widget='dvalue'>Description</th>";
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">Name</th>';
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">ID/MAC</th>';
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">Access</th>';
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">Configuration</th>';
    echo '<th class="th cbi-section-table-cell" style="width:10%" data-widget="dvalue">Remove</th>';
    echo "</tr>";
    
    //Loop all results of select statement
    foreach ($resultDevices as $rowDevices)
    {
        //Creates a unique ID for each dropdown list
        $Post_Access = "Access" . $i;
        $Post_Description = "Description" . $i;
        $HsID = "HostID" . $i;
        $Param_Description = "";
        $Param_Access = "00";
        $Post_mac = "mac" . $i;
        $Param_rating = "";
        $Post_AID = "AID" . $i;
        
        try
        {
            // Initialize JSON manager for different Docker Manager JSON files
            $deviceJson = new JsonManager($devicejsonPath);

            // Set parameters to serarch for mac address
            $jparam_Device_mac = $rowDevices['Device_MAC'];
            $resultDevicesselected = $deviceJson->getEntries(['Device_MAC' => trim($jparam_Device_mac, '"\'')]);
        }
        catch (Exception $e) 
        {
            $status .= "Error: " . $e->getMessage();
        }

        //Loop all results of select statement and create HTML output to table Client Group name
        foreach ($resultDevicesselected as $rowDevicesselected)
        {
            if ($rowDevicesselected['Device_Description'] == null)
            {
                $Param_Description = "";
            }
            else
            {
                $Param_Description = htmlspecialchars($rowDevicesselected['Device_Description']);
            }
            if ($rowDevicesselected['Device_Rating'] == null)
            {
                $Param_Access = "";
            }
            else
            {
                $Param_Access = htmlspecialchars($rowDevicesselected['Device_Rating']);
            }
            
            $Param_Hostname = htmlspecialchars($rowDevicesselected['Device_hostname']);
            $Param_IP = htmlspecialchars($rowDevicesselected['Device_ip']);
            $Param_id = $rowDevicesselected['ID'];
        }

        // Outputting and displays in HTML table format
        
        echo '<tr class="tr cbi-section-table-row cbi-rowstyle-1">';
        if($Param_Description !== "")
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="Description">' . $Param_Description . "</td>";
        }
        else
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue">' . $Param_Description . "</td>";
        }
        if($Param_Hostname !== "")
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="Name">' . $Param_Hostname . "</td>";
        }
        else
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue">' . $Param_Hostname . "</td>";
        }
        echo '<td style="color: #800000;" class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="ID/MAC">' . $rowDevices['Device_MAC'] . "</td>";
        if($Param_Access !== "")
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="Access">' . $Param_Access . "</td>";
        }
        else
        {
            echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue">' . $Param_Access . "</td>";
        }
        echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="Configuration">';

        //Create HTML form
        echo '<form id=' . $HsID . ' method="post">';
        echo "<input type='hidden' name='form_id' value=" . $i . ">";
        //Creates HTML Hidden Value
        echo "<input type='hidden' id=" . $Post_mac . " name=" . $Post_mac . " value=" . $rowDevices['Device_MAC'] . ">";
        //Creates HTML Hidden Value to determine which Post Active or inActive devices should be processed
        echo "<input type='hidden' id=" . $Post_AID . " name=" . $Post_AID . " value=" . "IAD" . ">";
        //Creates HTML Text input
        echo '<input type="text" name=' . $Post_Description . ' placeholder="Host Deccription"><br>';
        //Create HTML dropdown list
        echo "<select class='cbi-input-select' name=" . $Post_Access . ">";
        echo "<option selected disabled>Choose Rating</option>";
        echo $SelectRatings;
        echo "</select></br>";
        
        //Creates HTML Button
        echo '<input class="btn" type="submit" value="Submit">';
        echo "</form>";
        echo "</td>";
        echo '<td class="td cbi-value-field" data-name="_id" data-widget="dvalue" data-title="Remove">';
            echo '<div>';
                echo '<!-- Delete Icon -->';
                //echo '<center><a href="#" class="delete-link" data-id="' . $Param_id . '" data-mac="' . $rowDevices['Device_mac'] . '" data-hostname="' . $OWRT_hostname . '" data-cookiepath="' . $OWRT_cookie_path . '"><span>&#128465;</span></a></center>';
                echo '<center>
                        <form method="POST" id="DeleteInactiveDevices">
                            <input type="hidden" name="form_id" value="0">
                            <input type="hidden" name="device_id" value="' . $Param_id . '">
                            <input type="hidden" name="device_mac" value="' . $rowDevices['Device_MAC'] . '">
                            <button type="submit" class="delete-link" onclick="return confirm(\'Are you sure you want to delete this device?\');" style="background: transparent; border: none; padding: 0; cursor: pointer;"><span>&#128465;</span></button>
                        </form>
                      </center>';
            echo '</div>';
        echo '</td>';
        echo '</tr>';
        echo "</tr>";
        
        $i++;
    }
    //Finish Device Inactive HTML Table
    echo "</table></br>";
    //echo $status;

    return $status;
}

function JSONDeleteInactiveDevice($id, $mac, $hostname, $cookiepath)
{
    $Status = "";
    $devicejsonPath = '/www/html/web-rated-device.json';
    $DataType = "JSON";
    $mysqli = "";

    // Initialize JSON manager for different Docker Manager JSON files
    $deviceJson = new JsonManager($devicejsonPath);

    //Delete Client Device
    $success = $deviceJson->removeEntry($id);
    if ($success)
    {
        $Status .= "</br>Delete Entry: ID " . $id . " succesfully deleted devices.</.br>";
    }
    else
    {
        $Status .= "</br>Delete Entry: ID " . $id . " failed to delete devices.</.br>";
    }

    //Removes MAC Address from  Firewalld IPSet
    $response_info = RemoveIDfromFirewalldIPSet($mac, $hostname, $cookiepath);
    // Check if data was exported successful
    if($response_info === false) 
    {
        // If the request is not a POST request, return an error message
        $Status = $Status . "Failed to remove IP/MAC from DataBase and OpenWRT Firewall ipsets."; 
    }
    //DeviceAccess("CLI", null, null, null);
    return $Status;
}
?>
