<?php

$_INCOME_STATEMENT_GROUPS = [
  [ 'Gaji Karyawan', '600.07', '600.15' ],
  [ 'Bonus Karyawan', '600.39', '600.27', '600.10' ],
  [ 'Kesehatan Karyawan', '600.34', '600.38' ],
  [ 'Komisi Karyawan', '600.26' ],
  [ 'Parkir Bensin Tol Pulsa Ongkos Kirim', '600.03', '600.05', '600.09', '600.16' ],
  [ 'Biaya Gudang & Kantor', '600.06', '600.12', '600.13', '600.14', '600.24', '600.25', '600.31' ],
  [ 'Sewa Gudang', '600.19' ],
  [ 'Biaya Listrik, Air & Telp', '600.28', '600.29', '600.30' ],
  [ 'Biaya Kendaraan', '600.21' ],
  [ 'Insentif Outlet, Diskon, Entertain, Sponsorship', '600.08', '600.11', '600.35', '600.23', '600.23' ],
  [ 'Pajak Perusahaan', '600.17' ],
  [ 'Bad Debt', '600.37' ],
  [ 'Biaya Lain2', '600.01', '600.22', '600.32' ],
];

function incomestatementlist($date1, $date2){

  $sales = pmc("select sum(total) from salesinvoice where `date` between ? and ?", [ $date1, $date2 ]);
  $sales_tax = pmc("select sum(total) from salesinvoice where `date` between ? and ? and taxable = 1", [ $date1, $date2 ]);
  $sales_non_tax = pmc("select sum(total) from salesinvoice where `date` between ? and ? and taxable = 0", [ $date1, $date2 ]);
  $sales_receivable = pmc("select sum(total - paymentamount) from salesinvoice where `date` between ? and ? and taxable = 0", [ $date1, $date2 ]);
  $sales_tax_amount = pmc("select sum(taxamount) from salesinvoice where `date` between ? and ? and taxamount > 0", [ $date1, $date2 ]);
  $sales = $sales - $sales_tax_amount;

  $purchase = pmc("select sum(paymentamount) from purchaseinvoice where `date` between ? and ?", [ $date1, $date2 ]) +
    pmc("select sum(paymentamount) from purchaseorder where `date` between ? and ?", [ $date1, $date2 ]);
  $purchase_local = pmc("select sum(paymentamount) from purchaseinvoice where `date` between ? and ? and currencyid = 1", [ $date1, $date2 ]) +
    pmc("select sum(paymentamount) from purchaseorder where `date` between ? and ? and currencyid = 1", [ $date1, $date2 ]);
  $purchase_import = pmc("select sum(paymentamount) from purchaseinvoice where `date` between ? and ? and currencyid > 1", [ $date1, $date2 ]) +
    pmc("select sum(paymentamount) from purchaseorder where `date` between ? and ? and currencyid > 1", [ $date1, $date2 ]);

  $purchase_ppn = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2  where t1.id = t2.jvid and
    t1.date between ? and ? and t2.coaid = ?;", [ $date1, $date2, 1004 ]);
  $purchase_pph = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2  where t1.id = t2.jvid and
    t1.date between ? and ? and t2.coaid = ?;", [ $date1, $date2, 1005 ]);
  $purchase_kso = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2  where t1.id = t2.jvid and
    t1.date between ? and ? and t2.coaid = ?;", [ $date1, $date2, 55 ]);
  $purchase_ski = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2  where t1.id = t2.jvid and
    t1.date between ? and ? and t2.coaid = ?;", [ $date1, $date2, 1003 ]);
  $purchase_clearance_fee = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2  where t1.id = t2.jvid and
    t1.date between ? and ? and t2.coaid = ?;", [ $date1, $date2, 1006 ]);
  $purchase_import_cost = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2  where t1.id = t2.jvid and
    t1.date between ? and ? and t2.coaid = ?;", [ $date1, $date2, 1007 ]);
  $purchase_handling_fee = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2  where t1.id = t2.jvid and
    t1.date between ? and ? and t2.coaid = ?;", [ $date1, $date2, 40 ]);
  $purchase = $purchase + ($purchase_ppn + $purchase_pph + $purchase_kso + $purchase_ski + $purchase_clearance_fee + $purchase_import_cost + $purchase_handling_fee);

  $purchase_payable = [];
  $rows = pmrs("select t2.code, sum(t1.total) as total from purchaseinvoice t1, currency t2 
    where t1.currencyid = t2.id and t1.`date` between ? and ? and t1.ispaid = 0 group by t1.currencyid", [ $date1, $date2 ]);
  foreach($rows as $row)
    $purchase_payable[] = $row['code'] . ' ' . number_format($row['total']);

  $cost = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2
    where t1.id = t2.jvid and
    t1.date between ? and ? and
    t2.coaid in (select `id` from chartofaccount where code like '600.%');", [ $date1, $date2 ]);

  $coas = [];
  $coaids = pmrs("select `id`, `name` from chartofaccount where code like '600.%'");
  foreach($coaids as $coa){
    $coas[$coa['name']] = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2 
    where t1.id = t2.jvid and
    t1.date between ? and ? and
    t2.coaid = ?;", [ $date1, $date2, $coa['id'] ]);
  }
  arsort($coas);

  $profit_share = pmc("SELECT sum(debit - credit) FROM journalvoucher t1, journalvoucherdetail t2
    where t1.id = t2.jvid and
    t1.date between ? and ? and
    t2.coaid in (select `id` from chartofaccount where code like '800.05')", [ $date1, $date2 ]);

  $incomestatement = [];

  $incomestatement['sales'] = [
    '_total'=>number_format($sales, 0, ',', '.'),
    'SPSP'=>number_format($sales_tax, 0, ',', '.'),
    'SPS'=>number_format($sales_non_tax, 0, ',', '.'),
    'receivable'=>number_format($sales_receivable, 0, ',', '.'),
    'tax_amount'=>number_format($sales_tax_amount, 0, ',', '.'),
  ];
  $incomestatement['purchase'] = [
    '_total'=>number_format($purchase, 0, ',', '.'),
    'local'=>number_format($purchase_local, 0, ',', '.'),
    'import'=>number_format($purchase_import, 0, ',', '.'),
    'payable'=>number_format($purchase_payable, 0, ',', '.'),
    'ppn'=>number_format($purchase_ppn, 0, ',', '.'),
    'pph'=>number_format($purchase_pph, 0, ',', '.'),
    'kso'=>number_format($purchase_kso, 0, ',', '.'),
    'ski'=>number_format($purchase_ski, 0, ',', '.'),
    'clearance_fee'=>number_format($purchase_clearance_fee, 0, ',', '.'),
    'import_cost'=>number_format($purchase_import_cost, 0, ',', '.'),
    'handling_fee'=>number_format($purchase_handling_fee, 0, ',', '.')
  ];
  $incomestatement['cost'] = [
    '_total'=>number_format($cost),
  ];
  foreach($coas as $coa_name=>$coa_value)
    $incomestatement['cost'][$coa_name] = number_format($coa_value, 0, ',', '.');

  $incomestatement['revenue'] = [
    'gross'=>number_format($sales - $purchase, 0, ',', '.'),
    'net'=>number_format($sales - $purchase - $cost, 0, ',', '.'),
    'profit_share'=>number_format($profit_share, 0, ',', '.')
  ];

  return $incomestatement;

}

function incomestatement_purchase($date1, $date2){

  $items = pmrs("
    select t1.code as pi_code, (select code from purchaseorder where `id` = t1.purchaseorderid) as po_code, t1.supplierdescription, t2.code as currency_code, t1.currencyrate, t1.total, 
    t1.paymentamount, t1.purchaseorderid as po_id
    from purchaseinvoice t1, currency t2 
    where t1.currencyid = t2.id and t1.`date` between ? and ?  
    ",
    [ $date1, $date2 ]);

  $purchaseorderids = [];
  foreach($items as $item)
    if($item['po_id'] > 0)
      $purchaseorderids[$item['po_id']] = 1;
  $purchaseorderids = array_keys($purchaseorderids);
  $po_items = pmrs("select '-' as pi_code, t1.code as po_code, t1.supplierdescription, t2.code as currency_code, t1.currencyrate, t1.total, t1.paymentamount, t1.id as po_id
    from purchaseorder t1, currency t2 
    where t1.currencyid = t2.id and t1.id in (" . implode(', ', $purchaseorderids) . ")");
  $po_items = array_index($po_items, [ 'po_id' ]);

  $temp = [];
  foreach($items as $item){

    $purchaseorderid = $item['po_id'];
    unset($item['po_id']);
    $temp[] = $item;

    if(isset($po_items[$purchaseorderid])){
      $po = $po_items[$purchaseorderid][0];
      unset($po['po_id']);
      $temp[] = $po;
    }

  }

  $filepath = "usr/incomestatement_purchase-{$date1}-{$date2}.xlsx";
  array_to_excel($temp, $filepath);

  return $filepath;

}
function incomestatement_purchase_local($date1, $date2){

  $po_items = pmrs("select '-' as pi_code, t1.code as po_code, t1.supplierdescription, t2.code as currency_code, t1.currencyrate, t1.total, t1.paymentamount, t1.id as po_id
    from purchaseorder t1, currency t2 
    where t1.currencyid = t2.id and t1.`date` between ? and ? and t1.currencyid = 1", [ $date1, $date2 ]);
  $po_items = array_index($po_items, [ 'po_id' ]);

  $items = pmrs("
    select t1.code as pi_code, (select code from purchaseorder where `id` = t1.purchaseorderid) as po_code, t1.supplierdescription, t2.code as currency_code, t1.currencyrate, t1.total, 
    t1.paymentamount, t1.purchaseorderid as po_id
    from purchaseinvoice t1, currency t2 
    where t1.currencyid = t2.id and t1.`date` between ? and ? and t1.currencyid = 1  
    ",
    [ $date1, $date2 ]);

  $temp = [];
  foreach($items as $item){

    $purchaseorderid = $item['po_id'];
    unset($item['po_id']);
    $temp[] = $item;

    if(isset($po_items[$purchaseorderid])){
      $po = $po_items[$purchaseorderid][0];
      unset($po['po_id']);
      $temp[] = $po;
    }

  }

  $filepath = "usr/incomestatement_purchase_local-{$date1}-{$date2}.xlsx";
  array_to_excel($temp, $filepath);

  return $filepath;

}
function incomestatement_purchase_import($date1, $date2){

  $po_items = pmrs("select '-' as pi_code, t1.code as po_code, t1.supplierdescription, t2.code as currency_code, t1.currencyrate, t1.total, t1.paymentamount, t1.id as po_id
    from purchaseorder t1, currency t2 
    where t1.currencyid = t2.id and t1.`date` between ? and ? and t1.currencyid > 1", [ $date1, $date2 ]);
  $po_items = array_index($po_items, [ 'po_id' ]);

  $items = pmrs("
    select t1.code as pi_code, (select code from purchaseorder where `id` = t1.purchaseorderid) as po_code, t1.supplierdescription, t2.code as currency_code, t1.currencyrate, t1.total, 
    t1.paymentamount, t1.purchaseorderid as po_id
    from purchaseinvoice t1, currency t2 
    where t1.currencyid = t2.id and t1.`date` between ? and ? and t1.currencyid > 1  
    ",
    [ $date1, $date2 ]);

  $temp = [];
  foreach($items as $item){

    $purchaseorderid = $item['po_id'];
    unset($item['po_id']);
    $temp[] = $item;

    if(isset($po_items[$purchaseorderid])){
      $po = $po_items[$purchaseorderid][0];
      unset($po['po_id']);
      $temp[] = $po;
    }

  }

  $filepath = "usr/incomestatement_purchase_import-{$date1}-{$date2}.xlsx";
  array_to_excel($temp, $filepath);

  return $filepath;

}

function incomestatement_sales_items($date1, $date2){

  $query = "SELECT SUM(salesinvoiceinventory.qty * salesinvoiceinventory.unitprice) FROM salesinvoice, salesinvoiceinventory 
    WHERE salesinvoice.date BETWEEN ? AND ?
    AND salesinvoice.id = salesinvoiceinventory.salesinvoiceid";
  $total = pmc($query, [ $date1, $date2 ]);
  return $total > 0 ? $total : 0;

}

function incomestatement_sales_discounts($date1, $date2){

  $query = "SELECT SUM(discountamount) FROM salesinvoice WHERE salesinvoice.date BETWEEN ? AND ?";
  $total = pmc($query, [ $date1, $date2 ]);
  return $total > 0 ? $total : 0;

}

function incomestatement_sales_cost_prices($date1, $date2){

  $query = "SELECT SUM(salesinvoiceinventory.qty * salesinvoiceinventory.costprice) FROM salesinvoice, salesinvoiceinventory 
    WHERE salesinvoice.date BETWEEN ? AND ?
    AND salesinvoice.id = salesinvoiceinventory.salesinvoiceid";
  $total = pmc($query, [ $date1, $date2 ]);
  return $total > 0 ? $total : 0;

}

function incomestatement_handling_fee($date1, $date2){

  $query = "SELECT SUM(handlingfeepaymentamount) FROM purchaseinvoice where handlingfeedate BETWEEN ? AND ?";
  $total = pmc($query, [ $date1, $date2 ]);
  return $total > 0 ? $total : 0;

}

function incomestatement_operating_expenses_cost($idx, $date1, $date2){

  global $_INCOME_STATEMENT_GROUPS;

  if(!isset($_INCOME_STATEMENT_GROUPS[$idx])) return 0;

  $coa_codes = $_INCOME_STATEMENT_GROUPS[$idx];

  if(!is_array($coa_codes) || count($coa_codes) == 0) return 0;

  $coa_text = array_splice($coa_codes, 0, 1);

  $coa_code_text = [];
  foreach($coa_codes as $coa_code)
    $coa_code_text[] = "'$coa_code'";

  $coa_ids = pmrs("select `id` from chartofaccount where code in (" . implode(', ', $coa_code_text) . ")");
  $temp = [];
  foreach($coa_ids as $coa_id)
    $temp[] = $coa_id['id'];
  $coa_ids = $temp;

  $query = "SELECT SUM(debit) FROM journalvoucher, journalvoucherdetail
    WHERE journalvoucher.id = journalvoucherdetail.jvid 
    AND journalvoucherdetail.coaid IN (" . implode(', ', $coa_ids) . ")
    AND journalvoucher.date BETWEEN ? AND ?";

  $total = pmc($query, [ $date1, $date2 ]);
  return $total > 0 ? $total : 0;

}

?>