<?php
if(privilege_get('journalvoucher', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'journalvoucher';

require_once 'api/journalvoucher.php';
require_once 'ui/journalvoucher.php';

function defaultmodule(){

  $columns = journalvoucher_ui_columns();

  $module = array(
      'title'=>'journalvoucher',
      'columns'=>$columns,
      'presets'=>array(
          array(
            'text'=>'Detil',
            'columns'=>$columns,
            'sorts'=>array(
              array('name'=>'createdon', 'sorttype'=>'desc')
            ),
            'filters'=>array(
              array('name'=>'ref', 'operator'=>'equals', 'value'=>'JV')
            ),
            'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
        array('text'=>'', 'value'=>'date|description|coaname|ref&contains&'),
        array('text'=>'Ref:', 'value'=>'ref&contains&'),
        array('text'=>'Nama Akun:', 'value'=>'coaname&contains&'),
        array('text'=>'Ref ID:', 'value'=>'refid&=&')
      ),
      'detailcolumns'=>array(
          array('active'=>1, 'name'=>'col0', 'text'=>'Akun', 'type'=>'html', 'html'=>'ui_journalvoucherdetail_col0', 'width'=>300),
          array('active'=>1, 'name'=>'col1', 'text'=>'Debit', 'type'=>'html', 'html'=>'ui_journalvoucherdetail_col1', 'width'=>100),
          array('active'=>1, 'name'=>'col2', 'text'=>'Kredit', 'type'=>'html', 'html'=>'ui_journalvoucherdetail_col2', 'width'=>100),
          array('active'=>1, 'name'=>'col3', 'text'=>'', 'type'=>'html', 'html'=>'ui_journalvoucherdetail_col3', 'width'=>24),
      )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  if(!is_array($filters)) $filters = [];

  $chartofaccounttype = userkeystoreget($_SESSION['user']['id'], 'privilege.chartofaccounttype');
  if(!$chartofaccounttype) $chartofaccounttype = '999000999';
  if($chartofaccounttype != '*'){
    $filters[] = [
      'name'=>'coaid',
      'operator'=>'in',
      'value'=>$chartofaccounttype
    ];
  }

  return journalvoucherlist($columns, $sorts, $filters, $limits);

}

function customheadcolumns(){

  $html = array();
  if(privilege_get('journalvoucher', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_journalvoucherdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('purchaseinvoice', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_journalvoucherexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function grid_options($obj){

  global $deletable;
  $id = $obj['id'];
  $code = $obj['code'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_journalvoucherdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_journalvoucherremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_journalvoucherdetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('journalvoucher', 'delete');
include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/journalvoucher.js"></script>