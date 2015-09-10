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
 * This is a command line example that includes a few simple calls using the TSheets PHP API Library.
 * It assumes/requires you already have your access token. If you do not have it yet:
 *
 *      - Visit your TSheets web dashboard
 *      - Click on "Company Settings" in the menu bar on the left
 *      - Click on "Add-ons"
 *      - Locate the "API" add-on and either install it or open the preferences
 *      - Create or edit an application and your access token will be provided
 */
 
require_once('../tsheets.inc.php');

// Enter your credentials here if you don't want to be prompted each time
$access_token = NULL;

if (!isset($access_token)) {
    $access_token = readline('Enter your access token: ');
}
$tsheets = new TSheetsRestClient(1, $access_token);

//////////////////////////////////////////////////////////////////////////////////
readline('Press enter to get a list of users:');

// Get a list of users
$users = $tsheets->get(ObjectType::Users);
print("TSheets Users\n");
print("-------------\n");
foreach($users['results']['users'] as $user) {
    print("User: {$user['first_name']} {$user['last_name']}\n");
}


//////////////////////////////////////////////////////////////////////////////////
readline('Press enter to create two new timesheets:');

// Get jobcodes
$jobcodes = $tsheets->get(ObjectType::Jobcodes, array('type' => 'regular'));

// Pick a first user and jobcode to work on
$user = reset($users['results']['users']);
$jobcode = reset($jobcodes['results']['jobcodes']);

// Create two timesheets with a single api call
$request = array();
$request[] = array(
    'user_id' => $user['id'],
    'jobcode_id' => $jobcode['id'],
    'type' => 'regular',
    'start' => '2014-01-18T15:19:21-07:00',
    'end' => '2014-01-18T16:19:21-07:00'
);
$request[] = array(
    'user_id' => $user['id'],
    'jobcode_id' => $jobcode['id'],
    'type' => 'regular',
    'start' => '2014-01-19T08:00:00-07:00',
    'end' => '2014-01-19T17:10:00-07:00'
);
$result = $tsheets->add(ObjectType::Timesheets, $request);
print "Create timesheet returned:\n";
print_r($result);


//////////////////////////////////////////////////////////////////////////////////
readline('Press enter to edit a timesheet:');

// Save the new timesheet ids

$timesheet_ids = array($result['results']['timesheets']['1']['id'], $result['results']['timesheets']['2']['id']);
// Edit a timesheet
$request = array();
$request[] = array('id' => $timesheet_ids[0], 'end' => '2014-01-18T17:19:21-07:00');
$result = $tsheets->edit(ObjectType::Timesheets, $request);
print "Edit timesheet returned:\n";
print_r($result);


//////////////////////////////////////////////////////////////////////////////////
readline('Press enter to delete both timesheets:');

// Delete the timesheets
$result = $tsheets->delete(ObjectType::Timesheets, $timesheet_ids);
print "Delete timesheet returned:\n";
print_r($result);