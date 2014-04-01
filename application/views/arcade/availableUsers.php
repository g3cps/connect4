
<table>
<?php 
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-error.log");
error_log( "Hello, errors!" );


	if ($users) {
		foreach ($users as $user) {
			if ($user->id != $currentUser->id) {
?>		
			<tr>
			<td> 
			<?= anchor("arcade/invite?login=" . $user->login,$user->fullName()) ?> 
			</td>
			</tr>

<?php 	
			}
		}
	}
?>

</table>
