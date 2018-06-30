<?php
require_once 'user.php';

function privilege_get($module, $key){

  global $__system;

  if(isset($__system) && $__system == 'bgp') return 1;

  $id = $_SESSION['user']['id'];
  $value = pmc("SELECT `value` FROM userprivilege WHERE userid = ? AND module = ? AND `key` = ?", array($id, $module, $key));
  return $value;

}

?>