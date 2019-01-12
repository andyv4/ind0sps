<?php

require_once 'api/incomestatement.php';

$title = 'Income Statement';

function m_loads($params){

  $start_date = $params['start_date'];
  $end_date = $params['end_date'];

  $report = incomestatementlist($start_date, $end_date);

  /*$c = [];
  $c[] = "<element exp='#row2'>";
  $c[] = "<pre>" . print_r($report, 1) . "</pre>";
  $c[] = "</element>";
  return implode('', $c);*/

  $c = [];
  $c[] = "<element exp='#row2'>";
  $c[] = "<div  class='ict'>";
  $c[] = "<table>";

  $c[] = "<tr><td colspan='2'><b>Penjualan</b></td><td><a href='#'><b>" . $report['sales']['_total'] . "</b></a></td></tr>";
  $c[] = "<tr><td></td><td>SPS</td><td><a href='#'>" . $report['sales']['SPS'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>SPSP</td><td><a href='#'>" . $report['sales']['SPSP'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>(Tax)</td><td><a href='#'>(" . $report['sales']['tax_amount'] . ")</a></td></tr>";
  $c[] = "<tr class='gray'><td></td><td>Piutang</td><td>" . $report['sales']['receivable'] . "</td></tr>";

  $c[] = "<tr><td colspan='2'><b>Pembelian</b></td><td><a href='javascript:m_open(5)'><b>" . $report['purchase']['_total'] . "</b></a></td></tr>";
  $c[] = "<tr><td></td><td>Lokal</td><td><a href='javascript:m_open(6)'>" . $report['purchase']['local'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>Import</td><td><a href='javascript:m_open(7)'>" . $report['purchase']['import'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>PPn Pembelian</td><td><a href=''>" . $report['purchase']['ppn'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>PPh Pembelian</td><td><a href=''>" . $report['purchase']['pph'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>KSO</td><td><a href=''>" . $report['purchase']['kso'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>SKI</td><td><a href=''>" . $report['purchase']['ski'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>Clearance Fee</td><td><a href=''>" . $report['purchase']['clearance_fee'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>Bea Masuk</td><td><a href=''>" . $report['purchase']['import_cost'] . "</a></td></tr>";
  $c[] = "<tr><td></td><td>Handling Fee</td><td><a href=''>" . $report['purchase']['handling_fee'] . "</a></td></tr>";
  $c[] = "<tr class='gray'><td></td><td>Hutang</td><td>" . (strlen(implode("<br />", $report['purchase']['payable'])) > 0 ? implode("<br />", $report['purchase']['payable']) : 0) . "</td></tr>";

  $c[] = "<tr class='row-line'><td colspan='2'><b>LABA KOTOR</b></td><td><b>" . $report['revenue']['gross'] . "</b></td></tr>";
  $c[] = "<tr class='row-line'><td colspan='3'></td></tr>";

  $c[] = "<tr><td colspan='2'><b>Biaya</b></td><td><b>" . $report['cost']['_total'] . "</b></td></tr>";
  foreach($report['cost'] as $key=>$value){
    if($key == '_total') continue;
    $c[] = "<tr><td></td><td>$key</td><td>$value</td></tr>";
  }

  $c[] = "<tr class='row-line'><td colspan='2'><b>LABA BERSIH</b></td><td><b>" . $report['revenue']['net'] . "</b></td></tr>";
  $c[] = "<tr class='row-line'><td colspan='3'></td></tr>";

  $c[] = "</table>";
  $c[] = "</div>";
  $c[] = "</element>";
  return implode('', $c);

}

function m_detail($params){

  $start_date = $params['start_date'];
  $end_date = $params['end_date'];
  $id = $params['id'];

  switch($id){
    case 5:
      $filepath = incomestatement_purchase($start_date, $end_date);
      echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();ui.modal_close(ui('.modal'));");
      break;
    case 6:
      $filepath = incomestatement_purchase_local($start_date, $end_date);
      echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();ui.modal_close(ui('.modal'));");
      break;
    case 7:
      $filepath = incomestatement_purchase_import($start_date, $end_date);
      echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();ui.modal_close(ui('.modal'));");
      break;
  }

}

function m_print($params){

  $start_date = $params['start_date'];
  $end_date = $params['end_date'];

  $report = incomestatementlist($start_date, $end_date);

  $c = [];
  $c[] = "<element exp='.printarea'>";
  $c[] = "<div  class='ict'>";
  $c[] = "<div style='text-align:center'>";
  $c[] = "<h1>Laba Rugi</h1>";
  $c[] = date('j M Y', strtotime($start_date)) . ' s/d ' . date('j M Y', strtotime($end_date));
  $c[] = "</div>";
  $c[] = "<br /><br /><br />";
  $c[] = "<table>";

  $c[] = "<tr class='row-line'><td colspan='2'><b>Penjualan</b></td><td><b>" . $report['sales']['_total'] . "</b></td></tr>";
  $c[] = "<tr><td></td><td>SPS</td><td>" . $report['sales']['SPS'] . "</td></tr>";
  $c[] = "<tr><td></td><td>SPSP</td><td>" . $report['sales']['SPSP'] . "</td></tr>";
  $c[] = "<tr class='gray'><td></td><td>Piutang</td><td>" . $report['sales']['receivable'] . "</td></tr>";

  $c[] = "<tr><td colspan='2'><b>Pembelian</b></td><td><b>" . $report['purchase']['_total'] . "</b></td></tr>";
  $c[] = "<tr><td></td><td>Lokal</td><td>" . $report['purchase']['local'] . "</td></tr>";
  $c[] = "<tr><td></td><td>Import</td><td>" . $report['purchase']['import'] . "</td></tr>";
  $c[] = "<tr class='gray'><td></td><td>Hutang</td><td>" . (strlen(implode("<br />", $report['purchase']['payable'])) > 0 ? implode("<br />", $report['purchase']['payable']) : 0) . "</td></tr>";

  $c[] = "<tr class='row-line'><td colspan='2'><b>LABA KOTOR</b></td><td><b>" . $report['revenue']['gross'] . "</b></td></tr>";
  $c[] = "<tr class='row-line'><td colspan='3'></td></tr>";

  $c[] = "<tr><td colspan='2'><b>Biaya</b></td><td><b>" . $report['cost']['_total'] . "</b></td></tr>";
  foreach($report['cost'] as $key=>$value){
    if($key == '_total') continue;
    $c[] = "<tr><td></td><td>$key</td><td>$value</td></tr>";
  }

  $c[] = "<tr class='row-line'><td colspan='2'><b>LABA BERSIH</b></td><td><b>" . $report['revenue']['net'] . "</b></td></tr>";
  $c[] = "<tr class='row-line'><td colspan='3'></td></tr>";

  $c[] = "</table>";
  $c[] = "</div>";
  $c[] = "</element>";
  return implode('', $c) . "<script>window.print();</script>";

}

ui_async();
?>
<style>
  .ict{ background:#fff; display:inline-block;max-width:600px;padding:20px; }
  .ict table { border-collapse: collapse;width:100%; }
  .ict td{ padding:4px; }
  .ict tr td:nth-child(1){ min-width:20px; }
  .ict tr td:nth-child(2){ width: 100%; }
  .ict tr td:last-child{ text-align: right;min-width:150px; }
  .ict .line{ height:1px; background:#000; padding:0; }
  .ict .row-line td{ border-top: solid 1px #000; border-bottom; solid 1px #000; }
  .ict .gray, .ict .gray *{ color: #ccc; }
</style>
<div class="padding10">

  <div id="row0">
    <div style="max-width:655px">
      <table cellspacing='5'>
        <tr>
          <td><?=ui_datepicker([ 'id'=>'start_date', 'width'=>'100px', 'value'=>date('Y') . '0101' ])?></td>
          <td><?=ui_datepicker([ 'id'=>'end_date', 'width'=>'100px', 'value'=>date('Y') . '1231' ])?></td>
          <td style="width:180px"></td>
          <td><button id="view_report_btn" class="blue" style="width:100px"><label>Lihat Laporan</label></button></td>
          <td><button id="view_report_btn2" class="green" style="width:100px"><label>Cetak</label></button></td>
        </tr>
      </table>
    </div>
  </div>

  <div id="row1"></div>

  <div id="row2" class='scrollable padding10' data-loadprogresscallback="m_loadingprogress"></div>

  <script type="text/javascript">

    function m_load(){

      var start_date = ui.datepicker_value(ui('#start_date'));
      var end_date = ui.datepicker_value(ui('#end_date'));
      var params = {
        start_date:start_date,
        end_date:end_date
      }
      ui.async('m_loads', [ params ], {  });

    }

    function m_open(id){

      var start_date = ui.datepicker_value(ui('#start_date'));
      var end_date = ui.datepicker_value(ui('#end_date'));
      var params = {
        start_date:start_date,
        end_date:end_date,
        id:id
      }
      ui.async('m_detail', [ params ], {  });

    }


    function m_resize(){

      ui('#row2').style.height = (window.innerHeight - ui('#row0').clientHeight - ui('#row1').clientHeight) - 50 + "px";

    }

    function m_init(){

      $('#view_report_btn').click(function(){ m_load(); });
      $('#view_report_btn2').click(function(){

        var start_date = ui.datepicker_value(ui('#start_date'));
        var end_date = ui.datepicker_value(ui('#end_date'));
        var params = {
          start_date:start_date,
          end_date:end_date
        }
        ui.async('m_print', [ params ], {  });

      });
      m_resize();
      $(window).resize(function(){ m_resize(); });

    }

    $(m_init);

  </script>

</div>
