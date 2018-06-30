<?php
if(privilege_get('warehousetransfer', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'warehousetransfer';

require_once 'api/warehousetransfer.php';
require_once 'ui/warehousetransfer.php';

function defaultmodule(){

  $columns = warehousetransfer_ui_columns();

  $module = array(
      'title'=>'warehousetransfer',
      'columns'=>$columns,
      'presets'=>array(
        array(
          'text'=>'Detil per Barang',
          'columns'=>$columns,
          'sorts'=>array(
            array('name'=>'createdon', 'sorttype'=>'desc')
          ),
          'viewtype'=>'list'
        ),
        array(
            'text'=>'Ringkasan per Kode',
            'columns'=>columns_setactive($columns, array('options', 'inventorycode', 'inventorydescription', 'qty')),
            'groups'=>array(
                array('name'=>'code', 'aggregrate'=>'', 'columns'=>array(
                    array('name'=>'code', 'logic'=>'first'),
                    array('name'=>'date', 'logic'=>'first'),
                    array('name'=>'description', 'logic'=>'first'),
                    array('name'=>'fromwarehousename', 'logic'=>'first'),
                    array('name'=>'towarehousename', 'logic'=>'first'),
                    array('name'=>'totalqty', 'logic'=>'first'),
                ))
            ),
            'viewtype'=>'group'
        )
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
        array('text'=>'', 'value'=>'code|description|inventorycode|inventorydescription|fromwarehousename|towarehousename&contains&'),
        array('text'=>'Gudang Asal:', 'value'=>'fromwarehousename&contains&'),
        array('text'=>'Gudang Tujuan:', 'value'=>'towarehousename&contains&'),
        array('text'=>'Nama Barang:', 'value'=>'inventorydescription&contains&')
      ),
      'detailcolumns'=>array(
          array('active'=>1, 'name'=>'col0', 'text'=>'Barang', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col0', 'width'=>300),
          array('active'=>1, 'name'=>'col1', 'text'=>'Kuantitas', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col1', 'width'=>60),
          array('active'=>1, 'name'=>'col2', 'text'=>'Catatan', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col2', 'width'=>200),
          array('active'=>1, 'name'=>'col3', 'text'=>'', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col3', 'width'=>24),
      )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $columnaliases = array(
    'id'=>'t1.id',
    'code'=>'t1.code',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'inventoryid'=>'t2.inventoryid',
    'inventorycode'=>'t5.code',
    'inventorydescription'=>'t5.description',
    'fromwarehouseid'=>'t1.fromwarehouseid',
    'towarehouseid'=>'t1.towarehouseid',
    'fromwarehousename'=>'t3.name',
    'towarehousename'=>'t4.name',
    'totalqty'=>'t1.totalqty',
    'inventoryid'=>'t2.inventoryid',
    'qty'=>'t2.qty',
    'unit'=>'t5.unit',
    'remark'=>'t2.remark',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases);
  $wherequery = 'WHERE t1.id = t2.warehousetransferid AND t1.fromwarehouseid = t3.id AND t1.towarehouseid = t4.id AND t2.inventoryid = t5.id ' .
      str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;

  $query = "SELECT 'warehousetransfer' as `type`, t1.id $columnquery
    FROM warehousetransfer t1, warehousetransferinventory t2, warehouse t3, warehouse t4, inventory t5 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('warehousetransfer', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_warehousetransferdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('warehousetransfer', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_warehousetransferexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function grid_options($obj){

  global $deletable;
  $id = $obj['id'];
  $code = $obj['code'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_warehousetransferdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code')) ui.async('ui_warehousetransferremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_warehousetransferdetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('warehousetransfer', 'delete');
include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/warehousetransfer.js"></script>