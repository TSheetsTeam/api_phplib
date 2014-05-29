<?php
/*
Copyright (c) 2014 TSheets.com, LLC.

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/


/*
 * IMPORTANT NOTE:
 *
 * This example is designed to show how an oAuth 2.0 callback can be utilized to obtain an access token which can then
 * be used in future API calls. This mechanism is normally used when your application will be requesting data from
 * another user's TSheets account.
 *
 * If you only want to consume data from your own account, this callback process is not necessary. You can simply
 * retrieve your access token from the API add-on preferences dialog and start using it in calls to the TSheets
 * PHP API Library.
 *
 * See the PHP API Library documentation for details: http://developers.tsheets.com/docs/api/
 *
 *
 * INSTALLATION:
 *
 *      - Place this file and the tsheets.inc.php library on your web server. NOTE: TSheets will only call back to
 *        an https url.
 *
 *      - If not copying the entire library directory, you may need to modify the require_once below and remove
 *        the "../"
 *
 *      - Load this script in your browser.
 */

require_once('../tsheets.inc.php');

// Dynamically build the redirect uri to send to the tsheets authorize endpoint so it calls back to this script, the
// path to this script must match the uri you used when you registered your application with the TSheets API.
if ($_SERVER["HTTPS"] != "on") {
    echo "TSheets API WILL NOT call back to an unsecure connection. Enable https.<br/>";
    die();
}
else if ($_SERVER["SERVER_PORT"] != "443") {
    $redirect_uri = "https://{$_SERVER[HTTP_HOST]}:{$_SERVER["SERVER_PORT"]}{$_SERVER[REQUEST_URI]}";
}
else {
    $redirect_uri = "https://{$_SERVER[HTTP_HOST]}{$_SERVER[REQUEST_URI]}";
}

// strip any query params
if (strpos($redirect_uri, '?') !== false) {
    $redirect_uri = substr($redirect_uri, 0, strpos($redirect_uri, '?'));
}

session_start();
$js = '';
$status_output = '';
$test_request_html = '';

try {
    switch($_GET['state']) {
        case 'auth_request':
            // This is called when "Authorize" button is clicked. Take the client_id and client_secret and send the
            // browser to the TSheets site so the user can authorize this app to use their account data.

            // save client info in the session so we can use it later when called by TSheets
            $_SESSION['client_id'] = $_POST['client_id'];
            $_SESSION['client_secret'] = $_POST['client_secret'];

            // save client info in cookie so user doesn't have to constantly enter it
            setcookie('client_id', $_POST['client_id'], time()+60*60*24*30, '/', '', true, true);
            setcookie('client_secret', $_POST['client_secret'], time()+60*60*24*30, '/', '', true, true);

            // get the TSheets auth url and redirect there so the user can authorize us to use their TSheets data
            $tsheets = new TSheetsRestClient(1, null, $_SESSION['client_id'], $_SESSION['client_secret']);
            $auth_url = $tsheets->get_auth_url($redirect_uri, 'auth_callback');
            header("Location: {$auth_url}");
            die();

        case 'auth_callback':
            // This is called by TSheets once the user has either authorized or aborted the authorization.
            // Take the authorization code and trade it for an access token to use in all future API calls.
            if ($_GET['error']) {
                $status_output .= $_GET['error'] . "<br/>";
                $status_output .= $_GET['error_description'] . "</br></br>";
                break;
            }
            else {
                $tsheets = new TSheetsRestClient(1, null, $_SESSION['client_id'], $_SESSION['client_secret']);
                $result = $tsheets->get_access_token($_GET['code'], $redirect_uri);
                $_SESSION['access_token'] = $result['access_token'];
                $_SESSION['refresh_token'] = $result['refresh_token'];
                // redirect back to base uri with no params so refresh will work on this page later
                header("Location: {$redirect_uri}");
                die();
            }

        case 'test_request':
            // Called if the "Test a 'User List' Request" button is clicked. Makes an API call using the access token
            $tsheets = new TSheetsRestClient(1, $_SESSION['access_token']);
            $users = $tsheets->get(ObjectType::Users);
            foreach($users['results']['users'] as $user) {
                $test_request_html .= "<br/>{$user['first_name']} {$user['last_name']}";
            }
            break;

        case 'clear_session':
            session_destroy();
            header("Location: {$redirect_uri}");
            die();

        default:
            break;
    }
}
catch(TSheetsException $e) {
    $status_output .= $e->__toString() . "<br/>";
}


