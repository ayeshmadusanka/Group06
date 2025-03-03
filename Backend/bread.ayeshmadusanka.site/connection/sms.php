<?php
// SMS credentials
$sms_user = "94756668708";
$sms_password = "9183";

// Function to send SMS
function sendSMS($to, $message) {
    global $sms_user, $sms_password;

    $user = $sms_user;
    $password = $sms_password;
    $text = urlencode($message);

    $baseurl = "http://www.textit.biz/sendmsg";
    $url = "$baseurl/?id=$user&pw=$password&to=$to&text=$text";
    $ret = file($url);

    return $ret;
}
?>
