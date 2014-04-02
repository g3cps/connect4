<?php
class Match  {
	const ACTIVE = 1;
	const U1WON = 2;
	const U2WON = 3;
	
	public $id;
	
	public $user1_id;  
	public $user2_id;
	public $board_state;
	
	public $match_status_id = self::ACTIVE;
		
}