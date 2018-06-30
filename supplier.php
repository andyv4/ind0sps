<?php
if(privilege_get('supplier', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'supplier';

require_once 'api/supplier.php';
require_once 'ui/supplier.php';

function defaultmodule(){

  $columns = supplier_ui_columns();

  // Remove moved column on next database
  if($_SESSION['dbschema'] == 'indosps2'){
    $index = -1;
    for($i = 0 ; $i < count($columns) ; $i++)
      if($columns[$i]['name'] == 'moved'){
        $index = $i;
        break;
      }
    array_splice($columns, $index, 1);
  }

  $module = array(
      'title'=>'supplier',
      'columns'=>$columns,
      'presets'=>array(
          array(
              'text'=>'Semua Barang',
              'columns'=>$columns,
              'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
          array('text'=>'', 'value'=>'code|name&contains&')
      )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $supplier_columnaliases = array(
    'isactive'=>'t1.isactive',
    'code'=>'t1.code',
    'description'=>'t1.description',
    'address'=>'t1.address',
    'city'=>'t1.city',
    'country'=>'t1.country',
    'payable'=>'t1.payable',
    'phone1'=>'t1.phone1',
    'phone2'=>'t1.phone2',
    'fax1'=>'t1.fax1',
    'fax2'=>'t1.fax2',
    'email'=>'t1.email',
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $supplier_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $supplier_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $supplier_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT 'supplier' as `type`, t1.id, $columnquery FROM supplier t1 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  $html = array();
  if(privilege_get('supplier', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_supplierdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('supplier', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_supplierexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function grid_options($obj){

  global $deletable;
  $id = $obj['id'];
  $code = $obj['code'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_supplierdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?'))ui.async('ui_supplierremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function supplierlist_moved($obj){

  $c = "<div class='align-center suppliermove'>";
  $c .= $obj['moved'] ? "<span class='fa fa-check color-green'></span>" : "<span class='fa fa-plus color-blue' onclick=\"if(confirm('Pindah supplier ini?')) ui.async('ui_suppliermove', [ $obj[id] ])\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_supplierdetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('supplier', 'delete');
include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/supplier.js"></script>