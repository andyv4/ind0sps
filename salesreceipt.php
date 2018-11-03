<?php
if(!systemvarget('salesable') || privilege_get('salesreceipt', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'salesreceipt';

require_once 'api/salesreceipt.php';
require_once 'ui/salesreceipt.php';

function defaultmodule(){

  $columns = salesreceipt_uicolumns();

  $module = array(
      'title'=>'Sales Receipt',
      'columns'=>$columns,
      'presets'=>array(
          array(
            'text'=>'Detil',
            'columns'=>$columns,
            'sorts'=>array(
              array('name'=>'createdon', 'sorttype'=>'desc')
            ),
            'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
        array('text'=>'', 'value'=>'code|customerdescription|paymentaccountname&contains&'),
        array('text'=>'Lunas:', 'value'=>'ispaid&equals&')
      ),
      'griddoubleclick'=>"ui.async('ui_salesreceiptdetail', [ id ], { waitel:this })",
      'detailcolumns'=>array(
        array('active'=>1, 'name'=>'col0', 'text'=>'Kode', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col0', 'width'=>150),
        array('active'=>1, 'name'=>'col2', 'text'=>'Tanggal', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col2', 'width'=>150),
        array('active'=>1, 'name'=>'col3', 'text'=>'Total', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col3', 'width'=>100),
        array('active'=>1, 'name'=>'col4', 'text'=>'Lunas', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col4', 'width'=>50),
        array('active'=>1, 'name'=>'col5', 'text'=>'Pembayaran', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col5', 'width'=>100),
        array('active'=>1, 'name'=>'col6', 'text'=>'', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col6', 'width'=>24),
      )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $salesreceipt_columnaliases = array(
    'id'=>'t1.id!',
    'ispaid'=>'t1.ispaid',
    'date'=>'t1.date',
    'code'=>'t1.code',
    'customerid'=>'t1.customerid',
    'customerdescription'=>'t1.customerdescription',
    'address'=>'t1.address',
    'note'=>'t1.note',
    'total'=>'t1.total',
    'paymentaccountname'=>'t3.name',
    'paymentdate'=>'t1.paymentdate',
    'paymentamount'=>'t1.paymentamount',
    'beneficiarydetail'=>'t1.beneficiarydetail',
    'createdon'=>'t1.createdon',
    'salesinvoicegroupcode'=>'t2.code'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $salesreceipt_columnaliases);
  $wherequery = 'WHERE t1.id = t2.salesreceiptid AND t1.paymentaccountid = t3.id' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $salesreceipt_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $salesreceipt_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);
  $query = "SELECT 'salesreceipt' as `type`, t1.id, $columnquery FROM
    salesreceipt t1, salesinvoicegroup t2, chartofaccount t3 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);

  return $data;

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('salesreceipt', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_salesreceiptdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('salesreceipt', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_salesreceiptexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function grid_options($obj){

  global $deletable;
  $id = $obj['id'];
  $code = ov('code', $obj);

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_salesreceiptdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_salesreceiptremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}
function salesreceiptlist_ispaid($obj){

  return "<div class='align-center'>" . ($obj['ispaid'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}

function m_griddoubleclick(){

  return "ui.async('ui_salesreceiptdetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('salesreceipt', 'delete');
include 'rcfx/dashboard1.php';
?>