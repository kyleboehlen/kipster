<?PHP
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load PHPMailer through composer

require 'vendor/autoload.php';
require_once('config.php');

$mysqli = mysqli_connect('localhost', $database_user, $database_password, 'KIPSTER');
$host_query = "SELECT * FROM HOSTS;";
$host_query_results = mysqli_query($mysqli, $host_query);

echo "Number of hosts to check: ".mysqli_num_rows($host_query_results)."\n";

if(mysqli_num_rows($host_query_results) != 0){
	while($host_query_fields = mysqli_fetch_array($host_query_results, MYSQLI_ASSOC)){

		echo "IPADDRESS: ".$host_query_fields['IPADDRESS']."\n";

		$date = new DateTime('now');
		exec("ping -c 4 ".$host_query_fields['IPADDRESS'], $output, $result);

		echo "Result: ".$result."\n";

		if($result != 0){
			if($host_query_fields['UP'] == 1 || $host_query_fields['DOWNTIME'] == ''){
				$parent_up = true;

				$parent_query = "SELECT PARENT FROM HOSTRELATIONS WHERE ID = ".$host_query_fields['ID']." LIMIT 1;";

				$parent_query_results = mysqli_query($mysqli, $parent_query);

				if(mysqli_num_rows($parent_query_results) != 0){
					$parent_query_fields = mysqli_fetch_array($parent_query_results, MYSQLI_ASSOC);

					$parent_down_query = "SELECT UP FROM HOSTS WHERE ID = ".$parent_query_fields['PARENT']." LIMIT 1;";
					$parent_down_query_results = mysqli_query($mysqli, $parent_down_query);
					$parent_down_query_fields = mysqli_fetch_array($parent_down_query_results, MYSQLI_ASSOC);

					if($parent_down_query_fields['UP'] == 0){
						$parent_up = false;
					}
				}

				if(!$parent_up){
					$down_query = "UPDATE HOSTS SET UP = 0 WHERE ID = ".$host_query_fields['ID'].";";

					echo "Host parent is down. Setting host to offline, not logging downtime\n";
				}else{
					$down_query = "UPDATE HOSTS SET UP = 0, DOWNTIME = NOW() WHERE ID = ".$host_query_fields['ID'].";";

					echo "Running down query...\n";
				}

				mysqli_query($mysqli, $down_query);

			}elseif($host_query_fields['ALERTSENT'] == 0){
				$down_time = new DateTime($host_query_fields['DOWNTIME']);

				$down_minutes = $down_time->diff($date);

				$down_minutes = abs($down_minutes->i);
				echo "Host has been down for ".$down_minutes." minutes.\n";
				if($down_minutes >= $host_query_fields['ALERTTIME']){
					echo "Host has been down for too long, sending alert.\n";

					sendAlert($host_query_fields['ID'], false, $down_minutes);
				}else{
					echo "Host has not been down long enough to send alert.\n";
				}
			}else{
				echo "Alert has already been sent\n";
			}
		}else{
			if($host_query_fields['UP'] == 0){
				echo "Host has come back online, updating database...\n";

				if($host_query_fields['ALERTSENT'] == 1){
					sendAlert($host_query_fields['ID'], true, 0);
					echo "Sending up alert\n";
				}

				$up_query = "UPDATE HOSTS SET UP = 1, DOWNTIME = NULL, ALERTSENT = 0 WHERE ID = ".$host_query_fields['ID'].";";
				mysqli_query($mysqli, $up_query);
			}
		}
	}
}

function sendAlert($id, $up, $down_minutes){
	include 'config.php';

	$mysqli = mysqli_connect('localhost', $database_user, $database_password, 'KIPSTER');
	$mail = new PHPMailer(true);

	try{
		$mail->isSMTP();
		$mail->Host = $email_host;
		$mail->SMTPAuth = true;
		$mail->Username = $email_user;
		$mail->Password = $email_password;
		$mail->setFrom($sent_from, 'Kipster Alerts');

		$alert_query = "SELECT PEOPLESID, TEXT FROM ALERTS WHERE HOSTSID = ".$id.";";
		$alert_query_results = mysqli_query($mysqli, $alert_query);

		if(mysqli_num_rows($alert_query_results) != 0){
			while($alert_query_fields = mysqli_fetch_array($alert_query_results, MYSQLI_ASSOC)){
				$email_query = "SELECT * FROM PEOPLES WHERE ID = ".$alert_query_fields['PEOPLESID']." LIMIT 1;";
				$email_query_results = mysqli_query($mysqli, $email_query);
				$email_query_fields = mysqli_fetch_array($email_query_results, MYSQLI_ASSOC);
				if($alert_query_fields['TEXT'] == 1){
					$phone_query = "SELECT CARRIEREMAIL FROM CARRIERS WHERE CARRIERNAME = '".$email_query_fields['CARRIER']."' LIMIT 1;";
					$phone_query_results = mysqli_query($mysqli, $phone_query);
					$phone_query_fields = mysqli_fetch_array($phone_query_results, MYSQLI_ASSOC);

					$mail->addAddress($email_query_fields['PHONENUMBER'].$phone_query_fields['CARRIEREMAIL']);
				}else{
					$mail->addAddress($email_query_fields['EMAIL']);
				}
			}
		}else{
			$mail->addAddress($default_alert_address);
		}

		$message_query = "SELECT NAME, IPADDRESS FROM HOSTS WHERE ID = ".$id." LIMIT 1;";
		$message_query_results = mysqli_query($mysqli, $message_query);
		$message_query_fields = mysqli_fetch_array($message_query_results, MYSQLI_ASSOC);

		$mail->isHTML(true);

		if($up){
			$mail->Subject = 'Up Alert';
			$mail->Body = "Kipster has detected that ".$message_query_fields['NAME']." at ".$message_query_fields['IPADDRESS']." has come back online.";
			$mail->AltBody = "Kipster has detected that ".$message_query_fields['NAME']." at ".$message_query_fields['IPADDRESS']." has come back online.";
		}else{
			$mail->Subject = 'Down Alert';
			$mail->Body = "Kipster has detected that ".$message_query_fields['NAME']." at ".$message_query_fields['IPADDRESS']." has been down for ".$down_minutes." minutes.";
			$mail->AltBody = "Kipster has detected that ".$message_query_fields['NAME']." at ".$message_query_fields['IPADDRESS']." has been down for ".$down_minutes." minutes.";
		}

		$mail->send();

		$sent_query = "UPDATE HOSTS SET ALERTSENT = 1 WHERE ID = ".$id.";";
		mysqli_query($mysqli, $sent_query);

		echo "Alert Sent\n";
	}catch(Exception $e){
		echo "Error sending alert.";
	}
}
?>
