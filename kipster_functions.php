<?php
function ConnectDatabase($database_user, $database_password){
  $mysqli = new mysqli("localhost", $database_user, $database_password, "KIPSTER");

  return $mysqli;
}
function GetTypeArray($mysqli){
  $query = "SELECT * FROM HOSTTYPES;";
  $query_result = mysqli_query($mysqli, $query);

  $i = 0;
  while($query_fields = mysqli_fetch_array($query_result, MYSQLI_ASSOC)){
    $array[$i] = array('value'=>$query_fields['TYPE_ID'], 'name'=>$query_fields['NAME']);
    $i++;
  }

  return $array;
}
function ConvertToSelectOps($array){
  $options = '';
  for($i = 0; $i < sizeof($array); $i++){
    $options .= '<option value="'.$array[$i]['value'].'" >'.$array[$i]['name'].'</option>';
  }

  return $options;
}
?>
