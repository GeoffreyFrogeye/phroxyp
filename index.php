<?php

// PROXY
// Config
$serv = 'servclubinfo.insecure.deule.net';
$port = 33736;
$root = '/ci_website';

// Functions
if (!function_exists('getallheaders')) {
    function getallheaders() {
        if (!is_array($_SERVER)) {
            return array();
        }
        
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Target determination
$metd = $_SERVER['REQUEST_METHOD'];
$reqp = $_SERVER['REQUEST_URI'];

// Preparing request headers
$reqHeds = "$metd $root$reqp HTTP/1.1\r\n";
$reqHeds .= "Host: $serv:$port\r\n";

// Converting client request headers to server request headers
$reqsHedsC = getallheaders();
foreach ($reqsHedsC as $name => $content) {
    if ($name != 'Host') {
        $reqHeds .= "$name: $content\r\n";
    }
}

if ($metd == 'POST') { // TODO Waaaay too long
    $postinfo = '';
    foreach ($_POST as $key => $value) {
        $postinfo .= $key . '=' . urlencode($value) . '&';
    }
    $postinfo = rtrim($postinfo, '&');
    $reqHeds .= "\r\n" . $postinfo;
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
    while (!feof($fp)) {
        // Getting response
        if ($resBuf === True) { // If headers sent
            $get = fgets($fp, 128);
            echo $get;
        } else {
            $resBuf .= fgets($fp, 128);
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