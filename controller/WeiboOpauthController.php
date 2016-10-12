<?php
class WeiboOpauthController extends OpauthController{

	private static $allowed_actions = array(
			'addemail',
			'EmailForm'
	),
	$url_handlers = array(
			'addemail' => 'addemail'
	);
	
	public function index(SS_HTTPRequest $request) {
	}

	public function getSuccessBackURL(Member $member, OpauthIdentity $identity, &$redirectURL, $mode){
		$redirectURL = Session::get('OpauthBackURL');
		$redirectURL = preg_replace("/^url=/","",$redirectURL);
		$redirectURL = preg_replace("/\&/","?",$redirectURL,1);
		return null;
	}

	public function addemail(){

		// Don't load the Authentication module's CSS file, to cut down on requests

		$this->ThemeDir = 'themes/' . SSViewer::current_theme() . '/';
		
		Requirements::set_force_js_to_bottom(true);
		
		$template = 'Page';
		
		$form = $this->EmailForm();
		
		$content = '';

		$this->extend('updateEmailFormPage',$form, $template, $content);
		
		return $this->customise(array(
				'Content' => $content,
				'Form' => $form
		))->renderWith($template);
	}

	public function EmailForm(){
		$fields = new FieldList();
		$fields->add(EmailField::create('Email', _t('IRXWeibo_ss.EMAIL', 'Email'))->setDescription(_t('IRXWeibo_ss.ReservedEmail', 'This email is reserved for the account')));

		$actions = new FieldList(
				new FormAction('submit', 'Submit')
				);

		$validator = new RequiredFields('Email');

		return new Form($this, 'EmailForm', $fields, $actions, $validator);

	}

	public function submit($data, $form) {
		
		//Make sure that email hasn't already been used.
		$check = Member::get()->filter('Email', Convert::raw2sql($data['Email']))->first();
		
		if($check) {
			$form->addErrorMessage('Email', 'This email already exists', 'bad');
		
			return $this->redirectBack();
		}

		$opauth = OpauthAuthenticator::opauth(false);

		$response = $this->getOpauthResponse();

		if (!$response) {
			$response = array();
		}
		// Clear the response as it is only to be read once (if Session)
		Session::clear('opauth');

		// Handle all Opauth validation in this handy function
		try {
			$this->validateOpauthResponse($opauth, $response);
		}
		catch(OpauthValidationException $e) {
			return $this->handleOpauthException($e);
		}
		
		$response['auth']['info']['email'] = Convert::raw2sql($data['Email']);

		$identity = OpauthIdentity::factory($response);

		$member = $identity->findOrCreateMember();
		

		// If the member exists, associate it with the identity and log in
		if($member->isInDB() && $member->validate()->valid()) {
			if(!$identity->exists()) {
				$identity->write();
				$flag = self::AUTH_FLAG_LINK;
			}
			else {
				$flag = self::AUTH_FLAG_LOGIN;
			}
		}
		else {

			$flag = self::AUTH_FLAG_REGISTER;

			// Write the identity
			$identity->write();

			// Even if written, check validation - we might not have full fields
			$validationResult = $member->validate();
			if(!$validationResult->valid()) {
				// Keep a note of the identity ID
				Session::set('OpauthIdentityID', $identity->ID);
				// Set up the register form before it's output
				$regForm = $this->RegisterForm();
				$regForm->loadDataFrom($member);
				$regForm->setSessionData($member);
				$regForm->validate();
				return $this->redirect(parent::Link('profilecompletion'));
			}
			else {
				$member->write();
				$identity->MemberID = $member->ID;
				$identity->write();
			}
		}
		return $this->loginAndRedirect($member, $identity, $flag);

	}
	
	public function Link($action = null) {
		return Controller::join_links('weibo'.
				trim(self::config()->opauth_path,'/'),
				$action
				);
	}

}