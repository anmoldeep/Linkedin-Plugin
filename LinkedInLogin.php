<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


class LinkedInLogin {
	const _AUTHORIZE_URL = 'https://www.linkedin.com/uas/oauth2/authorization';

	const _TOKEN_URL = 'https://www.linkedin.com/uas/oauth2/accessToken';

	const _BASE_URL = 'https://api.linkedin.com/v1';
//
	public $authorize_url    = "";
	public $token_info_url   = "";

	public $client_id        = "" ;
	public $client_secret    = "" ;
	public $redirect_uri     = "" ;
	public $refresh_token    = "" ;

	public $access_token_expires_in = "" ;
	public $access_token_expires_at = "" ;
// LinkedIn Application Key
	public $li_api_key;

	// LinkedIn Application Secret
	public $li_secret_key;

	// Stores Access Token

	// Stores OAuth Object
	public $oauth;

	// Stores the user redirect after login
	public $user_redirect = false;

	// Stores our LinkedIn options
	public $li_options;
//
	public $curl_authenticate_method = 'GET';
	public $access_token;
	public $token_url = 'https://www.linkedin.com/uas/oauth2/accessToken';
	public $api_base_url = 'https://api.linkedin.com/v1';
//--

	public $sign_token_name          = "oauth2_access_token";
	public $decode_json              = false;
	public $curl_time_out            = 30;
	public $curl_connect_time_out    = 30;
	public $curl_ssl_verifypeer      = false;
	public $curl_header              = array();
	public $curl_useragent           = "OAuth/2 Simple PHP Client v0.1; HybridAuth http://hybridauth.sourceforge.net/";
	public $curl_proxy               = null;

	//--
//--

	public $http_code             = "";
	public $http_info             = "";

	//--

	public function __construct()
{
	add_action('init', array($this, 'process_login'));
}
	// Returns LinkedIn authorization URL
	public function get_auth_url($redirect = false) {

		$state = wp_generate_password(12, false);
		$authorize_url = "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=865qz6kfz9mql7&redirect_uri=http%3A%2F%2Fwww.duopod.net%2Fjobapp%2Fcandidate-dashboard-2&state=".$state."&scope=r_basicprofile+r_emailaddress";

		// Store state in database in temporarily till checked back
		if (!isset($_SESSION['li_api_state'])) {
			$_SESSION['li_api_state'] = $state;
		}

		// Store redirect URL in session
		$_SESSION['li_api_redirect'] = $redirect;

		return $authorize_url;

	}


	// This function displays the login button on the default WP login page
/*	public function display_login_button() {

		// User is not logged in, display login button
		echo "<p><a rel='nofollow' href='" . $this->get_auth_url() . "'>
                                            <img alt='LinkedIn' src='" . plugins_url() . "/linkedin-login/includes/assets/img/linkedin-button.png' />
        </a></p>";

	}*/

	public function process_login()
	{
		// If this is not a linkedin sign-in request, do nothing
		if (!$this->is_linkedin_signin()) {
			return;
		}

		// Get profile XML response
		$xml = $this->get_linkedin_profile();


	}

	/*
	* Checks if this is a LinkedIn sign-in request for our plugin
	*/

	private function is_linkedin_signin() {

		// If no action is requested or the action is not ours
		if (!isset($_REQUEST['action'])) {
			return false;
		}

		// If a code is not returned, and no error as well, then OAuth did not proceed properly
		if (!isset($_REQUEST['code']) && !isset($_REQUEST['error'])) {
			return false;
		}
		/*
		 * Temporarily disabled this because we're getting two different states at random times

		  // If state is not set, or it is different than what we expect there might be a request forgery
		  if ( ! isset($_SESSION['li_api_state'] ) || $_REQUEST['state'] != $_SESSION['li_api_state']) {
		  return false;
		  }
		 */

		// This is a LinkedIn signing-request - unset state and return true
		unset($_SESSION['li_api_state']);

		return true;

	}

	/*
     * Get the user LinkedIN profile and return it as XML
     */
//changing to public
	public function get_linkedin_profile() {

		// Use GET method since POST isn't working

		// Request access token
		$response = $this->authenticate($_REQUEST['code']);
		$this->access_token = $response->{'access_token'};

		// Get first name, last name and email address, and load
		// response into XML object
		$xml = simplexml_load_string($this->get('https://api.linkedin.com/v1/people/~:(id,first-name,last-name,email-address,headline,specialties,positions:(id,title,summary,start-date,end-date,is-current,company),summary,site-standard-profile-request,picture-url,location:(name,country:(code)),industry)'));
		$email = (string) $xml->{'summary'};
		$email2 = (string) $xml->{'headline'};

		return $email.$email2;
		//return $xml;
	}
	function get( $url, $parameters = array() )
	{
		return $this->api( $url, 'GET', $parameters );
	}

