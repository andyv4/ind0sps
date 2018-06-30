<?php
if(privilege_get('pettycash', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'pettycash';

require_once 'api/pettycash.php';
require_once 'ui/pettycash.php';

function defaultmodule(){

  $columns = pettycash_ui_columns();

  $module = array(
      'title'=>'pettycash',
      'columns'=>$columns,
      'presets'=>array(
        array(
          'text'=>'Semua',
          'columns'=>$columns,
          'sorts'=>array(
            array('name'=>'createdon', 'sorttype'=>'desc')
          ),
          'viewtype'=>'list'
        ),
        array(
          'text'=>'Per Hari',
          'columns'=>columns_setwidth(columns_setactive($columns, array('options', 'creditaccountname', 'debitaccountname', 'debitamount')), array('debitaccountname'=>300)),
          'viewtype'=>'list',
          'filters'=>array(
            array('name'=>'date', 'operator'=>'today')
          ),
          'sorts'=>array(
            array('name'=>'debitaccountname', 'sorttype'=>'asc')
          )
        )
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
          array('text'=>'', 'value'=>'code|description|creditaccountname|debitaccountname&contains&'),
          array('text'=>'Akun Kredit:', 'value'=>'creditaccountname&contains&'),
          array('text'=>'Akun Debit:', 'value'=>'debitaccountname&contains&')
      ),
      'detailcolumns'=>array(
          array('active'=>1, 'name'=>'col0', 'text'=>'Deskripsi', 'type'=>'html', 'html'=>'ui_pettycashdetailaccount_col0', 'width'=>200),
          array('active'=>1, 'name'=>'col1', 'text'=>'Jumlah', 'type'=>'html', 'html'=>'ui_pettycashdetailaccount_col1', 'width'=>120),
          array('active'=>1, 'name'=>'col2', 'text'=>'Catatan', 'type'=>'html', 'html'=>'ui_pettycashdetailaccount_col2', 'width'=>200),
          array('active'=>1, 'name'=>'col3', 'text'=>'', 'type'=>'html', 'html'=>'ui_pettycashdetailaccount_col3', 'width'=>24)
      )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $pettycash_columnaliases = array(
    'id'=>'t1.id',
    'code'=>'t1.code',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'total'=>'t1.total',
    'creditaccountname'=>'t3.name',
    'debitaccountname'=>'t4.name',
    'debitamount'=>'t2.amount',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $pettycash_columnaliases);
  $wherequery = 'WHERE t1.id = t2.pettycashid and t1.creditaccountid = t3.id and t2.debitaccountid = t4.id' .
      str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $pettycash_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $pettycash_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;
  $query = "SELECT 'pettycash' as `type`, t1.id $columnquery FROM pettycash t1, pettycashdebitaccount t2, chartofaccount t3, chartofaccount t4 $wherequery $sortquery $limitquery";

  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('pettycash', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_pettycashdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('pettycash', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_pettycashexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function grid_options($obj){

  global $deletable;
  $id = $obj['id'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_pettycashdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus " . $obj['code'] . "')) ui.async('ui_pettycashremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_pettycashdetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('pettycash', 'delete');
include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/pettycash.js"></script>