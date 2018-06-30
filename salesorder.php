<?php
if(!systemvarget('salesable') || privilege_get('salesorder', 'list') < 1){ include 'notavailable.php'; return; }


$modulename = 'salesorder';

require_once 'api/salesorder.php';
require_once 'ui/salesorder.php';

function defaultmodule(){

  $columns = salesorder_ui_columns();

  $module = array(
      'title'=>'Sales Invoice',
      'columns'=>$columns,
      'presets'=>array(
          array(
              'text'=>'Semua Faktur',
              'columns'=>$columns,
              'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
      'quickfiltercolumns'=>array(
          array('text'=>'', 'value'=>'customerdescription|inventorydescription&contains&')
      ),
      'griddoubleclick'=>"ui.async('ui_salesorderdetail', [ id ], { waitel:this })"
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $params = array();
  $columnquery = columnquery_from_columns($columns, array('type'=>1));
  $wherequery = 'WHERE t1.id = t2.salesorderid' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters));
  $sortquery = sortquery_from_sorts($sorts);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT 'salesorder' as `type`, t1.id, $columnquery FROM salesorder t1, salesorderinventory t2 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  return "<td><button class='blue' onclick=\"ui.async('ui_salesorderdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";

}

function grid_options($obj){

  $id = $obj['id'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_salesorderdetail', [ $id ], { waitel:this })\"></span>";
  $c .= "<span class='fa fa-times'></span>";
  $c .= "</div>";
  return $c;

}
function grid_ispaid($obj){

  return "<div class='align-center'>" . ($obj['ispaid'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}


include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/salesorder.js"></script>