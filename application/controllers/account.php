<?php

class Account extends CI_Controller {
     
    function __construct() {
    		// Call the Controller constructor
	    	parent::__construct();
	    	session_start();
	    	$this->load->library('securimage');
			$this->load->helper('url');
			$this->load->helper('html');
    }
        
    public function _remap($method, $params = array()) {
	    	// enforce access control to protected functions	

    		$protected = array('updatePasswordForm','updatePassword','index','logout');
    		
    		if (in_array($method,$protected) && !isset($_SESSION['user']))
   			redirect('account/loginForm', 'refresh'); //Then we redirect to the index page again
 	    	
	    	return call_user_func_array(array($this, $method), $params);
    }
          
    
    function index(){
    		if (!isset($_SESSION['user'])){
   				redirect('account/loginForm', 'refresh'); //Then we redirect to the index page again
   			} else {
   				redirect('arcade/index', 'refresh');
   			}
    }

    function loginForm() {
    		$data['main'] = 'account/loginForm';
    		$data['title'] = 'Connect 4 - Login';
    		$this->load->view('template', $data);
    }
    
    function login() {
    		$this->load->library('form_validation');
    		$this->form_validation->set_rules('username', 'Username', 'required');
    		$this->form_validation->set_rules('password', 'Password', 'required');
    		$data['main'] = 'account/loginForm';
    		$data['title'] = 'Connect 4 - Login';
    		if ($this->form_validation->run() == FALSE)
    		{
    			$this->load->view('template', $data);
    		}
    		else
    		{
    			$login = $this->input->post('username');
    			$clearPassword = $this->input->post('password');
    			 
    			$this->load->model('user_model');
    		
    			$user = $this->user_model->get($login);
    			 
    			if (isset($user) && $user->comparePassword($clearPassword)) {
    				$_SESSION['user'] = $user;
    				$_SESSION['turn'] = FALSE; //Keep track of player's turn
    				$data['user']=$user;
    				
    				$this->user_model->updateStatus($user->id, User::AVAILABLE);
    				
    				redirect('arcade/index', 'refresh'); //redirect to the main application page
    			}
 			else {   			
				$data['errorMsg']='Incorrect username or password!';
 				$this->load->view('template',$data);
 			}
    		}
    }

    function logout() {
		$user = $_SESSION['user'];
    		$this->load->model('user_model');
	    	$this->user_model->updateStatus($user->id, User::OFFLINE);
    		session_destroy();
    		redirect('account/index', 'refresh'); //Then we redirect to the index page again
    }

    function newForm() {
    		$data['main'] = 'account/newForm';
    		$data['title'] = 'Connect 4 - Create Account';
	    	$this->load->view('template', $data);
    }
    
    function captcha() {
		$this->securimage->show();
	}
    
    function createNew() {
		    $captcha = $this->input->post('captcha');
    		$this->load->library('form_validation');
    	    $this->form_validation->set_rules('username', 'Username', 'required|is_unique[user.login]');
	    	$this->form_validation->set_rules('password', 'Password', 'required');
	    	$this->form_validation->set_rules('first', 'First', "required");
	    	$this->form_validation->set_rules('last', 'last', "required");
	    	$this->form_validation->set_rules('email', 'Email', "required|is_unique[user.email]");
	    	
	    
	    	if ($this->form_validation->run() == FALSE || $this->securimage->check($captcha) == FALSE)
	    	{
	    		$data['main'] = 'account/newForm';
	    		$data['title'] = 'Connect 4 - Create Account';
	    		$this->load->view('template', $data);
	    	}
	    	else  
	    	{
	    		$user = new User();
	    		 
	    		$user->login = $this->input->post('username');
	    		$user->first = $this->input->post('first');
	    		$user->last = $this->input->post('last');
	    		$clearPassword = $this->input->post('password');
	    		$user->encryptPassword($clearPassword);
	    		$user->email = $this->input->post('email');
	    		
	    		$this->load->model('user_model');
	    		 
	    		
	    		$error = $this->user_model->insert($user);
	    		$data['main'] = 'account/loginForm';
	    		$data['title'] = 'Connect 4 - Login';
	    		$this->load->view('template', $data);
	    	}
    }

    
    function updatePasswordForm() {
    		$data['main'] = 'account/updatePasswordForm';
    		$data['title'] = 'Connect 4 - Update Password';
	    	$this->load->view('template', $data);
    }
    
