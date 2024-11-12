<?php
require_once 'Zebra_Image.php';
if(session_status() == PHP_SESSION_NONE){
   session_start();
}
define('BASE_URL', 'http://localhost/jjyshop/');

$conn = new mysqli('localhost', 'root', '', 'jjyshop');

function db_select($table,$condition =null)
{
  $sql = "SELECT * FROM $table";
  if($condition != null){
    $sql .= " WHERE $condition";
  }
  global $conn;
  $res = $conn->query($sql);
  $rows = [];
  while($row = $res->fetch_assoc()){
    $rows[] = $row;
  }
  return $rows;
}


function db_insert($table_name, $data){
    $sql = "INSERT INTO $table_name ";

    $column_names = "(";
    $column_values = "(";
    $is_first = true;
    
    foreach($data as $key => $value){
        if($is_first){
          $is_first = false;
        }else {
          $column_names .= ",";
          $column_values .= ",";
        }
        $column_names .= $key;
        $gettype = gettype($value);
        if($gettype == 'string'){
          $column_values .= "'$value'";
        }else {
          $column_values .= $value;
        }    
    }
    $column_names .= ")";
    $column_values .= ")";
    $sql .= $column_names . " VALUES " . $column_values;
    echo $sql;
    global $conn;
    if($conn->query($sql)){
      return true;
    } else{
        return false;
      }
}

function create_thumb($source,$target) {
  $image = new Zebra_Image();

  $image->auto_handle_exif_orientation = true;
  $image->source_path = $source;
  $image->target_path = $target;
  $image->preserve_aspect_ratio = true;
  $image->enlarge_smaller_images = true;
  $image->preserve_time = true;

  $image->jpeg_quality = get_jpeg_quality(filesize($image->source_path));
  $width = 100;
  $height = 100;
  if(!$image->resize($width, $height, ZEBRA_IMAGE_CROP_CENTER)){
    return $image->source_path;
  } else {
    return $image->target_path;
  }
}

function get_jpeg_quality($_size){
  $size = ($_size / 1000000);
  $qt =50;
  if($size > 5){
    $qt=10;
  } else if ($size>4){
    $qt = 13;
  } else if ($size>2){
    $qt = 15;
  } else if ($size>1){
  $qt = 17;
  } else if ($size>0.8){
  $qt = 50;
  } else if ($size> .5){
  $qt = 80;
  }else {
  $qt =90;
  }
return $qt;
}

function upload_images($files)
{
  if ($files == null || empty($files)) {
    return [];
  }

  $uploaded_images = array();
  foreach ($files as $file) {
    if (
      isset($file['name']) &&
      isset($file['type']) &&
      isset($file['tmp_name']) &&
      isset($file['error']) &&
      isset($file['size'])
    ) {
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      $file_name = time() . "-" . rand(100000, 1000000) . "." . $ext;
      $destination = 'uploads/' . $file_name;
      $thumb_destination = 'uploads/thumb_' . $file_name;
      $res = move_uploaded_file($file['tmp_name'], $destination);
      if (!$res) {
        continue;
      }
      create_thumb($destination, $thumb_destination);
      $img['src'] = $destination;
      $img['thumb'] = $thumb_destination;
      $uploaded_images[] = $img;
    }
  }

  return $uploaded_images;
}


function url($path = "/"){
  return BASE_URL . $path;
}

function protected_area(){
  if(!isset($_SESSION['user'])){
    alert('warning', 'You need to login first');
    header('location:'.url('login.php'));
    die();
  }
}

function is_logged_in(){
  return isset($_SESSION['user']);
}

function logout(){
  if(isset($_SESSION['user'])){
    unset($_SESSION['user']);
  }
  alert('success', 'You have been logged out');
  header('location:'.url('login.php'));
  die();
}

function alert($type, $message){
  $_SESSION['alert']['type'] = $type;
  $_SESSION['alert']['message'] = $message;
}

function login_user($email, $password) {
  global $conn;
  $sql = "SELECT * FROM users WHERE email='$email'";
  $res = $conn->query($sql);

  if($res->num_rows < 1){
    return false;
  }

  $row = $res->fetch_assoc();
  if(!password_verify($password, $row['password'])){
    return false;
  }
  $_SESSION['user'] = $row;
  return true;
}

function text_input($data)
{
    $name = isset($data['name']) ? $data['name'] : "";
   $attributes = isset($data['attributes']) ? $data['attributes'] : "";
    $value = "";
   $error_text = "";
  
    if(isset($_SESSION['form'])){
    if(isset($_SESSION['form']['value'])){
      if(isset($_SESSION['form']['value'][$name])){
        $value = $_SESSION['form']['value'][$name];
        }
    }
  }
  if(isset($_SESSION['form'])){
  if(isset($_SESSION['form']['error'])){
    if(isset($_SESSION['form']['error'][$name])){
      $error = $_SESSION['form']['error'][$name];
      $error_text = "<div class='form-text text-danger'>'.$error.'</div>";
    }
  }
  }
  
  $label = isset($data['label']) ? $data['label'] : $name;
  $value = isset($data['value']) ? $data['value'] : '';
  
  return '<label class="form-label text-capitalize" for="'.$name.'">'.$label.'</label>
          <input name="' . $name . '" value="'.$value.'" class="form-control" type="text" id="'. $name .'" placeholder="'. $name. '" '.$attributes.'>'.$error_text;
}

function select_input($data, $options)
{
    $name = isset($data['name']) ? $data['name'] : "";
    $attributes = isset($data['attributes']) ? $data['attributes'] : "";
    $value = "";
    $error_text = "";

    if (isset($_SESSION['form']['value'][$name])) {
        $value = $_SESSION['form']['value'][$name];
    }

    if (isset($_SESSION['form']['error'][$name])) {
        $error = $_SESSION['form']['error'][$name];
        $error_text = "<div class='form-text text-danger'>$error</div>";
    }

    $label = isset($data['label']) ? $data['label'] : $name;
    $value = isset($data['value']) ? $data['value'] : $value;

    $select_options = "";
    foreach ($options as $key => $val) {
        $select_options .= '<option value="' . $key . '">' . $val . '</option>';
    }

    $select_tag = '<select name="' . $name . '" class="form-control" id="' . $name . '" ' . $attributes . '>' . $select_options . '</select>';

    return '<label class="form-label text-capitalize" for="' . $name . '">' . $label . '</label>' . $select_tag . $error_text;
}


