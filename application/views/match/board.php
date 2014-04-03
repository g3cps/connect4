
<!DOCTYPE html>

<html>
	<head>
	<script src="http://code.jquery.com/jquery-latest.js"></script>
	<script src="<?= base_url() ?>/js/jquery.timers.js"></script>
	<script>

		var otherUser = "<?= $otherUser->login ?>";
		var user = "<?= $user->login ?>";
		var status = "<?= $status ?>";
		
		$(function(){
			//Receive data
			$('body').everyTime(2000,function(){
					var url = "<?= base_url() ?>index.php/board/getBoard";
					$.getJSON(url, function (data,text,jqXHR){
						$('#see').empty();
						if (data) {
							for (var y = 0; y < 6; y++){
								for (var x = 0; x < 7; x++){
									$('#see').append(data[x][y]);
								}
								$('#see').append("<br/>");
							}
						}
					});
					//alert("HERE");
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
						if (data && data.status=='success') {
							var conversation = $('[name=conversation]').val();
							var msg = data.message;
							if (msg && msg.length > 0)
								$('[name=conversation]').val(conversation + "\n" + otherUser + ": " + msg + "\n" + data);
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
					var msg = $('[name=msg]').val();
					//append message to the end of conversation
					$('[name=conversation]').val(conversation + "\n" + user + ": " + msg + "\n" + data);
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
	<div id='see'></div>
	<div id='status'> 
	<?php 
		if ($status == "playing")
			echo "Playing " . $otherUser->login;
		else
			echo "Wating on " . $otherUser->login;
	?>
	</div>

	<div>
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

