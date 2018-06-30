<?php
if(privilege_get('staff', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'staff';

require_once 'api/staff.php';
require_once 'ui/staff.php';

if(date('Ymd') < 20180316) $mod_reset = true;

function defaultmodule(){

  $columns = staff_ui_columns();

  $module = array(
      'title'=>'staff',
      'columns'=>$columns,
      'presets'=>array(
          array(
              'text'=>'Semua',
              'columns'=>$columns,
              'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
          array('text'=>'', 'value'=>'name|loginid&contains&')
      )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $customers_columnaliases = array(
    'name'=>'t1.name',
    'userid'=>'t1.userid',
    'multilogin'=>'t1.multilogin',
    'dept'=>'t1.dept',
    'position'=>'t1.position'
  );

  // Generating sql queries
  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $customers_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $customers_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $customers_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery; // Add comma if columnquery exists
  $query = "SELECT 'customer' as `staff`, t1.id $columnquery FROM `user` t1 $wherequery $sortquery $limitquery";

  // Fetch data
  $data = pmrs($query, $params);

  // Get online status
  if(is_array($data) && count($data) > 0){
    $columns_indexed = array_index($columns, array('name'), 1);
    if(isset($columns_indexed['online']) && $columns_indexed['online']){

      $ids = array();
      foreach($data as $obj)
        $ids[] = $obj['id'];

      $sessions = pmrs("SELECT * FROM session WHERE userid IN (" . implode(', ', $ids) . ") AND isopen = 1");
      $sessions = array_index($sessions , array('userid'), 1);

      for($i = 0 ; $i < count($data) ; $i++){
        $obj = $data[$i];
        $id = $obj['id'];

        $session = $sessions[$id];

        $data[$i]['online'] = $session ? 1 : 0;
        $data[$i]['session_lasturl'] = $session ? $session['lasturl'] : '';
        $data[$i]['session_lastupdatedon'] = $session ? $session['lastupdatedon'] : '';

        if($session){
          $data[$i]['session_browser'] = $session['useragent'];
        }
        else
          $data[$i]['session_browser'] = '';
      }

    }
  }

  return $data;

}

function customheadcolumns(){

  return "<td><button class='blue' onclick=\"ui.async('ui_staffdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";

}

function staff_options($obj){
  $id = $obj['id'];

  $c = "<div class='align-left'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_staffdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
//  $c .= "<span class='fa fa-times' onclick=\"if(confirm('" . lang('c50', array('name'=>$obj['name'])) . "')) ui.async('ui_customerremove', [ $id ], { waitel:this })\"></span>";
  if($obj['online']) $c .= "<span class='fa fa-power-off' onclick=\"if(confirm('Logoff user ini?')) ui.async('ui_stafflogoff', [ $id ], {})\"></span>";
  $c .= "</div>";
  return $c;

}

function staff_online($obj){

  $c = "<div class='align-center'>";
  if($obj['online']) $c .= "<span class='fa fa-circle color-green'></span>";
  else $c .= "<span class='fa fa-circle color-red'></span>";
  $c .= "</div>";
  return $c;

}

function staff_multilogin($obj){

  $c = "<div class='align-center'>";
  if($obj['multilogin']) $c .= "<span class='fa fa-check-circle color-green'></span>";
  else $c .= "<span class='fa fa-minus-circle color-red'></span>";
  $c .= "</div>";
  return $c;

}

function customerlist_taxable($obj){

  return "<div class='align-center'>" . ($obj['taxable'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}

function customerdetailsales_ispaid($obj){

  return "<div class='align-center'>" . ($obj['ispaid'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}

function customerlist_moved($obj){

  $c = "<div class='align-center customermove'>";
  $c .= $obj['moved'] ? "<span class='fa fa-check color-green'></span>" : "<span class='fa fa-plus color-blue' onclick=\"if(confirm('Pindah pelanggan ini?')) ui.async('ui_customermove', [ $obj[id] ])\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_staffdetail', [ this.dataset['id'] ], {})";

}

include 'rcfx/dashboard1.php';
?>
<script type="text/javascript">

  function mod_checkall(){
    $("*[data-name='granted']").val(true);
  }
  function mod_uncheckall(){
    $("*[data-name='granted']").val(false);
  }

</script>