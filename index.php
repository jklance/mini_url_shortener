<?php
$configsPath = "./";

// Let's get them over on https if they're not
if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit();
}

require('UrlRedirector.php');
require('UrlRedirectDb.php');


$urlElements    = trim($_SERVER["REQUEST_URI"], "\s\/");
$urlParts       = explode('/', $urlElements);
$redirectUrl    = null;

$configFile     = $configPath . "redirector.conf";
require($configFile);

$redirector = new UrlRedirector($urlParts[0]);
$redirectDb = new UrlRedirectDb($config['db']);

if ($redirector->getShort()) {
    $redirector->setLong($redirectDb->getRedirecturl($redirector));
} 

if ($redirector->getLong()) {
    $redirector->getRedirectHeader();
    exit();
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
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 3px;
            text-align: left;
        }
        .resultBox {
            text-align: center;
            background: white;
            border: 1px solid grey;
            padding: 3px;
            min-height: 20px;
            margin: 8px auto;
            font-weight: bold;
            font-family: monospace;
            font-size: 16px;
        }
    </style>
</head>
<body>
<h1>Jer...WTF?!</h1>
<h2>Redirection Service</h2>

<div id="submissionResult" class="resultBox">&nbsp;</div>

<form id="urlshortenerform" >
<input type="text" id="short" name="short" />Shortened URL (<a href="#" onclick="makeShort(); return false;">Generate</a>)<br />
<input type="url" id="url" name="url" />URL <br />
<input type="text" id="secusr" name="secusr" />Username <br />
<input type="password" id="seckey" name="seckey" />Password <br />
<input type="submit" onclick="submitUrl(); return false;" /><br />
</form>
<?php
$count = 10;
$logEntries = $redirectDb->getTopShorts($count);

if ($logEntries) {
    echo "<h3>Most Used Shorts</h3>\n<table>\n";

    foreach ($logEntries as $entry) {
        echo '<tr><td>' . $entry['short'];
        echo '</td><td><a href="' . $entry['short'] . '">' . $entry['url'] . '</a>';
        echo '</td><td>' . $entry['count'];
        echo '</td></tr>';
    }

    echo "</table>";
}
?>

<?php
$count = 10;
$logEntries = $redirectDb->getAllLogEntries($count);

if ($logEntries) {
    echo "<h3>Most Recent Uses</h3>\n<table>\n";

    foreach ($logEntries as $entry) {
        echo '<tr><td>' . $entry['short'];
        echo '</td><td><a href="' . $entry['short'] . '">' . $entry['url'] . '</a>';
        echo '</td><td>' . $entry['date'];
        echo '</td></tr>';
    }

    echo "</table>";
}

?>
</body>
<script>
function makeShort() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for( var i=0; i < 7; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    
    $('#short').val(text);

}

function submitUrl() {
    var formData = $("#urlshortenerform").serialize();
    $.post(
        "https://jer.wtf/addEntry.php",
        formData,
        function (data) {
            $('#submissionResult').css("color", "black");
            $('#submissionResult').text(data.responseText).css("color", "black");
        },
        'json'
    ).fail(function(data) {
        $('#submissionResult').text(data.responseText);
        
        var textColor = 'black';
        if(data.status != 200) {
            textColor = 'red';
        }
        $('#submissionResult').css("color", textColor);
    });
}
</script>
</html>
