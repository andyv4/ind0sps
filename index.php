<?php

function onshutdown(){

  $content = ob_get_contents();
  if(!empty($content)) ob_end_clean();
  $err = error_get_last();
  if($err && $err['type'] == 1){

    $message = $err["message"];
    $file = $err["file"];
    $line = $err["line"];
    $traces = debug_backtrace(true);

    if(isset($_GET['_async'])){

      if(strpos($message, 'Allowed memory size') !== false) $message = 'Issue teknikal (NOT_ENOUGH_RAM), silakan dicoba kembali beberapa saat atau hubungi developer.';

      $isdebug = isset($_SESSION['debugmode']) && $_SESSION['debugmode'] > 0 ? true : false;
      echo ui_dialog("Error", $message . ($isdebug ? "\n\n" . json_encode($traces) : ''));
    }
    else{
      header('HTTP/1.1 500 Internal Server Error');
      echo $message . "\n" . $file . ":" . $line . "\n\n" . json_encode($traces);
    }

    file_put_contents(__DIR__ . '/usr/system/error.log', $message . PHP_EOL . $file . ":" . $line . PHP_EOL . PHP_EOL . json_encode($traces) . PHP_EOL, FILE_APPEND); // Log error

  }
  else{
    global $title;
    $content = str_replace('%%TITLE%%', 'INDOSPS :: ' . ucwords($title), $content);
    echo $content;
  }

  global $_REQUIRE_WORKER;
  if($_REQUIRE_WORKER) system_worker_run();

  pdo_close();

}
function session_isopen(){

  $uid = isset($_COOKIE['indosps_uid']) ? $_COOKIE['indosps_uid'] : null;
  if(!$uid) return false;

  // Retrieve open session
  $session = pmr("SELECT * FROM `session` WHERE uid = ? AND isopen = 1", array($uid));

  // Session not available
  if(!$session) return false;

  // Maximum idle time set to 2 hours (7200s)
  if(time() - strtotime($session['lastupdatedon']) > 7200) return false;

  if(!isset($_SESSION['user'])){

    global $mysqlpdo_database;
    $userid = $session['userid'];
    $user = pmr("select `id`, userid, multilogin from user where `id` = ?", [ $userid ]);
    $tax_mode = false;
    $_SESSION['lang'] = 'id';
    $_SESSION['dbschema'] = $mysqlpdo_database;
    $_SESSION['user'] = $user;
    $_SESSION['tax_mode'] = $tax_mode ? 1 : 0;

  }

  return true;

}
function sidebar_state($state = -1){

  if($state != -1) userkeystoreadd($_SESSION['user']['id'], 'sidebarstate', $state);
  return userkeystoreget($_SESSION['user']['id'], 'sidebarstate');

}
function ui_ack(){

  global $cachedir;
  $cachedir = 'usr/' . md5($_SESSION['user']['id'] . $_SESSION['dbschema']);

}
function ui_notification(){

  require_once 'api/notification.php';
  $notifications = notification_list();
  $count = count($notifications);
  if($count > 99) $count = '99+';
  $c = "<element exp='#notification_label'>";
  if($count > 0){
    $c .= "<span class=\"indicator\" style=\"position:absolute;right:0;top:-5px;font - size:.8em;width:10px;height:10px;padding:5px 6px 7px 6px\">$count</span>";
    $c .= "</element>";
    $c .= "<element exp='#notification_cont'>";
    $c .= "<span class='notification-panel'>";
    $c .= "<span class='notification-panel-inner'>";
    foreach($notifications as $notification){

      $title = $notification['title'];
      switch($title){
        case 'Pelanggan dengan hutang jatuh tempo.':
          $url = 'customer?preset=1';
          break;
        case 'Faktur cash belum lunas':
          $url = 'salesinvoice?preset=1';
          break;
      }

      $c .= "<span class='notification-item' onclick=\"window.location = '$url'\">";
      $c .= "<b>$notification[title]</b><br />";
      $c .= "<label>$notification[description]</label>";
      $c .= "</span>";

    }
    $c .= "</span>";
  }
  $c .= "</element>";
  return $c;

}
function is_debugmode(){
  return isset($_SESSION['debugmode']) && $_SESSION['debugmode'] ? true : false;
}
function require_worker(){

  global $_REQUIRE_WORKER;
  $_REQUIRE_WORKER = true;

}
function acquire_lock($key){

  $lock_file = __DIR__ . "/usr/system/{$key}.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Unable to acquire LOCK_EX');
  return $fp;

}
function release_lock($fp, $key){

  $lock_file = __DIR__ . "/usr/system/{$key}.lock";
  fclose($fp);
  unlink($lock_file);

}

