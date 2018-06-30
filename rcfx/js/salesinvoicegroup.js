function salesinvoicegroup_paymentamount(){



}

function salesinvoicegroupdetail_onrowpaid(checked, el){

  var tr = el.parentNode.parentNode.parentNode;
  var total = checked ? ui.control_value(ui('%total', tr)) : 0;
  ui.textbox_setvalue(ui('%paymentamount', tr), total);
  salesinvoicegroupdetail_paymentamount();

}

// Calculate total of current salesinvoice group
function salesinvoicegroupdetail_total(){

  // Calculate total payment amount from paid invoice
  var el = ui('#salesinvoicegroupdetail_items');
  var tbody = el.querySelector('tbody');
  var totalamount = 0;
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var obj = ui.container_value(tr, 1);
    switch(obj['type']){
      case 'SI': totalamount += obj['total']; break;
      case 'SN': totalamount -= obj['total']; break;
    }
  }

  // Update total ui
  ui.control_setvalue(ui('#total'), totalamount);

}

function salesinvoicegroupdetail_paymentamount(){

  // Calculate total payment amount from paid invoice
  var el = ui('#salesinvoicegroupdetail_items');
  var tbody = el.querySelector('tbody');
  var totalpaymentamount = 0;
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var obj = ui.container_value(tr, 1);
    switch(obj['type']){
      case 'SI': totalpaymentamount += obj['paymentamount']; break;
      case 'SN': totalpaymentamount -= obj['paymentamount']; break;
    }
  }

  // Update payment amount ui
  ui.control_setvalue(ui('#paymentamount'), totalpaymentamount);

  // Update paid checkbox
  var total = ui.control_value(ui('#total'));
  ui.control_setvalue(ui('%ispaid', ui('.modal')), total == paymentamount ? 1 : 0);

}

function salesinvoicegroupdetail_paidchange(checked){

  var el = ui('#salesinvoicegroupdetail_items');
  var tbody = el.querySelector('tbody');
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];

    ui.control_setvalue(ui('%ispaid', tr), checked);
    ui.control_setvalue(ui('%paymentamount', tr), checked ? ui.control_value(ui('%total', tr)) : 0);
  }
  salesinvoicegroupdetail_paymentamount();

}

function salesinvoicegroupdetail_oninvoiceremove(e, tr){

  ui.grid_remove(tr);
  salesinvoicegroupdetail_total();
  salesinvoicegroupdetail_paymentamount();

}

function salesinvoicegroup_open(param){

  ui.modal_open(ui('.modal'), param);
  salesinvoicegroupdetail_total();
  salesinvoicegroupdetail_paymentamount();

}

function salesinvoicegrouplist_groupopt_checkstate(){

  var grid = ui('#grid1');

  // On isreceipt checked exists, change create button color
  var el_groupenabled = grid.querySelectorAll("*[data-name='isreceipt']:checked");

  if(el_groupenabled.length > 0){
    ui('#createbtn').lastElementChild.innerHTML = 'Buat Kwitansi';
  }
  else{
    ui('#createbtn').lastElementChild.innerHTML = 'Buat Grup';
  }

}

function salesinvoicegroup_new(){



  if(ui('#createbtn').lastElementChild.innerHTML == 'Buat Grup'){
    ui.async('ui_salesinvoicegroupdetail', [ null, 'write' ], { waitel:this });
  }
  else if(ui('#createbtn').lastElementChild.innerHTML == 'Buat Kwitansi'){

    // Retrieve salesinvoice id
    var salesinvoiceids = [];
    var grid1 = ui('#grid1');
    var tbody = grid1.querySelector('tbody');
    for(var i = 0 ; i < tbody.children.length ; i++){
      var tr = tbody.children[i];
      var salesinvoiceid = tr.getAttribute("data-id");
      var isgroup_el = ui('%isreceipt', tr);
      if(isgroup_el){
        var isgroup = isgroup_el.checked;
        if(isgroup) salesinvoiceids.push(salesinvoiceid);
      }
    }
    ui.async('ui_salesreceiptdetail_createfromgroups', [ salesinvoiceids ], { waitel:ui('#createbtn') });
  }

}

function salesinvoicegroupdetail_itemlookupapply(){

  var grid = ui('#itemlookupgrid');
  var arr = ui.grid_value(grid);

  var selecteditems = [];
  for(var i = 0 ; i < arr.length ; i++){
    var obj = arr[i];
    if(obj['checked']) selecteditems.push(obj['id']);
  }

  if(selecteditems.length > 0)
    ui.async('ui_salesinvoicegroupdetail_additems', [ selecteditems ], { });

}

function salesinvoicegroup_itemlookup(){

  var items = ui.grid_value(ui('#salesinvoicegroupdetail_items'));
  ui.async('ui_salesinvoicegroupdetail_itemlookup', [ ui.control_value(ui('%customerdescription', ui('.modal'))), items ], {})

}