<?php
/**
 */

class WeiboStrategy extends OpauthStrategy{
	
	/**
	 * Compulsory config keys, listed as unassociative arrays
	 * eg. array('app_id', 'app_secret');
	 */
	public $expects = array('app_id_CN', 'app_secret_CN', 'app_domain_CN','app_id_AU', 'app_secret_AU', 'app_domain_AU');
	
	/**
	 * Optional config keys with respective default values, listed as associative arrays
	 * eg. array('scope' => 'email');
	 */
	public $defaults = array(
		'redirect_uri' => '{complete_url_to_strategy}int_callback'
	);
	
	private $connection = null;
	
	public function __construct($strategy, $env){
		parent::__construct($strategy, $env);
		$this->initConnection();
	}

	/**
	 * Auth request
	 */
	public function request(){
		
		$callback_url = $this->strategy['redirect_uri'];
		
		$this->redirect($this->connection->getAuthorizeURL( $callback_url ));
	}
	
	/**
	 * Internal callback, after Weibo's OAuth
	 */
	public function int_callback(){
		if (array_key_exists('code', $_GET) && !empty($_GET['code'])){
			
			$keys = array();
			$keys['code'] = trim($_GET['code']);
			$keys['redirect_uri'] = $this->strategy['redirect_uri'];
			$results = null;
			$headers = null;
			try {
				$results = $this->connection->getAccessToken( 'code', $keys ) ;
			} catch (OAuthException $e) {
			}
			if (!empty($results) && !empty($results['access_token'])){
				$me = $this->me($results['access_token'], $results['uid']);
				$this->auth = array(
					'provider' => 'Weibo',
					'uid' => $me['id'],
					'info' => array(
						'name' => $me['name'],
						'image' => $me['profile_image_url']
					),
					'credentials' => array(
						'token' => $results['access_token'],
						'expires' => date('c', time() + $results['expires_in'])
					),
					'raw' => $me
				);
				
				if (isset($me['email '])) $this->auth['info']['email'] = $me['email'];
				if (isset($me['verified_contact_email'])) $this->auth['info']['email'] = $me['verified_contact_email'];
				if (isset($me['screen_name'])) $this->auth['info']['nickname'] = $me['screen_name'];
				if (isset($me['name'])) $this->auth['info']['first_name'] = $me['name'];		
				if (isset($me['location'])) $this->auth['info']['location'] = $me['location'];
				if (isset($me['url'])) $this->auth['info']['urls']['website'] = $me['url'];
				
				if(!isset($this->auth['info']['email']) || !$this->auth['info']['email']){
					
					$opauthID = OpauthIdentity::get()->filter('UID',$me['id'])->first();
					
					if(!$opauthID || !$opauthID->Member() || !$opauthID->Member()->ID){
						$this->env['callback_url'] = '/weiboopauth/addemail/';
					}
					
					$this->callback();
				}else{
					$this->callback();
				}
				
			}
			else{
				$error = array(
					'provider' => 'Weibo',
					'code' => 'access_token_error',
					'message' => 'Failed when attempting to obtain access token',
					'raw' => $headers
				);

				$this->errorCallback($error);
			}
		}
		else{
			$error = array(
				'provider' => 'Weibo',
				'code' => $_GET['error'],
				'message' => $_GET['error_description'],
				'raw' => $_GET
			);
			
			$this->errorCallback($error);
		}
	}
	
	/**
	 * Queries Facebook Graph API for user info
	 *
	 * @param string $access_token 
	 * @return array Parsed JSON results
	 */
	private function me($access_token, $uid){
		$operation = $this->getOperation($access_token);
		$me = $operation->show_user_by_id($uid);
		if (!empty($me)){
			return $me;
		}
		else{
			$error = array(
				'provider' => 'Weibo',
				'code' => 'me_error',
				'message' => 'Failed when attempting to query for user information',
				'raw' => array(
					'response' => $me,
					'headers' => $headers
				)
			);

			$this->errorCallback($error);
		}
	}
	
	private function initConnection(){
		
		$countryCode = $_SERVER['HTTP_HOST'] == $this->strategy['app_domain_AU'] ? 'AU': 'CN' ;
		$this->connection = new SaeTOAuthV2( $this->strategy['app_id'.'_'.$countryCode] , $this->strategy['app_secret'.'_'.$countryCode] );
		
	}
	
	private function getOperation($access_token){
		$countryCode = $_SERVER['HTTP_HOST'] == $this->strategy['app_domain'] ? 'AU' : 'CN';
		return new SaeTClientV2($this->strategy['app_id'.'_'.$countryCode], $this->strategy['app_secret'.'_'.$countryCode], $access_token);
	}
}