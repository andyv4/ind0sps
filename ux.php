<?php

require_once 'api/customer.php';

function ui_progress(){

  for($i = 1 ; $i <= 10 ; $i++){

    $percentage = $i * 10;
    echo uijs("ui.button_setprogress(ui('#button1'), $percentage)");

    ob_end_flush();
    ob_flush();
    sleep(1);

  }
  echo uijs("ui.button_setprogress(ui('#button1'), 0)");

}

ui_async();
?>
<div class="padding10">

  <!-- BUTTON -->
  <h4>Button</h4>
  <div class="height10"></div>
  <div class="padding10">
    <button id="button1" class="hover-blue width200" onclick="ui.async('ui_progress', []);"><label>Progress Button</label></button>
  </div>

  <!-- DROPDOWN -->
  <h4>Dropdown</h4>
  <div class="height10"></div>
  <small>Properties:</small>
  <table class="table-style1">
    <tr>
      <td style="min-width:120px">value</td>
      <td style="min-width:90px">String</td>
      <td>Set dropdown value</td>
    </tr>
  </table>
  <div class="height10"></div>
  <small>Examples:</small>
  <small class="color-blue selectable" data-toggle="#dropdown_pre">Show Code</small>
  <div>
    <?=ui_dropdown([
      'value'=>1,
      'items'=>[
        [ 'text'=>'Very long long long item', 'value'=>1 ],
      ],
      'width'=>'300px'
    ])?>
  </div>
  <pre id="dropdown_pre" class="bg-lightgray margin5 padding10">&lt;?=ui_dropdown([
  'value'=>1,
  'items'=>[
    [ 'text'=>'Very long long long item', 'value'=>1 ],
  ],
  'width'=>'100px'
])?&gt;</pre>

  <div class="height20"></div>

  <!-- AUTOCOMPLETE -->
  <h4>Autocomplete</h4>
  <div class="height10"></div>
  <small>Properties:</small>
  <table class="table-style1">
    <tr>
      <td style="min-width:120px">value</td>
      <td style="min-width:90px">String</td>
      <td>Set dropdown value</td>
    </tr>
  </table>
  <div class="height10"></div>
  <small>Examples:</small>
  <div>
    <?=ui_autocomplete([
      'type'=>'autocomplete',
      'id'=>'autocomplete',
      'width'=>'300px',
      'src'=>'customerlist_hints_asitems2',
      'readonly'=>0,
      'text'=>'',
      'value'=>'',
      'any_text'=>1,
      'placeholder'=>'Placeholder',
      'prehint'=>"test2",
      'onchange'=>"ui.async('ui_sampleinvoicedetail_customerchange', [ value ], { waitel:this })",

    ])?>
  </div>

  <div class="height20"></div>

  <!-- TOGGLER -->
  <h4>Toggler</h4>
  <div class="height10"></div>
  <small>Properties:</small>
  <table class="table-style1">
    <tr>
      <td style="min-width:120px">value</td>
      <td style="min-width:90px">String</td>
      <td>Set dropdown value</td>
    </tr>
  </table>
  <div class="height10"></div>
  <table>
    <tr>
      <td>
        <?=ui_toggler([
          'id'=>'autocomplete2',
          'name'=>'taxable',
          'onchange'=>'',
          'value'=>1,
          'text'=>'Off,On',
          'onchange'=>"console.log(arguments);",
        ])?>
      </td>
      <td>
    </tr>
  </table>

  <div class="height20"></div>

  <!-- DATEPICKER -->
  <h4>Datepicker</h4>
  <div class="height10"></div>
  <small>Properties:</small>
  <table class="table-style1">
    <tr>
      <td style="min-width:120px">value</td>
      <td style="min-width:90px">String</td>
      <td>Set dropdown value</td>
    </tr>
  </table>
  <div class="height10"></div>
  <table>
    <tr><td>
        <small>Datepicker with no default value</small><div></div>
        <?=ui_datepicker([
          'id'=>'datepicker1',
          'name'=>'datepicker1',
          'readonly'=>false,
          'width'=>'200px',
          'text_empty'=>"Pilih Tanggal...",
          'onchange'=>'console.log(JSON.stringify(arguments))',
        ])?>
    </td></tr>
    <tr><td>
        <small>Datepicker with default value set</small><div></div>
        <?=ui_datepicker([
          'id'=>'datepicker1',
          'name'=>'datepicker1',
          'readonly'=>false,
          'width'=>'200px',
          'onchange'=>'console.log(JSON.stringify(arguments))',
          'value'=>'20171212',
        ])?>
    </td></tr>
  </table>


</div>

<script type="text/javascript">

  function test2(){
    alert('test2');
  }

  //ui.eventcall('test23');


</script>