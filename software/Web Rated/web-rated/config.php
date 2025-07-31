<?php
class JsonManager 
{
    private string $jsonFile;
    private array $data = []; // Initialize the property with empty array

    public function __construct(string $jsonFile) 
    {
        $this->jsonFile = $jsonFile;
        $this->loadData();
    }

    private function loadData(): void 
    {
        if (!file_exists($this->jsonFile)) 
        {
            $this->data = [];
            return;
        }

        $content = file_get_contents($this->jsonFile);
        if ($content === false) 
        {
            throw new Exception("Failed to read JSON file: {$this->jsonFile}");
        }

        if (trim($content) === '')
        {
            // Create an empty result
            $this->date = [];
        }
        else
        {
            $this->data = json_decode($content, true);
        }
        if (json_last_error() !== JSON_ERROR_NONE) 
        {
            throw new Exception("Invalid JSON format: " . json_last_error_msg());
        }
    }

    private function saveData(): void 
    {
        // Create directory if it doesn't exist
        $directory = dirname($this->jsonFile);
        if (!is_dir($directory)) 
        {
            if (!mkdir($directory, 0755, true)) 
            {
                throw new Exception("Failed to create directory: {$directory}");
            }
        }

        // Create backup if file exists
        if (file_exists($this->jsonFile)) 
        {
            copy($this->jsonFile, $this->jsonFile . '.bak');
        }

        // Save with pretty print
        if (file_put_contents($this->jsonFile, json_encode($this->data, JSON_PRETTY_PRINT)) === false) 
        {
            throw new Exception("Failed to write to JSON file");
        }
    }

    public function addEntry(array $entry, bool $generateId = true): int|string 
    {
        if ($generateId) {
            // Generate a new unique ID regardless of whether ID exists or not
            $newId = $this->generateUniqueId();
            
            // If entry doesn't have an ID, add it to beginning of array
            if (!isset($entry['ID'])) {
                $entry = ['ID' => $newId] + $entry;
            } else {
                // If ID exists, just update its value
                $entry['ID'] = $newId;
            }
        }

        $this->data[] = $entry;
        $this->saveData();

        return $generateId ? $entry['ID'] : count($this->data) - 1;
    }

    public function removeEntry(int|string $id): bool 
    {
        foreach ($this->data as $key => $entry) 
        {
            // Convert $id to string for consistent comparison
            $searchId = (int)$id;
            
            if (isset($entry['ID']) && $entry['ID'] === $searchId) 
            {
                unset($this->data[$key]);
                $this->data = array_values($this->data); // Reindex array
                $this->saveData();
                return true;
            }
        }
        return false;
    }

    public function updateEntry(int|string $id, array $newData): bool 
    {
        // Convert $id to string for consistent comparison
        $searchId = (int)$id;

        foreach ($this->data as $key => $entry) 
        {
            if (isset($entry['ID']) && $entry['ID'] === $searchId) 
            {
                $this->data[$key] = array_merge($entry, $newData);
                $this->saveData();
                return true;
            }
        }
        return false;
    }

    public function updateMACEntry(int|string $id, array $newData): bool 
    {
        // Convert $id to string for consistent comparison
        $searchId = $id;

        foreach ($this->data as $key => $entry) 
        {
            if (isset($entry['Device_MAC']) && $entry['Device_MAC'] === $searchId) 
            {
                $this->data[$key] = array_merge($entry, $newData);
                $this->saveData();
                return true;
            }
        }
        return false;
    }

    public function getEntries(array $filters = []): array 
    {
        if (empty($filters)) 
        {
            return $this->data;
        }

        return array_filter($this->data, function($entry) use ($filters) 
        {
            foreach ($filters as $key => $value) 
            {
                if (!isset($entry[$key]) || $entry[$key] !== $value) 
                {
                    return false;
                }
            }
            return true;
        });
    }

    public function getEntry(int|string $id): ?array 
    {
        // Convert $id to string for consistent comparison
        $searchId = (int)$id;

        foreach ($this->data as $entry) 
        {
            if (isset($entry['ID']) && $entry['ID'] === $searchId) 
            {
                return $entry;
            }
        }
        return null;
    }

    private function generateUniqueId(): int 
    {
        if (empty($this->data)) 
        {
            return 1;
        }
        return max(array_column($this->data, 'ID')) + 1;
    }

    public function clear(): void 
    {
        $this->data = [];
        $this->saveData();
    }
}

