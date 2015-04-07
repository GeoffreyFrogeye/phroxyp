<?php

// PROXY
// Config
// $serv = 'requestb.in';
// $port = 80;
// $root = '19jt8qq1';
$serv = 'servclubinfo.insecure.deule.net';
$port = 1337;
$root = '/';

// Functions
if (!function_exists('getallheaders')) {
    function getallheaders() {
        if (!is_array($_SERVER)) {
            return array();
        }

        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 
5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Target determination
$metd = $_SERVER['REQUEST_METHOD'];
$reqp = $_SERVER['REQUEST_URI'];
$reqp = '';

// Preparing request headers
$reqHeds = "$metd $root$reqp HTTP/1.1\r\n";
$reqHeds .= "Host: $serv:$port\r\n";

// Converting client request headers to server request headers
$reqsHedsC = getallheaders();
foreach ($reqsHedsC as $name => $content) {
    if ($name != 'Host' && $name != 'Connection') {
        $reqHeds .= "$name: $content\r\n";
    }
}

if ($metd == 'POST') { // TODO Waaaay too long
    $postData = stripslashes($_POST['payload']);
    $reqHeds .= "Content-Length: ".strlen($postData)."\r\n";
    $reqHeds .= "Connection: Close\r\n";
    $reqHeds .= "\r\n" . $postData;
} else {
    $reqHeds .= "Connection: Close\r\n\r\n";
}

$fp = fsockopen($serv, $port, $errno, $errstr, 30);
if (!$fp) {
    echo "Impossible de se connecter au serveur du Club Info :-(\n<br/>$errstr ($errno)<br />\n";
} else {
    // Sending request
    fwrite($fp, $reqHeds);
    $resBuf = '';
    while ($get = fgets($fp, 128)) {
        // Getting response
        if ($resBuf === True) { // If headers sent
            echo $get;
        } else {
            $resBuf .= $get;
            if ($sepPos = strrpos($resBuf, "\r\n\r\n")) { // Headers have been retrieved
                $resHeds = explode("\r\n", substr($resBuf, 0, $sepPos));
                foreach ($resHeds as $resHed) { // Setting headers
                    header($resHed);
                    if (substr($resHed, 0, 4) == 'HTTP') { // FastCGI fix when using ErrorDocument
                        header('Status: ' . substr($resHed, 9));
                    }
                }
                echo substr($resBuf, $sepPos + 4); // Sending the rest (usually empty)
                $resBuf = True; // Setting mode to automatically redirect
            }
        }
    }
    fclose($fp);
}
?>

