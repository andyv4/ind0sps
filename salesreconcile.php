<?php
if(!systemvarget('salesable') || privilege_get('salesreconcile', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'salesreconcile';

require_once 'api/salesreconcile.php';
require_once 'ui/salesreconcile.php';

function defaultmodule(){

  $columns = salesreconcile_ui_columns();

  $module = array(
      'title'=>'Sales Reconcile',
      'columns'=>$columns,
      'presets'=>array(
          array(
            'text'=>'Semua Faktur',
            'columns'=>$columns,
            'viewtype'=>'list',
            'sorts'=>array(
              array('name'=>'createdon', 'sorttype'=>'desc')
            )
          ),
      ),
      'presetidx'=>0,
      'quickfiltercolumns'=>array(
          array('text'=>'', 'value'=>'customerdescription|inventorydescription&contains&')
      ),
      'griddoubleclick'=>"ui.async('ui_salesreconciledetail', [ id ], { waitel:this })"
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  return salesreconcilelist($columns, $sorts, $filters, null, $limits);

}

function customheadcolumns(){

  //return "<td><button class='blue' onclick=\"ui.async('ui_salesreconciledetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";

}

function grid_options($obj){

  $id = $obj['id'];

  $c = "<div class='align-center'>";
  //$c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_salesreconciledetail', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}
function grid_ispaid($obj){

  return "<div class='align-center'>" . ($obj['ispaid'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}

function grid_isreconciled($obj){

  $checked = $obj['isreconciled'] ? ' checked' : '';
  $granted = privilege_get('salesreconcile', 'new');

  $c = "<div class='align-center'>";
  if($granted) $c .= "<input type='checkbox' data-name='isreconciled'$checked onchange=\"ui.async('ui_salesreconcile_setreconciled', [ $obj[id], this.checked ])\"/>";
  $c .= "</div>";
  return $c;

}


include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/salesreconcile.js"></script>