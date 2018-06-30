function journalvoucherdetail_debitamountchange(value, el){

  // Clear creditamount
  var tr = el.parentNode.parentNode;
  ui.textbox_setvalue(ui('%creditamount', tr), '');

  // Calculate total
  journalvoucherdetail_total();

}

function journalvoucherdetail_creditamountchange(value, el){

  // Clear debitamount
  var tr = el.parentNode.parentNode;
  ui.textbox_setvalue(ui('%debitamount', tr), '');

  // Calculate total
  journalvoucherdetail_total();

}

function journalvoucherdetail_total(){

  // Calculate totaldebitamount and totalcreditamount
  var jvdetails = ui('#jvdetails', ui('.modal'));
  var arr = ui.grid_value(jvdetails);
  var totaldebitamount = totalcreditamount = 0;
  for(var i = 0 ; i < arr.length ; i++){
    var obj = arr[i];
    var debitamount = parseFloat(ui.ov('debitamount', obj));
    var creditamount = parseFloat(ui.ov('creditamount', obj));
    if(!isNaN(debitamount)) totaldebitamount += debitamount;
    if(!isNaN(creditamount)) totalcreditamount += creditamount;
  }

  // Set amount
  var amount = 0;
  if(!isNaN(totaldebitamount) && !isNaN(totalcreditamount) && totaldebitamount == totalcreditamount && totaldebitamount > 0)
    amount = totaldebitamount;
  ui.textbox_setvalue(ui('%amount', ui('.modal')), amount);

}