require_once 'rcfx/php/component.php';
require_once 'rcfx/php/log.php';
require_once 'rcfx/php/pdo.php';
require_once 'rcfx/php/util.php';
require_once 'api/privilege.php';
require_once 'api/intl.php';
require_once 'api/job.php';
require_once 'api/system.php';
require_once 'api/config.php';
require_once 'api/user.php';
require_once 'api/staff.php';

date_default_timezone_set("Asia/Jakarta");
register_shutdown_function("onshutdown");
ob_start();
error_reporting(E_ERROR | E_WARNING | E_PARSE);
set_time_limit(30);
ini_set('memory_limit', '512M');
if(session_id() == '') session_start();

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

$url = explode('.', basename($_GET['url']))[0];
$tax_mode = isset($_SESSION['tax_mode']) && $_SESSION['tax_mode'] > 0 ? true : false;
$tax_mode_modules = [
  'chartofaccount',
  'salesinvoice',
  'purchaseinvoice',
];

$requestid = uniqid();
$requeststarttimestamp = microtime(1);
$nocache = '3.9.2.2';
$cachedir = 'usr/' . md5($_SESSION['user']['id'] . $_SESSION['dbschema']);
if(!file_exists($cachedir)) mkdir($cachedir);

if(!session_isopen() || !isset($_SESSION['user'])){
  include 'login.php';
  exit();
}
else if($url == 'logout'){
  $uid = $_COOKIE['indosps_uid'];
  pm("UPDATE session SET isopen = 0 WHERE uid = ?", array($uid));
  setcookie('indosps_uid', $uid, time() - 3600);
  unset($_SESSION['user']);
  unset($_SESSION['dbschema']);
  unset($_SESSION['lang']);
  session_destroy();
  header('Location: .');
}

$_SESSION['user'] = staffdetail([ 'id', 'userid', 'name', 'accesslevel', 'multilogin', 'dept', 'position' ], [ 'id'=>$_SESSION['user']['id'] ]);

if(isset($_GET['debugmode'])){
  if($_GET['debugmode'] > 0) $_SESSION['debugmode'] = 1;
  else unset($_SESSION['debugmode']);
}

if(isset($_GET['db'])){
  if(!($_GET['db'])) unset($_SESSION['db']);
  else $_SESSION['db'] = $_GET['db'];
}

$notification_enabled = userkeystoreget($_SESSION['user']['id'], 'privilege.notification_enabled', true);

