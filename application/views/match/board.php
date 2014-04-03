
<!DOCTYPE html>

<html>
	<head>
	<script src="http://code.jquery.com/jquery-latest.js"></script>
	<script src="<?= base_url() ?>/js/jquery.timers.js"></script>
	<script>

		var otherUser = "<?= $otherUser->login ?>";
		var user = "<?= $user->login ?>";
		var id = "<?= $user->id ?>";
		var p1 = "<?= $p1 ?>";
		var p2 = "<?= $p2 ?>";
		var status = "<?= $status ?>";
		var p1img = "<img src=" + "<?= base_url() ?>/images/blue.jpg" + " height='40' width='40'>";
		var p2img = "<img src=" + "<?= base_url() ?>/images/yellow.jpg" + " height='40' width='40'>";
		
		$(function(){
			//Receive data
			$('body').everyTime(1000,function(){
				var url = "<?= base_url() ?>index.php/board/getBoard";
				//update screen client's board
				$.getJSON(url, function (data_board,text,jqXHR){
					if (data_board) {
						if (p1 == id){
							$('#player').html("You are player1, your colour is <span id='blue'>Blue</span>");
						} else if (p2 == id) {
							$('#player').html("You are player2, your colour is <span id='yellow'>Yellow</span>");
						}
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
				url = "<?= base_url() ?>index.php/board/getMsg";
				$.getJSON(url, function (data,text,jqXHR){
					if (data.status=='success') {
						var conversation = $('[name=conversation]').val();
						var msg = data.message;
						if (msg && msg.length > 0)
							$('[name=conversation]').val(
							conversation + "\n" + otherUser + "'s move: column "+ msg + "\nPlease make the next move.");
					}
				});
			});
			//Send data
			$('.board').click(function(){
				var row = $(this).attr('id');
				$('[name=msg]').val(row);
				$( "form" ).trigger( "submit");
			});
			$('form').submit(function(){
				var arguments = $(this).serialize();
				var url = "<?= base_url() ?>index.php/board/postMsg";
				$.post(url,arguments, function (data,textStatus,jqXHR){
					var conversation = $('[name=conversation]').val();
					//append message to the end of conversation
					if (data){
						data = JSON.parse(data);
						var msg = data.message;
						if (data.status == "success"){
							$('[name=conversation]').val(conversation + "\n" + user + "'s move: column" + msg);
						} else {
							$('[name=conversation]').val(conversation + "\nINVALID MOVE: " + msg);
						}
					}
				});
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

