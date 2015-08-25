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

/**
 * Class for all TSheets API usage. Provides basic REST operations as well as methods to help with authentication and
 * token retrieval.
 *
 * Synopsis:
 *      $tsheets = new TSheetsRestClient(1, 'your_access_token', 'your_oauth_oauth_client_id', 'your_oauth_oauth_client_secret');
 *      $result = $tsheets->get(ObjectType::Jobcodes);
 *
 * @link http://developers.tsheets.com/docs/api/ API Documentation
 */
class TSheetsRestClient {
    private $_lib_version = 1.0;
    private $_host;
    private $_output_format;
    private $_api_version;
    private $_url;
    private $_oauth_client_id;
    private $_oauth_client_secret;
    private $_access_token;
    private $_refresh_token;
    private $_bypass_ssl_peer_verification;

    /**
     * TSheetsRestClient constructor
     *
     * @param integer $api_version Required. Version of the TSheets rest API to use. 1 is currently the only supported value.
     *
     * @param string $access_token Required* Access token either from a call to get_access_token or taken directly from
     * your API add-on settings in the TSheets API add-on dashboard. *Optional if you do not have a token yet and will
     * be retrieving one via the get_access_token method.
     *
     * @param string $oauth_client_id Optional. OAuth2 client id of your application when you registered it via the TSheets API add-on.
     * Only necessary if calling get_access_token or refresh_access_token.
     *
     * @param string $oauth_client_secret Optional. OAuth2 client secret provided when you registered your application via the TSheets
     * API add-on. Required if calling get_access_token or refresh_access_token, but otherwise not necessary.
     *
     * @param string $refresh_token Optional. Only necessary if you will be calling the refresh_access_token method.
     */
    public function __construct($api_version, $access_token=null, $oauth_client_id=null, $oauth_client_secret=null, $refresh_token=null) {
        // ensure Curl is installed
        if(!extension_loaded("curl")) {
            throw(new Exception("The Curl extension is required for the client to function."));
        }

        $this->_host = 'rest.tsheets.com';
        $this->_api_version = $api_version;
        $this->_output_format = OutputFormat::AssociativeArray;
        $this->_oauth_client_id = $oauth_client_id;
        $this->_oauth_client_secret = $oauth_client_secret;
        $this->_access_token = $access_token;
        $this->_refresh_token = $refresh_token;
        $this->_bypass_ssl_peer_verification = false;
        $this->set_url();
    }


    /**
     * Private method used internally to set a consistent url with variable host name.
     */
    private function set_url() {
        $this->_url = "https://{$this->_host}/api/v{$this->_api_version}";
    }


    /**
     * Utility method to allow setting the access token on a TSheetsRestClient instance after it has been constructed.
     *
     * @param string $access_token
     */
    public function set_access_token($access_token) {
        $this->_access_token = $access_token;
    }


    /**
     * Utility method to allow modifying the hostname portion of the REST url.
     *
     * @param string $hostname
     */
    public function set_host($hostname) {
        $this->_host = $hostname;

        $this->set_url();
    }


    /**
     * Utility method to allow callers to specify how they want results returned. Default is ObjectType::AssociativeArray.
     *
     * @param OutputFormat $output_format Output format the library should use when returning results from API calls.
     */
    public function set_output_format($output_format) {
        if (!OutputFormat::valid($output_format)) {
            throw new TSheetsException('Invalid output format, see OutputFormat class definition');
        }
        $this->_output_format = $output_format;
    }

    
    /**
     * Utility method to allow callers to bypass the SSL peer verification. This should not normally be used.
     *
     * @param BypassVerification $bypass_verification true/false to indicate if verification should be skipped
     */
    public function bypass_ssl_peer_verification($bypass_verification = false) {
        $this->_bypass_ssl_peer_verification = $bypass_verification;
    }


    /**
     * Retrieves a list of objects from the API.
     *
     * @param string $object_type The type of resource being requested. Can be ObjectType constant or a string.
     * Example: $tsheets->get(ObjectType::Jobcodes) and $tsheets->get('jobcodes') are synonymous.
     *
     * @param array $filters Optional. Associative array of filters to apply.
     * Example: $tsheets->get(ObjectType::Users, array('modified_since' => '2014-01-12T15:19:21+00:00'))
     *
     * @return mixed API response. Default type is an associative array, but configurable via
     * TSheetsRestClient::set_output_format.
     */
    public function get($object_type, $filters=array()) {
        return $this->_deserialize($this->_request('GET', $object_type, $filters));
    }


