function salesreturn_pickinvoice_apply(){

	var salesinvoiceitemids = [];
	var grid3 = ui('#grid3');
	var selected_checkboxes = grid3.querySelectorAll("input:checked");
	if(selected_checkboxes != null){
		for(var i = 0 ; i < selected_checkboxes.length ; i++){
			salesinvoiceitemids.push(selected_checkboxes[i].getAttribute("data-id"));
		}
	}

	ui.async('ui_salesreturndetail_additems', [ salesinvoiceitemids ], {});

}

function salesreturn_pickinvoice_rowclick(e, tr){

  var checkbox = ui("input[type='checkbox']", tr);
  checkbox.checked = !checkbox.checked;

}

function salesreturndetail_onrowremove(e, span){

  var tr = span.parentNode.parentNode.parentNode;
  ui.grid_remove(tr);
  salesreturndetail_total();

}

function salesreturn_ispaidchange(ispaid, el){

  if(ispaid){

    var grid = ui('#grid4');
    var arr = ui.grid_value(grid);
    var returnamount = 0;
    for(var i = 0 ; i < arr.length ; i++){
      var obj = arr[i];
      if(parseFloat(obj['ispaid']) > 0){
        var unittotal = obj['qty'] * obj['unitprice'];
        returnamount += parseFloat(unittotal);
      }
    }

    ui.textbox_setvalue(ui('%returnamount', ui('.modal')), returnamount);
  }
  else{
    ui.textbox_setvalue(ui('%returnamount', ui('.modal')), 0);
  }

}

function salesreturndetail_total(){

	var total = 0;
	var grid3 = ui('#grid4');
	var tbody = grid3.querySelector('tbody');
	for(var i = 0 ; i < tbody.children.length ; i++){
		var tr = tbody.children[i];
		var obj = ui.container_value(tr, 1);
		var unitprice = obj['unitprice'];
		var qty = obj['qty'];
		var itemtotal = unitprice * qty;
		total += itemtotal;
	}
	ui.control_setvalue(ui('%total', ui('.modal')), total);

}