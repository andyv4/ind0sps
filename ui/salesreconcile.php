<?php

function ui_salesreconcile_setreconciled($id, $reconciled){

  $reconciled = intval($reconciled);
  $salesinvoice = pmr("SELECT `id`, isreconciled FROM salesinvoice WHERE `id` = ?", array($id));
  if($salesinvoice['isreconciled'] != $reconciled){
    pm("UPDATE salesinvoice SET isreconciled = ? WHERE `id` = ?", array($reconciled, $id));
  }

}


?>