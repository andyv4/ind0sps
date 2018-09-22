<?php
if(!systemvarget('purchaseable') || privilege_get('purchaseorder', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'purchaseorder';

require_once 'api/purchaseorder.php';
require_once 'ui/purchaseorder.php';
require_once 'ui/purchaseinvoice.php';

function defaultmodule(){

  $columns = purchaseorder_uicolumns();

  $module = array(
      'title'=>'purchaseorder',
      'columns'=>$columns,
      'presets'=>array(
          array(
            'text'=>'Aktif',
            'columns'=>$columns,
            'sorts'=>array(
              array('name'=>'createdon', 'sorttype'=>'desc')
            ),
            'filters'=>[
              [ 'name'=>'isinvoiced', 'operator'=>'=', 'value'=>0 ],
              [ 'name'=>'isbaddebt', 'operator'=>'=', 'value'=>0 ],
            ],
            'viewtype'=>'list'
          ),
          array(
            'text'=>'Terfaktur',
            'columns'=>$columns,
            'sorts'=>array(
              array('name'=>'createdon', 'sorttype'=>'desc')
            ),
            'filters'=>[
              [ 'name'=>'isinvoiced', 'operator'=>'=', 'value'=>1 ],
            ],
            'viewtype'=>'list'
          ),
          array(
            'text'=>'Bad Debt',
            'columns'=>$columns,
            'sorts'=>array(
              array('name'=>'createdon', 'sorttype'=>'desc')
            ),
            'filters'=>[
              [ 'name'=>'isbaddebt', 'operator'=>'=', 'value'=>1 ],
            ],
            'viewtype'=>'list'
          ),
          array(
            'text'=>'Semua Pesanan',
            'columns'=>$columns,
            'sorts'=>array(
              array('name'=>'createdon', 'sorttype'=>'desc')
            ),
            'filters'=>[
            ],
            'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
          array('text'=>'', 'value'=>'code|description|supplierdescription|paymentaccountname|inventorycode|inventorydescription&contains&')
      )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $purchaseorder_columnaliases = array(
    'id'=>'t1.id',
    'ispaid'=>'t1.ispaid!',
    'isinvoiced'=>'t1.isinvoiced!',
    'isbaddebt'=>'t1.isbaddebt!',
    'code'=>'t1.code',
    'date'=>'t1.date',
    'supplierdescription'=>'t1.supplierdescription',
    'address'=>'t1.address',
    'currencycode'=>'t3.code',
    'currencyname'=>'t3.name',
    'currencyrate'=>'t1.currencyrate',
    'subtotal'=>'t1.subtotal',
    'discount'=>'t1.discount',
    'discountamount'=>'t1.discountamount',
    'taxable'=>'t1.taxable',
    'taxamount'=>'t1.taxamount',
    'freightcharge'=>'t1.freightcharge',
    'handlingfeeaccountid'=>'t1.handlingfeeaccountid',
    'handlingfeeaccountname'=>'(select `name` from chartofaccount where `id` = t1.handlingfeeaccountid)',
    'handlingfeeamount'=>'t1.handlingfeeamount',
    'total'=>'t1.total',
    'paymentaccountid'=>'t1.paymentaccountid',
    'paymentaccountname'=>'(select `name` from chartofaccount where `id` = t1.paymentaccountid)',
    'paymentaccountdate'=>'t1.paymentaccountdate',
    'paymentaccountamount'=>'t1.paymentaccountamount',
    'note'=>'t1.note',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'unitdiscount'=>'t2.unitdiscount',
    'unitdiscountamount'=>'t2.unitdiscountamount',
    'unittotal'=>'t2.unittotal',
    'unithandlingfee'=>'t2.unithandlingfee',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $purchaseorder_columnaliases);
  $wherequery = 'WHERE t1.id = t2.purchaseorderid AND t1.currencyid = t3.id' .
      str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $purchaseorder_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $purchaseorder_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;

  $query = "SELECT 'purchaseorder' as `type`, t1.supplierid, t1.id, t1.currencyid, t1.handlingfeeaccountid, t1.paymentaccountid, t2.inventoryid $columnquery
    FROM purchaseorder t1, purchaseorderinventory t2, currency t3 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('purchaseorder', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_purchaseorderdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('purchaseorder', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_purchaseorderexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function purchaseorderlist_options($obj){

  global $deletable;
  $id = $obj['id'];
  $code = $obj['code'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_purchaseorderdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_purchaseorderremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function purchaseorderlist_ispaid($obj){

  return "<div class='align-center'>" . ($obj['ispaid'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}

function purchaseorderlist_isinvoiced($obj){

  $c = "<div class='align-center'>";
  if(!ov('isbaddebt', $obj)){
    if($obj['isinvoiced']){
      $c .= "<span class='fa fa-check-circle color-green hover-to-open' onclick=\"ui.async('ui_purchaseinvoicedetail_by_purchaseorderid', [ $obj[id], 'read' ], { waitel:this })\"></span>";
    }
    else{
      $c .= "<span class='fa fa-plus color-blue' onclick=\"ui.async('ui_purchaseorder_createinvoice', [" . $obj['id'] . "], { waitel:this })\"></span>";
    }
  }
  $c .= "</div>";
  return $c;

}

function purchaseorderlist_isbaddebt($obj){

  $c = "<div class='align-center'>";
  if(isset($obj['isbaddebt'])){
    if($obj['isbaddebt']){
      $c .= "<span class='fa fa-check-circle color-green'></span>";
    }
    else{

    }
  }
  $c .= "</div>";
  return $c;

}

function ui_purchaseorder_createinvoice($id){

  if(!privilege_get('purchaseinvoice', 'new')) throw new Exception('Tidak dapat membuat faktur pembelian, .');

  $purchaseinvoice = purchaseinvoicedetail(null, array('purchaseorderid'=>$id));
  if(is_array($purchaseinvoice)) throw new Exception('Tidak dapat membuat faktur pembelian, faktur sudah dibuat.');

  $purchaseorder = purchaseorderdetail(null, array('id'=>$id));

  $date = date('Ymd');
  $taxable = $purchaseorder['taxable'];

  $purchaseinvoice = array(
    'date'=>date('Ymd'),
    'code'=>purchaseinvoicecode($date, $taxable),
    'supplierdescription'=>$purchaseorder['supplierdescription'],
    'address'=>$purchaseorder['address'],
    'warehousename'=>$purchaseorder['warehousename'],
    'currencyid'=>$purchaseorder['currencyid'],
    'currencyrate'=>$purchaseorder['currencyrate'],
    'inventories'=>$purchaseorder['inventories'],
    'subtotal'=>$purchaseorder['subtotal'],
    'discount'=>$purchaseorder['discount'],
    'discountamount'=>$purchaseorder['discountamount'],
    'taxable'=>$purchaseorder['taxable'],
    'taxamount'=>$purchaseorder['taxamount'],
    'total'=>$purchaseorder['total'],
    'ispaid'=>0,
    'downpaymentamount'=>$purchaseorder['paymentamount'],
    'downpaymentdate'=>$purchaseorder['paymentdate'],
    'downpaymentaccountid'=>$purchaseorder['paymentaccountid'],
    'purchaseorderid'=>$id,
    'pocode'=>$purchaseorder['code'],
    'freightcharge'=>$purchaseorder['freightcharge']
  );

  return ui_purchaseinvoicedetail($purchaseinvoice, false);

}

function purchaseorderlist_supplierdescription($obj){

  $supplierdescription = $obj['supplierdescription'];
  $supplierid = $obj['supplierid'];
  $c = "<span class='text-clickable' onclick=\"ui.async('ui_supplierdetail', [ $supplierid, 'read' ], { waitel:this })\">" . $supplierdescription . "</span>";
  return $c;

}

function purchaseorderlist_inventorydescription($obj){

  $inventorydescription = $obj['inventorydescription'];
  $inventoryid = $obj['inventoryid'];
  $c = "<span class='text-clickable' onclick=\"ui.async('ui_inventorydetail', [ $inventoryid, 'read' ], { waitel:this })\">" . $inventorydescription . "</span>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_purchaseorderdetail', [ this.dataset['id'] ], {})";

}
function grid_journaloption($obj){

  if(!privilege_get('chartofaccount', 'list')) return;

  $id = $obj['id'];
  return "
  <div class='align-center'>
    <span class='padding5 fa fa-folder-open' onclick=\"ui.async('ui_purchaseorderdetail_journal', [ $id ], { waitel:this })\"></span>
  </div>
  ";

}

$deletable = privilege_get('purchaseorder', 'delete');
include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/purchaseorder.js"></script>