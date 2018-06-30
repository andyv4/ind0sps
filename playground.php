<?php



ui_async();
?>
<div class="padding10">

  <table class="form">
    <tr>
      <th><label>Textbox-1</label></th>
      <td><?=ui_textbox(array('name'=>'textbox', 'ischild'=>1))?></td>
    </tr>
    <tr>
      <th><label>Label-1</label></th>
      <td><?=ui_label(array('name'=>'label', 'value'=>'Label1', 'ischild'=>1))?></td>
    </tr>
  </table>
  <script type="text/javascript">

    console.log(ui.container_value(ui('.form'), 1));

  </script>

</div>