function LoginOpenWRTAPI($API_hostname, $API_Username, $API_Password)
{
    // Initializing curl for Post - Login to ADGuardHome using API
    $URL = "https://" . $API_hostname . "/cgi-bin/luci/rpc/auth";
    $curl = curl_init($URL);

    //Stores ADGuardHome API Login token
    $cookie_path = tempnam('/tmp','openwrtapicookie.txt');

    //JSON format data to be POST by curl for login
    $data = json_encode(array("id" => 1, "method" => "login", "params" => array($API_Username, $API_Password)));

    // Sending POST request to ADGuard Login API
    // server to post JSON data
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: */*', 'Content-Type: application/json'));
    // Telling curl to store JSON data in a variable instead of dumping on screen
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    // Telling curl to get authentication token and save to cookie
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_path);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_path);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (use with caution)
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // Disable hostname verification (use with caution)

    // Executing curl ADGuardHome Login
    $response = curl_exec($curl);

    // Checking if any error occurs
    // during request or not
    if($e = curl_error($curl)) 
    {
        //Display CURL error to screen
        die("OpenWRT API login error: " . $e);

        // Closing curl for Post ADGuardHome login
        curl_close($curl);
        //$cookie_path = false;
    }
    else
    {   
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // Closing curl for Post ADGuardHome login
        curl_close($curl);
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode);
        }
    }

    //$responseData = json_decode($response, true);

    //if (isset($responseData['result'])) {
    //    return $responseData['result'];
    //} else {
    //    throw new Exception("Authentication failed: " . ($responseData['error']['message'] ?? 'Unknown error'));
    //    return false;
    //}

    return $cookie_path;

    /*
    $username = "user";
    $password = "password";
    $hostname = "192.168.1.1";
    $cookie_path = LoginADGuardHomeAPI($hostname, $username, $password);
    */
}

function GetOpenWRTAPI ($API_URL, $data, $cookie_path)
{
    //$API_URL .= (strpos($API_URL, '?') === false ? '?' : '&') . 'auth=' . urlencode($cookie_path);
    
    // Initializing curl for GET
    $curl = curl_init();

    // Sending GET request to ADGuardHome DHCP API
    // server to get JSON data
    curl_setopt($curl, CURLOPT_URL, $API_URL);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    //curl_setopt($curl, CURLOPT_HTTPHEADER, array('accept: application/json'));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Auth-Token: "' . $cookie_path . '"'  // Add the auth token to the header
    ));
    
    // Telling curl to store JSON data in a variable instead of dumping on screen
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    // Telling curl to keep connection open and use authentication token
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);   
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_path);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_path);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (use with caution)
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // Disable hostname verification (use with caution)

    // Executing curl
    $response = curl_exec($curl);
    
    // Checking if any error occurs
    // during request or not
    if($e = curl_error($curl)) 
    {
        //Display CURL error to screen
        die("ADGuard Get API error: " . $e);
        
        // Closing GET curl ADGuardHome DHCP
        curl_close($curl);
        $JSONData = false;
    } 
    else 
    {
        
        // Decoding ADGuardHome DHCP JSON data
        $JSONData = $response;

        // Closing GET curl ADGuardHome DHCP
        curl_close($curl);
    }
    
    return $JSONData;

    /*
    $username = "user";
    $password = "password";
    $hostname = "192.168.1.1";
    $login_data = json_encode(array("id" => 1, "method" => "login", "params" => array($username, $password)))
    $API_JSONData = GetADGuardHomeAPI("http://172.0.0.1:3080/control/function/sub-function", $cookie_path) 
    */
}

function PostOpenWRTAPI($API_URL, $data, $cookie_path) 
{
    //$API_URL .= (strpos($API_URL, '?') === false ? '?' : '&') . 'auth=' . urlencode($cookie_path);
    
    $url = $API_URL;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
    
    // Send JSON data directly
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Auth-Token: "' . $cookie_path . '"' // Add the auth token to the header
    ));

     // Telling curl to where the authentication token is
     curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path);
     curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path);
     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (use with caution)
     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable hostname verification (use with caution)
     
    // Set content type header
    $response = curl_exec($ch);
    //echo $response;
    $response_info = json_decode($response, true);

    // Checking if any error occurs
    // during data process or not
    if($e = curl_error($ch))
    {
        
        //Display CURL error to screen
        die("OpenWRT Get API error: " . $e);
        $Status = "OpenWRT Get API error: " . $e;

        // Closing curl for Post
        curl_close($ch);
    }
    else
    {
        if ($response_info !== null && isset($response_info['result']))
        {
            //Display CURL Post status
            $Status = "OpenWRT Post API was successful.";
        }
        else 
        {
            $Status = "OpenWRT Post API was not successful.";
        }
        
        // Closing curl for Post
        curl_close($ch);
    }


    return $Status;

    /* 
        $username = "user";
        $password = "password";
        $hostname = "192.168.1.1";
        $login_data = json_encode(array("id" => 1, "method" => "login", "params" => array($username, $password)))
        $URL = "http://" . $hostname . "/cgi-bin/luci/rpc/auth";
        $status = PostOpenWRTAPI($usl, $login_data, $cookie_path)
    */
}