    /**
     * Applies edits to existing objects.
     *
     * @param string $object_type The type of resource being edited. Can be ObjectType constant or a string.
     * Example: $tsheets->edit(ObjectType::Jobcodes, $json) and $tsheets->edit('jobcodes', $json) are synonymous.
     *
     * @param array $objects An indexed array of associative arrays that describe the objects to be edited.
     * Example: array(array('id' => 112235, 'notes' => 'note1 text'), array('id' => 4244211, 'notes' => 'note2 text'))
     *
     * @return mixed API response. Default type is an associative array, but configurable via
     * TSheetsRestClient::set_output_format.
     */
    public function edit($object_type, $objects) {
        return $this->put($object_type, $objects);
    }


    /**
     * Synonym for TSheetsRestClient::edit
     * @see TSheetsRestClient::edit
     */
    public function put($object_type, $objects) {
        return $this->_deserialize($this->_request('PUT', $object_type, $this->_json_encode_array($objects)));
    }


    /**
     * Adds new objects.
     *
     * @param string $object_type The type of resource being added. Can be ObjectType constant or a string.
     * Example: $tsheets->add(ObjectType::Jobcodes, $json) and $tsheets->add('jobcodes', $json) are synonymous.
     *
     * @param array $objects An indexed array of associative arrays that describe the objects to be added.
     * Example: array(array('name' => 'jobcode1'), array('name' => 'jobcode2'))
     *
     * @return mixed API response. Default type is an associative array, but configurable via
     * TSheetsRestClient::set_output_format.
     */
    public function add($object_type, $objects) {
        return $this->post($object_type, $objects);
    }

    /**
     * Runs a report and returns the output.
     *
     * @param string $report_type The type of report being run. Can be ObjectType constant or a string.
     * Example: $tsheets->get_report(ReportType::Project, $filters and $tsheets->get_report('project', $filters) are
     * synonymous.
     *
     * @param array $filters An associative array of filters to pass when calling the report.
     * @return mixed API response. Default type is an associative array, but configurable via
     * TSheetsRestClient::set_output_format.
     */
    public function get_report($report_type, $filters) {
        $report_endpoint = $report_type;
        // Our reports endpoint follows the format '/reports/REPORT_TYPE'. If they didn't prepend $report_type with 'reports/',
        // let's put it on for them.
        if (strstr($report_type, 'reports/') === false) {
            $report_endpoint = "reports/{$report_endpoint}";
        }
        return $this->post($report_endpoint, $filters, false);
    }


    /**
     * POSTs to the API
     *
     * @param string $api_endpoint  Endpoint to POST to
     * @param array $post_array  Array that will be converted to the proper JSON string and included as the body of the POST
     * @param bool $require_indexed_array If true (default), $post_array must be an indexed array (i.e. for POST-ing jobcode objects).
     *                                    If false, it may be an associative array (i.e. for POST-ing report filters).
     *
     * @return mixed API response. Default type is an associative array, but configurable via
     * TSheetsRestClient::set_output_format.
     */
    public function post($api_endpoint, $post_array, $require_indexed_array = true) {
        return $this->_deserialize($this->_request('POST', $api_endpoint, $this->_json_encode_array($post_array, $require_indexed_array)));
    }


    /**
     * Deletes one or more objects.
     *
     * @param string $object_type Currently only supports the ObjectType::Timesheets
     *
     * @param mixed $id_or_array Single id or an array of ids
     *
     * @return mixed API response. Default type is an associative array, but configurable via
     * TSheetsRestClient::set_output_format.
     *
     * @see http://developers.tsheets.com/docs/api/timesheets/delete-timesheets Delete Timesheets Documentation
     */
    public function delete($object_type, $id_or_array) {
        if (is_array($id_or_array)) {
            $vars = array('ids' => implode(',', $id_or_array));
        }
        else {
            $vars = array('ids' => $id_or_array);
        }

        return $this->_deserialize($this->_request('DELETE', $object_type, $vars));
    }


    /**
     * Builds the authorization URL necessary to retrieve an authorization code which can later be used to retrieve
     * an access token.
     *
     * @param string $redirect_uri The HTTPS url that you submitted as the 'OAuth Redirect URI' when you set up your app
     * in the TSheets API Add-On.
     *
     * @param string $state A random value used by the client to maintain state between the request and callback. The
     * authorization server includes this value when redirecting the user-agent back to the client.
     *
     * @return string Full URL to TSheets authorization page for this client application.
     *
     * @see http://developers.tsheets.com/docs/api/authentication Authentication Documentation
     */
    public function get_auth_url($redirect_uri, $state) {
        $vars = array(
            'response_type' => 'code',
            'client_id' => $this->_oauth_client_id,
            'redirect_uri' => $redirect_uri,
            'state' => $state
        );

        // Convert the fields in $vars into a query string
        $query_str = '';
        foreach ($vars as $key => $value) {
            $query_str .= "{$key}=".urlencode($value)."&";
        }
        $query_str = rtrim($query_str, '&');

        return "{$this->_url}/authorize?{$query_str}";
    }


