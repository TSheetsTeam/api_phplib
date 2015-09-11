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
 * This is a command line example that includes a few simple calls using the TSheets PHP API Library
 * to list, add, and edit the reminders endpoint.
 * The Reminders endpoint refers to clock in/out reminders only, not custom Notifications.
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
readline('Press enter to get a list of reminders:');

// Get a list of users
$reminders = $tsheets->get(ObjectType::Reminders);
print("TSheets Users\n");
print("-------------\n");
foreach($reminders['results']['reminders'] as $reminders) {
    print("Active: {$reminders['id']} {$reminders['reminder_type']}\n");
    // If User ID is 0, that means it is a global (company wide) reminder
    print("User ID: {$reminders['user_id']}\n");
    print("Reminder: {$reminders['active']} {$reminders['enabled']}\n");
}
//////////////////////////////////////////////////////////////////////////////////
readline('Press enter to add a reminder:');

// Create a clock-in and a clock-out reminder with a single api call
$request = array();
$request[] = array(
    'user_id' => '0',
    'reminder_type' => 'clock-in',
    'due_time' => '06:00:00',
    'due_days_of_week' => 'Mon,Tue,Wed,Thu,Fri',
    'distribution_methods' => 'Push',
    'active' => 'true',
    'enabled' => 'true'
);
$request[] = array(
    'user_id' => '0',
    'reminder_type' => 'clock-out',
    'due_time' => '20:00:00',
    'due_days_of_week' => 'Mon,Tue,Wed,Thu,Fri',
    'distribution_methods' => 'Push',
    'active' => 'true',
    'enabled' => 'true'
);

$result = $tsheets->add(ObjectType::Reminders, $request);
print "Create reminder returned:\n";
print_r($result);


//////////////////////////////////////////////////////////////////////////////////
readline('Press enter to edit an existing reminder:');

// Save the new reminders ids in order to edit them in the next step
$reminder_id = array($result['results']['reminders']['1']['id'], $result['results']['reminders']['2']['id']);

// Edit the first reminder
$request = array();
$request[] = array('id' => $reminder_id[0], 'due_days_of_week' => 'Mon,Wed,Fri');
$result = $tsheets->edit(ObjectType::Reminders, $request);
print "Edit reminder returned:\n";
print_r($result);


//////////////////////////////////////////////////////////////////////////////////
readline('Press enter to delete both reminders:');

// Delete the reminders we created
$result = $tsheets->delete(ObjectType::Reminders, $reminder_id);
print "Deleted reminders:\n";
print_r($result);