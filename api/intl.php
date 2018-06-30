<?php

$_EXCEPTIONS = array(
  'id'=>array(
    'i00'=>'Kode barang harus diisi.',
    'i01'=>'Nama barang harus diisi.',
    'i02'=>'Satuan harus diisi.',
    'i03'=>'Barang sudah ada. [code] - [description]',
    'i04'=>'Tidak dapat menghapus barang ini, sudah ada transaksi.',

    'cr01'=>'Kode mata uang harus diisi.',
    'cr02'=>'Nama mata uang harus diisi.',
    'cr03'=>'Kode mata uang telah digunakan, silakan memakai kode lain.',
    'cr04'=>'Nama mata uang telah digunakan, silakan memakai nama lain.',
    'cr05'=>'Mata uang tidak dapat dihapus, sudah digunakan dalam transaksi.',

    'coa01'=>'Kode akun harus diisi.',
    'coa02'=>'Nama akun harus diisi.',
    'coa03'=>'Kode akun telah digunakan, silakan memakai kode lain.',
    'coa04'=>'Nama akun telah digunakan, silakan memakai nama lain.',
    'coa05'=>'Akun tidak dapat dihapus, sudah digunakan dalam transaksi.',
    'coa06'=>'Tidak dapat menghapus akun ini, sudah ada jurnal.',
    'coa07'=>'D/K harus diisi',
    'coa08'=>'Tipe akun harus diisi',
    'coa09'=>'Mata uang harus diisi',

    'c01'=>'Kode pelanggan harus diisi.',
    'c02'=>'Nama pelanggan harus diisi.',
    'c03'=>'Kode pelanggan sudah ada.',
    'c04'=>'Nama pelanggan sudah ada.',
    'c05'=>'Tidak dapat menghapus pelanggan ini. Sudah ada transaksi.',
    'c06'=>'',

    's01'=>'Kode supplier harus diisi.',
    's02'=>'Nama supplier harus diisi.',
    's03'=>'Kode supplier sudah ada.',
    's04'=>'Nama supplier sudah ada.',
    's05'=>'Tidak dapat menghapus supplier ini, sudah ada transaksi.',
    's06'=>'',

    'pe01'=>'Kode harus diisi',
    'pe02'=>'Kode sudah ada',
    'pe03'=>'Deskripsi harus diisi',
    'pe04'=>'',
    'pe05'=>'Akun kredit harus diisi',
    'pe06'=>'Akun kredit tidak terdaftar',
    'pe07'=>'Akun debit belum diisi',
    'pe08'=>'Akun debit belum diisi pada baris [row]',
    'pe09'=>'Akun debit tidak terdaftar pada baris [row]',
    'pe10'=>'Saldo debit belum diisi pada baris [row]',
    'pe11'=>''
  ),
  'en'=>array(
      'i00'=>'Code required.',
      'i01'=>'Description required.',
      'i02'=>'Unit required.',
      'i03'=>'Inventory already exists.',
      'i04'=>'Unable to remove, transaction exists on this item.',
  )
);

$_LANG = array(
  'id'=>array(
    '001'=>'Ubah',
    '002'=>'Simpan',
    '003'=>'Tutup',
    '004'=>'Tidak ada data untuk ditampilkan.',
    '005'=>'Kustomisasi',

    'coa01'=>'Debit',
    'coa02'=>'Kredit',
    'coa03'=>'Asset',
    'coa04'=>'Expense',
    'coa05'=>'Others',
    'coa06'=>'Kode',
    'coa07'=>'Nama Akun',
    'coa08'=>'D/K',
    'coa09'=>'Tipe Akun',
    'coa10'=>'Saldo',
    'coa11'=>'Mata Uang',

    'c01'=>'Aktif',
    'c02'=>'Kode Pelanggan',
    'c03'=>'Nama Pelanggan',
    'c04'=>'Alamat',
    'c05'=>'Kota',
    'c06'=>'Negara',
    'c07'=>'Telpon 1',
    'c08'=>'Telpon 2',
    'c09'=>'Fax 1',
    'c10'=>'Fax 2',
    'c11'=>'Kontak',
    'c12'=>'Email',
    'c13'=>'Catatan',
    'c14'=>'Diskon',
    'c15'=>'PPn',
    'c16'=>'Salesman',
    'c17'=>'Maksimal Lama Piutang',
    'c18'=>'Batas Kredit',
    'c19'=>'',
    'c20'=>'Alamat Penagihan',
    'c21'=>'Nomor NPWP',
    'c50'=>'Hapus [name]?',

    's01'=>'Kode',
    's02'=>'Nama Supplier',
    's03'=>'Alamat',
    's04'=>'Kota',
    's05'=>'Negara',
    's06'=>'Telp1',
    's07'=>'Telp2',
    's08'=>'Fax1',
    's09'=>'Fax2',
    's10'=>'Email',
    's11'=>'Kontak',
    's12'=>'Catatan',
    's13'=>'Aktif',
    's14'=>'Detil',
    's15'=>'Pembelian',
    's16'=>'Nomor NPWP',
    's17'=>'',
    's18'=>'',
    's19'=>'',

    'pe01'=>'Kode',
    'pe02'=>'Deskripsi',
    'pe03'=>'Akun Kredit',
    'pe04'=>'Total',
    'pe05'=>'Tanggal',
    'pe06'=>'',

    'jv01'=>'Tanggal',
    'jv02'=>'Deskripsi',
    'jv03'=>'Total',
    'jv04'=>'',
    'jv05'=>''
  )
);

function excmsg($idx, $params = null){
  global $_EXCEPTIONS;
  $exp = $_EXCEPTIONS[$_SESSION['lang']][$idx];
  $matches = array();
  preg_match_all('/(\[\w+\])/', $exp, $matches);
  if(isset($matches[0][0])){
    for($i = 0 ; $i < count($matches[0]) ; $i++){
      $match = $matches[0][$i];
      $key = substr($match, 1, strlen($match) - 2);
      if(isset($params[$key]))
        $exp = str_replace($match, $params[$key], $exp);
    }
  }
  return $exp;
}
function lang($idx, $params = null){
  global $_LANG;
  $value = $_LANG[$_SESSION['lang']][$idx];

  if(is_array($params)){
    preg_match_all('/(\[\w+\])/', $value, $matches);
    foreach($matches as $match){
      $key = substr($match[0], 1, strlen($match[0]) - 2);
      $value = str_replace($match[0], isset($params[$key]) ? $params[$key] : '', $value);
    }
  }

  return $value;

}

?>