    /**
     * Exchanges an authorization code for an access token.
     *
     * @param string $auth_code The authorization code being exchanged for an access token.
     *
     * @param string $redirect_uri The HTTPS url that you submitted as the 'OAuth Redirect URI' when you set up your app
     * in the TSheets API Add-On.
     *
     * @return mixed Response that contains the following keys: access_token, expires_in, token_type, scope, refresh_token
     *
     * @see http://developers.tsheets.com/docs/api/authentication Authentication Documentation
     */
    public function get_access_token($auth_code, $redirect_uri) {
        $vars = array(
            'grant_type' => 'authorization_code',
            'code' => $auth_code,
            'client_id' => $this->_oauth_client_id,
            'client_secret' => $this->_oauth_client_secret,
            'redirect_uri' => $redirect_uri
        );

        return $this->_deserialize($this->_request('POST', 'grant', $vars));
    }


    /**
     * Refreshes access token.
     *
     * @param string $refresh_token This parameter is the refresh_token that you're exchanging for a new access_token.
     *
     * @return mixed Response that contains the following keys: access_token, expires_in, token_type, scope, refresh_token
     *
     * @see http://developers.tsheets.com/docs/api/authentication Authentication Documentation
     */
    public function refresh_access_token($refresh_token=null) {
        // If the user didn't pass in a refresh token, use member variable they may have set when obj created
        if (!$refresh_token) {
            $refresh_token = $this->_refresh_token;
        }

        $vars = array(
            'grant_type' => 'refresh_token',
            'client_id' => $this->_oauth_client_id,
            'client_secret' => $this->_oauth_client_secret,
            'refresh_token' => $refresh_token
        );

        return $this->_deserialize($this->_request('POST', 'grant', $vars));
    }


    /**
     * Utility function used to deserialize the json API response depending on the current output format.
     *
     * @param string $json
     *
     * @return mixed Value of $json converted to the correct object type
     *
     * @throws TSheetsException
     *
     * @see OutputFormat
     */
    protected function _deserialize($json) {
        switch($this->_output_format) {
            case OutputFormat::AssociativeArray:
                return json_decode($json, true);
            case OutputFormat::Object:
                return json_decode($json);
            case OutputFormat::Raw:
                return $json;
            default:
                throw new TSheetsException("Unknown output format encountered in _deserialize");
        }
    }


    /**
     * Utility method that actually makes the web request.
     *
     * @param string $method The http method to use in the call.
     *
     * @param string $endpoint The API endpoint to exercise.
     *
     * @param mixed $data Data to post. Can be an array or string. If array it will be converted into key/value pair
     * parameters. If a string it is assumed to be json data and a "application/json" header will be included in the
     * request.
     *
     * @return string Response body.
     *
     * @throws TSheetsException
     * @see _deserialize
     */
    protected function _request($method, $endpoint, $data=array()) {
        $data_str = '';
        $headers = array();

        if (is_array($data)) {
            if (count($data) > 0) {
                foreach ($data as $key => $value) {
                    $data_str .= "{$key}=".urlencode($value)."&";
                }
                $data_str = rtrim($data_str, '&');
            }
        }
        else {
            // if not array, expect $data to be json
            $data_str = $data;
            array_push($headers, "Content-Type: application/json");
        }

        // Setup the CURL call
        $ch = curl_init();
        
        // Configure to accept and decode compressed responses
        curl_setopt($ch,CURLOPT_ENCODING, '');

        if ($this->_access_token) {
            array_push($headers, "Authorization: Bearer {$this->_access_token}");
        }
        
        if ($this->_bypass_ssl_peer_verification) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        }

