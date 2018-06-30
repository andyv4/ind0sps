<?php
if(!systemvarget('salesable') || privilege_get('salesinvoice', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'sampleinvoice';

require_once 'api/sampleinvoice.php';
require_once 'ui/sampleinvoice.php';

function defaultmodule(){

  $columns = sampleinvoice_ui_columns();

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
      'title'=>'Sample Invoice',
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
      'quickfilterscolumns'=>array(
          array('text'=>'', 'value'=>'code|customerdescription|inventorydescription|inventorycode&contains&'),
          array('text'=>'ID:', 'value'=>'id&equals&'),
          array('text'=>'Inventory ID:', 'value'=>'inventoryid&equals&')
      ),
      'griddoubleclick'=>"ui.async('ui_salesinvoicedetail', [ id ], { waitel:this })"
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $salesinvoice_columnaliases = array(
      'status'=>'t1.status',
      'date'=>'t1.date',
      'code'=>'t1.code',
      'creditterm'=>'t1.creditterm',
      'pocode'=>'t1.pocode',
      'customerid'=>'t1.customerid',
      'customerdescription'=>'t1.customerdescription',
      'avgsalesmargin'=>'t1.avgsalesmargin',
      'subtotal'=>'t1.subtotal',
      'discount'=>'t1.discount',
      'discountamount'=>'t1.discountamount',
      'taxable'=>'t1.taxable',
      'taxamount'=>'t1.taxamount',
      'deliverycharge'=>'t1.deliverycharge',
      'total'=>'t1.total',
      'paymentaccountid'=>'t1.paymentaccountid',
      'paymentamount'=>'t1.paymentamount',
      'paymentdate'=>'t1.paymentdate',
      'inventoryid'=>'t2.inventoryid',
      'inventorycode'=>'t2.inventorycode',
      'inventorydescription'=>'t2.inventorydescription',
      'qty'=>'t2.qty',
      'returnqty'=>'t2.returnqty',
      'unit'=>'t2.unit',
      'unitprice'=>'t2.unitprice',
      'costprice'=>'t2.costprice',
      'totalcostprice'=>'t2.totalcostprice',
      'margin'=>'t2.margin',
      'unitdiscount'=>'t2.unitdiscount',
      'unitdiscountamount'=>'t2.unitdiscountamount',
      'unittotal'=>'t2.unittotal',
      'createdon'=>'t1.createdon',
      'warehousename'=>'t3.name as warehousename',
      'moved'=>'t1.moved'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $salesinvoice_columnaliases);
  $wherequery = 'WHERE t1.id = t2.sampleinvoiceid AND t1.warehouseid = t3.id' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $salesinvoice_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $salesinvoice_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT 'Sample Invoice' as `type`, t1.id, $columnquery FROM sampleinvoice t1, sampleinvoiceinventory t2, warehouse t3 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);

  return $data;

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('sampleinvoice', 'new')) $html[] = "<td><button id='createbtn' class='blue' onclick=\"ui.async('ui_sampleinvoicedetail', [ null, 'write' ], {})\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('sampleinvoice', 'download')) $html[] = "<td><button id='createbtn' class='hollow' onclick=\"ui.async('ui_sampleinvoiceexport', [ ], {})\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function grid_options($obj){

  $id = $obj['id'];
  $code = $obj['code'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_sampleinvoicedetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_sampleinvoiceremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function grid_moveoption($obj){

  $c = "<div class='align-center salesinvoicemove'>";
  $c .= $obj['moved'] ? "<span class='fa fa-check color-green'></span>" : "<span class='fa fa-plus color-blue' onclick=\"if(confirm('Pindah transaksi ini?')) ui.async('ui_salesinvoicemove', [ $obj[id] ])\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_sampleinvoicedetail', [ this.dataset['id'] ], {})";

}


include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/salesinvoice.js"></script>