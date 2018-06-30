<?php
if(!systemvarget('salesable') || privilege_get('taxreservedcode', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'tax-reserved-code';

require_once 'api/taxreservation.php';

function customheadcolumns(){

  $html = [];
  $html[] = "<td><button class='blue new-btn' onclick=\"ui.async('ui_taxcode_new', [ ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(ui_hasmoreoptions()) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_moreoptions', [])\"><span class='mdi mdi-menu'></span></button></td>";
  return implode('', $html);

}
function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  return taxreservation_list($columns, $sorts, $filters, $limits);

}
function defaultcolumns(){

  return [
    [ 'active'=>1, 'name'=>'options', 'text'=>'Opsi', 'width'=>'60px', 'type'=>'html', 'html'=>'ui_grid_col0' ],
    [ 'active'=>1, 'name'=>'createdon', 'text'=>'Tanggal', 'width'=>'160px' ],
    [ 'active'=>1, 'name'=>'type', 'text'=>'Tipe', 'width'=>'40px' ],
    [ 'active'=>1, 'name'=>'start_range', 'text'=>'Dari', 'width'=>'160px' ],
    [ 'active'=>1, 'name'=>'end_range', 'text'=>'Sampai', 'width'=>'160px' ],
    [ 'active'=>1, 'name'=>'summary', 'text'=>'Jumlah Kode', 'width'=>'100px' ],
  ];

}
function defaultpresets(){

  $columns = defaultcolumns();

  $presets = [
    array(
      'text'=>'Semua',
      'columns'=>$columns,
      'viewtype'=>'list',
      'sorts'=>array(
        array('name'=>'createdon', 'sorttype'=>'desc')
      )
    ),
  ];
  return $presets;

}
function defaultmodule(){

  $columns = defaultcolumns();

  $module = array(
    'title'=>'Tax Reserved Code',
    'columns'=>$columns,
    'presets'=>defaultpresets(),
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'code|customerdescription|inventorydescription|inventorycode&contains&'),
      array('text'=>'ID:', 'value'=>'id&equals&'),
      array('text'=>'Inventory ID:', 'value'=>'inventoryid&equals&'),
      array('text'=>'Taxable:', 'value'=>'taxable&equals&'),
    ),
    'onrowdoubleclick'=>"ui.async('ui_taxcode_detail', [ this.dataset['id'] ], { waitel:this })"
  );
  return $module;

}

function ui_taxcode_new(){

  $readonly = false;
  $types = [
    [ 'text'=>'Faktur Penjualan', 'value'=>'SI' ],
    [ 'text'=>'Faktur Pembelian', 'value'=>'PI' ],
  ];

  $controls = [
    'id'=>array('type'=>'hidden', 'name'=>'id', 'id'=>'id', 'value'=>0),
    'type'=>array('type'=>'dropdown', 'name'=>'type', 'items'=>$types, 'readonly'=>$readonly, 'value'=>'SI', 'width'=>150),
    'prefix'=>array('type'=>'textbox', 'name'=>'prefix', 'readonly'=>$readonly, 'value'=>'', 'width'=>80),
    'midfix'=>array('type'=>'textbox', 'name'=>'midfix', 'readonly'=>$readonly, 'value'=>'', 'width'=>60),
    'start_index'=>array('type'=>'textbox', 'name'=>'start_index', 'readonly'=>$readonly, 'value'=>'', 'width'=>200),
    'end_index'=>array('type'=>'textbox', 'name'=>'end_index', 'readonly'=>$readonly, 'value'=>'', 'width'=>200),
  ];

  $actions = [];
  $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_taxcode_save', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-times'></span><label>Save</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"if(confirm('Batalkan isian ini?')) ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $html = [];
  $html[] = "<element exp='.modal'>";
  $html[] = "<div class='scrollable padding1020'>";
  $html[] = "
  <table class='form'>
        <tr><th><label>Tipe</label></th><td colspan='3'>" . ui_control($controls['type']) . "</td></tr>
        <tr><th><label>Dari</label></th><td>" . ui_control($controls['prefix']) . "</td><td>" . ui_control($controls['midfix']) . "</td><td>" . ui_control($controls['start_index']) . "</td></tr>
        <tr><th><label>Sampai</label></th><td></td><td></td><td>" . ui_control($controls['end_index']) . "</td></tr>
  </table>";
  $html[] = "</div>";
  $html[] = "
    <div class='foot'>
      <table cellspacing='5'>
        <tr>
          <td style='width: 100%'></td>
          " . implode('', $actions) . "
        </tr>
      </table>
    </div>
  ";
  $html[] = "
	<script>
		ui.modal_open(ui('.modal'), { closeable:false, width:600, autoheight:false })
	</script>
	";
  $html[] = "</div>";
  $html[] = "</element>";
  return implode('', $html);

}

