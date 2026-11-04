<?php
// Include config file
include __DIR__ . "/config.php";

blacklistip();

function blacklistip()
{
    // Define variables and initialize with empty values
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL ^ E_NOTICE);
    $i = 1;
    $Status = "";
    // ensure the directory for IP_BLACKLIST exists (it won't be created automatically)
    $IP_BLACKLIST_JSON = '/etc/blacklist-ip/blacklist-ip.json';

    // Executing curl OpenWRT Login
    $username = "root";
    $password = "Dy5l3x1c";
    $OWRT_hostname = "127.0.0.1";
    

    if (CreateBlacklistIP('/etc/blacklist-ip/blacklist-ip-conf.php', $IP_BLACKLIST_JSON))
    {
        try
        {
            // Initialize JSON manager for different Docker Manager JSON files
            $blacklistipJson = new JsonManager($IP_BLACKLIST_JSON);
            
            // Get multiple entries
            $ArrayofResults = $blacklistipJson->getEntries();
            foreach($ArrayofResults as $results)
            {
                $allips[] = $results['IP'];
            }
            $OWRT_cookie_path = LoginOpenWRTAPI($OWRT_hostname, $username, $password);
            if (!empty($allips))
            {
                $response_info = updateFirewallBlacklistIPs($allips, "blacklist-ip-rated", $OWRT_hostname, $OWRT_cookie_path);

                // Check if data was exported successful
                if($response_info)
                {
                    //Display status to screen
                    $Status = $Status . deleteCookies($OWRT_cookie_path) . "\n";
                    $Status .= 'Added IP list to OpenWRT Firewall Blacklist' . "\n";
                }
                else
                {
                    //Display error to screen
                    $Status = $Status . deleteCookies($OWRT_cookie_path) . "\n";
                    $Status .= 'Failed to add IP to OpenWRT Firewall Blacklist' . "\n";
                    die('Failed to add IP to OpenWRT Firewall Blacklist');  
                }
            }
            else
            {
                $Status .= "Firewall Blacklist not updated, no data found.\n";
            }
        }
        catch (Exception $e) 
        {
            echo "Error: " . $e->getMessage();
        }
    }
    else
    {
        $Status .= "Error creating blacklist\n";
    }

    //Display Status information for debugging
    //echo StatusError($Status) . "</br>";
    echo $Status;
}

