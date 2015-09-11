api_phplib
==========

PHP Helper Library for TSheets API

This library provides basic REST operations as well as methods to help with authentication and
token retrieval.

##Synopsis

```php
$tsheets = new TSheetsRestClient(1, 'your_access_token');
$result = $tsheets->get(ObjectType::Jobcodes);
```
##Examples

The examples folder has three examples, the basic, and reminders, example can be used if you already have an access
token (which you can get for your own account via the TSheets Web Dashboard -> Add-ons -> API dialog).

The basic example is a command line example of how to add, edit, and delete timesheets using the TSheets API.

The reminders example is similar to the basic example, but for clock-in/out reminders. 

The callback example shows how you can obtain an access token for another user's TSheets account using
oAuth 2.

##API Documentation

Full API documentation can be found at http://developers.tsheets.com/docs/api/
