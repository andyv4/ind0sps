<?php
if(privilege_get('customer', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'customer';

$groupable = true;
if(date('Ymd') < 20180313) $mod_reset = true;

require_once 'api/customer.php';
require_once 'ui/customer.php';

function defaultcolumns(){

  return customer_uicolumns();

}
function defaultmodule(){

  $columns = customer_uicolumns();

  $module = array(
      'title'=>'customer',
      'columns'=>$columns,
      'presets'=>array(
          array(
              'text'=>'Semua Pelanggan',
              'columns'=>$columns,
              'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
        array('text'=>'', 'value'=>'code|description&contains&')
      ),
      'detailpricepresets'=>array(
          array(
              'text'=>'default',
              'columns'=>array(
                  array('active'=>0, 'name'=>'inventoryid', 'text'=>'Inventory ID', 'width'=>50, 'datatype'=>'number'),
                  array('active'=>1, 'name'=>'code', 'text'=>'Code', 'width'=>70),
                  array('active'=>1, 'name'=>'description', 'text'=>'Description', 'width'=>280),
                  array('active'=>1, 'name'=>'price', 'text'=>'Inventory Price', 'width'=>100, 'datatype'=>'money'),
                  array('active'=>1, 'name'=>'customerprice', 'text'=>'Custom Price', 'width'=>120, 'nodittomark'=>1, 'datatype'=>'money', 'type'=>'html', 'html'=>'ui_customerdetail_pricedetail_customerprice')
              )
          )
      ),
      'detailpricepresetidx'=>0,
      'detailsalespresets'=>array(
          array(
              'text'=>'default',
              'columns'=>array(
                  array('active'=>1, 'name'=>'ispaid', 'text'=>'Paid', 'width'=>30, 'type'=>'html', 'html'=>'customerdetailsales_ispaid'),
                  array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>80),
                  array('active'=>1, 'name'=>'code', 'text'=>'Invoice Code', 'width'=>100),
                  array('active'=>0, 'name'=>'inventorycode', 'text'=>'Inventory Code', 'width'=>50),
                  array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Inventory Description', 'width'=>150),
                  array('active'=>1, 'name'=>'qty', 'text'=>'Qty', 'width'=>50, 'datatype'=>'number'),
                  array('active'=>1, 'name'=>'unitprice', 'text'=>'Unit Price', 'width'=>100, 'datatype'=>'money'),
                  array('active'=>1, 'name'=>'total', 'text'=>'Invoice Total', 'width'=>100, 'datatype'=>'money'),
              )
          )
      ),
      'detailsalespresetidx'=>0
  );
  return $module;

}
function systemmodule(){

  $columns = customer_uicolumns();
  $module = array(
    'title'=>'customer',
    'columns'=>$columns,
    'presets'=>array(
      array(
        'text'=>'Jatuh Tempo',
        'columns'=>$columns,
        'viewtype'=>'list',
        'filters'=>[
          [ 'name'=>'issuspended', 'operator'=>'=', 'value'=>1 ]
        ]
      ),
    ),
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'code|description&contains&')
    ),
    'detailpricepresets'=>array(
      array(
        'text'=>'default',
        'columns'=>array(
          array('active'=>0, 'name'=>'inventoryid', 'text'=>'Inventory ID', 'width'=>50, 'datatype'=>'number'),
          array('active'=>1, 'name'=>'code', 'text'=>'Code', 'width'=>70),
          array('active'=>1, 'name'=>'description', 'text'=>'Description', 'width'=>280),
          array('active'=>1, 'name'=>'price', 'text'=>'Inventory Price', 'width'=>100, 'datatype'=>'money'),
          array('active'=>1, 'name'=>'customerprice', 'text'=>'Custom Price', 'width'=>120, 'nodittomark'=>1, 'datatype'=>'money', 'type'=>'html', 'html'=>'ui_customerdetail_pricedetail_customerprice')
        )
      )
    ),
    'detailpricepresetidx'=>0,
    'detailsalespresets'=>array(
      array(
        'text'=>'default',
        'columns'=>array(
          array('active'=>1, 'name'=>'ispaid', 'text'=>'Paid', 'width'=>30, 'type'=>'html', 'html'=>'customerdetailsales_ispaid'),
          array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>80),
          array('active'=>1, 'name'=>'code', 'text'=>'Invoice Code', 'width'=>100),
          array('active'=>0, 'name'=>'inventorycode', 'text'=>'Inventory Code', 'width'=>50),
          array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Inventory Description', 'width'=>150),
          array('active'=>1, 'name'=>'qty', 'text'=>'Qty', 'width'=>50, 'datatype'=>'number'),
          array('active'=>1, 'name'=>'unitprice', 'text'=>'Unit Price', 'width'=>100, 'datatype'=>'money'),
          array('active'=>1, 'name'=>'total', 'text'=>'Invoice Total', 'width'=>100, 'datatype'=>'money'),
        )
      )
    ),
    'detailsalespresetidx'=>0
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null, $groups = null){

  if(!is_array($filters)) $filters = [];

  // Apply allowed_salesman (_self,*,<name>,<name>)
  $sales_allowed_salesman = userkeystoreget($_SESSION['user']['id'], 'privilege.sales_allowed_salesman');
  if(strpos($sales_allowed_salesman, '*') === false){
    $sales_allowed_salesman = explode(',', $sales_allowed_salesman);
    $salesman_count = 0;
    if(count($filters) > 0) $filters[] = [ 'type'=>'and' ];
    $filters[] = [ 'type'=>'(' ];
    foreach($sales_allowed_salesman as $salesman){
      if(empty($salesman)) continue;
      $salesman = $salesman == '_self' ? $_SESSION['user']['userid'] : $salesman;
      if($salesman_count > 0) $filters[] = [ 'type'=>'or' ];
      $filters[] = [ 'name'=>'defaultsalesmanname', 'operator'=>'contains', 'value'=>$salesman ];
      $salesman_count++;
    }
    if(!$salesman_count) $filters[] = [ 'name'=>'defaultsalesmanname', 'operator'=>'contains', 'value'=>$_SESSION['user']['userid'] ];
    $filters[] = [ 'type'=>')' ];
  }

  return customerlist($columns, $sorts, $filters, $limits, $groups);

}

