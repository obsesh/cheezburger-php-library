<?php
/**
 * Cheezburger REST API Library for PHP
 *
 * @description Cheezburger - PHP Client API Library
 * @copyright   Copyright (c) 2012 Michael Schonfeld
 * @autor       Michael Schonfeld <michael@dwolla.com>
 * @version     1.0.0
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

define ("API_SERVER", "https://api.cheezburger.com/v1/");

if (!function_exists('curl_init'))  throw new Exception("Cheezburger's API Client Library requires the CURL PHP extension.");
if (!function_exists('json_decode')) throw new Exception("Cheezburger's API Client Library requires the JSON PHP extension.");

class CheezburgerClient {
    private $apiKey;
    private $apiSecret;
    private $oauthToken;

    private $redirectUri;

    private $errorMessage = FALSE;

    public function __construct($apiKey = FALSE,
                                $apiSecret = FALSE,
                                $redirectUri = FALSE)
    {
        $this->apiKey       = $apiKey;
        $this->apiSecret    = $apiSecret;
        $this->redirectUri  = $redirectUri;
        $this->apiServerUrl = API_SERVER;
    }

    // ***********************
    // Authentication Methods
    // ***********************
    public function getAuthUrl()
    {
        $params = array(
            'client_id'     => $this->apiKey,
            'response_type' => 'code',
            'redirect_uri'  => $this->redirectUri
        );
        $url = 'https://api.cheezburger.com/oauth/authorize?' . http_build_query($params);

        return $url;
    }

    public function requestToken($code)
    {
        if(!$code) { return $this->_setError('Please pass an oauth code.'); }

        $params = array(
            'client_id'     => $this->apiKey,
            'client_secret' => $this->apiSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
            'code'          => $code
        );
        $url = 'https://api.cheezburger.com/oauth/access_token?'  . http_build_query($params);
        $response = $this->_curl($url, 'GET');

        if(isset($response['error']))
        {
            $this->errorMessage = $response['error_description'];
            return FALSE;
        }

        return $response['access_token'];
    }

    public function setToken($token) {
        if(!$token) { return $this->_setError('Please pass a token string.'); }

        $this->oauthToken = $token;

        return TRUE;
    }

    public function getToken() {
        return $this->oauthToken;
    }

    // ******************
    // Methods
    // ******************
    public function asset($asset_id)
    {
    	if(!$asset_id) { return $this->_setError('Please pass an asset ID.'); }

        $response = $this->_get("assets/{$asset_id}");

        $asset = $this->_parse($response);

        return $asset;
    }

    public function assetTypes()
    {
        $response = $this->_get('assettypes');

        $assettypes = $this->_parse($response);

        return $assettypes;
    }

    public function ohai($message = 'Test Message')
    {
        $response = $this->_get('ohai');

        $ohai = $this->_parse($response);

        return $ohai;
    }

    public function me()
    {
        $response = $this->_get('me');

        $me = $this->_parse($response);

        return $me;
    }

    public function user($user_id)
    {
    	if(!$user_id) { return $this->_setError('Please pass a user ID.'); }

        $response = $this->_get("users/{$user_id}");

        $user = $this->_parse($response);

        return $user;
    }
    
    public function siteTypes()
    {
        $response = $this->_get('sitetypes');

        $sitetypes = $this->_parse($response);

        return $sitetypes;
    }

    public function sites($parent_site_id = FALSE, $site_type_id = FALSE)
    {
        $params = array();
        if($parent_site_id) { $params['parent_site_id'] = $parent_site_id; }
        if($site_type_id) { $params['site_type_id'] = $site_type_id; }

        $response = $this->_get('sites', $params);

        $sites = $this->_parse($response);

        return $sites;
    }

    public function mySites()
    {
        $response = $this->_get('my/sites');

        $sites = $this->_parse($response);

        return $sites;
    }

    public function site($site_id)
    {
    	if(!$site_id) { return $this->_setError('Please pass a site ID.'); }

        $response = $this->_get("sites/{$site_id}");

        $site = $this->_parse($response);

        return $site;
    }

    // ***************
    // Public Helper methods
    // ***************
    public function getError()
    {
        if(!$this->errorMessage) { return FALSE; }

        $error = $this->errorMessage;
        $this->errorMessage = FALSE;

        return $error;
    }

    // ********************
    // Private Helper methods
    // ********************
    protected function _setError($message)
    {
        $this->errorMessage = $message;
        return FALSE;
    }

    protected function _parse($response)
    {
    	if(!isset($response['Success'])) {
	    	// Successful response?
	    	return $response;	
    	}
    	
        if(!$response['Success'])
        {
            $this->errorMessage = $response['Message'];
            return FALSE;
        }

        return FALSE;
    }

    protected function _post($request, $params = FALSE)
    {
        $params['access_token'] = $this->oauthToken;
        $url = $this->apiServerUrl . $request . "?" . http_build_query($params);

        $rawData = $this->_curl($url, 'POST', $params);

        return $rawData;
    }

    protected function _get($request, $params = array())
    {
        $params['access_token'] = $this->oauthToken;
        $url = $this->apiServerUrl . $request . "?" . http_build_query($params);

        $rawData = $this->_curl($url, 'GET');

        return $rawData;
    }

    protected function _curl($url, $method = 'GET', $params = array())
    {
    	// Set up our CURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-type: application/json;charset=UTF-8'));

        // Initiate request
        $rawData = curl_exec($ch);
        
        // If HTTP response wasn't 200,
        // log it as an error!
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($code !== 200) {
        	return array(
        		'Success' => FALSE,
        		'Message' => "Request failed. Server responded with: {$code}"
        	);
        }

        // All done with CURL
        curl_close($ch);

        // Otherwise, assume we got some
        // sort of a response
        return json_decode($rawData, TRUE);;
    }
}
?>