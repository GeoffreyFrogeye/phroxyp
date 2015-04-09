<?php

class Proxy {
    public function __construct($serv, $port = 80, $root = '/', $localRoot = '') {
        // Functions
        function str_replace_once($search, $replace, $subject) { // TODO Credit
            $pos = strpos($subject, $search);
            if ($pos === false) {
                return $subject;
            }

            return substr($subject, 0, $pos) . $replace . substr($subject, $pos + strlen($search));
        }

        if (!function_exists('getallheaders')) { // TODO Credit
            function getallheaders() {
                if (!is_array($_SERVER)) {
                    return array();
                }

                $headers = array();
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    } else if (substr($name, 0, 8) == 'CONTENT_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))))] = $value;
                    }
                }
                return $headers;
            }
        }

        // Target determination
        $metd = $_SERVER['REQUEST_METHOD'];
        $reqp = $_SERVER['REQUEST_URI'];
        if ($localRoot != '') {
            $reqp = str_replace_once($localRoot, '', $reqp); // TODO Reliable method
        }

        // Preparing request headers
        $reqHeds = "$metd $root$reqp HTTP/1.1\r\n";
        $reqHeds .= "Host: $serv:$port\r\n";

        // Converting client request headers to server request headers
        $reqsHedsC = getallheaders();
        foreach ($reqsHedsC as $name => $content) {
            switch ($name) {
                case 'Host':
                case 'Connection':
                case 'Content-Length':
                break;

                default:
                $reqHeds .= "$name: $content\r\n";
            }
        }

        if (isset($reqsHedsC['Content-Type'])) {
            switch ($reqsHedsC['Content-Type']) { // TODO Other content-types
                case 'application/x-www-form-urlencoded':
                $postData = '';
                foreach ($_POST as $key => $value) {
                    $postData .= $key . '=' . urlencode($value) . '&';
                }
                $postData = rtrim($postData, '&');
                break;
                
                case 'application/json':
                $postData = file_get_contents('php://input');
                break;

                case 'TODO':
                $postData = stripslashes($_POST['payload']);
                break;

                default:
                $postData = "Unknown Content-Type";
                break;

            }
            $reqHeds .= "Content-Length: ".strlen($postData)."\r\n";
            $reqHeds .= "Connection: Close\r\n";
            $reqHeds .= "\r\n" . $postData;
        } else {
            $reqHeds .= "Connection: Close\r\n\r\n";
        }

        $fp = fsockopen($serv, $port, $errno, $errstr, 30);
        if (!$fp) { // TODO ErrorCode, ErrorDocument
            echo "Couldn't connect to server\n<br/>$errstr ($errno)<br />\n";
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
    }
}

?>