function openWRTFirewallJSONtoArray ($hostname, $cookie_path)
{
    // JSON Data from LuCI
    $JSON_data = json_encode(array( "method" => "get_all", "params" => array("firewall")));
    $response_info = GetOpenWRTAPI("https://" . $hostname . "/cgi-bin/luci/rpc/uci", $JSON_data, $cookie_path);
    //echo "Firewall info; " . $response_info . " " . $hostname . " " . $cookie_path . "<br>";
    
    // Check if data was exported successful
    if($response_info === false) //or $response_info === "") 
    {
        $status = false;
        return $status;
        //Display error to screen
        $status = 'Failed to GET OpenWRT Firewall information' . "</br>";
        die('Failed to GET OpenWRT Firewall information');
        
    }
    else
    {
        $json_response = json_decode($response_info, true);

        // Filter for IPsets
        $ipsets = array_filter($json_response['result'], function ($item) {
            return (isset($item['.type']) && ($item['.type'] === "rule" || $item['.type'] === "redirect")) && isset($item['name']) && strpos($item['name'], "-rated") !== false;
        });

        return $ipsets;        
    }
}

function AddIDtoFirewalldIPSet ($Param_mac, $Param_FDIPset, $hostname, $cookie_path)
{
//
    //Search FD IPSet Table for IPSet Name with Link_id
    //Then add IP/MAC address to Firewalld IPSet
    //
    // Get a list of all ipsets
    $FDIPsetoutput = array();
    $FDIPsetstatus = "";
    //$FDIPsetoutput = explode(' ', str_replace("\n","",shell_exec("sudo firewall-cmd --permanent --get-ipsets")));
    $FDIPsetoutput = openWRTFirewallJSONtoArray($hostname, $cookie_path);
    //print_r($FDIPsetoutput);
    // Check if data was exported successful
    if($FDIPsetoutput === false) 
    {
        $status = false;
        return $status;
    }
    else
    {
        // Search for the IP/MAC address in each ipset
        foreach($FDIPsetoutput as $ipset) 
        {
            //$FDIPsetoutput_search = array();
            //$FDIPsetoutput_search = explode(' ', str_replace("\n"," ",shell_exec("sudo firewall-cmd --permanent --ipset=" . $ipset . " --get-entries")));
            //CHANGE 'entry' TO 'src_mac'
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
                
                if(in_array($Param_mac, $ipset_entry)) 
                {
                    // Remove the IP/MAC address from the ipset
                    //$FDIPsetstatus = exec("sudo firewall-cmd --permanent --ipset=" . $ipset . " --remove-entry=" . $Param_mac);
                    
                    // Remove IP/MAC from the array
                    $array = $ipset_entry;
                    //echo "MAC to Remove " . $Param_mac . "</br>";
                    $array = array_filter($array, function($value) use ($Param_mac) {
                        return strpos($value, $Param_mac) !== 0;
                    });
                    $array = array_values($array);  // Reindex the array keys
                    //echo "ArraytoString: " . implode(" ", $array) . "</br>";
                    $removeMAC = $array; 
                    $JSON_data_remove = json_encode(array( "method" => "set", "params" => array("firewall", $ipset['.name'], "src_mac", $removeMAC)));
                    //echo "JSON Remove " . $JSON_data_remove . "</br>";
                    $response_info = PostOpenWRTAPI("https://" . $hostname . "/cgi-bin/luci/rpc/uci", $JSON_data_remove, $cookie_path);
                    //echo $hostname;
                    // Check if removal was successful
                    //$json_response = json_decode($response_info, true);
                    if ($response_info === "OpenWRT Post API was successful.") 
                    {
                        $FDIPsetstatus = "success";
                        //echo "JSON Response " . $response_info . "</br>";
                        $JSON_data_remove_commit = json_encode(array( "method" => "commit", "params" => array("firewall", $ipset['.name'])));
                        //echo "JSON Commit " . $JSON_data_remove_commit . "</br>";
                        $response_info_commit = PostOpenWRTAPI("https://" . $hostname . "/cgi-bin/luci/rpc/uci", $JSON_data_remove_commit, $cookie_path); 
                        if ($response_info_commit === "OpenWRT Post API was successful.") 
                        {
                            $FDIPsetstatus = "success";
                            //echo "JSON Commit Response " . $response_info_commit . "</br>";
                        } 
                        else 
                        {
                            $FDIPsetstatus = "fail";
                            //echo "JSON Commit Response Fail " . $response_info_commit . "</br>";
                        }
                    } 
                    else 
                    {
                        $FDIPsetstatus = "fail";
                        //echo "JSON Response Fail " . $response_info . "</br>";
                    }
                }
            }
        }
    }   

    // Reload the firewall service to apply the changes
    //exec("sudo firewall-cmd --reload");
    //echo "Status " . $FDIPsetstatus . "</br>";
    if (($FDIPsetstatus === "success") or ($FDIPsetstatus === "")) 
    {
        // Add the IP/MAC address to the ipset
        //$FDIPsetstatus = exec("sudo firewall-cmd --permanent --ipset=" . escapeshellarg($Param_FDIPset) . " --add-entry=" . escapeshellarg($Param_mac));
        //$FDIPsetstatus = exec("sudo firewall-cmd --reload");
        //reset($FDIPsetoutput);
        //$Param_FDIPset = str_replace("r", "-r", $Param_FDIPset);
        foreach($FDIPsetoutput as $ipset) 
        {
            //echo "IPSet Name " . $Param_FDIPset . "</br>";
            if(strpos($ipset['name'], $Param_FDIPset) === 0) 
            {
                if(isset($ipset['src_mac']))
                {
                    // Check if $ipset['src_mac'] is an array
                    if (is_array($ipset['src_mac'])) 
                    {
                        $ipset_entry = $ipset['src_mac'];
                        //echo "Array " . implode(" ", $ipset_entry)  . "</br>";
                    } 
                    else 
                    {
                        //echo "String " . $ipset['src_mac'] . "</br>";
                        $ipset_entry = explode(" ", $ipset['src_mac']);
                    }
                
                    // Add IP/MAC from the array
                    $array = $ipset_entry;
                
                    // Add elements to the array
                    //echo "MAC to ADD " . $Param_mac . "</br>";
                    array_push($array, $Param_mac);
                    $addMAC = $array;
                }
                else 
                {
                    // Add elements to the string
                    //echo "MAC to ADD " . $Param_mac . "</br>";
                    $addMAC = array();
                    array_push($addMAC, $Param_mac);
                }
                //echo "String of MAC " . $addMAC . "</br>";
                $JSON_data_add = json_encode(array( "method" => "set", "params" => array("firewall", $ipset['.name'], "src_mac", $addMAC)));
                $response_info = PostOpenWRTAPI("https://" . $hostname . "/cgi-bin/luci/rpc/uci", $JSON_data_add, $cookie_path);
                
                // Check if removal was successful
                //$json_response = json_decode($response_info, true);
                if ($response_info === "OpenWRT Post API was successful.") 
                {
                    $FDIPsetstatus = "success";
                    //echo "JSON Response " . $response_info . "</br>";
                    $JSON_data_add_commit = json_encode(array( "method" => "commit", "params" => array("firewall", $ipset['.name'])));
                    //echo "JSON Commit " . $JSON_data_add_commit . "</br>";
                    $response_info_commit = PostOpenWRTAPI("https://" . $hostname . "/cgi-bin/luci/rpc/uci", $JSON_data_add_commit, $cookie_path); 
                    if ($response_info_commit === "OpenWRT Post API was successful.") 
                    {
                        $FDIPsetstatus = "success";
                        //echo "JSON Commit Response " . $response_info_commit . "</br>";
                    } 
                    else 
                    {
                        $FDIPsetstatus = "fail";
                        //echo "JSON Commit Response Fail " . $response_info_commit . "</br>";
                    }
                } 
                else 
                {
                    $FDIPsetstatus = "fail";
                    //echo "JSON Response Fail " . $response_info . "</br>";
                }
            }
        }

        if ($FDIPsetstatus === "success") 
        {
            $Status = true;
        }
        else
        {
            $Status = false;
        }
    }
    else
    {
        $Status = false;
    }
    //echo "Return Status " . $Status . "</br>";
    return $Status;

    /* 
        $username = "user";
        $password = "password";
        $login_data = json_encode(array("id" => 1, "method" => "login", "params" => array($username, $password)));
        if (AddIDtoFirewalldIPSet ("00:00:00:00:00", "ipsetname", "192.168.1.1", $login_data))
        {
            //if true do somthing
        }
        else
        {
            //if false do nothing
        }
    
    */
}

