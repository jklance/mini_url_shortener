<?php

$urlShort = $_POST['shortname'];
$urlLong  = $_POST['url'];
$username = $_POST['secusr'];
$password = $_POST['seckey'];
$errors   = null;


// TODO: once you leave POC state, let's do this intelligently
// Assumes a variable called $security as a 'username'=>'password' array
require('../configs/jer.wtf.users.txt');

require('../configs/db.conf');

if (!isset($security[$username]) || $security[$username] != $password) {
    $errors .= "Authorization Failure.\n";        
}

$shortenedChars = '/^[A-za-z0-9_]{1,20}$/';
if (!preg_match($shortenedChars, $urlShort)) {
    $errors .= "Invalid short name.\n";
}

if (filter_var($urlLong, FILTER_VALIDATE_URL) === false) {
    $errors .= "Invalid url.\n";
}

if (!$errors) {
    $handle = mysqli_connect($jerwtf['host'], $jerwtf['login'], $jerwtf['pass'], $jerwtf['database'])
        or die('Failure connecting to database');
    $query = "INSERT INTO redirects VALUES('$urlShort', '$urlLong', NOW(), 0, '$username')";
    mysqli_query($handle, $query) or die('Failure writing to database');
    mysqli_close($handle);
    echo 'Success! <a href="http://jer.wtf/' . $urlShort . '">jer.wtf/' . $urlShort . '</a>';
} else {
    echo 'Fail. Errors:';
    echo $errors;
}