    function updatePassword() {
	    	$this->load->library('form_validation');
	    	$this->form_validation->set_rules('oldPassword', 'Old Password', 'required');
	    	$this->form_validation->set_rules('newPassword', 'New Password', 'required');
	    	$data['main'] = 'account/updatePasswordForm';
	    	$data['title'] = 'Connect 4 - Update Password';
	    	 
	    	if ($this->form_validation->run() == FALSE)
	    	{
	    		$this->load->view('template', $data);
	    	}
	    	else
	    	{
	    		$user = $_SESSION['user'];
	    		
	    		$oldPassword = $this->input->post('oldPassword');
	    		$newPassword = $this->input->post('newPassword');
	    		 
	    		if ($user->comparePassword($oldPassword)) {
	    			$user->encryptPassword($newPassword);
	    			$this->load->model('user_model');
	    			$this->user_model->updatePassword($user);
	    			redirect('arcade/index', 'refresh'); //Then we redirect to the index page again
	    		}
	    		else {
	    			$data['errorMsg']="Incorrect password!";
	    			$this->load->view('template', $data);
	    		}
	    	}
    }
    
    function recoverPasswordForm() {
    		$data['main'] = 'account/recoverPasswordForm';
    		$data['title'] = 'Connect 4 - Password Recovery';
    		$this->load->view('template', $data);
    }
    
    function recoverPassword() {
	    	$this->load->library('form_validation');
	    	$this->form_validation->set_rules('email', 'email', 'required');
	    	$data['title'] = 'Connect 4 - Password Recovery';

	    	if ($this->form_validation->run() == FALSE)
	    	{
	    		$data['main'] = 'account/recoverPasswordForm';
	    		$this->load->view('template', $data);
	    	}
	    	else
	    	{ 
	    		$email = $this->input->post('email');
	    		$this->load->model('user_model');
	    		$user = $this->user_model->getFromEmail($email);

	    		if (isset($user)) {
	    			$newPassword = $user->initPassword();
	    			$this->user_model->updatePassword($user);
	    			
	    			$this->load->library('email');
	    		
	    			$config['protocol']    = 'smtp';
	    			$config['smtp_host']    = 'ssl://smtp.gmail.com';
	    			$config['smtp_port']    = '465';
	    			$config['smtp_timeout'] = '7';
	    			$config['smtp_user']    = 'worldbestcandy@gmail.com';
	    			$config['smtp_pass']    = 'csc309candy';
	    			$config['charset']    = 'utf-8';
	    			$config['newline']    = "\r\n";
	    			$config['mailtype'] = 'text'; // or html
	    			$config['validation'] = TRUE; // bool whether to validate email or not
	    			
		    	  	$this->email->initialize($config);
	    			
	    			$this->email->from('csc309Login@cs.toronto.edu', 'Login App');
	    			$this->email->to($user->email);
	    			
	    			$this->email->subject('Password recovery');
	    			$this->email->message("Your new password is $newPassword");
	    			
	    			$result = $this->email->send();
	    			
	    			//$data['errorMsg'] = $this->email->print_debugger();	
	    			
	    			//$this->load->view('emailPage',$data);
	    			$data['main'] = 'account/emailPage';
	    			$this->load->view('template', $data);
	    			
	    		}
	    		else {
	    			$data['errorMsg']="No record exists for this email!";
	    			$data['main'] = 'account/recoverPasswordForm';
	    			$this->load->view('account/recoverPasswordForm',$data);
	    		}
	    	}
    }    
 }

