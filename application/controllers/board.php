<?php

class Board extends CI_Controller {
     
    function __construct() {
    		// Call the Controller constructor
	    	parent::__construct();
	    	session_start();
    } 
          
    public function _remap($method, $params = array()) {
	    	// enforce access control to protected functions	
    		
    		if (!isset($_SESSION['user']))
   			redirect('account/loginForm', 'refresh'); //Then we redirect to the index page again
 	    	
	    	return call_user_func_array(array($this, $method), $params);
    }
    
    
    function index() {
		$user = $_SESSION['user'];
    		    	
    	$this->load->model('user_model');
    	$this->load->model('invite_model');
    	$this->load->model('match_model');
    	
    	$user = $this->user_model->get($user->login);

    	$invite = $this->invite_model->get($user->invite_id);
    	
    	if ($user->user_status_id == User::WAITING) {
    		$invite = $this->invite_model->get($user->invite_id);
    		$otherUser = $this->user_model->getFromId($invite->user2_id);
    	}
    	else if ($user->user_status_id == User::PLAYING) {
    		$match = $this->match_model->get($user->match_id);
    		if ($match->user1_id == $user->id)
    			$otherUser = $this->user_model->getFromId($match->user2_id);
    		else
    			$otherUser = $this->user_model->getFromId($match->user1_id);
    	}
    	
    	$data['user']=$user;
    	$data['otherUser']=$otherUser;
    	
    	switch($user->user_status_id) {
    		case User::PLAYING:	
    			$data['status'] = 'playing';
    			break;
    		case User::WAITING:
    			$data['status'] = 'waiting';
    			break;
    	}

    	//Creat board array
    	$board = new ArrayObject();
		for ($x=0; $x<7; $x++){
			$board->append(new ArrayObject());
			for ($y=0; $y<6; $y++){
				$board[$x]->append(0);
			}
		}
		$_SESSION['board'] = $board;

	    $data['main'] = 'match/board';
	    $data['title'] = 'Connect 4 - Game';
		$this->load->view('template',$data);
    }

 	function postMsg() {
		$this->load->model('user_model');
		$this->load->model('match_model');

		$user = $_SESSION['user'];
		$turn = $_SESSION['turn'];
		
		if (!$turn)
			goto not_turn;

		$user = $this->user_model->getExclusive($user->login);
		if ($user->user_status_id != User::PLAYING) {	
		$errormsg="Not in PLAYING state";
			goto error;
		}
		
		$match = $this->match_model->get($user->match_id);			
		
		//Change this to get row
		$msg = $this->input->post('msg');
		
		if ($match->user1_id == $user->id)  {
			$msg = $match->u1_msg == ''? $msg :  $match->u1_msg . "\n" . $msg;
			$this->match_model->updateMsgU1($match->id, $msg);
		}
		else {
			$msg = $match->u2_msg == ''? $msg :  $match->u2_msg . "\n" . $msg;
			$this->match_model->updateMsgU2($match->id, $msg);
		}
		$_SESSION['turn'] = FALSE;

		echo json_encode(array('status'=>'success', 'msg'=>$msg));
		return;
		
 		$errormsg="Missing argument";
 		
		error:
			echo json_encode(array('status'=>'failure','message'=>$errormsg));
		not_turn:
			echo json_encode(array('status'=>'failure','message'=>"Not your turn!"));
 	}
 
	function getMsg() {

 		$this->load->model('user_model');
 		$this->load->model('match_model');
 			
 		$user = $_SESSION['user'];
 		$user = $this->user_model->get($user->login);
 		if ($user->user_status_id != User::PLAYING) {	
 			$errormsg="Not in PLAYING state";
 			goto error;
 		}
 		// start transactional mode  
 		$this->db->trans_begin();
 			
 		$match = $this->match_model->getExclusive($user->match_id);			
 		
 		//Look for which user's message to get
 		if ($match->user1_id == $user->id) {
			$msg = $match->u2_msg;
 			$this->match_model->updateMsgU2($match->id,"");
 		}
 		else {
 			$msg = $match->u1_msg;
 			$this->match_model->updateMsgU1($match->id,"");
 		}

 		if (strlen($msg) > 0) //Read something
 			$_SESSION['turn'] = TRUE;

 		if ($this->db->trans_status() === FALSE) {
 			$errormsg = "Transaction error";
 			goto transactionerror;
 		}
 		
 		// if all went well commit changes
 		$this->db->trans_commit();
 		
 		echo json_encode(array('status'=>'success','message'=>$msg));
 		
		return;
		
		transactionerror:
		$this->db->trans_rollback();
		
		error:
		echo json_encode(array('status'=>'failure','message'=>$errormsg));
 	}
 	
 }

