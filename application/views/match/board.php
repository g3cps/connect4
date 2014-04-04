
<!DOCTYPE html>

<html>
	<head>
	<script src="http://code.jquery.com/jquery-latest.js"></script>
	<script src="<?= base_url() ?>/js/jquery.timers.js"></script>
	<script>

		var otherUser = "<?= $otherUser->login ?>";
		var user = "<?= $user->login ?>";
		var id = "<?= $user->id ?>";
		var status = "<?= $status ?>";
		var p1img = "<img src=" + "<?= base_url() ?>/images/blue.jpg" + " height='40' width='40'>";
		var p2img = "<img src=" + "<?= base_url() ?>/images/yellow.jpg" + " height='40' width='40'>";
		var p1 = -1;
		var p2 = -2;
		var gotPlayer = false; //make sure both players are in the game
		
		//update screen client's board
		function get_board(){
			var url = "<?= base_url() ?>index.php/board/getBoard";
			$.getJSON(url, function (data_board,text,jqXHR){
				if (data_board) {
					for (var y = 0; y < 6; y++){
						for (var x = 0; x < 7; x++){
							if (data_board[x][y] != -1){
								var loc = "#"+x+"."+y;
								if (data_board[x][y] == p1){
									$(loc).html(p1img);
								} else {
									$(loc).html(p2img);
								}
							}
						}
					}
				}						
			});
		}
		
		function get_datetime(){
			var date = new Date();
			var datetime = "<" + date.getDate() + "/"
                + (date.getMonth()+1)  + "/" 
                + date.getFullYear() + " @ "  
                + date.getHours() + ":"  
                + date.getMinutes() + ":" 
                + date.getSeconds() + ">";
			return datetime;
		}
		
		$(function(){
			//Receive data
			$('body').everyTime(1000,function(){
				
				if (!gotPlayer){//Let client know which player he/she is playing as
					$.getJSON('<?= base_url() ?>index.php/board/getPlayer',function(data, text, jqZHR){
						if (data){
							p1 = data.p1;
							p2 = data.p2;
							if (p1 == id){
								$('#player').html("You are player1, your colour is <span id='blue'>Blue</span>");
								gotPlayer = true;
							} else if (p2 == id) {
								$('#player').html("You are player2, your colour is <span id='yellow'>Yellow</span>");
								gotPlayer = true;
							}
							var turn = '<?=$_SESSION['turn']?>';
							if (turn){
								$('#order').html("You get to make the first move!");
							} else {
								$('#order').html("You make the second move.");
							}
						}
					});
				}
				
				if (status == 'waiting') {
					$.getJSON('<?= base_url() ?>index.php/arcade/checkInvitation',function(data, text, jqZHR){
							if (data && data.status=='rejected') {
								alert("Sorry, your invitation to play was declined!");
								window.location.href = '<?= base_url() ?>index.php/arcade/index';
							}
							if (data && data.status=='accepted') {
								status = 'playing';
								$('#status').html('Playing ' + otherUser);
							}
							
					});
				}
				if (gotPlayer) {
					url = "<?= base_url() ?>index.php/board/getMsg";
					$.getJSON(url, function (data,text,jqXHR){
						if (data.status=='success') {
							var conversation = $('[name=conversation]').val();
							var msg = data.message;
							if (msg && msg.length > 0){
								var datetime = get_datetime();
								get_board();
								//append message to the start of conversation to show updates
								$('[name=conversation]').val(
								datetime + "\n    " + otherUser + "'s move: column "+ msg + "\n    Please make the next move.\n\n" + conversation);
							}
						}
					});
				}
			});
			//Send data
			$('.board').click(function(){
				var row = $(this).attr('id');
				$('[name=msg]').val(row);
				$( "form" ).trigger( "submit");
			});
			$('form').submit(function(){
				if (gotPlayer){
					var arguments = $(this).serialize();
					var url = "<?= base_url() ?>index.php/board/postMsg";
					$.post(url,arguments, function (data,textStatus,jqXHR){
						var conversation = $('[name=conversation]').val();
						if (data){
							var datetime = get_datetime();
							data = JSON.parse(data);
							var msg = data.message;
							//append message to the start of conversation to show updates
							if (data.status == "success"){
								$('[name=conversation]').val(datetime + "\n    " + user + "'s move: column " + msg + "\n\n" + conversation);
								get_board();
							} else {
								$('[name=conversation]').val(datetime + "\n    INVALID MOVE: " + msg + "\n\n" + conversation);
							}
						}
					});
				}
				return false;
			});	
		});
	
	</script>
	</head> 
<body>  
	<h1>Game Area</h1>

	<div>
	Hello <?= $user->fullName() ?>  <?= anchor('account/logout','(Logout)') ?>  
	</div>
	<div id='status'> 
	<?php 
		if ($status == "playing")
			echo "Playing " . $otherUser->login;
		else
			echo "Wating on " . $otherUser->login;
	?>
	</div>

	<div>
    	<p id="player"></p>
        <p id="order"></p>
        <div id='inst'>
    	<span>
        	<h3>To make a move:</h3>
            <p>Click on the board</p>
           	<p> - or - </p>
            <p>Submit a column number through the input at the bottom.</p>
        </span>
   		</div>	
		<table>
			<?php
				//Generate the table layout
				for ($y=0; $y<6; $y++){
					echo "<tr>";
					for ($x=0; $x<7; $x++){
						//id is x position, class has y position
						echo "<td class='board $y' id='$x'></td>";
					}
					echo "</tr>";
				}
			?>
		</table>
	</div>

<?php 
	echo form_textarea('conversation');
	echo form_open();
	echo form_input('msg');
	echo form_submit('Send','Send');
	echo form_close();
	
?>

	
	
	
</body>

</html>

