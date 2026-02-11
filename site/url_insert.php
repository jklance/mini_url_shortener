<?php

// TODO: once you leave POC state, let's do this intelligently
// Assumes a variable called $config[security] as a 'username'=>'password' array

require('../redirector.conf');
require('UrlRedirector.php');
require('UrlRedirectDb.php');

$redirector = new UrlRedirector();
$redirectDb = new UrlRedirectDb($config['db']);
$errors   = null;


if (!isset($config['security'][$_POST['secusr']]) || 
    $config['security'][$_POST['secusr']] != $_POST['seckey']) {
        $errors .= "Authorization Failure.\n";        
}

$redirector->setUser($_POST['secusr']);

if (!$redirector->setShort($_POST['short'])) {
    $errors .= "Invalid short name.\n";
}
    
if (!$redirector->setLong($_POST['url'])) {
    $errors .= "Invalid url.\n";
}

if (!$errors) {
    $redirectDb->setRedirectUrl($redirector);
    echo 'Success! <a href="http://jer.wtf/' . $redirector->getShort() . '">jer.wtf/' . $redirector->getShort() . '</a>';
} else {
    echo 'Fail. Errors:';
    echo $errors;
}

