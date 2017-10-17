<?php
/*
Plugin Name: Alibi Filter
Plugin URI:
Description: Hide faces.
Author: Takenori Okashita
Version: 0.0.1
Author URI: https://5010works.com
*/

# サイズ追加
//add_image_size( 'alibi', 1028 , 1028, false );
function add_alibi_image_sizes() {
	global $alibi_image_sizes;
	$alibi_image_sizes = array(
		'alibi' => array(
			'name'       => '顔出しNG', // 選択肢のラベル名
			'width'      => 1028,    // 最大画像幅
			'height'     => 1028,    // 最大画像高さ
			'crop'       => false,  // 切り抜きを行うかどうか
			'selectable' => true   // 選択肢に含めるかどうか
		),
	);
	foreach ( $alibi_image_sizes as $slug => $size ) {
		add_image_size( $slug, $size['width'], $size['height'], $size['crop'] );
	}
}
add_action( 'after_setup_theme', 'add_alibi_image_sizes' );

# メディアから選択できるようにする
//function wp_alibi_size($sizes) {
//	return array_merge($sizes, array(
//		'alibi' => '顔出しNG',
//	));
//}
//add_filter('image_size_names_choose', 'wp_alibi_size');
function add_alibi_image_size_select( $size_names ) {
	global $alibi_image_sizes;
	$alibi_sizes = get_intermediate_image_sizes();
	foreach ( $alibi_sizes as $alibi_size ) {
		if ( isset( $alibi_image_sizes[$alibi_size]['selectable'] ) && $alibi_image_sizes[$alibi_size]['selectable'] ) {
			$size_names[$alibi_size] = $alibi_image_sizes[$alibi_size]['name'];
		}
	}
	return $size_names;
}
add_filter( 'image_size_names_choose', 'add_alibi_image_size_select' );

$a = new alibiUploadImg;

class alibiUploadImg{
	function __construct(){
		//require_once( ABSPATH . 'wp-admin/includes/image.php' );
		add_filter( 'wp_generate_attachment_metadata', array( $this , 'alibi_img_meta' ),10 ,2 );
	}

	function alibi_img_meta($metadata, $imgID){
		$alibi_img = get_attached_file( $imgID, false ); // 画像パス取得
		$mime_type = get_post_mime_type( $imgID ); // 画像の Mime Type 取得
		if( $mime_type == 'image/jpeg' ){
			//*ここで画像処理をする
			//メインファイル変換
			$im = $this->alibi_image_create( $alibi_img );
			//フィルター
			$im = $this->alibi_image_gd_func($im);
			//メインファイル保存
			$res = $this->alibi_save_image($im, $alibi_img);
			# 画像を破棄
			imagedestroy($im);
			foreach ( $metadata['sizes'] as $akey => $aval) {
				//サブファイル名を取得
				$sub_imgPath = dirname($alibi_img).'/'.$aval[ 'file' ];
				//ここで画像処理をする
				//サブメインファイル変換
				$sub_im = $this->alibi_image_create( $sub_imgPath );
				//フィルター
				$sub_im = $this->alibi_image_gd_func( $sub_im );
				//メインファイル保存
				$res = $this->alibi_save_image($sub_im,$sub_imgPath );
				# 画像を破棄
				imagedestroy( $sub_im );
			}
		}
		return $metadata;
	}

	//######################
	//GDのフィルターを使ってみる
	//######################
	private function alibi_image_gd_func($im){

		if($im && imagefilter($im, IMG_FILTER_EMBOSS)){
			//$str = '※変換が成功しました。';
			return $im;
		}else{
			//$str = '変換が失敗しました。';
			return false;
		}
	}
	//######################
	//画像のイメージ作成(jpeg/png/gif)
	//######################
	private function alibi_image_create( $imgPath ){
		$mime = wp_check_filetype(basename( $imgPath ), null );
		if( $mime['type'] == "image/jpeg"){
			$im = imagecreatefromjpeg( $imgPath );
		}elseif( $mime['type'] == "image/png" ) {
			$im = imagecreatefrompng( $imgPath );
		}elseif( $mime['type'] == "image/gif" ) {
			$im = imagecreatefromgif( $imgPath );
		} else {
			$im = false;
		}
		return $im;
	}
	//######################
	//画像の保存
	//######################
	private function alibi_save_image( $im, $imgPath ){
		$mime = wp_check_filetype( basename( $imgPath ), null );
		if($mime['type'] == "image/jpeg"){
			imagejpeg($im, $imgPath);
		}elseif($mime['type'] == "image/png"){
			imagepng($im, $imgPath);
		}elseif($mime['type'] == "image/gif"){
			imagegif($im, $imgPath);
		}else{
			return false;
		}
	}

}


?>
