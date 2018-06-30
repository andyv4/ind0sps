<?php

require_once 'api/chartofaccount.php';
require_once 'api/staff.php';

function ui_staffdetail($id, $mode = 'read', $index = 0){

  $readonly  = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $staff = pmr("SELECT * FROM user WHERE `id` = ?", array($id));

  if($mode != 'read' && $staff && !privilege_get('staff', 'modify')) $mode = 'read';
  $accesslevels = array(
    array('value'=>'USER', 'text'=>'User'),
    array('value'=>'ADMIN', 'text'=>'Admin')
  );

  $index = $index < 0 || $index > 3 ? 0 : $index;

  $defaultprivileges = array(
    array('text'=>'Akun', 'module'=>'chartofaccount', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Laba Rugi', 'module'=>'incomestatement', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Mata Uang', 'module'=>'currency', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Pelanggan', 'module'=>'customer', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Gudang', 'module'=>'warehouse', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Barang', 'module'=>'inventory', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Barang Dipesan', 'module'=>'inventoryanalysis', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Suplier', 'module'=>'supplier', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Staff', 'module'=>'staff', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Kategori', 'module'=>'category', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Grafik Penjualan', 'module'=>'salesoverview', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Faktur Penjualan', 'module'=>'salesinvoice', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Surat Jalan Sampel', 'module'=>'sampleinvoice', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Grup Faktur', 'module'=>'salesinvoicegroup', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Kwitansi', 'module'=>'salesreceipt', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Rekonsil', 'module'=>'salesreconcile', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Retur Penjualan', 'module'=>'salesreturn', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Pesanan Pembelian', 'module'=>'purchaseorder', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Faktur Pembelian', 'module'=>'purchaseinvoice', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Kas Kecil', 'module'=>'pettycash', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Jurnal', 'module'=>'journalvoucher', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Pindah Gudang', 'module'=>'warehousetransfer', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Penyesuaian Barang', 'module'=>'inventoryadjustment', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Daftar Kode Faktur Pajak', 'module'=>'taxreservedcode', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
    array('text'=>'Tools', 'module'=>'tools', 'new'=>0, 'modify'=>0, 'delete'=>0, 'list'=>0, 'print'=>0),
  );

  $privileges = pmrs("SELECT * FROM userprivilege WHERE userid = ?", array($id));
  $privileges = array_index($privileges, array('module', 'key'), 1);
  $privilege_inventorycostprice = pmc("SELECT `value` FROM userprivilege WHERE userid = ? AND `key` = 'costprice' AND `module` = 'inventory'", array($id));

  for($i = 0 ; $i < count($defaultprivileges) ; $i++){
    $defaultprivilege = $defaultprivileges[$i];
    $module = $defaultprivilege['module'];

    $defaultprivileges[$i]['new'] = isset($privileges[$module]) && $privileges[$module]['new']['value'] > 0 ? 1 : 0;
    $defaultprivileges[$i]['modify'] = isset($privileges[$module]) && $privileges[$module]['modify']['value'] > 0 ? 1 : 0;
    $defaultprivileges[$i]['delete'] = isset($privileges[$module]) && $privileges[$module]['delete']['value'] > 0 ? 1 : 0;
    $defaultprivileges[$i]['list'] = isset($privileges[$module]) && $privileges[$module]['list']['value'] > 0 ? 1 : 0;
    $defaultprivileges[$i]['print'] = isset($privileges[$module]) && $privileges[$module]['print']['value'] > 0 ? 1 : 0;
    $defaultprivileges[$i]['download'] = isset($privileges[$module]) && $privileges[$module]['download']['value'] > 0 ? 1 : 0;
    $defaultprivileges[$i]['userid'] = $id;
  }

  $columns = array(
      array('active'=>1, 'name'=>'text', 'text'=>'Modul', 'width'=>140),
      array('active'=>1, 'name'=>'new', 'text'=>'Buat', 'width'=>50, 'type'=>'html', 'html'=>'ui_staffdetail0', 'align'=>'center', 'nodittomark'=>1),
      array('active'=>1, 'name'=>'modify', 'text'=>'Ubah', 'width'=>50, 'type'=>'html', 'html'=>'ui_staffdetail1', 'align'=>'center', 'nodittomark'=>1),
      array('active'=>1, 'name'=>'delete', 'text'=>'Hapus', 'width'=>50, 'type'=>'html', 'html'=>'ui_staffdetail2', 'align'=>'center', 'nodittomark'=>1),
      array('active'=>1, 'name'=>'list', 'text'=>'Daftar', 'width'=>50, 'type'=>'html', 'html'=>'ui_staffdetail3', 'align'=>'center', 'nodittomark'=>1),
      array('active'=>1, 'name'=>'print', 'text'=>'Cetak', 'width'=>50, 'type'=>'html', 'html'=>'ui_staffdetail4', 'align'=>'center', 'nodittomark'=>1),
      array('active'=>1, 'name'=>'download', 'text'=>'Download', 'width'=>50, 'type'=>'html', 'html'=>'ui_staffdetail5', 'align'=>'center', 'nodittomark'=>1),
  );

  $chartofaccounttypecolumns = [
    array('active'=>1, 'name'=>'new', 'text'=>'', 'width'=>50, 'type'=>'html', 'html'=>'ui_staffdetail_b0', 'align'=>'center', 'nodittomark'=>1),
    [ 'active'=>1, 'name'=>'name', 'text'=>'Nama Akun', 'width'=>250,  ],
  ];
  $chartofaccounts = chartofaccountlist();
  $privilege_chartofaccounts = userkeystoreget($id, 'privilege.chartofaccounttype');
  $privilege_chartofaccounts = explode(',', $privilege_chartofaccounts);
  $granted_all = count($privilege_chartofaccounts) == 1 && $privilege_chartofaccounts[0] == '*' ? true : false;
  if(is_array($chartofaccounts)){
    for($i = 0 ; $i < count($chartofaccounts) ; $i++){

      $chartofaccount = $chartofaccounts[$i];
      $granted = $granted_all || in_array($chartofaccount['id'], $privilege_chartofaccounts) ? 1: 0;
      $chartofaccounts[$i]['granted'] = $granted;

    }
  }

  $privilege_salesinvoicetype = userkeystoreget($id, 'privilege.salesinvoicetype');
  $salesinvoice_modifytaxcode = userkeystoreget($id, 'privilege.salesinvoice_modifytaxcode');
  $privilege_purchaseinvoicetype = userkeystoreget($id, 'privilege.purchaseinvoicetype');
  $salesinvoicetypes = [
    [ 'text'=>'Semua', 'value'=>'*' ],
    [ 'text'=>'Hanya Non Pajak', 'value'=>'non-tax' ],
    [ 'text'=>'Hanya Pajak', 'value'=>'tax' ],
  ];
  $multilogin = $staff['multilogin'];
  $notification_enabled = userkeystoreget($id, 'privilege.notification_enabled', true);

  $privilege_sales_allowed_salesman = ui_staff_allowed_salesman_components(userkeystoreget($id, 'privilege.sales_allowed_salesman'));

  $depts = [
    [ 'text'=>'Management', 'value'=>'management' ],
    [ 'text'=>'Accounting', 'value'=>'accounting' ],
    [ 'text'=>'Sales', 'value'=>'sales' ],
    [ 'text'=>'Admin', 'value'=>'admin' ],
    [ 'text'=>'-', 'value'=>'' ]
  ];

  $positions = [
    [ 'text'=>'Manager', 'value'=>'manager' ],
    [ 'text'=>'Supervisor', 'value'=>'supervisor' ],
    [ 'text'=>'Staff', 'value'=>'' ]
  ];

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $staff)),
    'name'=>array('type'=>'textbox', 'name'=>'name', 'value'=>ov('name', $staff), 'width'=>200, 'readonly'=>$readonly),
    'userid'=>array('type'=>'textbox', 'name'=>'userid', 'value'=>ov('userid', $staff), 'width'=>150, 'readonly'=>$readonly),
    'dept'=>array('type'=>'dropdown', 'name'=>'dept', 'value'=>ov('dept', $staff), 'width'=>150, 'readonly'=>$readonly, 'items'=>$depts),
    'position'=>array('type'=>'dropdown', 'name'=>'position', 'value'=>ov('position', $staff), 'width'=>150, 'readonly'=>$readonly, 'items'=>$positions),
    'password'=>array('type'=>'textbox', 'name'=>'password', 'mode'=>'password', 'value'=>'', 'width'=>200, 'readonly'=>$readonly),
    'password_confirm'=>array('type'=>'textbox', 'name'=>'password_confirm', 'mode'=>'password', 'value'=>'', 'width'=>200, 'readonly'=>$readonly),
    'accesslevel'=>array('type'=>'dropdown', 'name'=>'accesslevel', 'readonly'=>$readonly, 'items'=>$accesslevels, 'value'=>ov('accesslevel', $staff)),
    'privilege_inventorycostprice'=>array('type'=>'checkbox', 'name'=>'privilege_inventorycostprice', 'value'=>$privilege_inventorycostprice, 'readonly'=>$readonly),
    'salesinvoice_modifytaxcode'=>[ 'type'=>'checkbox', 'name'=>'salesinvoice_modifytaxcode', 'value'=>$salesinvoice_modifytaxcode, 'readonly'=>$readonly ],
    'multilogin'=>[ 'type'=>'checkbox', 'name'=>'multilogin', 'value'=>$multilogin, 'readonly'=>$readonly ],
    'notification_enabled'=>[ 'type'=>'checkbox', 'name'=>'notification_enabled', 'value'=>$notification_enabled, 'readonly'=>$readonly ],
    'privilege_salesinvoicetype'=>[ 'type'=>'dropdown', 'name'=>'salesinvoicetype', 'items'=>$salesinvoicetypes, 'value'=>$privilege_salesinvoicetype, 'readonly'=>$readonly, 'width'=>'150px' ],
    'privilege_sales_allowed_salesman'=>[ 'type'=>'multicomplete', 'id'=>'allowed_salesman', 'name'=>'sales_allowed_salesman', 'src'=>'ui_staffdetail_salesman_completion', 'value'=>$privilege_sales_allowed_salesman, 'readonly'=>$readonly, 'width'=>'400px' ],
    'privilege_purchaseinvoicetype'=>[ 'type'=>'dropdown', 'name'=>'purchaseinvoicetype', 'items'=>$salesinvoicetypes, 'value'=>$privilege_purchaseinvoicetype, 'readonly'=>$readonly, 'width'=>'150px' ],
    'privilege_chartofaccounttype'=>[ 'type'=>'grid', 'name'=>'chartofaccounttype', 'id'=>'chartofaccounttypegrid', 'columns'=>$chartofaccounttypecolumns, 'value'=>$chartofaccounts, 'readonly'=>$readonly ]
  );

  // Action Controls
  if($readonly)
    $actions = array(
        "<td><button class='blue' onclick=\"ui.async('ui_staffdetail', [ $id, 'write', ui.tab_value($('.tabhead')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Ubah</label></button></td>"
    );
  else
    $actions = array(
        "<td><button class='blue' onclick=\"ui.async('ui_staffsave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>Simpan</label></button></td>"
    );

  $coa_actions = "<div class='padding5 align-right'>
                <button class='hollow' onclick='mod_checkall()'><label>Centang Semua</label></button>
                &nbsp;
                <button class='hollow' onclick='mod_uncheckall()'><label>Hapus Semua Centang</label></button>
              </div>";

  $c = "<element exp='.modal'>";
  $c .= "
  <div class='padding10 align-center'>
    <div class='tabhead' data-tabbody='.tabbody'>
      <div class='tabitem" . ($index == 0 ? ' active' : '') . "' onclick='ui.tabclick(event, this);'><label>Detil Staff</label></div>
      <div class='tabitem" . ($index == 1 ? ' active' : '') . "' onclick='ui.tabclick(event, this);'><label>Hak Akses</label></div>
      <div class='tabitem" . ($index == 2 ? ' active' : '') . "' onclick='ui.tabclick(event, this);'><label>Hak Akses Lain</label></div>
      <div class='tabitem" . ($index == 3 ? ' active' : '') . "' onclick='ui.tabclick(event, this);'><label>Waktu Login</label></div>
    </div>
  </div>
  <div class='scrollable padding10' style='height:300px'>
    " . ui_control($controls['id']) . "
    <div class='tabbody'>
      <div class='tab" . ($index == 0 ? '' : ' off') . "'>
        <table class='form'>
          <tr>
            <th style='width:133px'><label>Nama</label></th>
            <td>" . ui_control($controls['name']) . "</td>
          </tr>
          <tr>
            <th><label>Login ID</label></th>
            <td>" . ui_control($controls['userid']) . "</td>
          </tr>
          <tr>
            <th><label>Department</label></th>
            <td>" . ui_control($controls['dept']) . "
              " . (!$readonly ? "<br /><small class='color-gray'>Hanya accounting dapat set active/inactive pelanggan</small>" : '') . "
            </td>
          </tr>
          <tr>
            <th><label>Position</label></th>
            <td>
              " . ui_control($controls['position']) . "              
              " . (!$readonly ? "<br /><small class='color-gray'>- Faktur penjualan terkirim hanya dapat diubah posisi diatas staff</small>" : '') . "
            </td>
          </tr>
          <tr>
            <th><label>Level Akses</label></th>
            <td>" . ui_control($controls['accesslevel']) . "</td>
          </tr>
          <tr>
            <th><label>Multi Login</label></th>
            <td>" . ui_control($controls['multilogin']) . "</td>
          </tr>
          <tr>
            <th><label>Notifikasi</label></th>
            <td>" . ui_control($controls['notification_enabled']) . "</td>
          </tr>
        </table>
        ";
      if(!$readonly){
        $c .= "<div style='height:10px'></div>";
        $c .= "<table class='form'>
          <tr>
            <th style='width:133px'><label>Password</label></th>
            <td>" . ui_control($controls['password']) . "</td>
          </tr>
          <tr>
            <th><label>Confirm Password</label></th>
            <td>" . ui_control($controls['password_confirm']) . "</td>
          </tr>";
        if($staff){
          $c .= "<tr>
            <td></td>
            <td><button class='blue' onclick=\"ui.async('ui_staffpassword', [ ui.container_value(ui('.modal')) ], {})\"><label>Change Password</label></button></td>
          </tr>
          ";
        }
        $c .= "
        </table>
        ";
      }