if (isset($_SESSION['access_token'])) {
    $status_output .= "Access Token: ${_SESSION['access_token']}<br/>";
    $status_output .= "Refresh Token: ${_SESSION['refresh_token']}<br/><br/>";
    $js .= '$("#test_command").effect("highlight", {color:"lightgreen"}, 3000);';
    $js .= '$("#clear_session").show();';
}
else {
    $status_output .= 'Enter client credentials and press "Authorize" to start the example token request process.';
}

$this_script = htmlentities($_SERVER['PHP_SELF']);

$html = <<<EOL
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>TSheets API Auth Callback Example</title>
<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
<style>
html {
    margin-left:8px;
}
body {
    background:url('https://www.tsheets.com/images/api/api_doc_bg.png');
    font-family:'helvetica neue', 'helvetica', 'arial', sans sarif;
    font-size:14px;
    color:#3a3a3a;
}
a {
    color: #be3a25;
}
</style>
</head>
<body>

<div style="padding-bottom:20px;height:30px">
    <div style="float:left;width:300px">
        <img src="https://www.tsheets.com/images/api/ts_logo.png"/>
    </div>
    <div style="float:left;margin-left:150px"><a href="http://developers.tsheets.com/docs/api/" target="_blank">API Documentation</a></div>
</div>

<div id="status" style="background-color:lightyellow;padding:5px 5px 5px 5px;margin-bottom:14px;margin-top:20px">
    {$status_output}

    <div id="clear_session" style="display:none;">
    <form action="{$this_script}?state=clear_session" method="post">
        <input id="clear_session_button" type="submit" value="Clear Session"/>
    </form>
</div>
</div>

<div id="auth_and_help">
    <div id="auth" style="width:500px;float:left">
        <form action="{$this_script}?state=auth_request" method="post">
            <table style="margin-bottom:10px">
                <tr>
                <td style="width:120px"><label for="client_id">Client ID:</label></td>
                <td style="width:500px">
                    <input id="client_id" name="client_id" type="text" style="width:240px" value="{$_COOKIE['client_id']}"/>
                    <a href="#" style="font-size:smaller;" onclick="$('#help').toggle();">Where do I get these?</a>
                </td>
                </tr>

                <tr>
                <td><label for="client_secret">Client Secret:</label></td>
                <td><input id="client_secret" name = "client_secret" type="text" style="width:240px" value="{$_COOKIE['client_secret']}"/></td>
                </tr>
            </table>
            <input type="submit" value="Authorize"/>
        </form>
    </div>
    <div id="help" style="display:none;float:left;font-size:smaller;padding:10px;background-color:lightyellow" onclick="$(this).hide();">
        In your TSheets web dashboard:
        <ul>
            <li>Click on "Company Settings" in the menu bar on the left</li>
            <li>Click on "Add-ons"</li>
            <li>Locate the "API" add-on and either install it or open the preferences</li>
            <li>Create or edit an application and your client id and secret will be provided</li>
        </ul>
    </div>
</div>

<div id="test_command" style="margin-top:140px;width:160px;padding:4px 4px 4px 0px;display:none;clear:both;">
    <form action="{$this_script}?state=test_request" method="post">
        <input id="test_button" type="submit" value="Test a 'User List' Request"/>
    </form>
    {$test_request_html}
</div>

<script>
{$js}
</script>

</body>
</html>
EOL;

print($html);