<?php

function ui_customerdetail($id = null, $mode = 'read', $index = 0){

  // Parameters:
  // - $obj : current item obj to open
  // - mode : read, write

  $customer = $id ? customerdetail(null, array('id'=>$id)) : array();
  if($mode != 'read' && $customer && !privilege_get('customer', 'modify')) $mode = 'read';
  if($mode == 'read' && !$customer) throw new Exception('Pelanggan dengan nomor ini tidak ada.');
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$customer ? true : false;
  if($is_new && !privilege_get('customer', 'new')) exc("Anda tidak dapat membuat pelanggan.");
  $index = $index < 0 || $index > 2 ? 0 : $index;

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>$customer['id']),
    'isactive'=>array('type'=>'checkbox', 'name'=>'isactive', 'value'=>ov('isactive', $customer, 0, 1), 'readonly'=>$readonly),
    'code'=>array('type'=>'textbox', 'name'=>'code','width'=>'100px', 'value'=>$customer['code'], 'readonly'=>$readonly),
    'description'=>array('type'=>'textbox', 'name'=>'description','width'=>'300px', 'value'=>$customer['description'], 'readonly'=>$readonly),
    'tax_registration_number'=>array('type'=>'textbox', 'name'=>'tax_registration_number','width'=>'200px', 'value'=>$customer['tax_registration_number'], 'readonly'=>$readonly),
    'address'=>array('type'=>'textarea', 'name'=>'address','width'=>'400px', 'height'=>'60px', 'value'=>$customer['address'], 'readonly'=>$readonly),
    'billingaddress'=>array('type'=>'textarea', 'name'=>'billingaddress','width'=>'400px', 'height'=>'60px', 'value'=>$customer['billingaddress'], 'readonly'=>$readonly),
    'city'=>array('type'=>'textbox', 'name'=>'city','width'=>'100', 'value'=>$customer['city'], 'readonly'=>$readonly),
    'country'=>array('type'=>'textbox', 'name'=>'country','width'=>'120px', 'value'=>$customer['country'], 'readonly'=>$readonly),
    'phone1'=>array('type'=>'textbox', 'name'=>'phone1','width'=>'150px', 'value'=>$customer['phone1'], 'readonly'=>$readonly),
    'phone2'=>array('type'=>'textbox', 'name'=>'phone2','width'=>'150px', 'value'=>$customer['phone2'], 'readonly'=>$readonly),
    'fax1'=>array('type'=>'textbox', 'name'=>'fax1','width'=>'150px', 'value'=>$customer['fax1'], 'readonly'=>$readonly),
    'fax2'=>array('type'=>'textbox', 'name'=>'fax2','width'=>'150px', 'value'=>$customer['fax2'], 'readonly'=>$readonly),
    'contactperson'=>array('type'=>'textbox', 'name'=>'contactperson','width'=>'240px', 'value'=>$customer['contactperson'], 'readonly'=>$readonly),
    'email'=>array('type'=>'textbox', 'name'=>'email','width'=>'200px', 'value'=>$customer['email'], 'readonly'=>$readonly),
    'note'=>array('type'=>'textarea', 'name'=>'note','width'=>'400px', 'height'=>'60px', 'value'=>$customer['note'], 'readonly'=>$readonly),
    'discount'=>array('type'=>'textbox', 'name'=>'discount','width'=>'40px', 'value'=>$customer['discount'], 'readonly'=>$readonly),
    'taxable'=>array('type'=>'checkbox', 'name'=>'taxable', 'value'=>$customer['taxable'], 'readonly'=>$readonly),
    'defaultsalesmanid'=>array('type'=>'dropdown', 'name'=>'defaultsalesmanid', 'items'=>ui_defaultsalesmannames(), 'key'=>'name', 'value'=>ov('defaultsalesmanid', $customer, 0, 999), 'readonly'=>$readonly, 'width'=>150),
    'creditlimit'=>array('type'=>'textbox', 'name'=>'creditlimit', 'align'=>'left', 'width'=>'150px', 'value'=>$customer['creditlimit'], 'readonly'=>$readonly),
    'creditterm'=>array('type'=>'textbox', 'name'=>'creditterm', 'width'=>'60px', 'value'=>$customer['creditterm'], 'readonly'=>$readonly),
    'override_sales'=>array('type'=>'checkbox', 'name'=>'override_sales', 'value'=>$customer['override_sales'], 'readonly'=>$readonly),
    'sales_companyname'=>array('type'=>'textbox', 'name'=>'sales_companyname','width'=>'240px', 'value'=>$customer['sales_companyname'], 'readonly'=>$readonly),
    'sales_addressline1'=>array('type'=>'textbox', 'name'=>'sales_addressline1','width'=>'240px', 'value'=>$customer['sales_addressline1'], 'readonly'=>$readonly),
    'sales_addressline2'=>array('type'=>'textbox', 'name'=>'sales_addressline2','width'=>'240px', 'value'=>$customer['sales_addressline2'], 'readonly'=>$readonly),
    'sales_addressline3'=>array('type'=>'textbox', 'name'=>'sales_addressline3','width'=>'240px', 'value'=>$customer['sales_addressline3'], 'readonly'=>$readonly),
  );

  $tabs = array();
  if($customer){
    $tabs[] = "<div class='tabitem" . ($index == 0 ? ' active' : '') . "' onclick='ui.tabclick(event, this)'><label>Customer Detail</label></div>";
    $tabs[] = "<div class='tabitem" . ($index == 1 ? ' active' : '') . "' onclick=\"ui.tabclick(event, this); customerdetail_pricetabclick($id, this, '$mode')\"><label>Harga</label></div>";
    $tabs[] = "<div class='tabitem" . ($index == 2 ? ' active' : '') . "' onclick='ui.tabclick(event, this); customerdetail_salestabclick($id, this)'><label>Penjualan</label></div>";
  }

  $actions = array();
  if(!$readonly && !$customer && privilege_get('customer', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_customersave', [ ui.container_value(ui('#customerdetailpane')) ], { waitel:this })\"><span class='fa fa-check'></span><label>Save</label></button></td>";
  if(!$readonly && $customer && privilege_get('customer', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_customersave', [ ui.container_value(ui('#customerdetailpane')) ], { waitel:this })\"><span class='fa fa-check'></span><label>Save</label></button></td>";
  if($readonly && $customer && privilege_get('customer', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_customerdetail', [ $id, 'write', ui.tab_value($('.tabhead')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>Modify</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $c = "<element exp='.modal'>";
  $c .= "
	<div id='customerdetailpane'>
    <div class='padding10 align-center'>
      <div class='tabhead' data-tabbody='.tabbody'>" . implode('', $tabs) . "</div>
    </div>
    <div>
      <div class='tabbody'>
        <div class='tab" . ($index == 0 ? '' : ' off') . "'>
          <div id='customerdetailscrollable1' class='scrollable'>
            " . ui_control($controls['id']) . "
            <table class='form'>
              <tr><th><label>" . lang('c01') . "</label></th><td>" . ui_control($controls['isactive']) . "</td></tr>
              <tr><th><label title='$id'>" . lang('c02') . "</label></th><td>" . ui_control($controls['code']) . "</td></tr>
              <tr><th><label>" . lang('c03') . "</label></th><td>" . ui_control($controls['description']) . "</td></tr>
              <tr><th><label>" . lang('c21') . "</label></th><td>" . ui_control($controls['tax_registration_number']) . "</td></tr>
              <tr><th><label>" . lang('c04') . "</label></th><td>" . ui_control($controls['address']) . "</td></tr>
              <tr><th><label>" . lang('c20') . "</label></th><td>" . ui_control($controls['billingaddress']) . "</td></tr>
              <tr><th><label>" . lang('c05') . "</label></th><td>" . ui_control($controls['city']) . "</td></tr>
              <tr><th><label>" . lang('c06') . "</label></th><td>" . ui_control($controls['country']) . "</td></tr>
              <tr><th><label>" . lang('c07') . "</label></th><td>" . ui_control($controls['phone1']) . "</td></tr>
              <tr><th><label>" . lang('c08') . "</label></th><td>" . ui_control($controls['phone2']) . "</td></tr>
              <tr><th><label>" . lang('c09') . "</label></th><td>" . ui_control($controls['fax1']) . "</td></tr>
              <tr><th><label>" . lang('c10') . "</label></th><td>" . ui_control($controls['fax2']) . "</td></tr>
              <tr><th><label>" . lang('c11') . "</label></th><td>" . ui_control($controls['contactperson']) . "</td></tr>
              <tr><th><label>" . lang('c12') . "</label></th><td>" . ui_control($controls['email']) . "</td></tr>
              <tr><th><label>" . lang('c13') . "</label></th><td>" . ui_control($controls['note']) . "</td></tr>
              <tr><th><label>" . lang('c14') . "</label></th><td>" . ui_control($controls['discount']) . "</td></tr>
              <tr><th><label>" . lang('c15') . "</label></th><td>" . ui_control($controls['taxable']) . "</td></tr>
              <tr><th><label>" . lang('c16') . "</label></th><td>" . ui_control($controls['defaultsalesmanid']) . "</td></tr>
              <tr><th><label>" . lang('c17') . "</label></th><td>" . ui_control($controls['creditterm']) . "</td></tr>
              <tr><th><label>" . lang('c18') . "</label></th><td>" . ui_control($controls['creditlimit']) . "</td></tr>
              <tr><th><label>Override Sales Template</label></th><td>" . ui_control($controls['override_sales']) . "</td></tr>
              <tr><th><label>Nama Perusahaan</label></th><td>" . ui_control($controls['sales_companyname']) . "</td></tr>
              <tr><th><label>Alamat (baris 1)</label></th><td>" . ui_control($controls['sales_addressline1']) . "</td></tr>
              <tr><th><label>Alamat (baris 2)</label></th><td>" . ui_control($controls['sales_addressline2']) . "</td></tr>
              <tr><th><label>Alamat (baris 3)</label></th><td>" . ui_control($controls['sales_addressline3']) . "</td></tr>
            </table>
          </div>
        </div>
        <div class='tab" . ($index == 1 ? '' : ' off') . "'>
          <div id='customerdetailstatic2' style='height: 84px'></div>
          <div id='customerdetailscrollable2' class='scrollable'></div>
        </div>
        <div class='tab" . ($index == 2 ? '' : ' off') . "'>
          <div id='customerdetailstatic3' style='height: 84'></div>
          <div id='customerdetailscrollable3' class='scrollable'></div>
        </div>
      </div>
    </div>
    <div class='foot'>
      <table cellspacing='5'>
        <tr>
          <td style='width: 100%'></td>
          " . implode('', $actions) . "
        </tr>
      </table>
    </div>
  </div>
	";
  $c .= "</element>";
  $c .= uijs("
		ui.loadscript('rcfx/js/customer.js', \"customerdetail_resize();ui.modal_open(ui('.modal'), { closeable:$closable, width:800 });" . ($index == 1 ? "customerdetail_pricetabclick($id, this, '$mode');" : ($index == 2 ? "customerdetail_salestabclick($id, this)" : '')) . "\");
  ");

  global $module;
  $module['detailmode'] = $mode;
  m_savestate($module);

  return $c;

}

function ui_customerdetail_pricedetail($id){

  global $module;
  $customer = customerdetail(array('inventories'), array('id'=>$id));
  $inventories = $customer['inventories'];

  // Add customerid to each inventories
  for($i = 0 ; $i < count($inventories) ; $i++){
    $inventories[$i]['customerid'] = $id;
    $inventories[$i]['readonly'] = $module['detailmode'] == 'write' ? false : true;
  }

  $detailpricepreset = $module['detailpricepresets'][$module['detailpricepresetidx']];
  $columns = $detailpricepreset['columns'];

  // Quick filter value
  $quickfiltervalue = array();
  $quickfilters = array();
  $columns_indexed = array_index($columns, array('name'), 1);
  if(isset($detailpricepreset['detailpricequickfilters']) && is_array($detailpricepreset['detailpricequickfilters']))
    for($i = 0 ; $i < count($detailpricepreset['detailpricequickfilters']) ; $i++){
      $filter = $detailpricepreset['detailpricequickfilters'][$i];
      $filtername = $filter['name'];
      $filtervalue = $filter['value'];

      $quickfilters[] = array('name'=>$filtername, 'operator'=>'contains', 'value'=>$filtervalue, 'type'=>'text');
      $quickfiltervalue[] = array('text'=>$columns_indexed[$filtername]['text'] . ' : ' . $filtervalue, 'value'=>json_encode(array('name'=>$filtername, 'value'=>$filtervalue)));
    }

  if(isset($detailpricepreset['sorts']) && is_array($detailpricepreset['sorts']))
    data_sort($inventories, $detailpricepreset['sorts']);

  // Data filter
  data_filter($inventories, $quickfilters);

  //throw new Exception(print_r($quickfilters, 1));
  //throw new Exception(count($inventories) . ', ' . count($filtered_data));

  $c = "<element exp='#customerdetailstatic2'>";
  $c .= "<table cellspacing='4' style='width: 100%'><tr>";
  $c .= "<td style='width:100%'>" . ui_multicomplete(array('width'=>'100%', 'name'=>'search', 'src'=>'ui_customerdetail_pricedetail_quickfilter', 'placeholder'=>'Quick filter...',
          'value'=>$quickfiltervalue, 'separator'=>'|', 'onchange'=>"ui.async('ui_customerdetail_pricedetail_quickfilterapply', [ ui.multicomplete_value(this) ], {})")) . "</td>";
  $c .= "</tr></table>";
  $c .= ui_gridhead(array('columns'=>$detailpricepreset['columns'], 'gridexp'=>'#pricedetailgrid',
      'oncolumnresize'=>"ui.async('ui_customerdetail_pricedetail_columnresize', [ name, width ], {})",
      'oncolumnclick'=>"ui.async('ui_customerdetail_pricedetail_sortapply', [ name ], {})",
      'oncolumnapply'=>'ui_customerdetail_pricedetail_columnapply'));
  $c .= "</element>";
  $c .= "<element exp='#customerdetailscrollable2'>";
  $c .= ui_grid(array('id'=>'pricedetailgrid', 'columns'=>$detailpricepreset['columns'], 'value'=>$inventories, 'scrollel'=>'#customerdetailscrollable2'));
  $c .= "</element>";

  $module['customerid'] = $id;
  m_savestate($module);

  echo $c;

}

function ui_customerdetail_pricedetail_customerprice($obj){

  return ui_textbox(array(
      'name'=>'customerprice',
      'width'=>120,
      'onchange'=>"ui.async('ui_customerdetail_pricedetail_customerpricechange', [ $obj[customerid], $obj[inventoryid], value ])",
      'placeholder'=>'Auto save',
      'value'=>$obj['customerprice'],
      'readonly'=>$obj['readonly'],
    'datatype'=>'money'
  ));

}

function ui_customerdetail_pricedetail_customerpricechange($customerid, $inventoryid, $price){

  customerpricechange($customerid, $inventoryid, $price);

  //echo ui_dialog('Saved', print_r(get_defined_vars(), 1));

}

function ui_customerdetail_pricedetail_columnresize($name, $width){

  global $module;
  $preset = $module['detailpricepresets'][$module['detailpricepresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    if($preset['columns'][$i]['name'] == $name){
      $preset['columns'][$i]['width'] = $width;
    }
  }

  $module['detailpricepresets'][$module['detailpricepresetidx']] = $preset;
  m_savestate($module);

}

function ui_customerdetail_pricedetail_columnapply($columns){

  $columns = array_index($columns, array('name'), 1);
  global $module;
  $preset = $module['detailpricepresets'][$module['detailpricepresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    $name = $preset['columns'][$i]['name'];
    if(isset($columns[$name]))
      $preset['columns'][$i]['active'] = $columns[$name]['active'];
  }

  $module['detailpricepresets'][$module['detailpricepresetidx']] = $preset;
  m_savestate($module);

  return ui_customerdetail_pricedetail($module['customerid']);

}

function ui_customerdetail_pricedetail_sortapply($name){

  global $module;
  $preset = $module['detailpricepresets'][$module['detailpricepresetidx']];

  // If sort applied before is equal with this one, invert the sorttype
  if(isset($preset['sorts']) && count($preset['sorts']) == 1 && $preset['sorts'][0]['name'] == $name){
    $preset['sorts'][0]['sorttype'] = $preset['sorts'][0]['sorttype'] == 'desc' ? 'asc' : 'desc';
  }
  else{
    $preset['sorts'] = array();
    $preset['sorts'][] = array(
        'name'=>$name,
        'sorttype'=>'asc'
    );
  }

  $module['detailpricepresets'][$module['detailpricepresetidx']] = $preset;
  m_savestate($module);

  return ui_customerdetail_pricedetail($module['customerid']);

}

function ui_customerdetail_pricedetail_quickfilter($param0){

  $hint = $param0['hint'];
  global $module;
  $detailpricepreset = $module['detailpricepresets'][$module['detailpricepresetidx']];
  $columns = $detailpricepreset['columns'];
  for($i = 0 ; $i < count($columns) ; $i++){
    $column = $columns[$i];

    if(empty($column['name']) || empty($column['text'])) continue;

    $columntext = $column['text'];
    $items[] = array('text'=>"$columntext : $hint", 'value'=>json_encode(array('name'=>$column['name'], 'value'=>$hint)));
  }
  return $items;

}

function ui_customerdetail_pricedetail_quickfilterapply($exp){

  $quickfilters = array();
  $explodes = explode('|', $exp);
  for($i = 0 ; $i < count($explodes) ; $i++){
    $explode = $explodes[$i];
    $obj = objectToArray(json_decode($explode));
    if(is_array($obj)) $quickfilters[] = $obj;
  }

  global $module;
  $detailpricepreset = $module['detailpricepresets'][$module['detailpricepresetidx']];
  $detailpricepreset['detailpricequickfilters'] = $quickfilters;

  $module['detailpricepresets'][$module['detailpricepresetidx']] = $detailpricepreset;
  m_savestate($module);

  return ui_customerdetail_pricedetail($module['customerid']);

}

function ui_customerdetail_salesdetail($id){

  global $module;
  $salesinvoices = salesinvoicelistbyinventory(null, array('customerid'=>$id));
  $detailsalespreset = $module['detailsalespresets'][$module['detailsalespresetidx']];
  $columns = $detailsalespreset['columns'];

  // Quick filter value
  $quickfiltervalue = array();
  $quickfilters = array();
  $columns_indexed = array_index($columns, array('name'), 1);
  if(isset($detailsalespreset['detailsalesquickfilters']) && is_array($detailsalespreset['detailsalesquickfilters']))
    for($i = 0 ; $i < count($detailsalespreset['detailsalesquickfilters']) ; $i++){
      $filter = $detailsalespreset['detailsalesquickfilters'][$i];
      $filtername = $filter['name'];
      $filtervalue = $filter['value'];

      $quickfilters[] = array('name'=>$filtername, 'operator'=>'contains', 'value'=>$filtervalue, 'type'=>'text');
      $quickfiltervalue[] = array('text'=>$columns_indexed[$filtername]['text'] . ' : ' . $filtervalue, 'value'=>json_encode(array('name'=>$filtername, 'value'=>$filtervalue)));
    }

  if(isset($detailsalespreset['sorts']) && is_array($detailsalespreset['sorts']))
    data_sort($salesinvoices, $detailsalespreset['sorts']);

  // Data filter
  data_filter($salesinvoices, $quickfilters);

  $c = "<element exp='#customerdetailstatic3'>";
  $c .= "<table cellspacing='4' style='width: 100%'><tr>";
  $c .= "<td style='width:100%'>" . ui_multicomplete(array('width'=>'100%', 'name'=>'search', 'src'=>'ui_customerdetail_salesdetail_quickfilter', 'placeholder'=>'Quick filter...',
          'value'=>$quickfiltervalue, 'separator'=>'|', 'onchange'=>"ui.async('ui_customerdetail_salesdetail_quickfilterapply', [ ui.multicomplete_value(this) ], {})")) . "</td>";
  $c .= "</tr></table>";
  $c .= ui_gridhead(array('columns'=>$detailsalespreset['columns'], 'gridexp'=>'#salesdetailgrid',
      'oncolumnresize'=>"ui.async('ui_customerdetail_salesdetail_columnresize', [ name, width ], {})",
      'oncolumnclick'=>"ui.async('ui_customerdetail_salesdetail_sortapply', [ name ], {})",
      'oncolumnapply'=>'ui_customerdetail_salesdetail_columnapply'));
  $c .= "</element>";
  $c .= "<element exp='#customerdetailscrollable3'>";
  $c .= ui_grid(array('id'=>'salesdetailgrid', 'columns'=>$detailsalespreset['columns'], 'value'=>$salesinvoices, 'scrollel'=>'#customerdetailscrollable3'));
  $c .= "</element>";

  echo $c;

}

function ui_customerdetail_salesdetail_columnresize($name, $width){

  global $module;
  $preset = $module['detailsalespresets'][$module['detailsalespresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    if($preset['columns'][$i]['name'] == $name){
      $preset['columns'][$i]['width'] = $width;
    }
  }

  $module['detailsalespresets'][$module['detailsalespresetidx']] = $preset;
  m_savestate($module);

}

function ui_customerdetail_salesdetail_columnapply($columns){

  $columns = array_index($columns, array('name'), 1);
  global $module;
  $preset = $module['detailsalespresets'][$module['detailsalespresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    $name = $preset['columns'][$i]['name'];
    if(isset($columns[$name]))
      $preset['columns'][$i]['active'] = $columns[$name]['active'];
  }

  $module['detailsalespresets'][$module['detailsalespresetidx']] = $preset;
  m_savestate($module);

  return ui_customerdetail_salesdetail($module['customerid']);

}

function ui_customerdetail_salesdetail_sortapply($name){

  global $module;
  $preset = $module['detailsalespresets'][$module['detailsalespresetidx']];

  // If sort applied before is equal with this one, invert the sorttype
  if(isset($preset['sorts']) && count($preset['sorts']) == 1 && $preset['sorts'][0]['name'] == $name){
    $preset['sorts'][0]['sorttype'] = $preset['sorts'][0]['sorttype'] == 'desc' ? 'asc' : 'desc';
  }
  else{
    $preset['sorts'] = array();
    $preset['sorts'][] = array(
        'name'=>$name,
        'sorttype'=>'asc'
    );
  }

  $module['detailsalespresets'][$module['detailsalespresetidx']] = $preset;
  m_savestate($module);

  return ui_customerdetail_salesdetail($module['customerid']);

}

function ui_customerdetail_salesdetail_quickfilter($param0){

  $hint = $param0['hint'];
  global $module;
  $detailpricepreset = $module['detailsalespresets'][$module['detailsalespresetidx']];
  $columns = $detailpricepreset['columns'];
  for($i = 0 ; $i < count($columns) ; $i++){
    $column = $columns[$i];

    if(empty($column['name']) || empty($column['text'])) continue;

    $columntext = $column['text'];
    $items[] = array('text'=>"$columntext : $hint", 'value'=>json_encode(array('name'=>$column['name'], 'value'=>$hint)));
  }
  return $items;

}

function ui_customerdetail_salesdetail_quickfilterapply($exp){

  $quickfilters = array();
  $explodes = explode('|', $exp);
  for($i = 0 ; $i < count($explodes) ; $i++){
    $explode = $explodes[$i];
    $obj = objectToArray(json_decode($explode));
    if(is_array($obj)) $quickfilters[] = $obj;
  }

  global $module;
  $detailsalespreset = $module['detailsalespresets'][$module['detailsalespresetidx']];
  $detailsalespreset['detailsalesquickfilters'] = $quickfilters;

  $module['detailsalespresets'][$module['detailsalespresetidx']] = $detailsalespreset;
  m_savestate($module);

  return ui_customerdetail_salesdetail($module['customerid']);

}

function ui_defaultsalesmannames(){

  $users = userlist(
      array(
          array('name'=>'name')
      ),
      null,
      array(
          array('name'=>'salesable', 'operator'=>'equals', 'value'=>1)
      )
  );
  $items = array_cast($users, array('text'=>'name', 'value'=>'id'));
  return $items;

}

function ui_customerremove($id){

  customerremove(array('id'=>$id));

  return m_load();

}

function ui_customersave($obj){

  if(!empty($obj['id']))
    customermodify($obj);
  else
    customerentry($obj);

  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_customermove($id){

  customermove($id);
  return
      uijs("
        ui('#grid1').querySelector(\"tr[data-id='$id']\").querySelector('.customermove').innerHTML = \"<span class='fa fa-check color-green'></span>\";
      ") .
      ui_dialog('Info', 'Pelanggan berhasil dipindah.');

}

function ui_customer_isactive_toggle($customer){

  $customer = customerdetail([ 'id', 'isactive' ], [ 'id'=>$customer['id'] ]);
  $customer['isactive'] = $customer['isactive'] ? 0 : 1;
  $customer = customermodify($customer);
  $customer = customerdetail([ 'id', 'isactive' ], [ 'id'=>$customer['id'] ]);
  return "<script>if($(\"input[data-id = '$customer[id]'\").length == 1) $(\"input[data-id='$customer[id]'\")[0].checked = " . ($customer['isactive'] ? "true" : "false") . ";</script>";

}

function ui_customerexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $customers_columnaliases = array(
    'code'=>'t1.code',
    'description'=>'t1.description',
    'city'=>'t1.city',
    'country'=>'t1.country',
    'discount'=>'t1.discount',
    'taxable'=>'t1.taxable',
    'creditlimit'=>'t1.creditlimit',
    'creditterm'=>'t1.creditterm',
    'receivable'=>'t1.receivable',
    'avgsalesmargin'=>'t1.avgsalesmargin',
    'phone1'=>'t1.phone1',
    'phone2'=>'t1.phone2',
    'fax1'=>'t1.fax1',
    'fax2'=>'t1.fax2',
    'contactperson'=>'t1.contactperson',
    'email'=>'t1.email',
    'createdon'=>'t1.createdon',
    'moved'=>'t1.moved',
    'defaultsalesmanname'=>'t2.name'
  );

  // Generating sql queries
  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $customers_columnaliases);
  $wherequery = " WHERE t1.defaultsalesmanid = t2.id" . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $customers_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $customers_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery; // Add comma if columnquery exists
  $query = "SELECT 'customer' as `type`, t1.id, t1.defaultsalesmanid $columnquery FROM customer t1, user t2 $wherequery $sortquery $limitquery";

  // Fetch data
  $items = pmrs($query, $params);

  // Generate header
  $item = $items[0];
  $headers = array();
  foreach($item as $key=>$value)
    $headers[$key] = $key;

  $temp = array();
  $temp[] = $headers;
  foreach($items as $item)
    $temp[] = $item;
  $items = $temp;

  $filepath = 'usr/customer-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>