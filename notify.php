<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

/**
 * send notification via email
 *
 * @param string subject
 * @param string msg
 * @return void
 */
function sendNotification ($subject = null, $msg = null) {
    // subject at msg are required
    if (!$subject || !$msg) {
        throw new \Exception('Subject and Message cannot be null');
    }

    // get config file
    $conf = @file_get_contents(__DIR__ . '/conf.json');
    $conf = json_decode($conf, true);
    $conf = $conf['mail']; // get mail creds

    // check if email config is set
    if (!isset($conf['host'])
        || !isset($conf['user'])
        || !isset($conf['pass'])
        || !isset($conf['port'])
        || !isset($conf['sender'])
        || !isset($conf['sender'][0])
        || !isset($conf['sender'][1])
    ) {
        throw new \Exception('Email notification config is not set');
    }

    // init php mailer using creds in conf.json
    $mail = new PHPMailer(true);$mail->isSMTP();
    $mail->Host       = $conf['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $conf['user'];
    $mail->Password   = $conf['pass'];
    $mail->Port       = $conf['port'];

    //Recipients
    $mail->setFrom($conf['sender'][0], $conf['sender'][1]);

    $to = isset($conf['to']) && is_array($conf['to']) ? $conf['to'] : [];

    if (empty($to)) {
        $to[] = ['devteam@shoppable.ph', 'Shoppable Dev Team'];
    }

    foreach ($to as $entry) {
        $mail->addAddress($entry[0], $entry[1]);
    }


    //Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $msg;

    // send notification
    $mail->send();
}
