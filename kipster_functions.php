<?php
function ConnectDatabase($database_user, $database_password){
  $mysqli = new mysqli("localhost", $database_user, $database_password, "KIPSTER");

  return $mysqli;
}
function GetTypeArray($mysqli){
  $query = "SELECT * FROM HOSTTYPES;";
  $query_results = mysqli_query($mysqli, $query);

  $i = 0;
  while($query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC)){
    $array[$i] = array('value'=>$query_fields['TYPE_ID'], 'name'=>$query_fields['NAME']);
    $i++;
  }

  return $array;
}
function GetPeoplesArray($mysqli){
  $query = "SELECT ID, NAME FROM PEOPLES WHERE ID > 0;";
  $query_results = mysqli_query($mysqli, $query);

  $i = 0;
  while($query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC)){
    $array[$i] = array('value'=>$query_fields['ID'], 'name'=>$query_fields['NAME']);
    $i++;
  }

  return $array;
}
function GetCarrierArray($mysqli){
  $query = "SELECT CARRIERNAME FROM CARRIERS;";
  $query_results = mysqli_query($mysqli, $query);

  $i = 0;
  while($query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC)){
    $array[$i] = array('value'=>$query_fields['CARRIERNAME'], 'name'=>$query_fields['CARRIERNAME']);
    $i++;
  }

  return $array;
}
function GetSitesArray($mysqli){
  $query = "SELECT * FROM SITES;";
  $query_results = mysqli_query($mysqli, $query);

  $i = 0;
  while($query_fields = mysqli_fetch_array($query_results, MYSQLI_ASSOC)){
    $array[$i] = array('value'=>$query_fields['SITE_ID'], 'name'=>$query_fields['NAME']);
    $i++;
  }

  return $array;
}
function ConvertToSelectOps($array, $selected, $empty){
  if($empty){
    $options = '<option value="null"></option>';
  }else{
    $options = '';
  }

  for($i = 0; $i < sizeof($array); $i++){
    $options .= '<option value="'.$array[$i]['value'].'"';
    if($selected == $array[$i]['value']){
      $options .= " selected";
    }
    $options .= ' >';
    $options .= $array[$i]['name'].'</option>';
  }

  return $options;
}
?>
