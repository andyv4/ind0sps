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

  $purchase = pmc("select sum(paymentamount) from purchaseinvoice where `date` between ? and ?", [ $date1, $date2 ]) +
    pmc("select sum(paymentamount) from purchaseorder where `date` between ? and ?", [ $date1, $date2 ]);
  $purchase_local = pmc("select sum(paymentamount) from purchaseinvoice where `date` between ? and ? and currencyrate = 1", [ $date1, $date2 ]) +
    pmc("select sum(paymentamount) from purchaseorder where `date` between ? and ? and currencyrate = 1", [ $date1, $date2 ]);
  $purchase_import = pmc("select sum(paymentamount) from purchaseinvoice where `date` between ? and ? and currencyrate > 1", [ $date1, $date2 ]) +
    pmc("select sum(paymentamount) from purchaseorder where `date` between ? and ? and currencyrate > 1", [ $date1, $date2 ]);

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
    'receivable'=>number_format($sales_receivable, 0, ',', '.')
  ];
  $incomestatement['purchase'] = [
    '_total'=>number_format($purchase, 0, ',', '.'),
    'local'=>number_format($purchase_local, 0, ',', '.'),
    'import'=>number_format($purchase_import, 0, ',', '.'),
    'payable'=>$purchase_payable
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

  $incomestatement['sales_items'] = incomestatement_sales_items($date1, $date2);
  $incomestatement['sales_discounts'] = incomestatement_sales_discounts($date1, $date2);
  $incomestatement['sales_cost_prices'] = incomestatement_sales_cost_prices($date1, $date2);
  $incomestatement['handling_fee'] = incomestatement_handling_fee($date1, $date2);
  $incomestatement['gross_profit'] = $incomestatement['sales_items'] - ($incomestatement['sales_discounts'] + $incomestatement['sales_handling_fee'] + $incomestatement['sales_cost_prices']);

  global $_INCOME_STATEMENT_GROUPS;
  $operating_expenses = 0;
  foreach($_INCOME_STATEMENT_GROUPS as $index=>$income_statement_group){
    $incomestatement['operating_expenses_cost' . $index] = incomestatement_operating_expenses_cost($index, $date1, $date2);
    $operating_expenses += $incomestatement['operating_expenses_cost' . $index];
  }
  $incomestatement['operating_expenses'] = $operating_expenses;

  $incomestatement['net_profit'] = $incomestatement['gross_profit'] - $incomestatement['operating_expenses'];

  return $incomestatement;

  $incomestatement['sales'] = floatval(pmc("SELECT SUM(`credit`) FROM journalvoucher p1, journalvoucherdetail p2 WHERE p1.id = p2.jvid
    AND p1.date BETWEEN ? AND ? AND p2.coaid = 6 AND p1.ref = 'SI';", array($date1, $date2)));

  $incomestatement['cogs'] = floatval(pmc("SELECT SUM(`debit`) FROM journalvoucher p1, journalvoucherdetail p2 WHERE p1.id = p2.jvid
    AND p1.date BETWEEN ? AND ? AND p2.coaid = 11", array($date1, $date2)));

  $incomestatement['grossrevenue'] = $incomestatement['sales'] - $incomestatement['cogs'];

  $rows = pmrs("SELECT `id` FROM chartofaccount WHERE accounttype = 'Expense'");
  $expenseids = array();
  for($i = 0 ; $i < count($rows) ; $i++)
    $expenseids[] = $rows[$i]['id'];

  $incomestatement['expense'] = floatval(pmc("SELECT SUM(`debit`) FROM journalvoucher p1, journalvoucherdetail p2 WHERE p1.id = p2.jvid
    AND p1.date BETWEEN ? AND ? AND p2.coaid IN (" . implode(', ', $expenseids) . ")", array($date1, $date2)));

  $incomestatement['netrevenue'] = $incomestatement['grossrevenue'] - $incomestatement['expense'];

  return $incomestatement;

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