function salesinvoicedetail_calculaterow(tr){

  var obj = ui.container_value(tr, true);

  var unitprice = ui.ov('unitprice', obj);
  var qty = ui.ov('qty', obj);
  var unittotal = unitprice * qty;
  if(isNaN(unittotal)) unittotal = 0;

  console.log([ 'salesinvoicedetail_calculaterow', tr, obj, unitprice, qty, unittotal ]);

  ui.label_setvalue(ui('%unittotal', tr), unittotal);

  salesinvoicedetail_total();
  if(unittotal > 0)
    ui.grid_add(ui('#grid2'), 1);
}

function salesinvoicedetail_subtotal(){

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

function salesinvoicedetail_total(){

  var subtotal = salesinvoicedetail_subtotal();
  salesinvoicedetail_calculate_discountamount();
  salesinvoicedetail_calculate_taxamount();
  var discountamount = parseFloat($("*[data-name='discountamount']").val());
  if(isNaN(discountamount)) discountamount = 0;
  var taxamount = parseFloat($("*[data-name='taxamount']").val());
  if(isNaN(taxamount)) taxamount = 0;
  var deliverycharge = parseFloat($("*[data-name='deliverycharge']").val());
  if(isNaN(deliverycharge)) deliverycharge = 0;
  var total = subtotal - discountamount + taxamount + deliverycharge;
  console.log([ total, subtotal, discountamount, taxamount, deliverycharge ]);
  ui.label_setvalue(ui('%total', ui('.modal')), total);

}

function salesinvoicedetail_discountchange(){

  salesinvoicedetail_total();

}

function salesinvoicedetail_discountamountchange(){

  ui.textbox_setvalue(ui('%discount', ui('.modal')), '');
  salesinvoicedetail_total();

}

function salesinvoicedetail_calculate_discountamount(){

  var subtotal = salesinvoicedetail_subtotal();
  var discount = ui.textbox_value(ui('%discount', ui('.modal')));
  if(parseFloat(discount) != 0){
    var discountamount = ui.discount_calc(discount, subtotal);
    ui.textbox_setvalue(ui('%discountamount', ui('.modal')), discountamount);
  }

}

function salesinvoicedetail_calculate_taxamount(){

  var subtotal = salesinvoicedetail_subtotal();
  var discountamount = parseFloat(ui.textbox_value(ui('%discountamount', ui('.modal'))));
  if(isNaN(discountamount)) discountamount = 0;
  var taxable = $('#taxable', $('.modal')).val();
  var taxamount = taxable ? ((subtotal - discountamount) * .1) : 0;

  var taxable_excluded = 1;
  $("*[data-name='taxable_excluded']").each(function(){
    if(parseInt(this.value) == 0) taxable_excluded = 0;
  });
  if(taxable_excluded == 1) taxamount = 0;

  $("*[data-name='taxamount']").val(taxamount);

}

function salesinvoicedetail_paidchange(paid){

  salesinvoicedetail_total();
  ui.textbox_setvalue(ui('%paymentamount', ui('.modal')), paid ? ui.label_value(ui('%total', ui('.modal'))) : 0);

}

function salesinvoicelist_groupopt_checkstate(){

  var grid1 = ui('#grid1');

  if(grid1 != null){

    var groups = ui('#grid1').querySelectorAll("*[data-name='isgroup']:checked");

    var groupable = false;
    if(groups.length > 0){
      groupable = true;
      var groupable_taxable = -1;
      for(var i = 0 ; i < groups.length ; i++){
        var group = groups[i];
        var taxable = group.getAttribute("data-taxable");
        if(groupable_taxable == -1) groupable_taxable = taxable;
        else if(groupable_taxable != taxable){
          groupable = false;
          break;
        }
      }
    }

    groupable ? $('#groupable').show() : $('#groupable').hide();

  }

}

function salesinvoicegroup_create(){

  // Retrieve salesinvoice id
  var salesinvoiceids = [];
  var grid1 = ui('#grid1');
  var tbody = grid1.querySelector('tbody');
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var salesinvoiceid = tr.getAttribute("data-id");
    var isgroup_el = ui('%isgroup', tr);
    if(isgroup_el){
      var isgroup = isgroup_el.checked;
      if(isgroup) salesinvoiceids.push(salesinvoiceid);
    }
  }
  if(salesinvoiceids.length > 0)
    ui.async('ui_salesinvoicegroupdetail_createfrominvoices', [ salesinvoiceids, 'write' ], { waitel:ui('#groupable'), callback:"salesinvoicegroup_oncreate" });

}
function salesinvoicegroup_oncreate(){

  salesinvoicelist_groupopt_checkstate();

}

function m_onload(){

  salesinvoicelist_groupopt_checkstate();

}