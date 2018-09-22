<div class="padding20">

  <?php

  require_once 'api/salesinvoice.php';

  $rows = pmrs("select refid from userlog where action like 'salesinvoice%' and date(`timestamp`) >= 20180825 group by refid");
  $salesinvoiceids = [];
  foreach($rows as $row)
    $salesinvoiceids[] = $row['refid'];

  $salesinvoices = pmrs("select `id`, code, total from salesinvoice where `id` in (" . implode(', ', $salesinvoiceids) . ")");

  echo "<table border=1>";
  foreach($salesinvoices as $salesinvoice){

    $id = $salesinvoice['id'];
    $code = $salesinvoice['code'];
    $total = $salesinvoice['total'];
    $real_total = salesinvoice_total(salesinvoicedetail(null, array('id'=>$id)));
    $real_total = $real_total['total'];

    if(round($total) != round($real_total)){
      echo "<tr>";
      echo "<td>$code</td>";
      echo "<td>$total</td>";
      echo "<td>$real_total</td>";
      echo "<td>" . ($total != $real_total ? 'Not match' : 'Match') . "</td>";
      echo "</tr>";

      salesinvoicecalculate($id);
    }

  }
  echo "</table>";


  echo "<br /><br /> Completed in " . (microtime(1) - $time) . "\n";


  ?>

</div>