$dataavailables = array();
if(privilege_get('company', 'list') && (!$tax_mode || ($tax_mode && in_array('company', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Perusahaan', 'value'=>'company');
if(privilege_get('currency', 'list') && (!$tax_mode || ($tax_mode && in_array('currency', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Mata Uang', 'value'=>'currency');
if(privilege_get('chartofaccount', 'list') && (!$tax_mode || ($tax_mode && in_array('chartofaccount', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Akun', 'value'=>'chartofaccount');
if(privilege_get('incomestatement', 'list') && (!$tax_mode || ($tax_mode && in_array('incomestatement', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Laba Rugi', 'value'=>'incomestatement');
if(privilege_get('customer', 'list') && (!$tax_mode || ($tax_mode && in_array('customer', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Pelanggan', 'value'=>'customer');
if(privilege_get('warehouse', 'list') && (!$tax_mode || ($tax_mode && in_array('warehouse', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Gudang', 'value'=>'warehouse');
if(privilege_get('inventory', 'list') && (!$tax_mode || ($tax_mode && in_array('inventory', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Barang', 'value'=>'inventory');
if(privilege_get('inventoryanalysis', 'list') && (!$tax_mode || ($tax_mode && in_array('inventoryanalysis', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Barang Dipesan', 'value'=>'inventoryanalysis', 'indent'=>1);
if(privilege_get('inventoryanalysis', 'list') && (!$tax_mode || ($tax_mode && in_array('inventoryanalysis', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Formula Barang', 'value'=>'inventoryformula', 'indent'=>1);
if(privilege_get('inventoryanalysis', 'list') && (!$tax_mode || ($tax_mode && in_array('inventoryanalysis', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Kartu Stok', 'value'=>'inventorycard', 'indent'=>1);
if(privilege_get('category', 'list') && (!$tax_mode || ($tax_mode && in_array('category', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Kategori', 'value'=>'category');
if(privilege_get('supplier', 'list') && (!$tax_mode || ($tax_mode && in_array('supplier', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Supplier', 'value'=>'supplier');
if(privilege_get('staff', 'list') && (!$tax_mode || ($tax_mode && in_array('staff', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Staff', 'value'=>'staff');
if(privilege_get('staff', 'list') && (!$tax_mode || ($tax_mode && in_array('staff', $tax_mode_modules)))) $dataavailables[] = array('text'=>'Log', 'value'=>'log');

$salesavailables = array();
if(privilege_get('salesinvoice', 'list') && (!$tax_mode || ($tax_mode && in_array('salesinvoice', $tax_mode_modules)))) $salesavailables[] = array('text'=>'Faktur Penjualan', 'value'=>'salesinvoice');
if(privilege_get('sampleinvoice', 'list') && (!$tax_mode || ($tax_mode && in_array('sampleinvoice', $tax_mode_modules)))) $salesavailables[] = array('text'=>'Surat Jalan Sampel', 'value'=>'sampleinvoice');
if(privilege_get('salesinvoicegroup', 'list') && (!$tax_mode || ($tax_mode && in_array('salesinvoicegroup', $tax_mode_modules)))) $salesavailables[] = array('text'=>'Grup Faktur', 'value'=>'salesinvoicegroup');
if(privilege_get('salesreceipt', 'list') && (!$tax_mode || ($tax_mode && in_array('salesreceipt', $tax_mode_modules)))) $salesavailables[] = array('text'=>'Kwitansi', 'value'=>'salesreceipt');
if(privilege_get('salesreturn', 'list') && (!$tax_mode || ($tax_mode && in_array('salesreturn', $tax_mode_modules)))) $salesavailables[] = array('text'=>'Retur Penjualan', 'value'=>'salesreturn');
if(privilege_get('salesreconcile', 'list') && (!$tax_mode || ($tax_mode && in_array('salesreconcile', $tax_mode_modules)))) $salesavailables[] = array('text'=>'Rekonsiliasi', 'value'=>'salesreconcile');

$purchaseavailables = array();
if(privilege_get('purchaseorder', 'list') && (!$tax_mode || ($tax_mode && in_array('purchaseorder', $tax_mode_modules)))) $purchaseavailables[] = array('text'=>'Pesanan Pembelian', 'value'=>'purchaseorder');
if(privilege_get('purchaseinvoice', 'list') && (!$tax_mode || ($tax_mode && in_array('purchaseinvoice', $tax_mode_modules)))) $purchaseavailables[] = array('text'=>'Faktur Pembelian', 'value'=>'purchaseinvoice');

$otheravailables = array();
if(privilege_get('pettycash', 'list') && (!$tax_mode || ($tax_mode && in_array('pettycash', $tax_mode_modules)))) $otheravailables[] = array('text'=>'Kas Kecil', 'value'=>'pettycash');
if(privilege_get('journalvoucher', 'list') && (!$tax_mode || ($tax_mode && in_array('journalvoucher', $tax_mode_modules)))) $otheravailables[] = array('text'=>'Jurnal', 'value'=>'journalvoucher');
if(privilege_get('warehousetransfer', 'list') && (!$tax_mode || ($tax_mode && in_array('warehousetransfer', $tax_mode_modules)))) $otheravailables[] = array('text'=>'Pindah Gudang', 'value'=>'warehousetransfer');
if(privilege_get('inventoryadjustment', 'list') && (!$tax_mode || ($tax_mode && in_array('inventoryadjustment', $tax_mode_modules)))) $otheravailables[] = array('text'=>'Penyesuaian Barang', 'value'=>'inventoryadjustment');
if(privilege_get('taxreservedcode', 'list') && (!$tax_mode || ($tax_mode && in_array('taxreservedcode', $tax_mode_modules)))) $otheravailables[] = array('text'=>'Daftar Kode Faktur Pajak', 'value'=>'tax-reserved-code');
if(privilege_get('tools', 'list') && (!$tax_mode || ($tax_mode && in_array('tools', $tax_mode_modules)))) $otheravailables[] = array('text'=>'Tools', 'value'=>'tools');

$auditavailables = array();
if(privilege_get('salesinvoice', 'list')) $auditavailables[] = array('text'=>'Faktur Penjualan', 'value'=>'audit-salesinvoice');

if(empty($url)){
  $url = userkeystoreget($_SESSION['user']['id'], 'lasturl');
  if(!file_exists($url . '.php')) $url = 'salesinvoice';
}
if(in_array($url, array('logout'))) userkeystoreadd($_SESSION['user']['id'], 'lasturl', $url);

// Update session
pm("UPDATE `session` SET lasturl = ?, lastupdatedon = ? WHERE uid = ?", array($url, date('YmdHis'), $_COOKIE['indosps_uid']));

// Update user last url
userkeystoreadd($_SESSION['user']['id'], 'lasturl', $url);

?>
<html>
<head>
  <title>%%TITLE%%</title>
  <meta name="google-site-verification" content="X-p65v8PDlNN3DZG6IEhmnT44gZGcY0qeyqQ0zBReJs" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="rcfx/css/opensans.css?nocache=<?=$nocache?>" />
  <link rel="stylesheet" href="rcfx/css/materialdesignicons.css?nocache=<?=$nocache?>" />
  <link rel="stylesheet" href="rcfx/css/fontawesome.css?nocache=<?=$nocache?>" />
  <link rel="stylesheet" href="rcfx/css/animation.css?nocache=<?=$nocache?>" />
  <link rel="stylesheet" href="rcfx/css/component.css?nocache=<?=$nocache?>" />
  <link rel="stylesheet" href="rcfx/css/table.css?nocache=<?=$nocache?>" />
  <link rel="stylesheet" href="rcfx/css/spinner.css?nocache=<?=$nocache?>" />
  <link rel="stylesheet" href="rcfx/css/reconv.css?nocache=<?=$nocache?>" />
  <script type="text/javascript" src="rcfx/js/jquery-3.2.1.min.js"></script>
  <script type="text/javascript" src="rcfx/js/php.js?nocache=<?=$nocache?>"></script>
  <script type="text/javascript" src="rcfx/js/mattkrusedate.js?nocache=<?=$nocache?>"></script>
  <script type="text/javascript" src="rcfx/js/component.js?nocache=<?=$nocache?>"></script>
  <script type="text/javascript" src="rcfx/js/gridoption.js?nocache=<?=$nocache?>"></script>
  <script type="text/javascript" src="rcfx/js/chartjs/Chart.js"></script>
</head>
<body>
<div class="screen animated">

  <div class="sidebar<?=sidebar_state() ? ' on' : ''?>"<?=$_SESSION['dbschema'] == 'indosps2' ? " style='background:#R264348'" : ""?>>
    <div class="head">
      <table cellspacing="5">
        <tr>
          <td valign="middle"><span class="profile_photo"><span class="fa fa-user fa-4x" style="color:#8CC152"></span></span></td>
          <td valign="middle" style="width:100%">
            <h5><?=ucwords($_SESSION['user']['userid'])?><?=$tax_mode ? ' |' : ''?></h5>
          </td>
          <?php if($notification_enabled){ ?>
          <td>
            <span style="position:relative;margin-right:5px" class="notification-icon">
              <span class="fa fa-bell-o" style="cursor:pointer;font-size:1.5em"></span><label id="notification_label"></label>
            </span>
          </td>
          <?php } ?>
        </tr>
      </table>

    </div>
    <div class="body">
      <div class="menulist">
        <?php if(count($dataavailables) > 0){ ?>
          <div>
            <label class="title">Data</label>
          </div>

          <?php
          for($i = 0 ; $i < count($dataavailables) ; $i++){
            $menu = $dataavailables[$i];
            $text = $menu['text'];
            $value = $menu['value'];
            $indent = ov('indent', $menu, 0, 0);
            ?>
            <a href="<?=$value?>" class="menuitem<?=$url == $value ? ' active' : ''?> indent-<?=$indent?>">
              <label onclick=""><?=$text?></label>
            </a>
            <?php
          }
          ?>
        <?php } ?>

        <?php if(count($salesavailables) > 0){ ?>
          <div>
            <a href="<?=privilege_get('salesoverview', 'list')?'salesoverview':''?>"><label class="title">Penjualan</label></a>
          </div>

          <?php
          for($i = 0 ; $i < count($salesavailables) ; $i++){
            $menu = $salesavailables[$i];
            $text = $menu['text'];
            $value = $menu['value'];
            ?>
            <a href="<?=$value?>" class="menuitem<?=$url == $value ? ' active' : ''?>">
              <label onclick=""><?=$text?></label>
            </a>
            <?php
          }
          ?>
        <?php } ?>

        <?php if(count($purchaseavailables) > 0){ ?>
          <div>
            <label class="title">Pembelian</label>
          </div>

          <?php
          for($i = 0 ; $i < count($purchaseavailables) ; $i++){
            $menu = $purchaseavailables[$i];
            $text = $menu['text'];
            $value = $menu['value'];
            ?>
            <a href="<?=$value?>" class="menuitem<?=$url == $value ? ' active' : ''?>">
              <label onclick=""><?=$text?></label>
            </a>
            <?php
          }
          ?>
        <?php } ?>

        <?php if(count($otheravailables) > 0){ ?>
          <div>
            <label class="title">Lain-lain</label>
          </div>

          <?php
          for($i = 0 ; $i < count($otheravailables) ; $i++){
            $menu = $otheravailables[$i];
            $text = $menu['text'];
            $value = $menu['value'];
            ?>
            <a href="<?=$value?>" class="menuitem<?=$url == $value ? ' active' : ''?>">
              <label onclick=""><?=$text?></label>
            </a>
            <?php
          }
          ?>
        <?php } ?>

        <?php if(count($otheravailables) > 0){ ?>
          <div>
            <label class="title">Audit</label>
          </div>

          <?php
          for($i = 0 ; $i < count($auditavailables) ; $i++){
            $menu = $auditavailables[$i];
            $text = $menu['text'];
            $value = $menu['value'];
            ?>
            <a href="<?=$value?>" class="menuitem<?=$url == $value ? ' active' : ''?>">
              <label onclick=""><?=$text?></label>
            </a>
            <?php
          }
          ?>
        <?php } ?>

        <div style="height:80px"></div>

        <div class="align-center" style="color:#666">
          &sdot;&sdot;&sdot;
          <br /><br />
          <small style="color:#666;font-size:.8em">INDOSPS <?=date('Y')?></small>
          <br />
          <small style="color:#666;font-size:.8em">App Version <?=$nocache?></small>
        </div>

        <div style="height:20px"></div>

      </div>
    </div>
    <div class="foot align-center">
      <table style="table-layout: fixed;width:100%">
        <tr>
          <td style="width:30px"><button onclick="ui.sidebartoggler()"><span class="fa fa-expand"></span></button></td>
          <td style="width:100%;text-align: center"><button onclick="window.location = 'logout';"><span class="fa fa-power-off"</button></td>
          <td style="width:30px">&nbsp;</td>
        </tr>
      </table>

    </div>
  </div>

  <span id="notification_cont"></span>

  <div class="content<?=sidebar_state() ? ' sidebar-on' : ''?>">
    <?php

    include $url . '.php'


    ?>

  </div>

  <div class="modalbg off"></div>
  <div class="modal off animated"></div>

  <div class="dialogbg off"></div>
  <div class="dialog off animated"></div>

  <a class="off" id="downloader" download></a>
  <input id="uploader" type="file" class="off" />
  <div class="statusbar off"></div>

  <div id="tooltip" class="tooltip"></div>

  <div class="sidebar-toggler" onclick="ui.sidebartoggler()"></div>

  <?php if(is_debugmode()){ ?><div class="debugmode"></div><?php } ?>

  <script type="text/javascript">

    function ack(){

      window.setTimeout("ui.async('ui_ack', [], {})", 1000);

    }

    function ui_notification_load(){

      <?php if($notification_enabled){ ?>
      $('#notification_label').html("<span class=\"loading\" style=\"position:absolute;right:-5px;top:-5px;width:16px;height:16px;padding:6px\"></span>");
      $.post('?_async&_asyncm=ui_notification', function(this_response){

        if(this_response.indexOf('INDOSPS ::') < 0){ // Only if the response is valid notification response
          var stripObj = ui.striphtml(this_response);
          for(var exp in stripObj.elements){
            if(ui(exp)) ui(exp).innerHTML = stripObj.elements[exp];
          }
          if(stripObj.script) eval(stripObj.script);
        }
        else
          $('#notification_label').html("");

      })
      <?php } ?>

    }

    $(function(){

      // Prevent keyboard back button to trigger browser back
      var rx = /INPUT|SELECT|TEXTAREA/i;
      $(document).bind("keydown keypress", function(e){
        if( e.which == 8 ){ // 8 == backspace
          if(!rx.test(e.target.tagName) || e.target.disabled || e.target.readOnly ){
            e.preventDefault();
          }
        }
      });

      $('.notification-icon').on('click', function(e){

        e.preventDefault();
        e.stopPropagation();

        var offset = $(this).offset();
        var top = offset.top + $(this).outerHeight() + 10;
        var left = offset.left - 17;
        var maxHeight = window.innerHeight - top - 50;

        $('.notification-panel').css({ top:top, left:left });
        $('.notification-panel-inner').css({ 'max-height':maxHeight });
        $('.notification-panel').toggleClass('on', '');

      });

      $(window).click(function(e){

        if($('.notification-panel').hasClass('on') && $(e.originalEvent.originalTarget).closest('.notification-panel').length == 0){
          $('.notification-panel').removeClass('on');
        }
      })

      ui_notification_load();

    });

  </script>

</div>
<div class="printarea"></div>

</body>
</html>
