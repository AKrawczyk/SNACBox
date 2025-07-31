<?php
// Check if the correct number of arguments is provided
if ($argc == 2)
{
    // Get the input values from command line arguments
    $aidcli = $argv[1];
    scheduleaccessCLI("CLI", null, null, null, null, null, null, null, null, null, null, null, null, null);
}
elseif ($argc == 6)
{
    // Get the input values from command line arguments
    scheduleaccessCLI("CLI", $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], null, null, null, null, null, null, null, null);
}
elseif ($argc == 7)
{
    // Get the input values from command line arguments
    scheduleaccessCLI("CLI", $argv[1], $argv[2], $argv[3], $argv[4], null, $argv[5], $argv[6], null, null, null, null, null, null);
}
elseif ($argc == 8 && $argv[4] === "weekly")
{
    // Get the input values from command line arguments
    scheduleaccessCLI("CLI", $argv[1], $argv[2], $argv[3], $argv[4], null, null, null, $argv[5], $argv[6], $argv[7], null, null, null);
}
elseif ($argc == 8 && $argv[4] === "monthly")
{
    // Get the input values from command line arguments
    scheduleaccessCLI("CLI", $argv[1], $argv[2], $argv[3], $argv[4], null, null, null, null, null, null, $argv[5], $argv[6], $argv[7]);
}
elseif ($argc == 3)
{
    // Get the input values from command line arguments
    scheduledelete($argv[1]);
}
else
{
    scheduleaccessAccess();
}
function scheduleaccessCLI($inputtype, $schedule_access, $schedule_rating, $schedule_name, $schedule_type, $run_once_date, $daily_hour, $daily_minute, $weekly_hour, $weekly_minute, $weekly_day,  $monthly_hour, $monthly_minute, $monthly_day)
{
    // Include config file
    require_once "config.php";

    $Status = "";
    $scheduleSystem = null;
    $scheduleFriendly = null;
    $Rating_name = null;
    $Docker = null;
    $schedule = null;
    $scheduleATID = null;
    $dockermgrjsonpath = '/usr/bin/web-rated-docker.json';
    $schedulejsonPath = '/usr/bin/web-rated-schedule.json';

    try
    {
        // Initialize JSON manager for different Docker Manager JSON files
        $dockermgrJson = new JsonManager($dockermgrjsonpath);
        
        // Get multiple entries
        $ArrayofResults = $dockermgrJson->getEntries([
            'Schedule' => '1'
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

    echo '</br>';
    echo '<h3>Create New Access Schedule</h3>';
    echo '</br>';
    echo '<form method="post" action="">';

        echo '<div class="form-row">';
            echo '<div class="column">';
                echo '<label for="schedule_name">Description:</label>';
            echo '</div>';
            echo '<div class="column">';
                echo '<input class="cbi-input-text" type="text" name="schedule_name" required>';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-row">';
            echo '<div class="column">';
                echo '<label for="schedule_rating">Rating:</label>';
            echo '</div>';
            echo '<div class="column">';
                echo '<select class="cbi-input-select" name="schedule_rating" id="schedule_rating" required>';
                    echo '<option selected disabled>Choose Rating</option>';
                    echo $SelectRatings;
                echo '</select>';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-row">';
            echo '<div class="column">';
                echo '<label for="schedule_access">Internet Access:</label>';
            echo '</div>';
            echo '<div class="column">';
                echo '<select class="cbi-input-select" name="schedule_access" id="schedule_access" required>';
                    echo '<option selected disabled>Choose Access</option>';
                    echo '<option value="stop">Off</option>';
                    echo '<option value="start">On</option>';
                echo '</select>';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-row">';
            echo '<div class="column">';
                echo '<label for="schedule_type">Repeat:</label>';
            echo '</div>';
            echo '<div class="column">';
                echo '<select class="cbi-input-select" name="schedule_type" id="schedule_type" required>';
                    echo '<option value="run_once">None</option>';
                    echo '<option value="daily">Daily</option>';
                    echo '<option value="weekly">Weekly</option>';
                    echo '<option value="monthly">Monthly</option>';
                echo '</select>';
            echo '</div>';
        echo '</div>';

        echo '<div id="schedule-options" class="schedule-options">';
        echo '<!-- Schedule options will be displayed here -->';
        echo '</div>';

        echo '<div class="form-row">';
            echo '<div class="column"></div>';
            echo '<div class="column">';
                echo '<input class="btn" type="submit" value="Schedule">';
            echo '</div>';
        echo '</div>';

    echo '</form>';

    if($schedule_type != null)
    {
        // Function to validate a date and time
        function validateDateTime($datetime) 
        {
            return strtotime($datetime) !== false;
        }

        $SetCommandEnable = "start";
        $SetCommandDisable = isset($schedule_access) ? $schedule_access : '';
        $Rating_id = isset($schedule_rating) ? $schedule_rating : '';
        
        try
        {
            $dockermgrJson = new JsonManager($dockermgrjsonpath);
            // Get single entry
            $fArrayofResults = $dockermgrJson->getEntry($Rating_id);
            $Rating_name = strval($fArrayofResults['Name']);
            $Docker = strval($fArrayofResults['Docker Name']);
        }
        catch (Exception $e) 
        {
            $Status = "Error: " . $e->getMessage();
        }
        $scheduleName = isset($schedule_name) ? $schedule_name : '';
        $scheduleType = isset($schedule_type) ? $schedule_type : '';

        if ($scheduleType === 'run_once') {
            $runOnceDate = isset($run_once_date) ? $run_once_date : '';

            if (validateDateTime($runOnceDate)) {
                // Handle time zone if needed
                //date_default_timezone_set('YourTimezone');
                $posixTimeFormat = date('H:i Y-m-d', strtotime($runOnceDate));
                $schedule = $posixTimeFormat;
                $scheduleSystem = "at";
                $scheduleFriendly = "Run Once at " . convertPostfixTime($posixTimeFormat);
                //$scheduleCommand = "docker " . $SetCommandDisable . " " . $Docker . " | " . $scheduleSystem . " " . $schedule;
                $scheduleATID = "3";
            }
        } 
        elseif ($scheduleType === 'daily') 
        {
            $dailyHour = isset($daily_hour) ? $daily_hour : '';
            $dailyMinute = isset($daily_minute) ? $daily_minute : '';
            $schedule = "$dailyMinute $dailyHour * * *";
            $scheduleFriendly = "Run Daily at " . $dailyHour . ":" . $dailyMinute;
            $scheduleSystem = "cron";
            //$scheduleCommand = $schedule . "docker " . $SetCommandDisable . " " . $Docker;
            $scheduleATID = "";
        } 
        elseif ($scheduleType === 'weekly') 
        {
            $weeklyHour = isset($weekly_hour) ? $weekly_hour : '';
            $weeklyMinute = isset($weekly_minute) ? $weekly_minute : '';
            $weeklyDay = isset($weekly_day) ? $weekly_day : '';
            $schedule = "$weeklyMinute $weeklyHour * * $weeklyDay";
            $scheduleFriendly = "Run Weekly at " . $weeklyHour . ":" . $weeklyMinute . " on " . date('l', strtotime("Sunday +$weeklyDay days"));
            $scheduleSystem = "cron";
            //$scheduleCommand = $schedule . "docker " . $SetCommandDisable . " " . $Docker;
            $scheduleATID = "";
        } 
        elseif ($scheduleType === 'monthly') 
        {
            $monthlyHour = isset($monthly_hour) ? $monthly_hour : '';
            $monthlyMinute = isset($monthly_minute) ? $monthly_minute : '';
            $monthlyDay = isset($monthly_day) ? $monthly_day : '';
            $schedule = "$monthlyMinute $monthlyHour $monthlyDay * *";
            $scheduleFriendly = "Run Monthly at " . $monthlyHour . ":" . $monthlyMinute . " on the " . ordinal($monthlyDay);
            $scheduleSystem = "cron";
            //$scheduleCommand = $schedule . "docker " . $SetCommandDisable . " " . $Docker;
            $scheduleATID = "";
        }

        if($scheduleSystem == "at")
        {
            //$command = "echo docker '" . $SetCommandDisable . ' ' . $Docker . "' | at " . $schedule;
            $command = "echo /usr/bin/php8-cli /usr/bin/update-docker-state.php '" . $Docker . ' ' . $SetCommandDisable . "'  | at " . $schedule;

            //Execute the at command line job
            //$ATJobStatus = shell_exec("echo '" . $Docker . "' | sudo at " . $schedule);

            // Open the process
            $ATJobStatus = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

            if (is_resource($ATJobStatus)) {
                // Read stdout and stderr
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);

                // Close the process
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($ATJobStatus);

                // Display the output
                $Status = "</br>" . $Status . "</br>" . "Standard Output: " . $stdout;

                $Status = "</br>" . $Status . "</br>" . "Standard Error: " . $stderr;
                // Use a regular expression to extract the job number
                if (preg_match('/job (\d+)/', $stderr, $matches)) 
                {
                    $scheduleATID = $matches[1];
                    $Status = "</br>" . $Status . "</br>" . "Job Number: $scheduleATID";
                } 
                else 
                {
                    $Status = "</br>" . $Status . "</br>" . "Job number not found." . $stderr;
                }
            } 
            else 
            {
                $Status = "</br>" . $Status . "</br>" . "Error executing command: " . $command;
            }
        }

        // Generate the task data as an associative array
        $taskDataArray = [
            $scheduleName,
            $scheduleFriendly,
            $Rating_name,
            $SetCommandDisable,
            $Docker,
            $schedule,
            $scheduleSystem,
            $scheduleATID,
        ];

        $cronDataArray = [
            'Schedule' => $schedule,
            'ScheduleFunction' => $SetCommandDisable,
            'Docker_name' => $Docker
        ];
        
        try 
        {
            
            $scheduleManager = new JsonManager($schedulejsonPath);
        
            // Create new schedule entry
            $schedule = [
                'ScheduleName' => $taskDataArray[0],
                'ScheduleFriendly' => $taskDataArray[1],
                'ADClient_name' => $taskDataArray[2],
                'ScheduleFunction' => $taskDataArray[3],
                'Docker_name' => $taskDataArray[4],
                'Schedule' => $taskDataArray[5],
                'ScheduleSystem' => $taskDataArray[6],
                'ScheduleATID' => $taskDataArray[7]
            ];
            
            $newId = $scheduleManager->addEntry($schedule);
            $status = "Schedule added successfully with ID: $newId";
        }
        catch (Exception $e) 
        {
            $Status = "Error: " . $e->getMessage();
        }

        //Write AT command or append CRON Jobs to file
        if($scheduleSystem == "cron")
        {
            //Write Schedule Tasks to cron file
            //Table of Scheduled Tasks
            try
            {
                $scheduleManager = new JsonManager($schedulejsonPath);
                // Getting schedules (equivalent to SELECT query - Filtered, no array - all results)
                $cronSchedules = $scheduleManager->getEntries();
                $ArrayofResults = array_map(function($entry) {
                    return [
                        'ScheduleFunction' => $entry['ScheduleFunction'] ?? '',
                        'Docker_name' => $entry['Docker_name'] ?? '',
                        'Schedule' => $entry['Schedule'] ?? ''
                    ];
                }, $cronSchedules);
                $Status = "</br>" . $Status . "</br>" . ArrayToCronFile("/etc/crontabs/root", $ArrayofResults, "");
            }
            catch (Exception $e) 
            {
                $Status = "Error: " . $e->getMessage();
            }
        }
    }

    try
    {
        $scheduleManager = new JsonManager($schedulejsonPath);
        // Getting schedules (equivalent to SELECT query - Filtered, no array - all results)
        $cronSchedules = $scheduleManager->getEntries();
        $ArrayofResults = $cronSchedules;
    }
    catch (Exception $e) 
    {
        $Status = "Error: " . $e->getMessage();
    }

    //echo $Status;

    echo '</br>';
    echo '<h3>Access Schedules</h3>';
    echo '</br>';
    echo '<table border="1">';
    echo '<tr>';
    echo '<td>' . htmlspecialchars("Schedule Name") . '</td>';
    echo '<td>' . htmlspecialchars("Schedule Description") . '</td>';
    echo '<td>' . htmlspecialchars("Rating") . '</td>';
    echo '<td>' . htmlspecialchars("Start/Stop Access") . '</td>';
    echo '<td>' . htmlspecialchars("Modify") . '</td>';
    echo '</tr>';
    foreach ($ArrayofResults as $value)
    {
        echo '<tr>';
        echo '<td>' . htmlspecialchars(strval($value['ScheduleName'])) . '</td>';
        echo '<td>' . htmlspecialchars(strval($value['ScheduleFriendly'])) . '</td>';
        echo '<td>' . htmlspecialchars(strval($value['ADClient_name'])) . '</td>';
        echo '<td>' . htmlspecialchars(strval($value['ScheduleFunction'])) . '</td>';
        echo '<td>';
            echo '<div>';
                echo '<!-- Delete Icon -->';
                echo '<center>
                        <form method="POST" id="DeleteSchedules">
                            <input type="hidden" name="schedule_type" value="delete">
                            <input type="hidden" name="id" value="' . $value['ID'] . '">
                            <button type="submit" class="delete-link" onclick="return confirm(\'Are you sure you want to delete this schedule?\');" style="background: transparent; border: none; padding: 0; cursor: pointer;"><span>&#128465;</span></button>
                        </form>
                    </center>'; 
            echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<!-- Included Java script needed to process the schedule options systems -->';
    //echo '<script src="schedule.js"></script>';
    echo '<script>
                    const scheduleTypeSelect = document.getElementById("schedule_type");
                    const scheduleOptions = document.getElementById("schedule-options");

                    function updateScheduleOptions() {
                        scheduleOptions.innerHTML = ""; // Clear previous options

                        const selectedOption = scheduleTypeSelect.value;

                        if (selectedOption === "run_once") {
                            // Add run once options
                            scheduleOptions.innerHTML = `
                                <div class="form-row">
                                <div class="column">
                                <label for="run_once_date">Date and Time:</label>
                                </div>
                                <div class="column">
                                <input class="cbi-input-calander" type="datetime-local" name="run_once_date" required>
                                </div>
                                </div>
                                
                            `;
                        } else if (selectedOption === "daily") {
                            // Add daily options
                            scheduleOptions.innerHTML = `
                                <div class="form-row">
                                <div class="column">
                                <label for="daily_hour">Hour:</label>
                                </div>
                                <div class="column">
                                <input class="cbi-input-text" type="number" name="daily_hour" min="0" max="23" required>
                                </div>
                                </div>
                                <div class="form-row">
                                <div class="column">
                                <label for="daily_minute">Minute:</label>
                                </div>
                                <div class="column">
                                <input class="cbi-input-text" type="number" name="daily_minute" min="0" max="59" required>
                                </div>
                                </div>
                            `;
                        } else if (selectedOption === "weekly") {
                            // Add weekly options
                            scheduleOptions.innerHTML = `
                                <div class="form-row">
                                <div class="column">    
                                <label for="weekly_hour">Hour:</label>
                                </div>
                                <div class="column">
                                <input class="cbi-input-text" type="number" name="weekly_hour" min="0" max="23" required>
                                </div>
                                </div>
                                <div class="form-row">
                                <div class="column">
                                <label for="weekly_minute">Minute:</label>
                                </div>
                                <div class="column">
                                <input class="cbi-input-text" type="number" name="weekly_minute" min="0" max="59" required>
                                </div>
                                </div>
                                <div class="form-row">
                                <div class="column">
                                <label for="weekly_day">Day of the Week:</label>
                                </div>
                                <div class="column">
                                <select class="cbi-input-select" name="weekly_day" required>
                                    <option value="0">Sunday</option>
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                    <option value="6">Saturday</option>
                                </select>
                                </div>
                                </div>
                            `;
                        } else if (selectedOption === "monthly") {
                            // Add monthly options
                            scheduleOptions.innerHTML = `
                                <div class="form-row">
                                <div class="column">
                                <label for="monthly_hour">Hour:</label>
                                </div>
                                <div class="column">
                                <input class="cbi-input-text" type="number" name="monthly_hour" min="0" max="23" required>
                                </div>
                                </div>
                                <div class="form-row">
                                <div class="column">
                                <label for="monthly_minute">Minute:</label>
                                </div>
                                <div class="column">
                                <input class="cbi-input-text" type="number" name="monthly_minute" min="0" max="59" required>
                                </div>
                                </div>
                                <div class="form-row">
                                <div class="column">
                                <label for="monthly_day">Day of the Month:</label>
                                </div>
                                <div class="column">
                                <input class="cbi-input-text" type="number" name="monthly_day" min="1" max="31" required>
                                </div>
                                </div>
                            `;
                        }
                    }

                    // Initial update
                    updateScheduleOptions();

                    scheduleTypeSelect.addEventListener("change", updateScheduleOptions);
         </script>';

    //Enable status output for debugging
    //echo $Status;
    //echo '<div id="response-message"></div>';
}

function convertPostfixTime($postfixTime) 
{
    // Parse the postfix timestamp
    $datetime = DateTime::createFromFormat('H:i Y-m-d', $postfixTime, new DateTimeZone('UTC'));

    // Convert to the user's timezone (you can change 'America/New_York' to your desired timezone)
    //$datetime->setTimezone(new DateTimeZone('YourTimezone'));

    // Format the datetime for display
    $formattedTime = $datetime->format('H:i');
    $formattedDate = $datetime->format('Y-m-d');
    $friendly = $formattedTime . " on " . $formattedDate;

    return $friendly;
}

function ordinal($number) 
{
    $lastDigit = $number % 10;
    $secondToLastDigit = floor($number % 100 / 10);

    if ($secondToLastDigit == 1) 
    {
        $suffix = 'th';
    } else {
        switch ($lastDigit) {
            case 1:
                $suffix = 'st';
                break;
            case 2:
                $suffix = 'nd';
                break;
            case 3:
                $suffix = 'rd';
                break;
            default:
                $suffix = 'th';
        }
    }

    return $number . $suffix;
}

function ArrayToCronFile($filename, $data, $deleteJob) 
{
    $cronEmpty = "";
    $existing_crons = [];
    $status = '';

    try {
        // 1. Read existing cron jobs
        if (file_exists($filename)) 
        {
            $existing_crons = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($existing_crons === false) 
            {
                throw new Exception("Failed to read existing cron jobs");
            }
        }
    
        // 2. Clear the cron file
        if (file_put_contents($filename, $cronEmpty) !== false) 
        {
            $status = 'Cron jobs has been cleared </br>' . $filename;
        } 
        else 
        {
            throw new Exception('Unable to clear cron jobs to </br>' . $filename);
        }

        // 3. Process new cron jobs
        if ($deleteJob !== "")
        {
            // Remove matching entries
            $existing_crons = array_filter($existing_crons, function($line) use ($deleteJob) 
            {
                return trim($line) !== trim($deleteJob);
            });
        }

        foreach ($data as $field) 
        {
            $cronString = sprintf('%s /usr/bin/php8-cli /usr/bin/update-docker-state.php %s %s',
                $field['Schedule'],
                trim(escapeshellarg($field['Docker_name']), '"\''),
                trim(escapeshellarg($field['ScheduleFunction']), '"\'')
            );
            
            // Remove matching entries
            $existing_crons = array_filter($existing_crons, function($line) use ($cronString) 
            {
                return trim($line) !== trim($cronString);
            });

            // Add new cron job
            $cron_command = sprintf("(crontab -l ; echo %s) | crontab -", escapeshellarg($cronString));
            exec($cron_command, $output, $returnVal);
            if ($returnVal !== 0) 
            {
                throw new Exception("Failed to add new cron job: $cronString");
            }
        }
        
        // 4. Add remaining existing jobs
        if (!empty($existing_crons)) 
        {   
            foreach ($existing_crons as $cronLine) 
            {
                $status .= $cronLine . "<br>";
                $cron_command = sprintf("(crontab -l ; echo %s) | crontab -", escapeshellarg($cronLine));
                exec($cron_command, $output, $returnVal);
                if ($returnVal !== 0) 
                {
                    throw new Exception("Failed to add additional cron job: $cronLine");
                }
            }
            $status .= ' Additional cron jobs preserved.</br>';
        } 
        else 
        {
            $status .= ' No additional cron jobs.</br>';
        }
        
        return $status;
    } 
    catch (Exception $e) 
    {
        return "Error: " . $e->getMessage();
    }
}

function scheduledelete($id)
{
    // Include config file
    require_once "config.php";
    $Status = "";
    $dockermgrjsonpath = '/usr/bin/web-rated-docker.json';
    $schedulejsonPath = '/usr/bin/web-rated-schedule.json';

    try
    {
        
        $scheduleManager = new JsonManager($schedulejsonPath);

        // Get single entry
        $ArrayofResult = $scheduleManager->getEntry($id);

        $scheduleat_id = htmlspecialchars($ArrayofResult['ScheduleATID']);
        $scheduleSystem = htmlspecialchars($ArrayofResult['ScheduleSystem']);
    }
    catch (Exception $e) 
    {
        $Status = $Status . "Error: " . $e->getMessage() . "<br>";
    }

    if($scheduleSystem == "at")
    {
        $command = "atrm " . $scheduleat_id;

        //Execute the at command line job
        // Open the process
        $ATJobStatus = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (is_resource($ATJobStatus)) {
            // Read stdout and stderr
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            // Close the process
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($ATJobStatus);

            // Display the output
            $Status = "</br>" . $Status . "</br>" . "Standard Output: " . $stdout;
            $Status = "</br>" . $Status . "</br>" . "Standard Error: " . $stderr;
        } 
        else 
        {
            $Status = "</br>" . $Status . "</br>" . "Error executing command: " . $command;
        }
    }

    try
    {
        $cronDataArray = [
            'Schedule' => $ArrayofResult['Schedule'],
            'ScheduleFunction' => $ArrayofResult['ScheduleFunction'],
            'Docker_name' => $ArrayofResult['Docker_name']
        ];

        $cronString = sprintf('%s /usr/bin/php8-cli /usr/bin/update-docker-state.php %s %s',
        $ArrayofResult['Schedule'],
            trim(escapeshellarg($ArrayofResult['Docker_name']), '"\''),
            trim(escapeshellarg($ArrayofResult['ScheduleFunction']), '"\'')
        );

        $scheduleManager = new JsonManager($schedulejsonPath);
        // Removing a schedule
        if ($scheduleManager->removeEntry($id)) {
            $Status = "Schedule removed successfully";
        } else {
            $Status = "Schedule not found";
        }
    }
    catch (Exception $e) 
    {
        $Status = $Status . "Error: " . $e->getMessage() . "<br>";
    }
    //Write AT command or append CRON Jobs to file
    if($scheduleSystem == "cron")
    {
        //Write Schedule Tasks to cron file
        //Table of Scheduled Tasks
        try
        {
            $scheduleManager = new JsonManager($schedulejsonPath);
            // Getting schedules (equivalent to SELECT query - Filtered, no array - all results)
            $cronSchedules = $scheduleManager->getEntries();
            $ArrayofResults = array_map(function($entry) {
                return [
                    'ScheduleFunction' => $entry['ScheduleFunction'] ?? '',
                    'Docker_name' => $entry['Docker_name'] ?? '',
                    'Schedule' => $entry['Schedule'] ?? ''
                ];
            }, $cronSchedules);
            $Status = "</br>" . $Status . "</br>" . ArrayToCronFile("/etc/crontabs/root", $ArrayofResults, $cronString);
        }
        catch (Exception $e) 
        {
            $Status = "Error: " . $e->getMessage();
        }
    }

    scheduleaccessCLI("CLI", null, null, null, null, null, null, null, null, null, null, null, null, null);
    //echo $Status;
    return $Status;
}
?>
