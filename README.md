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

The examples folder has two examples, the basic example can be used if you already have an access
token (which you can get for your own account via the TSheets Web Dashboard -> Add-ons -> API dialog).

The callback example shows how you can obtain an access token for another user's TSheets account using
oAuth 2.

##API Documentation

Full API documentation can be found at http://developers.tsheets.com/docs/api/
