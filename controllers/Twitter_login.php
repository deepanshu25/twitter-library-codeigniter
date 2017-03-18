<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Twitter_login extends CI_Controller
{
	function __construct() {
		parent::__construct();
		$this->load->model('user');
		$this->load->library('session');
	}
	
	public function index()
	{
		$this->load->library('user_agent');
		include_once APPPATH."libraries/twitter-api/twitteroauth.php";
		$referrer=$this->agent->referrer();
		$referrer= strpos($referrer, BSURL)!== false ? $referrer : '/';
		$sessionuser=$this->session->userdata('userData');
		if(isset($sessionuser))
		{
			redirect($referrer,'refresh');
		}
		else
		{
			$this->session->set_userdata('referrer',$referrer);
			$twitter = new TwitterOAuth(TWITTER_CONSUMERKEY, TWITTER_SECRET);
			$request_token = $twitter->getRequestToken(OAUTH_CALLBACK);
			$this->session->set_userdata('twitter_token',$request_token);
			if($twitter->http_code == '200')
			{
				$twitter_url = $twitter->getAuthorizeURL($request_token['oauth_token']);
				redirect($twitter_url); 
			}
			else
			{
				die("Error connecting to twitter! try again later!");
			}
		}
	}

	public function login()
	{
		$sessionuser=$this->session->userdata('userData');
		if(isset($sessionuser))
		{
			redirect('/','refresh');
		}
		else
		{
			include_once APPPATH."libraries/twitter-api/twitteroauth.php";
			$oauth_token=$this->input->get('oauth_token');
			$session=$this->session->userdata('twitter_token');
			if(isset($oauth_token)  && $session['oauth_token'] == $oauth_token)
			{
				$connection = new TwitterOAuth(TWITTER_CONSUMERKEY, TWITTER_SECRET, $session['oauth_token'] , $session['oauth_token_secret']);
				$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
				if($connection->http_code == '200')
				{
					//Redirect user to twitter
					$user_info = $connection->get('account/verify_credentials'); 
					$name = explode(" ",$user_info->name);
					$fname = isset($name[0])?$name[0]:'';
					$lname = isset($name[1])?$name[1]:'';
					$userData['oauth_provider'] = 'twitter';
					$userData['oauth_uid'] = $user_info->id;
					$userData['first_name'] = $fname;
					$userData['last_name'] = $lname;
					$userData['email'] = '';
					$userData['profile_url'] = 'https://www.twitter.com/'.$user_info->screen_name;
					$userID = $this->user->checkUser($userData);
					$this->session->unset_userdata('twitter_token');
					if(!empty($userID))
					{
						$data['userData'] = $userData;
						$this->session->set_userdata('userData',$userData);
					} 
					else 
					{
					   $data['userData'] = array();
					}
					$urlref=$this->session->userdata('referrer');
					$this->session->unset_userdata('referrer');
					redirect($urlref,'refresh');
				}
			}
		}
	}
}