function customheadcolumns(){

  $c = [];
  if(privilege_get('customer', 'new'))
    $c[] = "<td><button class='blue' onclick=\"ui.async('ui_customerdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('customer', 'download'))
    $c[] = "<td><button class='hollow' onclick=\"ui.async('ui_customerexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $c);

}

function customerlist_options($obj){

  global $deletable;
  $id = $obj['id'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_customerdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('" . lang('c50', array('name'=>$obj['description'])) . "')) ui.async('ui_customerremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}
function customerlist_taxable($obj){

  return "<div class='align-center'>" . ($obj['taxable'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}
function customerlist_status($obj){

  $issuspended = ov('issuspended', $obj, false, 0);
  $c = "<span class='align-center'>";
  $c .= $issuspended ? "<span class='bg-red grid-indicator'>Macet</span>" : '';
  $c .= "</span>";
  return $c;

}
function customerlist_isactive($obj){

  $html = [ "<div class='align-center'>" ];
  if(privilege_get('customer', 'modify') && in_array($_SESSION['user']['dept'], [ 'accounting' ])){
    $html[] = "<input data-id=\"" . ov('id', $obj) . "\" data-text=\"" . ov('description', $obj) . "\" type='checkbox'" . (ov('isactive', $obj, 0) > 0 ? ' checked' : '') . " onchange='return customer_isactive_onchange(event, this)'/>";
  }
  else{
    $html[] = ov('isactive', $obj) > 0 ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>";
  }
  $html[] = "</div>";

  return implode('', $html);

}
function customerdetailsales_ispaid($obj){

  return "<div class='align-center'>" . ($obj['ispaid'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}
function m_griddoubleclick(){

  return "ui.async('ui_customerdetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('customer', 'delete');
include 'rcfx/dashboard1.php';

?>

<?php if(privilege_get('customer', 'modify') && in_array($_SESSION['user']['dept'], [ 'accounting' ])){ ?>
<script>

  function customer_isactive_onchange(e, input){

    var id = input.getAttribute("data-id");
    var text = input.getAttribute("data-text");
    if(confirm(!input.checked ? "Non-aktifkan " + text + "?" : "Aktifkan " + text + "?")){
      ui.async('ui_customer_isactive_toggle', [ { id:id } ]);
      return true;
    }
    else{
      input.checked = !input.checked;
      return false;
    }
  }

</script>
<?php } ?>