<?php


function ui_functionloglist(){

  // Get latest
  $query = "SELECT requestid FROM `functionlog` GROUP BY requestid ORDER BY endtimestamp DESC LIMIT 1;";
  $requestid = pmc($query);

  $data = pmrs("SELECT * FROM functionlog WHERE requestid = ? AND functionname != 'chartofaccountrecalculate' ORDER BY  `id` DESC", array($requestid));

  $c = "<element exp='#cont1'>";
  $c .= ui_functionlog(array('value'=>$data));
  $c .= "</element>";
  return $c;

}

ui_async();
?>
<div class="padding10">

<table cellspacing="5">
  <tr>
    <td><button class="blue"><span class="fa fa-cogs"></span><label>Fetch Last Request</label></button></td>
  </tr>
</table>

<div id="cont1">

</div>

<script type="text/javascript">

  ui.async('ui_functionloglist', [], {});

</script>

</div>