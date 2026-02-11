<?php

$logfile = "logfile.log";
function addlog($file, $line) {
    //file_put_contents($file, $line.PHP_EOL , FILE_APPEND | LOCK_EX);
}

addLog($logfile, 'Starting...');


// TODO: once you leave POC state, let's do this intelligently
// Assumes a variable called $config[security] as a 'username'=>'password' array

require('../redirector.conf');
require('UrlRedirector.php');
require('UrlRedirectDb.php');

$redirector = new UrlRedirector();
$redirectDb = new UrlRedirectDb($config['db']);
$response = array(
    'code' => null,
    'body' => null,
);
header('Content-Type: application/json');

if (!isset($config['security'][$_POST['secusr']]) || 
    $config['security'][$_POST['secusr']] != $_POST['seckey']) {
        $response = 'Error: Authorization Failure';
        http_response_code(401);

        echo $response;
        return $response;
}
addLog($logfile, 'Signed in as ' . $_POST['secusr']);

$redirector->setUser($_POST['secusr']);

if (!$redirector->setShort($_POST['short'])) {
    $response = 'Error: Invalid short name';
    http_response_code(400);
    
    echo $response;
    return $response;
}
    
if (!$redirector->setLong($_POST['url'])) {
    $response = 'Error: Invalid url';
    http_response_code(400);
        
    echo $response;
    return $response;
}

addLog($logfile, 'Processing ' . $_POST['short'] . ' as ' . $_POST['url']);

$redirectDb->setRedirectUrl($redirector);

$response = 'https://jer.wtf/' . $redirector->getShort();
http_response_code(200);

echo $response;

addLog($logfile, "Done. \n");
return $response;