function RemoveIDfromFirewalldIPSet ($Param_mac, $hostname, $cookie_path)
{
    //
    //Search FD IPSet Table for IPSet Name with Link_id
    //Then removes IP/MAC address from Firewalld IPSet
    //
    // Get a list of all ipsets
    $FDIPsetoutput = array();
    $FDIPsetstatus = "";
    $FDIPsetoutput = openWRTFirewallJSONtoArray($hostname, $cookie_path);
    // Check if data was exported successful
    if($FDIPsetoutput === false) 
    {
        $status = false;
        return $status;
    }
    else
    {
        // Search for the IP/MAC address in each ipset
        foreach($FDIPsetoutput as $ipset) 
        {
            if(isset($ipset['src_mac']))
            {
                // Check if $ipset['src_mac'] is an array
                if (is_array($ipset['src_mac'])) 
                {
                    $ipset_entry = $ipset['src_mac'];
                } 
                else 
                {
                    //echo $ipset['src_mac'];
                    $ipset_entry = explode(" ", $ipset['src_mac']);
                }

                if(in_array($Param_mac, $ipset_entry)) 
                {
                    // Remove the IP/MAC address from the ipset
                    // Remove IP/MAC from the array
                    $array = $ipset_entry;
                    $array = array_filter($array, function($value) use ($Param_mac) {
                        return strpos($value, $Param_mac) !== 0;
                    });
                    $array = array_values($array);  // Reindex the array keys
                    $JSON_data_remove = json_encode(array( "method" => "set", "params" => array("firewall", $ipset['.name'], "src_mac", $array)));
                    $response_info = PostOpenWRTAPI("https://" . $hostname . "/cgi-bin/luci/rpc/uci", $JSON_data_remove, $cookie_path);
                    
                    // Check if removal was successful
                    //$json_response = json_decode($response_info, true);
                    if ($response_info === "OpenWRT Post API was successful." ) 
                    {
                        $FDIPsetstatus = "success";
                        //echo $response_info;
                        $JSON_data_remove_commit = json_encode(array( "method" => "commit", "params" => array("firewall", $ipset['.name'])));
                        //echo $JSON_data_remove_commit;
                        $response_info_commit = PostOpenWRTAPI("https://" . $hostname . "/cgi-bin/luci/rpc/uci", $JSON_data_remove_commit, $cookie_path); 
                        if ($response_info_commit === "OpenWRT Post API was successful.") 
                        {
                            $FDIPsetstatus = "success";
                            //echo $response_info_commit;
                        } 
                        else 
                        {
                            $FDIPsetstatus = "fail";
                            //echo $response_info_commit;
                        }
                    } 
                    else 
                    {
                        $FDIPsetstatus = "fail";
                        //echo $response_info;
                    }
                }
            }
        }
    }

    if ($FDIPsetstatus === "success") 
    {
        $Status = true;
    }
    else
    {
        $Status = false;
    }

    return $Status;

    /* 
    $username = "user";
    $password = "password";
    $login_data = json_encode(array("id" => 1, "method" => "login", "params" => array($username, $password)));    
    if (RemoveIDfromFirewalldIPSet ("00:00:00:00:00", "192.168.1.1", $login_data))
        {
            //if true do somthing
        }
        else
        {
            //if false do nothing
        }
    
    */
}

function deleteCookies($file) 
{
    
    if (is_file($file)) 
    {
        if (unlink($file)) 
        {
            $status = "Sucessfuly Deleted cookie file " . $file;
        } 
        else 
        {
            $status = "Failed to Delete cookie file " . $file;
        }
    }
    else
    {
        $status = "Not a valid cookie file " . $file;
    }

    return $status;
}

function get_webguard_option($option_name, $config_file = '/etc/config/webguard') {
    if (!file_exists($config_file)) {
        return null;
    }
    $lines = file($config_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (preg_match("/option\s+$option_name\s+'([^']+)'/", $line, $matches)) {
            return $matches[1];
        }
    }
    return null;
}
?>