function ui_taxcode_detail($id){

  $value = taxreservation_detail($id);

  $columns = [
    [ 'active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>120, 'nodittomark'=>1 ],
    [ 'active'=>1, 'name'=>'typecode', 'text'=>'Kode Invoice', 'width'=>120, 'nodittomark'=>1 ],
    [ 'active'=>1, 'name'=>'typestatus', 'text'=>'Status', 'width'=>60, 'align'=>'center', 'type'=>'html', 'html'=>'ui_taxcode_detail_col0', 'nodittomark'=>1 ],
  ];

  $controls = [
    'poolhead'=>[ 'type'=>'gridhead', 'columns'=>$columns, 'gridexp'=>'#pool' ],
    'pool'=>[ 'type'=>'grid', 'id'=>'pool', 'columns'=>$columns, 'value'=>$value ],
  ];

  $html = [];
  $html[] = "<element exp='.modal'>";
  $html[] =   "<div class='head padding10'>
                " . ui_control($controls['poolhead']) . "  
              </div>
              <div class='scrollable padding10'>
                " . ui_control($controls['pool']) . "
              </div>
              <div class='foot'>
                <table cellspacing='5'>
                  <tr>
                    <td style='width: 100%'></td>
                    <td><td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td></td>
                  </tr>
                </table>
              </div>";
  $html[] = "</element>";
  $html[] = "
    <script>
      ui.modal_open(ui('.modal'), { closeable:true, width:600, autoheight:true });
    </script>
	";

  return implode('', $html);

}
function ui_taxcode_detail_col0($obj){

  $isactive = ov('typestatus', $obj);
  return "<div class='align-center'>" . ($isactive ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}

function ui_taxcode_remove($id){

  taxreservation_remove($id);
  return m_load() . "<script>ui.modal_close(ui('.modal'));</script>";

}

function ui_taxcode_save($obj){

  try{
    taxreservation_new($obj);
    return m_load() . "<script>ui.modal_close(ui('.modal'));</script>";
  }
  catch(Exception $ex){
    return ui_dialog('Error', $ex->getMessage());
  }

}

function ui_grid_col0($obj){

  $id = $obj['id'];

  $html = [];
  $html[] = "<div class='align-left'>";
  $html[] = "<span class='fa fa-bars' onclick=\"ui.async('ui_taxcode_detail', [ $id ])\"></span>";
  $html[] = "<span class='fa fa-times' onclick=\"if(confirm('Hapus kode ini?'))ui.async('ui_taxcode_remove', [ $id ])\"></span>";
  $html[] = "</div>";
  return implode('', $html);

}

function ui_hasmoreoptions(){

  return true;

}
function ui_moreoptions(){

  $html = [];
  $html[] = "<element exp='.modal'>";
  $html[] = "<div class='scrollable padding5'>";
  $html[] = "<button id='more1' class='hover-blue width-full align-left' onclick=\"ui.async('ui_process_salesinvoice_without_tax_code', [], { waitel:this })\"><span class='mdi mdi-comment-processing'></span><label>Proses Faktur Pajak Tanpa Kode Pajak</label></button>";
  $html[] = "</div>";
  $html[] = "</element>";
  $html[] = "<script>ui.modal_open(ui('.modal'), { closeable:true, width:300 });</script>";

  return implode('', $html);

}

function ui_process_salesinvoice_without_tax_code(){

  echo uijs("ui.modal_closeable = false");
  $result = salesinvoice_process_no_tax_code('ui_process_salesinvoice_without_tax_code_callback');
  echo uijs("ui.modal_closeable = true");
  echo uijs("ui.button_setprogress(ui('#more1'), 0)");
  if(!$result['count'])
    echo uijs("alert('Tidak ada Faktur Penjualan dengan kode pajak yang belum terisi')");
  else
    echo uijs("alert('')");

}
function ui_process_salesinvoice_without_tax_code_callback($process){

  $percentage = $process['percentage'];
  echo uijs("ui.button_setprogress(ui('#more1'), $percentage)");

}

include 'rcfx/dashboard1.php';

?>