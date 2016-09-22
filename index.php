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
    // Assumes a table named 'redirects' with column 'redirect_key' as the short
    // form and 'redirect_url' as the long form.

    $handle = mysqli_connect($dbInfo['host'], $dbInfo['login'], $dbInfo['pass'], $dbInfo['database']) 
        or die('Error connecting to database');
    
    $query = "SELECT redirect_url FROM redirects WHERE redirect_key = '$key'";
    $resultArr = array();

    $result = mysqli_query($handle, $query);
    $row    = mysqli_fetch_assoc($result);

    $counter = "UPDATE redirects SET uses = uses + 1 WHERE redirect_key = '$key'";
    $logger  = "INSERT INTO redirect_log VALUES('$key', NOW())";
    mysqli_query($handle, $counter);
    mysqli_query($handle, $logger);

    mysqli_close($handle);

    if (isset($row['redirect_url'])) {
        return $row['redirect_url'];
    }
    return null;
}


?>
<html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <title>Jer...WTF Redirection Service</title>
    <style>
        body {
            background: lightgrey;
        }
        
    </style>
</head>
<body>
<h1>Jer...WTF?!</h1>
<h2>Redirection Service</h2>

<form id="urlshortenerform" method="post" action="https://jer.wtf/url_insert.php">
<input type="text" id="shortname" name="shortname" />Short Name (<span onclick="makeShort()">Generate</span>)<br />
<input type="url" id="url" name="url" />URL <br />
<input type="text" id="secusr" name="secusr" />Username <br />
<input type="password" id="seckey" name="seckey" />Password <br />
<input type="submit" /><br />
</form>
</body>
<script>
function makeShort()
{
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for( var i=0; i < 7; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    
    document.getElementById('shortname').value = text;
}
</script>
</html>
