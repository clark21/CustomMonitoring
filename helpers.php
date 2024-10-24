<?php
// txt database
define('DB', __DIR__ . '/.db');
include(__DIR__ . '/notify.php');

/**
  ================= Start simple email tpl on string ==========================
*/
$stillDownMsg = [
    '%s is still DOWN!',
    '<p><b>%s</b>(<em>%s</em>) is still down as of this moment.</p>' 
        . '<p>Down since <b>%s</b> server time.</p>'
        . '<p>Last check: <b>%s</b> server time.</p>'
];


$downMsg = [
    '%s is DOWN!',
    '<p><b>%s</b>(<em>%s</em>) is down as of this moment.</p>' 
        . '<p>Down since <b>%s</b> server time.</p>'
];

$upMsg = [
    '%s is UP!',
    '<p><b>%s</b>(<em>%s</em>) is now up and running. See more info below:</p>'
        . '<p>Down since: <b>%s</b> server time'
        . '<p>Up time: <b>%s</b> server time</p><br /><hr/><br />'
        . '<p style="color: #999">Please consider that the server checker runs on time intervals. The time reported may vary depending on those intervals.</p>'
];

/**
  ================= End email tpl on sting ==========================
*/

/**
 * get data from text database
 *
 * @return array
 */
function getDbData() {
    $db = file_get_contents(DB);
    $db = base64_decode($db);
    // separate entries
    $db = explode("\n\r", $db);
    $dbData = [];
    foreach ($db as $data) {
        // separate columns
        $dbData[] = explode("|", $data);
    }

    return $dbData;
}

/**
 * get data from specific url
 *
 * @param url string
 * @return array
 */
function getDataFor($url) {
    // get data from database
    $data = getDbData();
    // find url from data entry
    foreach ($data as $entry) {
        if (isset($entry[1]) && $entry[1] == $url) {
            return $entry;
        }
    }

    // return empty array if data doesnt exist yet
    return [];
}


/**
 * Save data to datanase
 *
 * @param data array
 * @return void
 */
function saveDataToDb($data) {
    // validate data
    for($i = 0; $i < 5; $i++) {
        if (!isset($data[$i])) {
            throw new \Exception('Invalid data: ' . json_encode($data));
        }
    }

    // get current data from datanase
    $dbData = getDbData();
    
    $new = true;
    foreach ($dbData as $k => $entry) {
        // if entry is invalid dont proceed
        if (!isset($entry[1])) {
            continue;
        }

        // if url matches
        if ($entry[1] == $data[1]) {
            // replace with new data
            $dbData[$k] = $data;
            $new = false;
            break;
        }
    }

    // if new entry
    if ($new) {
        // add to data entries
        $dbData[] = $data;
    }

    // cleanup data before saving
    $newData = [];
    foreach ($dbData as $k => $entry) {
        if (!isset($entry[1])) {
            continue;
        }

        $newData[] = implode("|", $entry);
    }

    // save to database
    file_put_contents(DB, base64_encode(implode("\n\r", $newData)));
}

/**
 * handle errors
 *
 * @param name string
 * @param url string
 * @param info array
 * @return array
 */
function handleError($name, $url, $info = []) {
    global $stillDownMsg, $downMsg;
    // get data for this url
    $data = getDataFor($url);
    // send still down
    $timeDown = time();
    $checkTime = time();

    // if it was down before, send still down notification
    if (isset($data[2]) && $data[2] == 'down') {
        list($n, $url, $status, $downTime, $lastCheck) = $data;
        $timeDown = $downTime;
        echo "Sending still DOWN notification...\n\r";
        try {
            sendNotification(
                sprintf($stillDownMsg[0], $name),
                sprintf(
                    $stillDownMsg[1],
                    $url,
                    $name,
                    date('M d, Y H:i:s A', $timeDown),
                    date('M d, Y H:i:s A', $lastCheck)
                )
            );
        } catch (\Exception $e) {
            echo "Cannot send notification message. Please check error below\n\r";
            echo $e->getMessage();
        }
        
        // if it was up before, send down notification
    } else {
        //list($n, $url, $status, $downTime, $lastCheck) = $data;
        // TODO: send down notification
        echo "Sending DOWN notification...\n\r";
        try {
            sendNotification(
                sprintf($downMsg[0], $name),
                sprintf(
                    $downMsg[1],
                    $url,
                    $name,
                    date('M d, Y H:i:s A', $timeDown),
                    date('M d, Y H:i:s A', $checkTime)
                )
            );
        } catch (\Exception $e) {
            echo "Cannot send notification message. Please check error below\n\r";
            echo $e->getMessage();
        }
    }

    // save new data to db
    saveDataToDb([$name, $url, 'down', $timeDown, $checkTime]);
}

/**
 * handle success
 *
 * @param name string
 * @param url string
 * @param info array
 * @return array
 */
function handleSuccess($name, $url, $info = []) {
    global $upMsg;
    // get data for this url
    $data = getDataFor($url);
    
    $timeUp = time();
    $checkTime = time();

    // if it was down before, send up notification
    if (isset($data[2]) && $data[2] == 'down') {
        list($n, $url, $status, $downTime, $lastCheck) = $data;
        // TODO: send up notification
        echo "Sending UP notification...\n\r";
        try {
            sendNotification(
                sprintf($upMsg[0], $name),
                sprintf(
                    $upMsg[1],
                    $url,
                    $name,
                    date('M d, Y H:i:s A', $timeUp),
                    date('M d, Y H:i:s A', $checkTime)
                )
            );
        } catch (\Exception $e) {
            echo "Cannot send notification message. Please check error below\n\r";
            echo $e->getMessage();
        }
    }

    // save to database
    saveDataToDb([$name, $url, 'up', $timeUp, $checkTime]);
}
