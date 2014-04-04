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

			//Create board array
    		$board = array();
			for ($x=0; $x<7; $x++){
				array_push($board, array());
				for ($y=0; $y<6; $y++){
					array_push($board[$x], -1);
				}
			}
			$_SESSION['board'] = $board;
			$serialized = array();
			for ($x = 0; $x < 7; $x++){
				array_push($serialized, serialize($_SESSION['board'][$x]));
			}
			$serialized = serialize($serialized);
			$this->match_model->updateBoard($match->id, $serialized);
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

	    $data['main'] = 'match/board';
	    $data['title'] = 'Connect 4 - Game';
		$this->load->view('template',$data);
    }

 	function postMsg() {
		$this->load->model('user_model');
		$this->load->model('match_model');

		$user = $_SESSION['user'];
		$turn = $_SESSION['turn'];
		
		if (!$turn){
			$errormsg="Not Your turn";
			goto error;
		}

		$user = $this->user_model->getExclusive($user->login);
		if ($user->user_status_id != User::PLAYING) {	
			$errormsg="Not in PLAYING state";
			goto error;
		}
		
		$match = $this->match_model->get($user->match_id);			
		
		$msg = $this->input->post('msg');
		
		//Check if message is a number
		if (is_numeric($msg)){
			$col = (int) $msg;
			if ($col > 6){
				$errormsg="number too big, has to be less than 7";
				goto error;
			}
		} else {
			$errormsg="message not a number: " . $msg;
			goto error;
		}
		
		$check_cell = 0;
		if ($_SESSION['board'][$col][0] != -1){ //Column is full
			$errormsg="Column is full";
			goto error;
		}
		
		$row = -1;
		
		while ($check_cell <= 5){
			if ($check_cell + 1 > 5){
				$row = 5;
				break;
			} elseif ($_SESSION['board'][$col][$check_cell + 1] != -1){ //Insert to bottom most
				$row = $check_cell;
				break;
			} 
			$check_cell += 1;
		}
		$_SESSION['board'][$col][$row] = $user->id;
		
		
		$win = ($col - 3 >= 0 && $_SESSION['board'][$col - 3][$row] == $user->id && $_SESSION['board'][$col - 2][$row] == $user->id && $_SESSION['board'][$col - 1][$row] == $user->id) ||
			   ($col - 2 >= 0 && $col + 1 < 7 && $_SESSION['board'][$col - 2][$row] == $user->id && $_SESSION['board'][$col - 1][$row] == $user->id && $_SESSION['board'][$col + 1][$row] == $user->id) ||
			   ($col - 1 >= 0 && $col + 2 < 7 && $_SESSION['board'][$col - 1][$row] == $user->id && $_SESSION['board'][$col + 1][$row] == $user->id && $_SESSION['board'][$col + 2][$row] == $user->id) ||
			   ($col + 3 < 7 && $_SESSION['board'][$col + 1][$row] == $user->id && $_SESSION['board'][$col + 1][$row] == $user->id && $_SESSION['board'][$col + 2][$row] == $user->id && $_SESSION['board'][$col + 3][$row] == $user->id) ||
	
			   ($row - 3 >= 0 && $_SESSION['board'][$col][$row - 3] == $user->id && $_SESSION['board'][$col][$row - 2] == $user->id && $_SESSION['board'][$col][$row - 1] == $user->id) ||
			   ($row - 2 >= 0 && $row + 1 < 6 && $_SESSION['board'][$col][$row - 2] == $user->id && $_SESSION['board'][$col][$row - 1] == $user->id && $_SESSION['board'][$row + 1] == $user->id) ||
			   ($row - 1 >= 0 && $row + 2 < 6 && $_SESSION['board'][$col][$row - 1] == $user->id && $_SESSION['board'][$col][$row + 1] == $user->id && $_SESSION['board'][$row + 2] == $user->id) ||
			   ($row + 3 < 6 && $_SESSION['board'][$col][$row + 1] == $user->id && $_SESSION['board'][$col][$row + 1] == $user->id && $_SESSION['board'][$col][$row + 2] == $user->id && $_SESSION['board'][$col][$row + 3] == $user->id) ||
	
			   ($col - 3 >= 0 && $row - 3 >= 0 && $_SESSION['board'][$col - 3][$row - 3] == $user->id && $_SESSION['board'][$col - 2][$row - 2] == $user->id && $_SESSION['board'][$col - 1][$row - 1] == $user->id) ||
			   ($col - 2 >= 0 && $col + 1 < 7 && $row - 2 >= 0 && $row + 1 < 6 && $_SESSION['board'][$col - 2][$row - 2] == $user->id && $_SESSION['board'][$col - 1][$row - 1] == $user->id && $_SESSION['board'][$col + 1][$row + 1] == $user->id) ||
			   ($col - 1 >= 0 && $col + 2 < 7 && $row - 1 >= 0 && $row + 2 < 6 && $_SESSION['board'][$col - 1][$row - 1] == $user->id && $_SESSION['board'][$col + 1][$row + 1] == $user->id && $_SESSION['board'][$col + 2][$row + 2] == $user->id) ||
			   ($col + 3 < 7 && $row + 3 < 6 && $_SESSION['board'][$col + 1][$row + 1] == $user->id && $_SESSION['board'][$col + 2][$row + 2] == $user->id && $_SESSION['board'][$col + 3][$row + 3] == $user->id) ||
	
			   ($col - 3 >= 0 && $row + 3 < 6 && $_SESSION['board'][$col - 3][$row + 3] == $user->id && $_SESSION['board'][$col - 2][$row + 2] == $user->id && $_SESSION['board'][$col - 1][$row + 1] == $user->id) ||
			   ($col - 2 >= 0 && $col + 1 < 7 && $row - 1 >= 0 && $row + 2 < 6 && $_SESSION['board'][$col - 2][$row + 2] == $user->id && $_SESSION['board'][$col - 1][$row - 1] == $user->id && $_SESSION['board'][$col + 1][$row - 1] == $user->id) ||
			   ($col - 1 >= 0 && $col + 2 < 7 && $row - 2 >= 0 && $row + 1 < 6 && $_SESSION['board'][$col - 1][$row + 1] == $user->id && $_SESSION['board'][$col + 1][$row - 1] == $user->id && $_SESSION['board'][$col + 2][$row - 2] == $user->id) ||
			   ($col + 3 < 7 && $row - 3 <= 0 && $_SESSION['board'][$col + 1][$row - 1] == $user->id && $_SESSION['board'][$col + 2][$row - 2] == $user->id && $_SESSION['board'][$col + 3][$row - 3] == $user->id);
		
		$tie = true;
		for ($x = 0; $x < 7; $x++) {
			for ($y = 0; $y < 6; $y++) {
					if ($_SESSION['board'][$x][$y] == -1) {
						$tie = false;
					}
			}
		}
		
		if ($win && $user->id == $match->user1_id) {
			$this->match_model->updateStatus($match->id, 2);
			$this->user_model->updateStatus($user->id, 2);
			$msg = "\nPlayer 1 wins! Game over!\n";
		} else if ($win && $user->id == $match->user2_id) {
			$this->match_model->updateStatus($match->id, 3);
			$this->user_model->updateStatus($user->id, 2);
			$msg = "\nPlayer 2 wins! Game over!\n";
		} else if ($tie) {
			$this->match_model->updateStatus($match->id, 4);
			$this->user_model->updateStatus($user->id, 2);
			$msg = "\nTie Game! Game over!\n";
		}
		
		//Begin serialize the array and put it into the database
		$serialized = array();
		for ($x = 0; $x < 7; $x++){
			//Serialize each column independently
			array_push($serialized, serialize($_SESSION['board'][$x]));
		}
		$serialized = serialize($serialized); //Serialize the whole 2d array
		$this->match_model->updateBoard($match->id, $serialized);
		
		if ($match->user1_id == $user->id)  {
			$msg = $match->u1_msg == ''? $msg :  $match->u1_msg . "\n" . $msg;
			$this->match_model->updateMsgU1($match->id, $msg);
		}
		else {
			$msg = $match->u2_msg == ''? $msg :  $match->u2_msg . "\n" . $msg;
			$this->match_model->updateMsgU2($match->id, $msg);
		}
		$_SESSION['turn'] = FALSE;

		echo json_encode(array('status'=>'success', 'message'=>$msg));
		return;
		
 		$errormsg="Missing argument";
 		
		error:
			echo json_encode(array('status'=>'failure','message'=>$errormsg));
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
		
		if ($match->match_status_id >= 2) {
			$this->user_model->updateStatus($user->id, 2);
		}
		
		
		//update board array
		$_SESSION['board'] = unserialize($match->board_state);
		$unserialized = array();
		for ($x = 0; $x < 7; $x++){
			array_push($unserialized, unserialize($_SESSION['board'][$x]));
		}
		$_SESSION['board'] = $unserialized;
		//print_r($_SESSION['board']);		
 		
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
	
	/*For client side to get the new board by ajax*/
	function getBoard(){
		echo json_encode($_SESSION['board']);
	}
	
	function getPlayer(){
		$this->load->model('user_model');
 		$this->load->model('match_model');
 		
 		$user = $_SESSION['user'];
 		$user = $this->user_model->get($user->login);
 		if ($user->user_status_id != User::PLAYING) {
 			echo json_encode(array('p1'=>-1,'p2'=>-1));
 		}
		$match = $this->match_model->getExclusive($user->match_id);	
		if ($match){
			$p1 = $match->user1_id;
			$p2 = $match->user2_id;
			echo json_encode(array('p1'=>$p1,'p2'=>$p2));
		} else {
			echo json_encode(array('p1'=>-1,'p2'=>-1));
		}		
	}
	
 }

