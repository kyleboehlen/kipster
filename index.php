<?PHP
//for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

//start session and set cookie lenth to 1 week
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
    $query = $mysqli->prepare("SELECT ID, NAME, USERNAME, PASSWORD FROM PEOPLES WHERE USERNAME = ? LIMIT 1;");
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

$tbs->Show();
?>
