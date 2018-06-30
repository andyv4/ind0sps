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

  $incomestatement = [];

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