function supplierdetail_tabclick(index, id){

  switch(index){
    case 1:
      ui.async('ui_supplierdetail_mutationdetail', [ id ], {});
      break;
  }

}