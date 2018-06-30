<?php

require_once 'api/salesinvoice.php';

function ui_salesinvoice_audit($start_date, $end_date){

  $columns = [
    [ 'active'=>1, 'name'=>'type', 'text'=>'Tipe', 'width'=>90 ],
    [ 'active'=>1, 'name'=>'id', 'text'=>'ID', 'width'=>50 ],
    [ 'active'=>1, 'name'=>'code', 'text'=>'Kode Faktur', 'width'=>120 ],
    [ 'active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>240 ],
    [ 'active'=>1, 'name'=>'log_count', 'text'=>'Log Count', 'width'=>80, 'datatype'=>'number' ],
    [ 'active'=>1, 'type'=>'html', 'html'=>'ui_gridcol1', 'width'=>40, 'align'=>'center' ],
  ];

  $result = salesinvoice_audit($start_date, $end_date);
  $data = $result['data'];

  return "
    <element exp='#summary'>
      <table>
        <tr>
          <th>Total Invoice:</th>
          <td>$result[total]</td>
        </tr>
        <tr>
          <th>Total OK:</th>
          <td>$result[total_ok]</td>
        </tr>
        <tr>
          <th>Total Removed:</th>
          <td>$result[total_removed]</td>
        </tr>
        <tr>
          <th>Total Missing:</th>
          <td>$result[total_missing]</td>
        </tr>
        <tr>
          <th>Total Inconsistent:</th>
          <td>$result[total_inconsistent]</td>
        </tr>
      </table>
    </element>
    <element exp='#report'>"
    . ui_gridhead([
      'columns'=>$columns,
      'gridexp'=>'#grid1'
    ])
    . ui_grid([
      'id'=>'grid1',
      'value'=>$data,
      'columns'=>$columns
    ]) .
    "</element>";

}

function ui_salesinvoice_audit_detail($id){

  $grid2_columns = [
    [ 'active'=>1, 'name'=>'action', 'text'=>'Action', 'width'=>120 ],
  ];

  $logs = salesinvoice_getlogs($id);

  $actions = array();
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Tutup</label></button></td>";

  return "<element exp='.modal'>
    <div id='id_scrollable' class='scrollable padding10' style='position:relative'>
    <span style='width:160px;vertical-align:top'>
      " . ui_grid([ 'id'=>'grid2', 'value'=>$logs, 'columns'=>$grid2_columns, 'onrowclick'=>"ui.async('ui_salesinvoice_audit_compare', [ id ])" ]) . "
    </span>
    <span id='compare1' style='width:800px;vertical-align:top'></span>
    </div>
    <div class='foot'>
    <table cellspacing='5'>
      <tr>
        <td style='width: 100%'></td>
        " . implode('', $actions) . "
    </table>
  </div>
    </element>" .
    uijs("ui.modal_open(ui('.modal'), { closeable:true, width:1000, autoheight:1 })");

}

function ui_gridcol1($obj){

  $id = ov('id', $obj);

  $html = [];
  $html[] = "<div class='align-center'><span class='fa fa-chain padding5' onclick=\"ui.async('ui_salesinvoice_audit_detail', [ $id ])\"></span></div>";
  return implode('', $html);

}

function ui_salesinvoice_audit_compare($id){

  $result = salesinvoice_compare_with_logid($id);
  $comparisons = $result['data'];

  return "<element exp='#compare1'>
      " . ui_compare([ 'value'=>$comparisons ]) . "
    </element>";

}

ui_async();
?>
<div class="padding20">
  <table>
    <tr>
      <td style="width:100%"><h4>Sales Invoice Log Audit</h4></td>
      <td style="white-space: nowrap;">
        <?=ui_datepicker([ 'id'=>'start_date', 'value'=>date('Ym') . '01' ])?>
        <?=ui_datepicker([ 'id'=>'end_date', 'value'=>date('Ymd') ])?>
        <button id="button1" class="blue"><label class="padding5 width100">Start</label></button>
      </td>
    </tr>
  </table>
  <div class="height10"></div>
  <div id="summary"></div>
  <div class="height10"></div>
  <span id="report"></span>
  <script type="text/javascript">

    $('#button1').click(function(){
      ui.async('ui_salesinvoice_audit', [ ui.datepicker_value(ui('#start_date')), ui.datepicker_value(ui('#end_date'))  ], { waitel:ui('#button1') });
    })

  </script>
</div>