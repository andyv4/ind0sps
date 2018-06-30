<?php

require_once 'api/incomestatement.php';

$title = 'Income Statement';

function m_loads($params){

  $start_date = $params['start_date'];
  $end_date = $params['end_date'];

  $report = incomestatementlist($start_date, $end_date);

  $report['sales_items'] = number_format_auto($report['sales_items'], 0);
  $report['sales_discounts'] = number_format_auto($report['sales_discounts'], 0);
  $report['sales_cost_prices'] = number_format_auto($report['sales_cost_prices'], 0);
  $report['handling_fee'] = number_format_auto($report['handling_fee'], 0);
  $report['gross_profit'] = number_format_auto($report['gross_profit'], 0);
  $report['operating_expenses'] = number_format_auto($report['operating_expenses'], 0);
  $report['net_profit'] = number_format_auto($report['net_profit'], 0);

  $c = [];
  $c[] = "<element exp='#row2'>";
  $c[] = "<div class='ict'>";
  $c[] = "<table>";
  $c[] = "<tr><td colspan='2' width='480'><b>Penjualan</b></td><td></td></tr>";
  $c[] = "<tr><td></td><td>Penjualan Barang</td><td>$report[sales_items]</td></tr>";
  $c[] = "<tr><td></td><td>Diskon Penjualan</td><td>$report[sales_discounts]</td></tr>";
  $c[] = "<tr><td></td><td>Harga Modal</td><td>$report[sales_cost_prices]</td></tr>";
  $c[] = "<tr><td></td><td>Handling Fee</td><td>$report[handling_fee]</td></tr>";
  $c[] = "<tr><td colspan='5' class='line'></td></tr>";
  $c[] = "<tr><td colspan='2' width='200'><b>Laba Kotor</b></td><td>$report[gross_profit]</td></tr>";
  $c[] = "<tr><td colspan='5'><div class='height10'></div></td></tr>";
  $c[] = "<tr><td colspan='2' width='200'><b>Beban</b></td><td></td></tr>";

  global $_INCOME_STATEMENT_GROUPS;
  foreach($_INCOME_STATEMENT_GROUPS as $index=>$income_statement_group){
    $report['operating_expenses_cost' . $index] = number_format_auto($report['operating_expenses_cost' . $index], 0);
    $c[] = "<tr><td></td><td>$income_statement_group[0]</td><td>" . $report['operating_expenses_cost' . $index] . "</td></tr>";
  }
  $c[] = "<tr><td colspan='5' class='line'></td></tr>";
  $c[] = "<tr><td colspan='2' width='200'><b>Total Beban</b></td><td>$report[operating_expenses]</td></tr>";

  $c[] = "<tr><td colspan='5'><div class='height10'></div></td></tr>";
  $c[] = "<tr><td colspan='5' class='line'></td></tr>";
  $c[] = "<tr><td colspan='2' width='200'><b>Laba Bersih</b></td><td>$report[net_profit]</td></tr>";
  $c[] = "<tr><td colspan='5' class='line'></td></tr>";

  $c[] = "</table>";
  $c[] = "</div>";
  $c[] = "</element>";

  return implode('', $c);

}

ui_async();
?>
<style>
  .ict{ background:#fff; padding:20px; display:inline-block; }
  .ict table { border-collapse: collapse; }
  .ict tr:nth-child(2n){ background:#f5f5f5; }
  .ict td{ padding:10px; }
  .ict tr td:last-child{ text-align: right; }
  .ict .line{ height:1px; background:#000; padding:0; }
</style>
<div class="padding10">

  <div id="row0">
    <table cellspacing='5'>
      <tr>
        <td><?=ui_datepicker([ 'id'=>'start_date', 'width'=>'160px', 'value'=>'20180101' ])?></td>
        <td><?=ui_datepicker([ 'id'=>'end_date', 'width'=>'160px', 'value'=>'20180228' ])?></td>
        <td><button id="view_report_btn" class="blue"><label>Lihat Laporan</label></button></td>
      </tr>
    </table>
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


    function m_resize(){

      ui('#row2').style.height = (window.innerHeight - ui('#row0').clientHeight - ui('#row1').clientHeight) - 50 + "px";

    }

    function m_init(){

      $('#view_report_btn').click(function(){ m_load(); });
      m_resize();
      $(window).resize(function(){ m_resize(); });

    }

    $(m_init);

  </script>

</div>