        if (strcasecmp($method, 'POST') == 0 ) {
            curl_setopt($ch, CURLOPT_URL, "{$this->_url}/{$endpoint}");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
        }
        else if (strcasecmp($method, 'PUT') == 0 ) {
            curl_setopt($ch, CURLOPT_URL, "{$this->_url}/{$endpoint}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
        }
        else if (strcasecmp($method, 'DELETE') == 0 ) {
            curl_setopt($ch, CURLOPT_URL, "{$this->_url}/{$endpoint}?{$data_str}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        else {
            curl_setopt($ch, CURLOPT_URL, "{$this->_url}/{$endpoint}?{$data_str}");
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, "API Toolkit-v{$this->_lib_version} (PHP)");
        curl_setopt($ch, CURLOPT_REFERER, $this->_url);
        curl_setopt($ch, CURLOPT_HEADER, false);           // Return the header along with the body received from the remote host
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    // Return the content rather than TRUE/FALSE when running curl_exec()
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $output = curl_exec($ch);
        $error_text = curl_error($ch);
        $error_code = curl_errno($ch);
        $info = curl_getinfo($ch);

        // Error with HTTP communication or http_code >= 400
        if ($error_code or $error_text or $output === false or $info['http_code'] >= 400) {
            $message = "Invalid response from {$this->_url} http_code:{$info['http_code']}";
            if ($error_code) {
                $message .= " curl_error#: {$error_code}";
            }
            if ($error_text) {
                $message .= " curl_error: {$error_text}";
            }
            if ($output !== false and is_string($output) and strlen($output) > 0) {
                $message .= " http_body: {$output}";
            }
            curl_close($ch);
            throw new TSheetsException($message, "{$this->_url}/{$endpoint}?{$data_str}", $info['http_code'], $output, $error_text, $error_code);
        }
        else {
            // Success
            $result = $output;
        }

        curl_close($ch);
        return($result);
    }

    /**
     * Helper to enforce not taking in associative arrays in some methods.
     *
     * @param array $array array to check
     * @return bool
     */
    private function _is_assoc($array) {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * Turn array into json the TSheets API expects (wrapped in data element).
     *
     * @param mixed $array Array of associative arrays.
     * @param bool $require_indexed_array  If true, $array must be an array of associative arrays (i.e. for creating
     *                                     timesheets). If false, we skip this check.
     * @return string json representation
     * @throws TSheetsException on error
     */
    private function _json_encode_array($array, $require_indexed_array = true)
    {
        if (is_array($array)) {
            if ($this->_is_assoc($array) and $require_indexed_array) {
                throw new TSheetsException('Input arrays must be an array of associative arrays.');
            }
            return json_encode(array('data' => $array));
        }
        else {
            throw new TSheetsException('expected array, but received ' . gettype($array));
        }
    }

}   // end TSheetsRestClient


/**
 * Class TSheetsException
 */
class TSheetsException extends Exception {
    public $http_code;
    public $http_body;
    public $error_code;
    public $error_text;
    public $error_properties;
    public $full_url;

    public function __construct($message, $full_url='', $http_code=null, $http_body=null, $error_text=null, $error_code=null) {
        parent::__construct($message);
        $this->http_code = $http_code;
        $this->http_body = $http_body;
        $this->error_code = $error_code;
        $this->error_text = $error_text;
        $this->full_url = $full_url;

        $this->error_properties = array();
        if ($this->http_body) {
            $this->error_properties = json_decode($this->http_body);
        }
    }
}


/**
 * OutputFormat is used to specify the return format of API calls. By default the library returns associative arrays.
 * If you would prefer results to come back as a StdObject or as raw json text, use these in a call to set_output_format.
 *
 * Possible values: AssociativeArray (default), Object, Raw
 *
 * @see TSheets::set_output_format
 */
abstract class OutputFormat
{
    const AssociativeArray = 0;
    const Object = 1;
    const Raw = 2;
    // NOTE: If you add an option, update the valid method below too.

    public static function valid($output_format) {
        switch($output_format) {
            case OutputFormat::AssociativeArray:
            case OutputFormat::Object:
            case OutputFormat::Raw:
                return true;
            default:
                return false;
        }
    }
}


/**
 * Class ObjectType
 *
 * Used to specify what kind of object/entity API calls are operating on.
 * For example: $tsheets->get(ObjectType::CustomFields)
 */
abstract class ObjectType
{
    const Users = 'users';    
    const CurrentUser = 'current_user';
    const EffectiveSettings = 'effective_settings';
    const Jobcodes = 'jobcodes';
    const JobcodeAssignments = 'jobcode_assignments';
    const CustomFields = 'customfields';
    const CustomFieldItems = 'customfielditems';
    const Timesheets = 'timesheets';
    const TimesheetsDeleted = 'timesheets_deleted';
    const Geolocations = 'geolocations';
    const LastModifiedTimestamps = 'last_modified_timestamps';
    const Notifications = 'notifications';
    const Reminders = 'reminders';
}


/**
 * Class ReportType
 *
 * Used to specify what kind of report API calls are made for.
 * For example: $tsheets->get_report(ReportType::Project)
 */
abstract class ReportType
{
    const Project = 'project';
    const Payroll = 'payroll';
    const CurrentTotals = 'current_totals';
}