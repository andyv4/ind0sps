<?php

require_once __DIR__ . '/api/salesinvoice.php';

ui_async();
?>
<div class="padding20">

  <?=ui_textbox([
    'id'=>'method',
    'width'=>'300px',
    'placeholder'=>'Method Name',
  ])?>
  <div class="height10"></div>
  <?=ui_textarea([
    'id'=>'param1',
    'width'=>'300px',
    'height'=>'200px',
    'placeholder'=>'Param 1',
  ])?>
  <div class="height10"></div>
  <?=ui_textarea([
    'id'=>'param2',
    'width'=>'300px',
    'height'=>'60px',
    'placeholder'=>'Param 2',
  ])?>
  <div class="height10"></div>
  <button id="exec_btn" class="blue"><label>Execute</label></button>

  <div class="height20"></div>
  <small>Output</small>
  <div class="height10"></div>
  <pre id="output"></pre>

</div>

<script>

  $(function(){

    $('#exec_btn').click(function(){
      var method = ui.textbox_value($('#method')[0]);
      var param1 = ui.textarea_value($('#param1')[0]);
      var param2 = ui.textarea_value($('#param2')[0]);
      var params = [ param1, param2 ];

      ui.async(method, params, {
        callback:function(obj, text){
          $('#output').html(text);
        }
      });
    })

  })

</script>