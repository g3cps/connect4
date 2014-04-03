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
		if ($user->id < $otherUser->id){
			$data['p1'] = $user->id;
			$data['p2'] = $otherUser->id;
		} else {
			$data['p2'] = $user->id;
			$data['p1'] = $otherUser->id;
		}
		
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
		} else {
			$errormsg="message not a number: " . $msg;
			goto error;
		}
		
		$check_cell = 0;
		if ($_SESSION['board'][$col][0] != -1){ //Column is full
			$errormsg="Column is full";
			goto error;
		}
		while ($check_cell <= 5){
			if ($check_cell + 1 > 5){
				$_SESSION['board'][$col][5] = $user->id;
				break;
			} elseif ($_SESSION['board'][$col][$check_cell + 1] != -1){ //Insert to bottom most
				$_SESSION['board'][$col][$check_cell] = $user->id;
				break;
			} 
			$check_cell += 1;
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
		echo json_encode(array('p1'=>$_SESSION['p1'],'p2'=>$_SESSION['p2']));
	}
 	
	function checkWin($x, $y, $player, $board) {
		return (($x - 3 >= 0 && $board[$x - 3][$y] == $player && $board[$x - 2][$y] == $player && $board[$x - 1][$y] == $player) ||
			   ($x - 2 >= 0 && $x + 1 < 7 && $board[$x - 2][$y] == $player && $board[$x - 1][$y] == $player && $board[$x + 1][$y] == $player) ||
			   ($x - 1 >= 0 && $x + 2 < 7 && $board[$x - 1][$y] == $player && $board[$x + 1][$y] == $player && $board[$x + 2][$y] == $player) ||
			   ($x + 3 < 7 && $board[$x + 1][$y] == $player && $board[$x + 1][$y] == $player && $board[$x + 2][$y] == $player && $board[$x + 3][$y] == $player) ||
	
			   ($y - 3 >= 0 && $board[$x][$y - 3] == $player && $board[$x][$y - 2] == $player && $board[$x][$y - 1] == $player) ||
			   ($y - 2 >= 0 && $y + 1 < 6 && $board[$x][$y - 2] == $player && $board[$x][$y - 1] == $player && $board[$y + 1] == $player) ||
			   ($y - 1 >= 0 && $y + 2 < 6 && $board[$x][$y - 1] == $player && $board[$x][$y + 1] == $player && $board[$y + 2] == $player) ||
			   ($y + 3 < 6 && $board[$x][$y + 1] == $player && $board[$x][$y + 1] == $player && $board[$x][$y + 2] == $player && $board[$x][$y + 3] == $player) ||
	
			   ($x - 3 >= 0 && $y - 3 >= 0 && $board[$x - 3][$y - 3] == $player && $board[$x - 2][$y - 2] == $player && $board[$x - 1][$y - 1] == $player) ||
			   ($x - 2 >= 0 && $x + 1 < 7 && $y - 2 >= 0 && $y + 1 < 6 && $board[$x - 2][$y - 2] == $player && $board[$x - 1][$y - 1] == $player && $board[$x + 1][$y + 1] == $player) ||
			   ($x - 1 >= 0 && $x + 2 < 7 && $y - 1 >= 0 && $y + 2 < 6 && $board[$x - 1][$y - 1] == $player && $board[$x + 1][$y + 1] == $player && $board[$x + 2][$y + 2] == $player) ||
			   ($x + 3 < 7 && $y + 3 < 6 && $board[$x + 1][$y + 1] == $player && $board[$x + 2][$y + 2] == $player && $board[$x + 3][$y + 3] == $player) ||
	
			   ($x - 3 >= 0 && $y + 3 < 6 && $board[$x - 3][$y + 3] == $player && $board[$x - 2][$y + 2] == $player && $board[$x - 1][$y + 1] == $player) ||
			   ($x - 2 >= 0 && $x + 1 < 7 && $y - 1 >= 0 && $y + 2 < 6 && $board[$x - 2][$y + 2] == $player && $board[$x - 1][$y - 1] == $player && $board[$x + 1][$y - 1] == $player) ||
			   ($x - 1 >= 0 && $x + 2 < 7 && $y - 2 >= 0 && $y + 1 < 6 && $board[$x - 1][$y + 1] == $player && $board[$x + 1][$y - 1] == $player && $board[$x + 2][$y - 2] == $player) ||
			   ($x + 3 < 7 && $y - 3 <= 0 && $board[$x + 1][$y - 1] == $player && $board[$x + 2][$y - 2] == $player && $board[$x + 3][$y - 3] == $player));
	}
	
 }

