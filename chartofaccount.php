<?php
if(privilege_get('chartofaccount', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'chartofaccount';

require_once 'api/chartofaccount.php';
require_once 'ui/chartofaccount.php';

function defaultmodule(){

  $columns = chartofaccount_ui_columns();

  $module = array(
    'title'=>'Chart of Account',
    'columns'=>$columns,
    'presets'=>array(
        array(
            'text'=>'Semua Akun',
            'columns'=>$columns,
            'viewtype'=>'list'
        ),
    ),
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
        array('text'=>'', 'value'=>'code|name&contains&')
    ),
    'mutationdetailpresets'=>array(
        array(
            'text'=>'Default',
            'columns'=>array(
                array('active'=>0, 'name'=>'id', 'text'=>'ID', 'width'=>20),
                array('active'=>1, 'name'=>'ref', 'text'=>'Ref', 'width'=>20),
                array('active'=>0, 'name'=>'refid', 'text'=>'Ref ID', 'width'=>20),
                array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>90, 'datatype'=>'date'),
                array('active'=>1, 'name'=>'description', 'text'=>'Keterangan', 'width'=>160),
                array('active'=>1, 'name'=>'debit', 'text'=>'Debit', 'width'=>100, 'datatype'=>'money'),
                array('active'=>1, 'name'=>'credit', 'text'=>'Kredit', 'width'=>100, 'datatype'=>'money'),
                array('active'=>1, 'name'=>'balance', 'text'=>'Saldo', 'width'=>120, 'datatype'=>'money'),
            )
        )
    ),
    'mutationdetailpresetidx'=>0,
    'mutationdetailstartdate'=>date('Ymd'),
    'mutationdetailenddate'=>date('Ymd')
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  if(!is_array($filters)) $filters = [];

  $chartofaccounttype = userkeystoreget($_SESSION['user']['id'], 'privilege.chartofaccounttype');
  if(!$chartofaccounttype) $chartofaccounttype = '999000999';
  if($chartofaccounttype != '*'){
    $filters[] = [
      'name'=>'id',
      'operator'=>'in',
      'value'=>$chartofaccounttype
    ];
  }

  $tax_mode = isset($_SESSION['tax_mode']) && $_SESSION['tax_mode'] > 0 ? true : false;
  if($tax_mode){
    $tax_mode_codes = [
      "100.04"
    ];
    $filters[] = [
      'name'=>'code',
      'operator'=>'in',
      'value'=>implode(', ', $tax_mode_codes)
    ];
  }

  return chartofaccountlist($columns, $sorts, $filters, $limits);

}

function customheadcolumns(){

  if(privilege_get('chartofaccount', 'new'))
    return "<td><button class='blue' onclick=\"ui.async('ui_chartofaccountdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";

}

function grid_options($obj){
  $id = $obj['id'];
  $name = ov('name', $obj);
  global $deletable;

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_chartofaccountdetail', [ $id, event.altKey ? 'write' : '' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $name'))ui.async('ui_chartofaccountdetail_remove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function chartofaccountlist_moved($obj){

  $c = "<div class='align-center chartofaccountmove'>";
  $c .= $obj['moved'] ? "<span class='fa fa-check color-green'></span>" : "<span class='fa fa-plus color-blue' onclick=\"if(confirm('Pindah akun ini?')) ui.async('ui_chartofaccountmove', [ $obj[id] ])\"></span>";
  $c .= "</div>";
  return $c;

}
function m_griddoubleclick(){

  return "ui.async('ui_chartofaccountdetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('chartofaccount', 'delete');

include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/chartofaccount.js"></script>