<?php

$urlElements    = trim($_SERVER["REQUEST_URI"], "\s\/");
$urlParts       = explode('/', $urlElements);
$redirectUrl    = null;

$configs        = "../configs/db.conf";
require($configs);

$shortenedChars = '/^[A-za-z0-9_]{1,20}$/';

if (isset($urlParts[0]) && preg_match($shortenedChars, $urlParts[0])) {
    // Retrieve redirect URL
    $redirectUrl = retrieveRedirectUrl($urlParts[0], $jerwtf);
} 

if ($redirectUrl) {
    header("HTTP/1.1 302 Found");
    header("Location: " . $redirectUrl);
    exit();
}


function retrieveRedirectUrl($key, $dbInfo) {
    $handle = mysqli_connect($dbInfo['host'], $dbInfo['login'], $dbInfo['pass'], $dbInfo['database'], 3306, '/home/jer_/mysqld.sock') 
        or die('Error connecting to database: ' . mysqli_connect_error());
    
    $query = "SELECT redirect_url FROM redirects WHERE redirect_key = '$key'";
    $resultArr = array();

    $result = mysqli_query($handle, $query);
    $row    = mysqli_fetch_assoc($result);

    if (isset($row['redirect_url'])) {
        return $row['redirect_url'];
    }
    return null;
}


?>
<h1>Url Redirector</h1>
