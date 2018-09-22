<?php
if(!systemvarget('salesable') || privilege_get('salesinvoicegroup', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'salesinvoicegroup';

require_once 'api/salesinvoicegroup.php';
require_once 'ui/salesinvoicegroup.php';

function defaultmodule(){

  $columns = salesinvoicegroup_uicolumns();

  $module = array(
      'title'=>'Sales Invoice Group',
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
          array('text'=>'', 'value'=>'code|customerdescription&contains&'),
          array('text'=>'Kode Faktur: ', 'value'=>'itemcode&contains&'),
          array('text'=>'Catatan: ', 'value'=>'note&contains&')
      ),
      'griddoubleclick'=>"ui.async('ui_salesinvoicegroupdetail', [ id ], { waitel:this })"
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $salesinvoicegroup_columnaliases = array(
    'ispaid'=>'t1.ispaid',
    'isreceipt'=>'t1.isreceipt',
    'date'=>'t1.date',
    'code'=>'t1.code',
    'customerdescription'=>'t1.customerdescription',
    'address'=>'t1.address',
    'note'=>'t1.note',
    'total'=>'t1.total',
    'paymentaccountname'=>"(SELECT `name` FROM chartofaccount WHERE `id` = t1.paymentaccountid)",
    'paymentdate'=>'t1.paymentdate',
    'paymentamount'=>'t1.paymentamount',
    'itemtype'=>"IF(t2.type = 'SI', 'Faktur' , 'Retur')",
    'itemcode'=>"IF(t2.type = 'SI', (SELECT code FROM salesinvoice WHERE `id` = t2.typeid), (SELECT code FROM salesreturn WHERE `id` = t2.typeid))",
    'itemtotal'=>"IF(t2.type = 'SI', (SELECT total FROM salesinvoice WHERE `id` = t2.typeid), (SELECT total FROM salesreturn WHERE `id` = t2.typeid))",
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $salesinvoicegroup_columnaliases);

  $wherequery1 = wherequery_from_filters($params, $filters, $salesinvoicegroup_columnaliases);
  if(substr($wherequery1, 0, 6) == ' WHERE') $wherequery1 = ' AND ' . substr($wherequery1, 6);

  $wherequery = 'WHERE t1.id = t2.salesinvoicegroupid' . $wherequery1;
  $sortquery = sortquery_from_sorts($sorts, $salesinvoicegroup_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;

  $query = "SELECT 'salesinvoicegroup' as `type`, t1.id, t1.paymentaccountid $columnquery
    FROM salesinvoicegroup t1, salesinvoicegroupitem t2 $wherequery $sortquery $limitquery";
//  if($_SESSION['user']['userid'] == 'sundari') exc($filters);
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('salesinvoicegroup', 'new')) $html[] = "<td><button id='createbtn' class='blue' onclick=\"salesinvoicegroup_new()\"><span class='mdi mdi-plus'></span><label>Buat Grup</label></button></td>";
  if(privilege_get('salesinvoicegroup', 'download')) $html[] = "<td><button id='createbtn' class='hollow' onclick=\"ui.async('ui_salesinvoicegroupexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function salesinvoicegrouplist_options($obj){

  global $deletable;
  $id = $obj['id'];
  $code = $obj['code'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_salesinvoicegroupdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_salesinvoicegroupremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}
function salesinvoicegrouplist_ispaid($obj){

  return "<div class='align-center'>" . ($obj['ispaid'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}
function salesinvoicegrouplist_isreceipt($obj){

  $c = "<div class='align-center'>";
  if($obj['isreceipt']){
    $c .= "<span class='fa fa-check-circle color-green'></span>";
  }
  else{
    $c .= "<input type='checkbox' data-name='isreceipt' onchange=\"salesinvoicegrouplist_groupopt_checkstate()\"/>";
  }
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_salesinvoicegroupdetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('salesinvoicegroup', 'delete');
include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/salesinvoicegroup.js"></script>