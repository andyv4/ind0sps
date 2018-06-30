function sampleinvoicedetail_calculaterow(tr){

  var obj = ui.container_value(tr);

  var unitprice = ui.ov('unitprice', obj);
  var qty = ui.ov('qty', obj);

  if(qty > 0)
    ui.grid_add(ui('#grid2'), 1);
}

function sampleinvoicedetail_subtotal(){

  var inventories_el = ui('#grid2');
  var tbody = inventories_el.querySelector('tbody');
  var subtotal = 0;
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var unittotal_el = ui('%unittotal', tr);
    if(!unittotal_el) continue;

    var unittotal = parseFloat(ui.label_value(unittotal_el));
    if(!isNaN(unittotal)) subtotal += unittotal;
  }

  ui.label_setvalue(ui('%subtotal'), subtotal);
  return subtotal;

}

function sampleinvoicedetail_total(){

  var subtotal = sampleinvoicedetail_subtotal();
  var discountamount = parseFloat(ui.textbox_value(ui('%discountamount', ui('.modal'))));
  if(isNaN(discountamount)) discountamount = 0;
  var taxamount = parseFloat(ui.label_value(ui('%taxamount', ui('.modal'))));
  if(isNaN(taxamount)) taxamount = 0;
  var deliverycharge = parseFloat(ui.textbox_value(ui('%deliverycharge', ui('.modal'))));
  if(isNaN(deliverycharge)) deliverycharge = 0;

  var total = subtotal - discountamount + taxamount + deliverycharge;

  ui.label_setvalue(ui('%total', ui('.modal')), total);

  //console.warn('subtotal: ' + subtotal + ', total: ' + total);

}

function sampleinvoicedetail_discountchange(){

  var subtotal = sampleinvoicedetail_subtotal();
  var discount = ui.textbox_value(ui('%discount', ui('.modal')));
  var discountamount = ui.discount_calc(discount, subtotal);

  ui.textbox_setvalue(ui('%discountamount', ui('.modal')), discountamount);
  sampleinvoicedetail_total();

}

function sampleinvoicedetail_discountamountchange(){

  ui.textbox_setvalue(ui('%discount', ui('.modal')), '');
  sampleinvoicedetail_total();

}

function sampleinvoicedetail_taxchange(){

  var subtotal = sampleinvoicedetail_subtotal();
  var discountamount = parseFloat(ui.textbox_value(ui('%discountamount', ui('.modal'))));
  if(isNaN(discountamount)) discountamount = 0;
  var taxable = ui.checkbox_value(ui('%taxable', ui('.modal')));
  var taxamount = taxable ? ((subtotal - discountamount) * .1) : 0;
  ui.label_setvalue(ui('%taxamount', ui('.modal')), taxamount);
  sampleinvoicedetail_total();


}

function sampleinvoicedetail_paidchange(paid){

  sampleinvoicedetail_total();
  ui.textbox_setvalue(ui('%paymentamount', ui('.modal')), paid ? ui.label_value(ui('%total', ui('.modal'))) : 0);

}

function sampleinvoicelist_groupopt_checkstate(){

  var grid = ui('#grid1');

  // On isgroup checked exists, change create button color
  var el_groupenabled = grid.querySelectorAll("*[data-name='isgroup']:checked");
  if(el_groupenabled.length > 0){
    ui('#createbtn').lastElementChild.innerHTML = 'Buat Grup';
  }
  else{
    ui('#createbtn').lastElementChild.innerHTML = 'Buat Faktur';
  }

}