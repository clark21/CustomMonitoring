<?php
include(__DIR__ . '/helpers.php');
include(__DIR__ . '/curl.php');
include(__DIR__ . '/vendor/autoload.php');

// get config file
$config = @file_get_contents(__DIR__ . '/conf.json');
$config = json_decode($config, true);

// check if urls are set in config file
if (!isset($config['urls']) || empty($config['urls'])) {
	throw new Exception('No URLs to monitor');
}	

// check urls 1 by 1
foreach ($config['urls'] as $name => $url) {
    echo sprintf("Processing %s [%s]...\n\r", $name, $url);
	$monitor = new Monitor($url);
	$monitor->exec(); // execute monitoring script

	if ($monitor->isError()) {
		// handle error
		handleError($name, $url);
		sleep(1);
        continue;
	}

	$info = $monitor->getInfo();
	if (!isset($info['http_code']) || $info['http_code'] != 200) {
		// handle error
		handleError($name, $url);
		sleep(1);
		continue;
	}

    handleSuccess($name, $url, $info);
	sleep(1);
}