	private function request2( $url, $params=false, $type="GET" )
	{

		if( $type == "GET" ){
			$url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query( $params );
		}

		$this->http_info = array();
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL            , $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
		curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
		curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );
		curl_setopt($ch, CURLOPT_HTTPHEADER     , $this->curl_header );

		if($this->curl_proxy){
			curl_setopt( $ch, CURLOPT_PROXY        , $this->curl_proxy);
		}

		if( $type == "POST" ){
			curl_setopt($ch, CURLOPT_POST, 1);
			if($params) curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		}

		$response = curl_exec($ch);
		//Hybrid_Logger::debug( "OAuth2Client::request(). dump request info: ", serialize( curl_getinfo($ch) ) );
		//Hybrid_Logger::debug( "OAuth2Client::request(). dump request result: ", serialize( $response ) );
		$this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ch));

		curl_close ($ch);

		return $response;
	}

	public function api( $url, $method = "GET", $parameters = array() )
	{
		if ( strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0 ) {
			$url = $this->api_base_url . $url;
		}

		$parameters[$this->sign_token_name] = $this->access_token;
		$response = null;

		switch( $method ){
			case 'GET'  : $response = $this->request2( $url, $parameters, "GET"  ); break;
			case 'POST' : $response = $this->request2( $url, $parameters, "POST" ); break;
		}

		if( $response && $this->decode_json ){
			$response = json_decode( $response );
		}

		return $response;
	}
	public function authenticate( $code )
	{
		//$client_id = "865qz6kfz9mql7";
		//$client_secret = "f8ahWMI186DPux1U";
		//$redirect_uri = "http://www.duopod.net/jobapp/candidate-dashboard-2";
		$params = array(
			"grant_type"    => "authorization_code",
			"client_id"     => "865qz6kfz9mql7",
			"client_secret" => "f8ahWMI186DPux1U",
			"redirect_uri"  => "http://www.duopod.net/jobapp/candidate-dashboard-2",
			"code"          => $_REQUEST['code']
		);
		$params2 = http_build_query( $params );

		$response = $this->request( );

		$response = $this->parseRequestResult( $response );

		if( ! $response || ! isset( $response->access_token ) ){
			throw new Exception( "The Authorization Service has return: " . $response->error );
		}

		if( isset( $response->access_token  ) )  $this->access_token           = $response->access_token;
		if( isset( $response->refresh_token ) ) $this->refresh_token           = $response->refresh_token;
		if( isset( $response->expires_in    ) ) $this->access_token_expires_in = $response->expires_in;

		// calculate when the access token expire
		if( isset($response->expires_in)) {
			$this->access_token_expires_at = time() + $response->expires_in;
		}

		return $response;
	}

	// -- utilities
//changed to public from private
	public function request( $type="GET" )
	{
		$params2 = array(
			"grant_type"    => "",
			"client_id"     => "",
			"client_secret" => "",
			"redirect_uri"  => "http://www.duopod.net/jobapp/candidate-dashboard-2",
			"code"          => $_REQUEST['code']
		);

		if( $type == "GET" ){
			$url = 'https://www.linkedin.com/uas/oauth2/accessToken' . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query( $params2 );
		}

		$this->http_info = array();
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL            , $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
		curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
		curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );
		curl_setopt($ch, CURLOPT_HTTPHEADER     , array('Content-Type: application/x-www-form-urlencoded'));

		if($this->curl_proxy){
			curl_setopt( $ch, CURLOPT_PROXY        , $this->curl_proxy);
		}

		if( $type == "POST" ){
			curl_setopt($ch, CURLOPT_POST, 1);
			if($params) curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		}

		$response = curl_exec($ch);
		//Hybrid_Logger::debug( "OAuth2Client::request(). dump request info: ", serialize( curl_getinfo($ch) ) );
		//Hybrid_Logger::debug( "OAuth2Client::request(). dump request result: ", serialize( $response ) );
		$this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ch));

		curl_close ($ch);

		return $response;
	}



	private function parseRequestResult( $result )
	{
		if( json_decode( $result ) ) return json_decode( $result );

		parse_str( $result, $ouput );

		$result = new StdClass();

		foreach( $ouput as $k => $v )
			$result->$k = $v;

		return $result;
	}
}
