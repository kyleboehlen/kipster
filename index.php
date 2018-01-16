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
require('kipster_functions.php');
require('config.php');

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
$peoples = false;
$peoples_select = false;
$peoples_form = false;
$settings = false;

//declare tbs object and template
$tbs = new clsTinyButStrong;
$tbs->LoadTemplate('templates/home.html');

//database connection
$mysqli = ConnectDatabase($database_user, $database_password);

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
    $first_site = true;
    while($query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC)){
      if($first_site){
        $cur_site = $query_fields['SITE_NAME'];
        $first_site = false;
      }
      if($cur_site != $query_fields['SITE_NAME']){
        $das_block[$i] = array(
          'site'=>$cur_site,
          'settings'=>'<input type="submit" name="add-host" value="Add '.$cur_site.' Host" class="das-form-font" />',
          'type'=>'<select name="type" class="das-form-font">'.ConvertToSelectOps(GetTypeArray($mysqli), NULL, false).'</select>',
          'name'=>'<input type="text" name="host-name" class="das-form-font" />',
          'ip'=>'<input type="text" name="ip" class="das-form-font" />',
          'sitehighlighting'=>'up-highlighting',
          'downtime'=>'N/A',
          'alerttime'=>'<input type="number" name="alerttime" class="das-form-font num" /> (Minutes)'
        );
        $cur_site = $query_fields['SITE_NAME'];
        $i++;
      }
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

    $das_block[$i] = array(
      'site'=>$cur_site,
      'settings'=>'<input type="submit" name="add-host" value="Add '.$cur_site.' Host" class="das-form-font" />',
      'type'=>'<select name="type" class="das-form-font">'.ConvertToSelectOps(GetTypeArray($mysqli), NULL, false).'</select>',
      'name'=>'<input type="text" name="host-name" class="das-form-font" />',
      'ip'=>'<input type="text" name="ip" class="das-form-font" />',
      'sitehighlighting'=>'up-highlighting',
      'downtime'=>'N/A',
      'alerttime'=>'<input type="number" name="alerttime" class="das-form-font num" /> (Minutes)'
    );

    $i++;
    $query = "SELECT SITES.SITE_ID, SITES.NAME AS SITE_NAME
              FROM SITES
              LEFT JOIN HOSTS ON SITES.SITE_ID = HOSTS.SITE
              WHERE HOSTS.SITE IS NULL;";
    $query_results = mysqli_query($mysqli, $query);

    while($query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC)){
      $das_block[$i] = array(
        'site'=>$query_fields['SITE_NAME'],
        'settings'=>'<input type="submit" name="add-host" value="Add '.$query_fields['SITE_NAME'].' Host" class="das-form-font" />',
        'type'=>'<select name="type" class="das-form-font">'.ConvertToSelectOps(GetTypeArray($mysqli), NULL, false).'</select>',
        'name'=>'<input type="text" name="host-name" class="das-form-font" />',
        'ip'=>'<input type="text" name="ip" class="das-form-font" />',
        'sitehighlighting'=>'up-highlighting',
        'downtime'=>'N/A',
        'alerttime'=>'<input type="number" name="alerttime" class="das-form-font num" /> (Minutes)'
      );
      $i++;
    }

    for($i = 0; $i < sizeof($das_block); $i++){
      $das_block[$i]['htmlsite'] = strtolower(trim(str_replace(" ", "-", $das_block[$i]['site'])));
      $das_block[$i]['highlighting'] = $das_block[$i]['downtime'] > 0 ? 'down-highlighting' : 'up-highlighting';
    }

    $query = "SELECT SITES.NAME AS SITE_NAME
              FROM HOSTS
              INNER JOIN SITES ON SITE = SITE_ID
              WHERE HOSTS.DOWNTIME IS NOT NULL;";
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
  case "add":
    if(isset($_GET['type'])){
      $type = $_GET['type'];
    }else{
      $type = "unknown";
    }

    switch($type){
      case "das":
        if(isset($_POST['add-site'])){
          $query = $mysqli->prepare("INSERT INTO SITES (NAME) VALUES (?);");
          $query->bind_param("s", $_POST['site-name']);
        }elseif(isset($_POST['add-host'])){
          $site = $_POST['add-host'];
          $site = str_replace("Add", "", $site);
          $site = str_replace("Host", "", $site);
          $site = trim($site);

          echo $site; //debug

          $query = $mysqli->prepare("SELECT SITE_ID FROM SITES WHERE NAME = ? LIMIT 1;");
          $query->bind_param("s", $site);
          $query->execute();
          $query_result = $query->get_result();

          $query_fields = mysqli_fetch_array($query_result, MYSQLI_ASSOC);

          echo $query_fields['SITE_ID']; //debug
          echo $_POST['host-name'];
          echo $_POST['ip'];
          echo $_POST['alerttime'];
          echo $_POST['type'];

          $query = $mysqli->prepare("INSERT INTO HOSTS (NAME, IPADDRESS, ALERTTIME, SITE, TYPE) VALUES (?, ?, ?, ?, ?);");
          $query->bind_param("ssiii", $_POST['host-name'], $_POST['ip'], $_POST['alerttime'], $query_fields['SITE_ID'], $_POST['type']);
        }
        if(!$query->execute()){
          $note = "Failed to add";
          $logo = true;
        }else{
          header('Location: index.php?request=das');
        }
        break;
      case "person":
        break;
      default:
        $note = "Not quite sure what you were trying to add";
        $logo = true;
        break;
    }
    break;
  case "peoples":
    $peoples = true;

    if(isset($_GET['peoples-id']) || isset($_POST['peoples-name'])){
      $peoples_form = true;
    }elseif(!isset($_POST['id'])){
      $peoples_select = true;
    }

    if($peoples_select){
      $peoples_ops = ConvertToSelectOps(GetPeoplesArray($mysqli), NULL, false);
    }elseif(isset($_POST['peoples-name'])){
      $query = $mysqli->prepare("INSERT INTO PEOPLES (NAME, LOGIN_ACTIVE) VALUES(?, 0);");
      $query->bind_param("s", $_POST['peoples-name']);

      if(!$query->execute()){
        $logo = true;
        $note = "Issues adding person.";
        $tbs->Show();
        exit();
      }else{
        header('Location: index.php?request=peoples&peoples-id='.$mysqli->insert_id);
      }
    }elseif(isset($_GET['peoples-id'])){
      $id = $_GET['peoples-id'];
      $query = $mysqli->prepare("SELECT ID, NAME, PHONENUMBER, EMAIL, CARRIER, USERNAME, LOGIN_ACTIVE AS LOGIN FROM PEOPLES WHERE ID = ? LIMIT 1;");
      $query->bind_param("i", $id);

      if(!$query->execute()){
        $logo = true;
        $note = "Issues retrieving person.";
        $tbs->Show();
        exit();
      }

      $query_results = $query->get_result();

      $query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC);

      $name = $query_fields['NAME'];
      $phonenumber = strlen($query_fields['PHONENUMBER']) > 0 ? $query_fields['PHONENUMBER'] : '';
      $carriers = ConvertToSelectOps(GetCarrierArray($mysqli), $query_fields['CARRIER'], false);
      $email = strlen($query_fields['EMAIL']) > 0 ? $query_fields['EMAIL'] : '';
      $username = strlen($query_fields['USERNAME']) > 0 ? $query_fields['USERNAME'] : '';
      $login = $query_fields['LOGIN'];

    }elseif(isset($_POST['id'])){
      $id = $_POST['id'];
      $login_active = isset($_POST['login-active']) ? 1 : 0;

      $query = $mysqli->prepare("UPDATE PEOPLES SET NAME = ?, LOGIN_ACTIVE = ? WHERE ID = ?;");
      $query->bind_param("sii", $_POST['name'], $login_active, $id);
      $query->execute();

      if(isset($_POST['phonenumber'])){
        $query = $mysqli->prepare("UPDATE PEOPLES SET PHONENUMBER = ? WHERE ID = ?;");
        $query->bind_param("si", $_POST['phonenumber'], $id);
        $query->execute();
      }
      if(isset($_POST['email'])){
        $query = $mysqli->prepare("UPDATE PEOPLES SET EMAIL = ? WHERE ID = ?;");
        $query->bind_param("si", $_POST['email'], $id);
        $query->execute();
      }
      if(isset($_POST['carrier'])){
        $query = $mysqli->prepare("UPDATE PEOPLES SET CARRIER = ? WHERE ID = ?;");
        $query->bind_param("si", $_POST['carrier'], $id);
        $query->execute();
      }
      if(isset($_POST['username'])){
        $query = $mysqli->prepare("UPDATE PEOPLES SET USERNAME = ? WHERE ID = ?;");
        $query->bind_param("si", $_POST['username'], $id);
        $query->execute();
      }

      header('Location: index.php?request=peoples&peoples-id='.$id);
    }else{
      $logo = true;
      $note = "Not quite sure what you were trying to do there.";
      $tbs->Show();
      exit();
    }
    //SHOULD PROBABLY LOOK AT JUST USING A DROPDOWN LIST FOR PEOPLES SETTINGS AND A NORMAL FORM GENERATED
    break;
  case "settings":
    $id = $_GET['host-id'];
    $settings = true;

    if(!isset($_GET['host-id'])){
      $logo = true;
      $tbs->Show();
      exit();
    }

    //host info
    $query = $mysqli->prepare("SELECT HOSTS.NAME AS HOST_NAME, HOSTS.IPADDRESS, HOSTS.ALERTTIME, HOSTS.SITE AS SITE_ID, HOSTS.TYPE AS TYPE_ID, HOSTTYPES.NAME AS TYPE_NAME, HOSTTYPES.MEDIA, PARENT.NAME AS PARENT_NAME, PARENT.ID AS PARENT_ID FROM HOSTS INNER JOIN HOSTTYPES ON HOSTS.TYPE = HOSTTYPES.TYPE_ID LEFT JOIN HOSTRELATIONS ON HOSTS.ID = HOSTRELATIONS.ID LEFT JOIN HOSTS AS PARENT ON HOSTRELATIONS.PARENT = PARENT.ID WHERE HOSTS.ID = ? LIMIT 1;");

    try{
      $query->bind_param("i", $id);
    }catch(Exception $e){
      header('Location: index.php');
      exit();
    }

    if(!$query->execute()){
      $logo = true;
      $note = "Error finding host";
      $tbs->Show();
      exit();
    }

    $query_results = $query->get_result();

    if(!mysqli_num_rows($query_results) > 0){
      $logo = true;
      $note = "Error finding host;";
      $tbs->Show();
      exit();
    }

    $query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC);

    $host_name = $query_fields['HOST_NAME'];
    $ipaddress = $query_fields['IPADDRESS'];
    $alerttime = $query_fields['ALERTTIME'];

    $sites = ConvertToSelectOps(GetSitesArray($mysqli), $query_fields['SITE_ID'], true);
    $types = ConvertToSelectOps(GetTypeArray($mysqli), $query_fields['TYPE_ID'], true);

    $media = $query_fields['MEDIA'];

    //alerts
    $query = $mysqli->prepare("SELECT PEOPLES.ID AS PEOPLES_ID, PEOPLES.NAME AS PEOPLES_NAME, TEXTALERTS.TEXT, EMAILALERTS.EMAIL FROM PEOPLES LEFT JOIN (SELECT PEOPLESID, TEXT FROM ALERTS WHERE HOSTSID = ? AND TEXT = 1) AS TEXTALERTS ON PEOPLES.ID = TEXTALERTS.PEOPLESID LEFT JOIN (SELECT PEOPLESID, TEXT AS EMAIL FROM ALERTS WHERE HOSTSID = ? AND TEXT = 0) AS EMAILALERTS ON PEOPLES.ID = EMAILALERTS.PEOPLESID WHERE PEOPLES.ID > 0;");
    $query->bind_param("ii", $id, $id);
    $query->execute();
    $query_results = $query->get_result();

    $i = 0;
    while($query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC)){
      $text = strlen($query_fields['TEXT']) > 0 ? 1 : 0;
      $email = strlen($query_fields['EMAIL']) > 0 ? 1 : 0;

      $alerts_array[$i] = array(
        'peoples_id'=>$query_fields['PEOPLES_ID'],
        'peoples_name'=>$query_fields['PEOPLES_NAME'],
        'text'=>$text,
        'email'=>$email
      );
      $i++;
    }

    $tbs->MergeBlock('alerts_block', $alerts_array);

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
