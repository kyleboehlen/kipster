<?PHP
//for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

//start session and set cookie lenth to 1 week
ini_set('session.gc_maxlifetime', 604800);
session_set_cookie_params(604800);
session_start();

//require TBS class and fill in vars for template
require_once('tbs_class.php');
require("config.php");

//general page vars
$request = '';
$error = '';
$note = '';
$username_value = '';
$login = false;
$confirm_password = false;
$navbar = false;
$logo = false;
$das_table = false;
$peoples_table = false;

//declare tbs object and template
$tbs = new clsTinyButStrong;
$tbs->LoadTemplate('templates/home.html');

//database connection
$mysqli = new mysqli("localhost", $database_user, $database_password, "KIPSTER");

///////////////LOGIN////////////////////////////////
//if session isn't set check if username was entered
if(!isset($_SESSION['username'])){
  //if username is set, check database for username
  if(isset($_POST['username'])){
    $query = $mysqli->prepare("SELECT ID, NAME, USERNAME, PASSWORD FROM PEOPLES WHERE USERNAME = ? AND LOGIN_ACTIVE LIMIT 1;");
    $query->bind_param('s', $_POST['username']);

    if(!$query->execute()){
      $note = "Issues looking up username";
      $tbs->Show();
      exit();
    }

    $query_results = $query->get_result();

    //if username exsists, check password or add new password to database
    if(mysqli_num_rows($query_results) > 0){
      $query_fields = mysqli_fetch_array($query_results);

      if(strlen($query_fields['PASSWORD']) > 0){
        $hash = $query_fields['PASSWORD'];
        if(password_verify($_POST['password'], $hash) == true){
          //set session vars
          $_SESSION['username'] = $query_fields['USERNAME'];
          $_SESSION['id'] = $query_fields['ID'];
          $_SESSION['name'] = $query_fields['NAME'];
        }else{
          $login = true;
          $username_value = $_POST['username'];
          $note = "Incorrect Password";
          $tbs->Show();
          exit();
        }
      }else{
        if((isset($_POST['password']) && isset($_POST['confirm-password'])) && $_POST['password'] == $_POST['confirm-password']){
          $hash = password_hash($_POST['password'], PASSWORD_DEFAULT, ['cost' => 12]);

          $query = $mysqli->prepare("UPDATE PEOPLES SET PASSWORD = ? WHERE ID = ?;");
          $query->bind_param("si", $hash, $query_fields['ID']);

          if(!$query->execute()){
            $note = "Error writing password hash";
            $confirm_password = true;
          }else{
            $note = "Password has written successfully, please log in";
          }
          $login = true;
          $username_value = $_POST['username'];
          $tbs->Show();
          exit();
        }else{
          $username_value = $_POST['username'];
          $login = true;
          $confirm_password = true;
          $note = strlen($_POST['password']) > 0 ? "Passwords don't match" : "Please set your password";
          $tbs->Show();
          exit();
        }
      }
    }else{
      $note = "That's not a valid username";
      $login = true;
      $tbs->Show();
      exit();
    }
  }else{
    $login = true;
    $tbs->Show();
    exit();
  }
}

$navbar = true;

if(isset($_GET['request'])){
  $request = $_GET['request'];
}

switch($request){
  case "das":
    $das_table = true;

    $query = "SELECT HOSTS.ID AS HOST_ID,
              HOSTS.NAME AS HOST_NAME,
              HOSTS.IPADDRESS AS IP,
              HOSTS.UP,
              HOSTS.DOWNTIME,
              HOSTS.ALERTTIME,
              HOSTS.ALERTSENT,
              SITES.NAME AS SITE_NAME,
              HOSTTYPES.MEDIA
              FROM HOSTS INNER JOIN SITES ON SITES.SITE_ID = HOSTS.SITE
              INNER JOIN HOSTTYPES ON HOSTTYPES.TYPE_ID = HOSTS.TYPE
              ORDER BY SITE_NAME, HOSTTYPES.NAME, HOST_NAME;";
    $query_results = mysqli_query($mysqli, $query);

    $i = 0;
    while($query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC)){
      $das_block[$i] = array(
        'site'=>$query_fields['SITE_NAME'],
        'settings'=>'<a href="index.php?request=settings&host-id='.$query_fields['HOST_ID'].'"><img src="media/cogwheel.png" /></a>',
        'type'=>'<img src="media/'.$query_fields['MEDIA'].'" />',
        'name'=>$query_fields['HOST_NAME'],
        'ip'=>$query_fields['IP'],
        'sitehighlighting'=>'up-highlighting'
      );

      $date = date_create($query_fields['DOWNTIME']);
      $das_block[$i]['downtime'] = strlen($query_fields['DOWNTIME']) > 0 ? $date->format("n/j/Y g:i A") : "N/A";
      $date->modify('+'.$query_fields['ALERTTIME'].' minutes');
      $das_block[$i]['alerttime'] = $das_block[$i]['downtime'] == "N/A" ? "N/A" : $date->format("n/j/Y g:i A");

      $i++;
    }

    for($i = 0; $i < sizeof($das_block); $i++){
      $das_block[$i]['htmlsite'] = strtolower(trim(str_replace(" ", "-", $das_block[$i]['site'])));
      $das_block[$i]['highlighting'] = $das_block[$i]['downtime'] > 0 ? 'down-highlighting' : 'up-highlighting';
    }

    $query = "SELECT SITES.NAME AS SITE_NAME FROM HOSTS INNER JOIN SITES ON SITE = SITE_ID WHERE HOSTS.DOWNTIME IS NOT NULL;";
    $query_results = mysqli_query($mysqli, $query);

    while($query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC)){
      for($i = 0; $i < sizeof($das_block); $i++){
        if($das_block[$i]['site'] == $query_fields['SITE_NAME']){
          $das_block[$i]['sitehighlighting'] = 'down-highlighting';
        }
      }
    }

    $tbs->MergeBlock('das_block', $das_block);
    break;
  case "peoples":
    $peoples_table = true;
    break;
  case "logout":
    session_unset();
    session_destroy();
    header('Location: index.php');
    break;
  default:
    $logo = true;
    break;
}

$tbs->Show();
?>
