<?php

function ui_exec($source){

  ob_start();
  $filename = 'usr/c' . md5('exec') . '.php';
  file_put_contents($filename, "<?php \n" . $source . "\n\n ?>");
  include $filename;
  $output = ob_get_contents();

  $c = "<element exp='#resultpane'><pre style='font-family: monospace'>";
  $c .= $output;
  $c .= "</pre></element>";

  $_SESSION['exec_lastsource'] = $source;

  return $c;

}

ui_async();
?>
<div class="padding10">

  <table cellspacing="10">
    <tr>
      <td>
        <?=ui_textarea(array('name'=>'source', 'width'=>500, 'height'=>200, 'value'=>isset($_SESSION['exec_lastsource']) ? $_SESSION['exec_lastsource'] : "echo 'Hello World';"))?>
      </td>
    </tr>
    <tr>
      <td><button id='button1' class="blue" onclick="ui.async('ui_exec', [ ui.control_value(ui('%source')) ], { waitel:this })"><span class="fa fa-cog"></span><label>Execute</label></button></td>
    </tr>
    <tr>
      <td><div id="resultpane" class="padding10" style="background: #eee"></div></td>
    </tr>
  </table>

  <?php if(isset($_SESSION['exec_lastsource'])){ ?>
  <script type="text/javascript">
    ui('#button1').click();
    ui('%source').firstElementChild.style.fontFamily = 'Monospace';
  </script>
  <?php } ?>

</div>