function CreateBlacklistIP($configFile, $IP_BLACKLIST_JSON) 
{
    // Check if the configuration file is provided
    if (empty($configFile)) {
        echo "Error: please specify a configuration file.\n";
        return false;
    }

    // Load the configuration file
    if (!file_exists($configFile) || !is_readable($configFile)) {
        echo "Error: can't load configuration file $configFile\n";
        return false;
    }
    
    // Include the configuration file
    include $configFile;

    try
    {
        // Initialize JSON manager for different Docker Manager JSON files
        $blacklistipJson = new JsonManager($IP_BLACKLIST_JSON);

        // Remove all blacklists from a JSON file
        $success = $blacklistipJson->removeAllEntries();
        if($success)
        {
            echo "All blacklisted IPs removed successfully";
        }
        else
        {
            echo "No blacklisted IPs removed";
            //return;
        }

        // Check if BLACKLISTS is defined
        if (!isset($BLACKLISTS) || !is_array($BLACKLISTS)) {
            echo "Error: BLACKLISTS array is not defined in the configuration file.\n";
            return false;
        }

        $ipBlacklist = [];
        $idCounter = 1;
        $doOptimizeCIDR = false;

        // Check if iprange exists and if optimization is enabled
        if (function_exists('iprange') && (isset($OPTIMIZE_CIDR) && $OPTIMIZE_CIDR !== 'no')) {
            $doOptimizeCIDR = true;
        }

        // Create a temporary file for the IP blacklist
        $ipBlacklistTmp = tempnam(sys_get_temp_dir(), 'ip_blacklist_');

        foreach ($BLACKLISTS as $url) {
            // Fetch the content from the URL
            $httpResponse = null;
            $ipTmp = tempnam(sys_get_temp_dir(), 'ip_tmp_');
            
            // Initialize cURL session
            $ch = curl_init();

            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
            curl_setopt($ch, CURLOPT_USERAGENT, 'blacklist-update/script/github'); // Set User-Agent
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Set connection timeout
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set maximum execution time

            // Execute cURL request
            $response = curl_exec($ch);

            // Get HTTP response code
            $httpResponse = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Check for errors
            if (curl_errno($ch)) {
                echo "cURL error: " . curl_error($ch) . "\n";
            }

            // Save the response to the temporary file
            file_put_contents($ipTmp, $response);

            // Close cURL session
            curl_close($ch);   

            if (in_array($httpResponse, ['200', '302', '0'])) { // "0" for file:// URLs
                // Extract IP addresses using regex
                $ipAddresses = file($ipTmp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($ipAddresses as $line) {
                    if (preg_match('/^(?:\d{1,3}\.){3}\d{1,3}(?:\/\d{1,2})?/', $line, $matches)) {
                        $ipBlacklist[] = preg_replace('/^0*([0-9]+)\.0*([0-9]+)\.0*([0-9]+)\.0*([0-9]+)$/', '$1.$2.$3.$4', $matches[0]);
                    }
                }
            } elseif ($httpResponse == '503') {
                echo "Unavailable ($httpResponse): $url\n";
            } else {
                echo "Warning: curl returned HTTP response code $httpResponse for URL $url\n";
            }

            // Clean up temporary file
            unlink($ipTmp);
        }

        // Filter out unwanted addresses
        $ipBlacklist = array_filter($ipBlacklist, function($ip) {
            return !preg_match('/^(0\.0\.0\.0|10\.|127\.|172\.1[6-9]\.|172\.2[0-9]\.|172\.3[0-1]\.|192\.168\.|22[4-9]\.|23[0-9]\.)/', $ip);
        });

        // Optionally optimize CIDR
        if ($doOptimizeCIDR) {
            // Call the iprange function to optimize the CIDR
            $optimizedRanges = iprange($ipBlacklist);
            $ipBlacklist = $optimizedRanges; // Replace the original list with the optimized one

            if (isset($VERBOSE) && $VERBOSE === 'yes') {
                echo "Addresses after CIDR optimization: " . count($ipBlacklist) . "\n";
            }
        }

        foreach ($ipBlacklist as $ipjson)
        {
            // Save the blacklist to a JSON file
            $newId = $blacklistipJson->addEntry(["IP" => $ipjson]);
            echo "Blacklist IP added successfully with ID: $newId \n";
        }

        echo "Blacklist IPs addresses saved to $IP_BLACKLIST_JSON\n";
        return true;
    } 
    catch (Exception $e) 
    {
        echo "Error: " . $e->getMessage();
    }
}

function iprange($ips) {
    // Sort the IP addresses
    sort($ips);
    
    $cidrList = [];
    $count = count($ips);
    
    // Convert IP addresses to long format for easier manipulation
    $longIps = array_map('ip2long', $ips);
    
    // Initialize the start of the range
    $start = $longIps[0];
    $end = $longIps[0];
    
    for ($i = 1; $i < $count; $i++) {
        // Check if the current IP is contiguous
        if ($longIps[$i] == $end + 1) {
            // Extend the end of the range
            $end = $longIps[$i];
        } else {
            // If not contiguous, add the current range to the CIDR list
            $cidrList[] = long2cidr($start, $end);
            // Start a new range
            $start = $longIps[$i];
            $end = $longIps[$i];
        }
    }
    
    // Add the last range
    $cidrList[] = long2cidr($start, $end);
    
    return $cidrList;
}

function long2cidr($start, $end) {
    // Calculate the CIDR notation
    $rangeSize = $end - $start + 1;
    $cidr = 32 - log($rangeSize, 2);
    
    // Return the CIDR notation
    return long2ip($start) . '/' . $cidr;
}

// Example usage
//CreateBlacklistIP('/etc/ipset-blacklist-firewalld/ipset-blacklist-firewalld.conf');
?>