$c .= "</div>
      <div class='tab" . ($index == 1 ? '' : ' off') . "'>
      " . ui_gridhead(array('columns'=>$columns, 'gridexp'=>'#mutationdetailgrid')) .
        ui_grid(array('id'=>'mutationdetailgrid', 'columns'=>$columns, 'value'=>$defaultprivileges, 'scrollel'=>'.modal .scrollable', 'readonly'=>$readonly)) . "
      </div>
      <div class='tab" . ($index == 2 ? '' : ' off') . "'>
        <table class='form'>
          <tr>
            <th><label>Lihat harga modal barang<label></th>
            <td>
              " . ui_control($controls['privilege_inventorycostprice']) . "
              <small>Digunakan di Daftar Barang dan Detil Harga Modal Barang</small>
            </td>
          </tr>
          <tr>
            <th><label>Faktur Penjualan<label></th>
            <td>
              " . ui_control($controls['privilege_salesinvoicetype']) . "
            </td>
          </tr>
          <tr>
            <th><label>Hanya dapat melihat penjualan sales:<label></th>
            <td>
              " . ui_control($controls['privilege_sales_allowed_salesman']) . "
            </td>
          </tr>
          <tr>
            <th><label>Ubah Kode Faktur Pajak<label></th>
            <td>
              " . ui_control($controls['salesinvoice_modifytaxcode']) . "
            </td>
          </tr>
          <tr>
            <th><label>Faktur Pembelian<label></th>
            <td>
              " . ui_control($controls['privilege_purchaseinvoicetype']) . "
            </td>
          </tr>
          <tr>
            <th><label>Akun<label></th>
            <td>            
              " . (!$readonly ? $coa_actions : '') . "
              " . ui_control($controls['privilege_chartofaccounttype']) . "
            </td>
          </tr>
        </table>
      </div>
      <div class='tab" . ($index == 3 ? '' : ' off') . "'>
        " . ui_timeaccesscontrol(array('readonly'=>$readonly, 'name'=>'loginhour', 'value'=>$staff['loginhour'])) . "
      </div>
    </div>
  </div>
  <div class='foot'>
    <table cellspacing='5'>
      <tr>
        <td style='width: 100%'></td>
        " . implode(', ', $actions) . "
        <td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Tutup</label></button></td>
    </table>
  </div>
  ";
  $c .= "</element>";
  $c .= uijs("
    ui.loadscript('rcfx/js/staff.js', \"ui.modal_open(ui('.modal'), { closeable:$closable, width:800 })\");
  ");

  return $c;

}

function ui_staffdetail_b0($obj, $params){

  $granted = $obj['granted'];
  $id = $obj['id'];
  $readonly = ov('readonly', $params);

  return "<div class='align-center'>" .
    ui_checkbox(array('name'=>'granted', 'value'=>$granted, 'onchange'=>"", 'readonly'=>$readonly, 'ischild'=>1)) .
    ui_hidden(array('name'=>'id', 'value'=>$id, 'onchange'=>"", 'readonly'=>$readonly, 'ischild'=>1)) .
  "</div>";

}

function ui_staffdetail0($obj, $params){

  $userid = $obj['userid'];
  $module = $obj['module'];
  $key = 'new';
  $readonly = ov('readonly', $params);

  return "<div class='align-center'>" .  ui_checkbox(array('name'=>'new', 'value'=>$obj[$key], 'onchange'=>"ui.async('ui_staffprivilegeset', [ $userid, '$module', '$key', value ], {})", 'readonly'=>$readonly)) . "</div>";

}
function ui_staffdetail1($obj, $params){

  $userid = $obj['userid'];
  $module = $obj['module'];
  $key = 'modify';
  $readonly = ov('readonly', $params);

  return "<div class='align-center'>" .  ui_checkbox(array('name'=>'new', 'value'=>$obj[$key], 'onchange'=>"ui.async('ui_staffprivilegeset', [ $userid, '$module', '$key', value ], {})", 'readonly'=>$readonly)) . "</div>";

}
function ui_staffdetail2($obj, $params){

  $userid = $obj['userid'];
  $module = $obj['module'];
  $key = 'delete';
  $readonly = ov('readonly', $params);

  return "<div class='align-center'>" .  ui_checkbox(array('name'=>'new', 'value'=>$obj[$key], 'onchange'=>"ui.async('ui_staffprivilegeset', [ $userid, '$module', '$key', value ], {})", 'readonly'=>$readonly)) . "</div>";

}
function ui_staffdetail3($obj, $params){

  $userid = $obj['userid'];
  $module = $obj['module'];
  $key = 'list';
  $readonly = ov('readonly', $params);

  return "<div class='align-center'>" .  ui_checkbox(array('name'=>'new', 'value'=>$obj[$key], 'onchange'=>"ui.async('ui_staffprivilegeset', [ $userid, '$module', '$key', value ], {})", 'readonly'=>$readonly)) . "</div>";

}
function ui_staffdetail4($obj, $params){

  $userid = $obj['userid'];
  $module = $obj['module'];
  $key = 'print';
  $readonly = ov('readonly', $params);

  return "<div class='align-center'>" .  ui_checkbox(array('name'=>'new', 'value'=>$obj[$key], 'onchange'=>"ui.async('ui_staffprivilegeset', [ $userid, '$module', '$key', value ], {})", 'readonly'=>$readonly)) . "</div>";

}
function ui_staffdetail5($obj, $params){

  $userid = $obj['userid'];
  $module = $obj['module'];
  $key = 'download';
  $readonly = ov('readonly', $params);

  return "<div class='align-center'>" .  ui_checkbox(array('name'=>'new', 'value'=>$obj[$key], 'onchange'=>"ui.async('ui_staffprivilegeset', [ $userid, '$module', '$key', value ], {})", 'readonly'=>$readonly)) . "</div>";

}

function ui_stafflogoff($id){

  pm("UPDATE session SET isopen = 0 WHERE userid = ? AND isopen = 1", array($id));
  return m_load();

}

function ui_staffpassword($obj){

  $id = $obj['id'];
  $password = $obj['password'];
  $confirm = $obj['password_confirm'];

  if(empty($password)) throw new Exception('Password tidak boleh kosong.');
  if($password != $confirm) throw new Exception('Password tidak sama.');

  pm("UPDATE user SET password = ? WHERE `id` = ?", array(md5($password), $id));

  return uijs("alert('Password berhasil diubah.')");

}

function ui_staffsave($obj){

  if(isset($obj['id']) && $obj['id'] > 0) staffmodify($obj);
  else staffentry($obj);

  return uijs("ui.modal_close(ui('.modal'))") . m_load();

}

function ui_staffprivilegeset($userid, $module, $key, $value){

  $exists = pmc("select count(*) from userprivilege where userid = ? and module = ? and `key` = ?", array($userid, $module, $key));
  if($exists){
    pm("update userprivilege set value = ? where userid = ? and module = ? and `key` = ?", array($value, $userid, $module, $key));
  }
  else{
    pm("insert into userprivilege (userid, module, `key`, value) values (?, ?, ?, ?)", array($userid, $module, $key, $value));
  }

}

function ui_staff_allowed_salesman_components($value){

  $items = [];
  $value = explode(',', $value);
  foreach($value as $text){
    if(empty($text)) continue;
    switch($text){
      case '_self': $items[] = [ 'text'=>'Staff Ini', 'value'=>'_self' ]; break;
      case '*': $items[] = [ 'text'=>'Semua Salesman', 'value'=>'*' ]; break;
      default: $items[] = [ 'text'=>$text, 'value'=>$text ]; break;
    }
  }
  if(count($items) == 0) $items[] = [ 'text'=>'Staff Ini', 'value'=>'_self' ];
  return $items;

}
function ui_staffdetail_salesman_completion($param){

  $hint = ov('hint', $param);

  $items = [];
  $items[] = [ 'text'=>'Staff Ini', 'value'=>'_self' ];
  $items[] = [ 'text'=>'Semua Salesman', 'value'=>'*' ];

  $items[] = array('text'=>$hint, 'value'=>$hint);
  return $items;

}


?>