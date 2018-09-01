<?php
require_once dirname(__FILE__) . '/log.php';

/**
 * Get category columns for grid
 * @return array
 * @since 1.0
 */
function category_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>0, 'name'=>'imageurl', 'text'=>'Gambar', 'width'=>40, 'type'=>'html', 'html'=>'categorylist_imageurl'),
    array('active'=>1, 'name'=>'name', 'text'=>'Nama Kategori', 'width'=>200),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'datetime')
  );
  return $columns;

}

/**
 * Get category detail
 * @param $columns
 * @param $filters
 * @return mixed
 * @throws Exception
 * @since 1.0
 */
function categorydetail($columns, $filters){

  $category = mysql_get_row('category', $filters, $columns);
  return $category;

}

/**
 * Get category list
 * @param $columns
 * @param $filters
 * @return array
 * @throws Exception
 * @since 1.0
 */
function categorylist($columns, $filters){

  return mysql_get_rows('category', $columns, $filters);

}

/**
 * Save new category
 * @param $category
 * @return array
 * @throws Exception
 * @since 1.0
 */
function categoryentry($category){

  $name = ov('name', $category);
  $frontend_active = ov('frontend_active', $category);
  $imageurl = ov('imageurl', $category);
  $exists = pmc("select count(*) from category where `name` = ?", array($name));
  if($exists) throw new Exception('Nama kategori sudah ada, silakan menggunakan nama lain.');

  $lock_file = __DIR__ . "/../usr/system/category_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $id = pmi("insert into category (`name`, frontend_active, imageurl) values (?, ?, ?)",
      array($name, $frontend_active, $imageurl));

  userlog('categoryentry', $category, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  return array('id'=>$id);

}

/**
 * Modify a category
 * @param $category
 * @throws Exception
 * @since 1.0
 */
function categorymodify($category){

  $id = ov('id', $category);
  $current = categorydetail(null, array('id'=>$id));

  if(!$current) throw new Exception('Kategori tidak dapat diubah, tidak terdaftar.');

  $lock_file = __DIR__ . "/../usr/system/category_modify_$id.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedcols = array();

  if(isset($category['name']) && $category['name'] != $current['name']){
    $name = $category['name'];
    $exists = pmc("select count(*) from category where `name` = ?", array($name));
    if($exists) throw new Exception('Nama kategori sudah ada, silakan menggunakan nama lain.');

    $updatedcols['name'] = $category['name'];
  }

  if(isset($category['frontend_active']) && $category['frontend_active'] != $current['frontend_active'])
    $updatedcols['frontend_active'] = $category['frontend_active'];

  if(isset($category['imageurl']) && $category['imageurl'] != $current['imageurl'])
    $updatedcols['imageurl'] = $category['imageurl'];

  if(count($updatedcols) > 0)
    mysql_update_row('category', $updatedcols, array('id'=>$id));

  userlog('categorymodify', $current, $updatedcols, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

}

/**
 * Remove a category
 * @param $filters
 * @throws Exception
 * @since 1.0
 */
function categoryremove($filters){

  $category = categorydetail(null, $filters);
  if(!$category) throw new Exception('Kategori tidak terdaftar, tidak ada yang dihapus.');

  $lock_file = __DIR__ . "/../usr/system/category_remove_" . $category['id'] . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

  pm("delete from category where `id` = ?", array($category['id']));

  userlog('categoryremove', $category, '', $_SESSION['user']['id'], $category['id']);

  fclose($fp);
  unlink($lock_file);

}

?>