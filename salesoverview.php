<?php
if(!systemvarget('salesable')){ include 'notavailable.php'; return; }

$colors = array(
    array('1'=>"rgba(78, 179, 211, .2)", '2'=>"rgba(8, 88, 158, 1)", '3'=>"#fff", '4'=>"rgba(8, 88, 158, 1)", '5'=>"rgba(8, 88, 158, 1)", '6'=>"rgba(8, 88, 158, 1)"),
    array('1'=>"rgba(65, 171, 93, .2)", '2'=>"rgba(0, 90, 50, 1)", '3'=>"#fff", '4'=>"rgba(0, 90, 50, 1)", '5'=>"rgba(0, 90, 50, 1)", '6'=>"rgba(0, 90, 50, 1)"),
    array('1'=>"rgba(236, 112, 20, .2)", '2'=>"rgba(149, 45, 4, 1)", '3'=>"#fff", '4'=>"rgba(149, 45, 4, 1)", '5'=>"rgba(149, 45, 4, 1)", '6'=>"rgba(149, 45, 4, 1)"),
    array('1'=>"rgba(140, 107, 177, .2)", '2'=>"rgba(110, 1, 107, 1)", '3'=>"#fff", '4'=>"rgba(110, 1, 107, 1)", '5'=>"rgba(110, 1, 107, 1)", '6'=>"rgba(110, 1, 107, 1)"),
    array('1'=>"rgba(231, 41, 138, .2)", '2'=>"rgba(145, 0, 63, 1)", '3'=>"#fff", '4'=>"rgba(145, 0, 63, 1)", '5'=>"rgba(145, 0, 63, 1)", '6'=>"rgba(145, 0, 63, 1)"),
);


