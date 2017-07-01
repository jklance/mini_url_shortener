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


$urlElements    = trim($_SERVER["REQUEST_URI"], "\\/");
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
    <title>Jer...WTF Redirection Service</title>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <style>
        body {
            background: cornflowerblue;
            max-width: 800px;
            margin: 0 auto; 
        }
        #header {
            text-align: center;
        }
        #mainOperation {
            width: 90%;
            min-width: 320px;
            margin: 0 auto;
        }
        #urlShortenerForm {
            width: 98%;
            margin: 0 auto;
            font-size: 16px;
            text-align: center;
        }
        #statisticsArea {
            margin-top: 20px;
            font-size: 10pt;
        }
        .inputRow {
            position: relative;
            width: 95%;
            margin: 5px auto;
            padding: 2px;
        }
        .formElements {
            width: 100%;
            font-size: 16px;
        }
        #generate {
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 16px;
        }
        table, th, td {
            font-size: 8pt;
        }
        th, td {
            padding: 3px;
            margin: 2px 5px;
            text-align: left;
        }
        .resultBox {
            text-align: center;
            background: lightgrey;
            border: 1px solid grey;
            min-height: 20px;
            font-weight: bold;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div id="header">
    <h1>Jer...WTF?!</h1>
    <h2>Redirection Service</h2>
</div>

<div id="mainOperation">

    <form id="urlshortenerform" >
    <div class="inputRow"><input id="submissionResult" class="resultBox formElements" readonly /></div>
    <div class="inputRow">
        <input type="text"      class="formElements" id="short"    name="short"  placeholder="Short Code" />
        <input type="button"    id="generate" value="Auto" title="Click to generate a short code" onclick="makeShort(); return false;" />
    </div>
    <div class="inputRow"><input type="url"       class="formElements" id="url"      name="url"    placeholder="URL" /></div>
    <div class="inputRow"><input type="text"      class="formElements" id="secusr"   name="secusr" placeholder="User Name" /></div>
    <div class="inputRow"><input type="password"  class="formElements" id="seckey"   name="seckey" placeholder="Password" /></div>
    <div class="inputRow"><input type="submit"    class="formElements" onclick="submitUrl(); return false;" /></div>
    </form>
</div>

<div id="statisticsArea">
    <div id="statsTabs">
        <ul>
            <li><a href="#statsTopShorts">Top Short URLs</a></li>
            <li><a href="#statsRecentEntries">Recent Uses</a></li>
            <li><a href="#statsAllEntries">All Entries</a></li>
        </ul>
        <div id="statsTopShorts">
<?php
            $count = 20;
            $logEntries = $redirectDb->getTopShorts($count);

            if ($logEntries) {
                echo "<table>\n";

                foreach ($logEntries as $entry) {
                    if (strlen($entry['url']) > 75) {
                        $entry['url'] = substr($entry['url'],0,75) . '...';
                    }
                    echo '<tr><td>' . $entry['short'];
                    echo '</td><td><a href="' . $entry['short'] . '">' . $entry['url'] . '</a>';
                    echo '</td><td>' . $entry['count'];
                    echo '</td></tr>';
                }

                echo "</table>";
            }
?>
        </div>
        <div id="statsRecentEntries">
<?php
            $count = 20;
            $logEntries = $redirectDb->getAllLogEntries($count);

            if ($logEntries) {
                echo "<table>\n";

                foreach ($logEntries as $entry) {
                    if (strlen($entry['url']) > 75) {
                        $entry['url'] = substr($entry['url'],0,75) . '...';
                    }
                    echo '<tr><td>' . $entry['short'];
                    echo '</td><td><a href="' . $entry['short'] . '">' . $entry['url'] . '</a>';
                    echo '</td><td>' . $entry['date'];
                    echo '</td></tr>';
                }

                echo "</table>";
            }
?>
        </div>
        <div id="statsAllEntries">
<?php
            $count = 10;
            $logEntries = $redirectDb->getAllShorts();

            if ($logEntries) {
                echo "<table>\n";

                foreach ($logEntries as $entry) {
                    if (strlen($entry['url']) > 75) {
                        $entry['url'] = substr($entry['url'],0,75) . '...';
                    }
                    echo '<tr><td>' . $entry['short'];
                    echo '</td><td><a href="' . $entry['short'] . '">' . $entry['url'] . '</a>';
                    echo '</td><td>' . $entry['created'];
                    echo '</td></tr>';
                }

                echo "</table>";
            }
?>
        </div>
    </div>
</div>
</body>
<script>
$( function() {
    $( "#statsTabs" ).tabs();
});
function makeShort() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for( var i=0; i < 7; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    
    $('#short').val(text);

}

function submitUrl() {
    if (!$('#short').val()) {
        makeShort();
    }
    var formData = $("#urlshortenerform").serialize();
    $.post(
        "https://jer.wtf/addEntry.php",
        formData,
        function (data) {
            $('#submissionResult').css("color", "black");
            $('#submissionResult').val(data.responseText).css("color", "black");
        },
        'json'
    ).fail(function(data) {
        $('#submissionResult').val(data.responseText);
        
        var textColor = 'black';
        if(data.status != 200) {
            textColor = 'red';
        }
        $('#submissionResult').css("color", textColor);
    });
}
</script>
</html>