function ui_customer_sales_performance_load($exp){

  $customerids = explode(',', $exp);

  userkeystoreadd($_SESSION['user']['id'], 'customer_sales_performance_filters', $exp);

  if(strlen($exp) == 0) return '';

  $startdate = '20150101'; //date('Ymd', mktime(0, 0, 0, date('m') - 6, 1, date('Y')));
  $enddate = date('Ymd', mktime(0, 0, 0, date('m') + 1, 0, date('Y')));

  $data = pmrs("SELECT customerid, customerdescription, YEAR(`date`) as `year`, MONTH(`date`) as `month`, SUM(total) as total FROM salesinvoice
    WHERE customerid IN ($exp) AND `date` >= ? AND `date` <= ? GROUP BY customerid, YEAR(`date`), MONTH(`date`)", array($startdate, $enddate));
  $data = array_index($data, array('customerid', 'year', 'month'));
  //console_log($data);

  global $colors;

  $datasets = array();
  $counter = 0;
  foreach($customerids as $customerid){

    $customer = $data[$customerid];

    $customerdata = array();
    foreach($customer as $year=>$yearobj){
      foreach($yearobj as $month=>$monthobj){
        $customerdata[] = $monthobj['total'];
      }
    }

    $datasets[] = array(
        'label'=>$monthobj['customerdescription'],
        'fillColor'=>$colors[$counter]['1'],
        'strokeColor'=>$colors[$counter]['2'],
        'pointColor'=>$colors[$counter]['3'],
        'pointStrokeColor'=>$colors[$counter]['4'],
        'pointHighlightFill'=>$colors[$counter]['5'],
        'pointHighlightStroke'=>$colors[$counter]['6'],
        'data'=>$customerdata
    );

    $counter++;
  }

  $labels = array();
  $currentdate = $startdate;
  do{
    $labels[] = date('M', strtotime($currentdate));

    $currentdate = date('Ymd', mktime(0, 0, 0, date('m', strtotime($currentdate)) + 1, date('j', strtotime($currentdate)), date('Y', strtotime($currentdate))));
  }
  while(strtotime($currentdate) < strtotime($enddate));

  $data = array(
    'labels'=>$labels,
    'datasets'=>$datasets
  );

  return uijs("
    var canvas1data = " . json_encode($data) . ";
    if(typeof chart1 != 'undefined'){
      chart1.destroy();
    }
    canvas1ctx = document.getElementById('canvas1').getContext('2d');
    chart1 = new Chart(canvas1ctx).Line(canvas1data);
  ");

}
function ui_customer_sales_performance_hint($param){

  $hint = $param['hint'];
  $rows = pmrs("SELECT description as text, id as value FROM customer WHERE (code LIKE ? OR description LIKE ?)",
      array("%$hint%", "%$hint%"));
  return $rows;

}
function ui_customer_sales_performance_value(){

  $exp = userkeystoreget($_SESSION['user']['id'], 'customer_sales_performance_filters');

  if(strlen($exp) > 0){

    $customers = pmrs("SELECT `id`, description FROM customer WHERE `id` IN ($exp)");
    $customers = array_cast($customers, array('text'=>'description', 'value'=>'id'));
    return $customers;

  }

}

function ui_salesrevenuepermonth_options(){

  return array(
    array('text'=>'Tahun ini', 'value'=>'thisyear')
  );

}
function ui_salesrevenuepermonth_value(){
  return 'thisyear';
}
function ui_salesrevenuepermonth_load($exp){

  $startdate = date('Y') . '0101'; //date('Ymd', mktime(0, 0, 0, date('m') - 6, 1, date('Y')));
  $enddate = date('Ymd', mktime(0, 0, 0, 1, 0, date('Y') + 1));

  $rows = pmrs("SELECT MONTH(`date`) as `month`, SUM(total) as total FROM salesinvoice WHERE YEAR(`date`) = ? GROUP BY MONTH(`date`)", array(date('Y')));

  global $colors;

  $customerdata = array();
  foreach($rows as $row)
    $customerdata[] = $row['total'];

  $datasets[] = array(
    'label'=>'Revenue',
    'fillColor'=>$colors[0]['1'],
    'strokeColor'=>$colors[0]['2'],
    'pointColor'=>$colors[0]['3'],
    'pointStrokeColor'=>$colors[0]['4'],
    'pointHighlightFill'=>$colors[0]['5'],
    'pointHighlightStroke'=>$colors[0]['6'],
    'data'=>$customerdata
  );


  $labels = array();
  $currentdate = $startdate;
  do{
    $labels[] = date('M', strtotime($currentdate));

    $currentdate = date('Ymd', mktime(0, 0, 0, date('m', strtotime($currentdate)) + 1, date('j', strtotime($currentdate)), date('Y', strtotime($currentdate))));
  }
  while(strtotime($currentdate) < strtotime($enddate));

  $data = array(
    'labels'=>$labels,
    'datasets'=>$datasets
  );

  return uijs("
    var canvas2data = " . json_encode($data) . ";
    if(typeof chart2 == 'undefined'){
      canvas2ctx = document.getElementById('canvas2').getContext('2d');
      chart2 = new Chart(canvas2ctx).Line(canvas2data);
    }
    else{
      chart2.datasets = canvas2data;
      chart2.update();
    }
  ");

}

function ui_customertopsales_options(){

  return array(
      array('text'=>'Bulan ini', 'value'=>'thismonth'),
      array('text'=>'Tahun ini', 'value'=>'thisyear')
  );

}
function ui_customertopsales_value(){
  $option = userkeystoreget($_SESSION['user']['id'], 'customertopsales_option');
  return $option ? $option : 'thismonth';
}
function ui_customertopsales_list($elTag = false){

  $option = userkeystoreget($_SESSION['user']['id'], 'customertopsales_option');
  if(!$option) $option = 'thismonth';

  $columns = array(
    array('active'=>1, 'name'=>'customerdescription', 'width'=>200),
    array('active'=>1, 'name'=>'total', 'width'=>100, 'datatype'=>'number')
  );

  switch($option){
    case 'thismonth':
      $data = pmrs("SELECT customerdescription, SUM(total) as total FROM salesinvoice WHERE YEAR(`date`) = ?
        AND MONTH(`date`) = ? GROUP BY customerid ORDER BY total DESC LIMIT 10", array(date('Y'), date('m')));
      break;
    case 'thisyear':
      $data = pmrs("SELECT customerdescription, SUM(total) as total FROM salesinvoice WHERE YEAR(`date`) = ?
        GROUP BY customerid ORDER BY total DESC LIMIT 10", array(date('Y')));
      break;
  }

  $c = "";
  if($elTag) $c .= "<element exp='#metric4cont'>";
  $c .= ui_grid(array('columns'=>$columns, 'value'=>$data, 'id'=>'list4'));
  if($elTag) $c .= "</element>";
  return $c;

}
function ui_customertopsales_setoption($option){

  userkeystoreadd($_SESSION['user']['id'], 'customertopsales_option', $option);

  return ui_customertopsales_list(true);

}

function ui_salesrevenueamount_options(){

  return array(
      array('text'=>'Bulan ini', 'value'=>'thismonth'),
      array('text'=>'Tahun ini', 'value'=>'thisyear')
  );

}
function ui_salesrevenueamount_value(){

  $option = userkeystoreget($_SESSION['user']['id'], 'salesrevenueamount_option');
  return $option ? $option : 'thismonth';

}
function ui_salesrevenueamount_load($elTag = false){

  $option = userkeystoreget($_SESSION['user']['id'], 'salesrevenueamount_option');

  switch($option){
    case 'thisyear':
      $amount = pmc("SELECT SUM(total) as total FROM salesinvoice WHERE YEAR(date) = ?", array(date('Y')));
      break;
    default:
      $amount = pmc("SELECT SUM(total) as total FROM salesinvoice WHERE YEAR(date) = ? AND MONTH(date) = ?", array(date('Y'), date('m')));
      break;
  }

  $c = "";
  if($elTag) $c .= "<element exp='#metric5cont'>";
  $c .= "<h2 class='color-blue'>" . number_format($amount) . "</h2>";
  if($elTag) $c .= "</element>";
  return $c;

}
function ui_salesrevenueamount_setoption($option){

  userkeystoreadd($_SESSION['user']['id'], 'salesrevenueamount_option', $option);
  return ui_salesrevenueamount_load(1);

}

function ui_salesavgmargin_options(){

  return array(
      array('text'=>'Bulan ini', 'value'=>'thismonth'),
      array('text'=>'Tahun ini', 'value'=>'thisyear')
  );

}
function ui_salesavgmargin_value(){

  $option = userkeystoreget($_SESSION['user']['id'], 'salesavgmargin_option');
  return $option ? $option : 'thismonth';

}
function ui_salesavgmargin_load($elTag = false){

  $option = userkeystoreget($_SESSION['user']['id'], 'salesavgmargin_option');

  switch($option){
    case 'thisyear':
      $amount = pmc("SELECT AVG(avgsalesmargin) FROM salesinvoice WHERE avgsalesmargin < 200 AND YEAR(date) = ?", array(date('Y')));
      break;
    default:
      $amount = pmc("SELECT AVG(avgsalesmargin) FROM salesinvoice WHERE avgsalesmargin < 200 AND YEAR(date) = ? AND MONTH(date) = ?", array(date('Y'), date('m')));
      break;
  }

  $c = "";
  if($elTag) $c .= "<element exp='#metric6cont'>";
  $c .= "<h2 class='color-green'>" . number_format($amount) . "%</h2>";
  if($elTag) $c .= "</element>";
  return $c;

}
function ui_salesavgmargin_setoption($option){

  userkeystoreadd($_SESSION['user']['id'], 'salesavgmargin_option', $option);
  return ui_salesavgmargin_load(1);

}

function ui_salestotalqty_options(){

  return array(
      array('text'=>'Bulan ini', 'value'=>'thismonth'),
      array('text'=>'Tahun ini', 'value'=>'thisyear')
  );

}
function ui_salestotalqty_value(){

  $option = userkeystoreget($_SESSION['user']['id'], 'salestotalqty_option');
  return $option ? $option : 'thismonth';

}
function ui_salestotalqty_load($elTag = false){

  $option = userkeystoreget($_SESSION['user']['id'], 'salestotalqty_option');

  switch($option){
    case 'thisyear':
      $amount = pmc("SELECT SUM(t2.qty) FROM salesinvoice t1, salesinvoiceinventory t2 WHERE t1.id = t2.salesinvoiceid AND YEAR(date) = ?", array(date('Y')));
      break;
    default:
      $amount = pmc("SELECT SUM(t2.qty) FROM salesinvoice t1, salesinvoiceinventory t2 WHERE t1.id = t2.salesinvoiceid AND YEAR(date) = ? AND MONTH(date) = ?", array(date('Y'), date('m')));
      break;
  }

  $c = "";
  if($elTag) $c .= "<element exp='#metric7cont'>";
  $c .= "<h2 class='color-yellow'>" . number_format($amount) . "</h2>";
  if($elTag) $c .= "</element>";
  return $c;

}
function ui_salestotalqty_setoption($option){

  userkeystoreadd($_SESSION['user']['id'], 'salestotalqty_option', $option);
  return ui_salestotalqty_load(1);

}

function ui_salespaymentratio_load(){

  // Paid amount
  $paidamount = pmc("SELECT SUM(total) FROM salesinvoice WHERE ispaid = 1 AND YEAR(`date`) = ?", array(date('Y')));
  $dueamount = pmc("SELECT SUM(total) FROM salesinvoice WHERE ispaid <> 1 AND YEAR(`date`) = ? AND DATEDIFF(NOW(), `date`) <= 90", array(date('Y')));
  $baddebtamount = pmc("SELECT SUM(total) FROM salesinvoice WHERE ispaid <> 1 AND YEAR(`date`) = ? AND DATEDIFF(NOW(), `date`) > 90", array(date('Y')));
  $totalamount = $paidamount + $dueamount + $baddebtamount;

  $paidamount_ratio = round($paidamount / $totalamount * 100);
  $dueamount_ratio = round($dueamount / $totalamount * 100);
  $baddebtamount_ratio = round($baddebtamount / $totalamount * 100);

  console_log(get_defined_vars());

  $data = array(
    array(
      'value'=>$paidamount_ratio,
      'color'=>'#46BFBD',
      'highlight'=>'#5AD3D1',
      'label'=>"Lunas (" . number_format($paidamount) . ")"
    ),
    array(
      'value'=>$dueamount_ratio,
      'color'=>'#FDB45C',
      'highlight'=>'#FFC870',
      'label'=>"Piutang (" . number_format($dueamount) . ")"
    ),
    array(
        'value'=>$baddebtamount_ratio,
        'color'=>'#F7464A',
        'highlight'=>'#FF5A5E',
        'label'=>"Piutang Macet (" . number_format($baddebtamount) . ")"
    )
  );

  return uijs("
    var canvas3data = " . json_encode($data) . ";
    if(typeof chart3 == 'undefined'){
      canvas3ctx = document.getElementById('canvas3').getContext('2d');
      chart3 = new Chart(canvas3ctx).Doughnut(canvas3data, { percentageInnerCutout:50 });
    }
    else{
      chart3.datasets = canvas3data;
      chart3.update();
    }
  ");

}

function ui_salesoverview_load($exp1, $exp2){

  return ui_customer_sales_performance_load($exp1) .
  ui_salesrevenuepermonth_load($exp2) .
  ui_salespaymentratio_load();

}

ui_async();
?>

<div class="padding10">

  <span class="metric">
    <div class="head">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <td style="width:100%"><h1>Grafik Penjualan per Bulan</h1></td>
          <td><?=ui_dropdown(array('id'=>'dropdown2', 'items'=>ui_salesrevenuepermonth_options(),
                'value'=>ui_salesrevenuepermonth_value(), 'width'=>100, 'align'=>'right'))?></td>
        </tr>
      </table>
      <div class="height20"></div>
    </div>
    <canvas id="canvas2"></canvas>
  </span>

  <div class="height10"></div>

  <span class="metric" id="metric5">
    <div class="head">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <td style="width:100%"><h1>Penjualan</h1></td>
          <td><?=ui_dropdown(array('id'=>'dropdown2', 'items'=>ui_salesrevenueamount_options(), 'align'=>'right',
                'value'=>ui_salesrevenueamount_value(), 'width'=>100, 'onchange'=>"ui.async('ui_salesrevenueamount_setoption', [ value ], {})"))?></td>
        </tr>
      </table>
      <div class="height20"></div>
      <div class="align-right" id="metric5cont">
        <?=ui_salesrevenueamount_load()?>
      </div>
    </div>
  </span>

  <span class="metric" id="metric6">
    <div class="head">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <td style="width:100%"><h1>Margin Rata Rata</h1></td>
          <td><?=ui_dropdown(array('id'=>'dropdown2', 'items'=>ui_salesavgmargin_options(), 'align'=>'right',
                'value'=>ui_salesavgmargin_value(), 'width'=>100, 'onchange'=>"ui.async('ui_salesavgmargin_setoption', [ value ], {})"))?></td>
        </tr>
      </table>
      <div class="height20"></div>
      <div class="align-right" id="metric6cont">
        <?=ui_salesavgmargin_load()?>
      </div>
    </div>
  </span>

  <span class="metric" id="metric7">
    <div class="head">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <td style="width:100%"><h1>Total Kts</h1></td>
          <td><?=ui_dropdown(array('id'=>'dropdown2', 'items'=>ui_salestotalqty_options(), 'align'=>'right',
                'value'=>ui_salestotalqty_value(), 'width'=>100, 'onchange'=>"ui.async('ui_salestotalqty_setoption', [ value ], {})"))?></td>
        </tr>
      </table>
      <div class="height20"></div>
      <div class="align-right" id="metric7cont">
        <?=ui_salestotalqty_load()?>
      </div>
    </div>
  </span>

  <div class="height10"></div>

  <span class="metric">
    <div class="head">
      <h1>Grafik Penjualan per Pelanggan</h1>
      <div class="height10"></div>
      <?=ui_multicomplete(array('id'=>'multicomplete1', 'width'=>'100%', 'src'=>'ui_customer_sales_performance_hint', 'value'=>ui_customer_sales_performance_value(),
          'placeholder'=>'Pilih pelanggan...', 'onchange'=>"ui_customer_sales_performance_load()"))?>
      <div class="height20"></div>
    </div>
    <div id="canvas1cont"><canvas id="canvas1"></canvas></div>
  </span>

  <div class="height10"></div>

  <span class="metric" id="metric3" style="height:360px">
    <div class="head">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <td style="width:100%"><h1>Ratio Pelunasan Penjualan</h1></td>
          <td><?=ui_dropdown(array('id'=>'dropdown2', 'items'=>ui_salesrevenuepermonth_options(), 'align'=>'right',
                'value'=>ui_salesrevenuepermonth_value(), 'width'=>100))?></td>
        </tr>
      </table>
      <div class="height20"></div>
    </div>
    <canvas id="canvas3"></canvas>
  </span>

  <span class="metric" id="metric4" style="height:360px">
    <div class="head">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <td style="width:100%"><h1>Pelanggan Penjualan Terbanyak</h1></td>
          <td><?=ui_dropdown(array('id'=>'dropdown2', 'items'=>ui_customertopsales_options(), 'align'=>'right',
                'value'=>ui_customertopsales_value(), 'width'=>100, 'onchange'=>"ui.async('ui_customertopsales_setoption', [ value ])"))?></td>
        </tr>
      </table>
      <div class="height20"></div>
    </div>
    <div id="metric4cont">
      <?=ui_customertopsales_list()?>
    </div>
  </span>

</div>
<script type="text/javascript">

  function ui_customer_sales_performance_load(){
    ui.async('ui_customer_sales_performance_load', [ ui.control_value(ui('#multicomplete1'))  ]);
  }
  function ui_customer_sales_performance_resize(){

    ui('#canvas1').width = ui('.content').clientWidth - 60;
    ui('#canvas1').height = 300;
  }

  function ui_salesrevenuepermonth_load(){



  }
  function ui_salesrevenuepermonth_resize(){

    ui('#canvas2').width = ui('.content').clientWidth - 60;
    ui('#canvas2').height = 300;

  }

  function ui_customertopsales_resize(){

    ui('#metric4').style.width = (ui('.content').clientWidth - 100) / 2;

  }

  function ui_salespaymentratio_resize(){

    ui('#metric3').style.width = (ui('.content').clientWidth - 100) / 2;
    ui('#canvas3').width = (ui('.content').clientWidth - 100) / 2;
    ui('#canvas3').height = 280;

  }
  function ui_salespaymentratio_load(){

    ui.async('ui_salespaymentratio_load', [], {});

  }

  function ui_salesrevenueamount_resize(){

    ui('#metric5').style.width = (ui('.content').clientWidth - 135) / 3;
    ui('#metric6').style.width = (ui('.content').clientWidth - 135) / 3;
    ui('#metric7').style.width = (ui('.content').clientWidth - 135) / 3;

  }

  function canvas_resize(){

    ui_customer_sales_performance_resize();
    ui_salesrevenuepermonth_resize();
    ui_customertopsales_resize();
    ui_salespaymentratio_resize();
    ui_salesrevenueamount_resize();

  }
  function canvas_load(){

    ui.async('ui_salesoverview_load', [
      ui.multicomplete_value(ui('#multicomplete1')),
      ui.control_value(ui('#dropdown2'))
    ], {});

  }
  canvas_resize();
  window.addEventListener('resize', canvas_resize);
  canvas_load();

  /*
  var data = {
    labels: ["January", "February", "March", "April", "May", "June", "July", "August" ],
    datasets: [
      {
        label: "My First dataset",
        fillColor: "rgba(220,220,220,0.2)",
        strokeColor: "rgba(220,220,220,1)",
        pointColor: "rgba(220,220,220,1)",
        pointStrokeColor: "#fff",
        pointHighlightFill: "#fff",
        pointHighlightStroke: "rgba(220,220,220,1)",
        data: [65, 59, 80, 81, 56, 55, 40]
      },
      {
        label: "My Second dataset",
        fillColor: "rgba(151,187,205,0.2)",
        strokeColor: "rgba(151,187,205,1)",
        pointColor: "rgba(151,187,205,1)",
        pointStrokeColor: "#fff",
        pointHighlightFill: "#fff",
        pointHighlightStroke: "rgba(151,187,205,1)",
        data: [28, 48, 40, 19, 86, 27, 90]
      }
    ]
  };
  var ctx = document.getElementById("canvas1").getContext("2d");
  new Chart(ctx).Line(data);
  */


</script>