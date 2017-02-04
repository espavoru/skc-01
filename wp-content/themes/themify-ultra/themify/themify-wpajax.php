<?php
/***************************************************************************
 *
 * 	----------------------------------------------------------------------
 * 						DO NOT EDIT THIS FILE
 *	----------------------------------------------------------------------
 * 
 *  				     Copyright (C) Themify
 * 
 *	----------------------------------------------------------------------
 *
 ***************************************************************************/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Initialize actions
add_action('delete_attachment', 'themify_delete_attachment');
$themify_ajax_actions = array(
	'plupload',
	'delete_preset',
        'get_404_pages',
	'remove_post_image',
	'remove_video',
	'save',
	'reset_styling',
	'reset_setting',
	'pull',
	'add_link_field',
	'media_lib_browse',
	'refresh_webfonts',
	'import_sample_content',
	'erase_sample_content',
	'notice_dismiss',
        'clear_all_caches'
);
foreach($themify_ajax_actions as $action){
	add_action('wp_ajax_themify_' . $action, 'themify_' . $action);
}
add_action('added_post_meta', 'themify_after_post_meta', 10, 4);
add_action('updated_post_meta', 'themify_after_post_meta', 10, 4);
add_action('deleted_post_meta', 'themify_deleted_post_meta', 10, 4);

//Show 404 page in autocomplete
function themify_get_404_pages(){
    if(!empty($_POST['term'])){
        $args = array(
                'sort_order' => 'asc',
                'sort_column' => 'post_title',
                'post_type' => 'page',
                's'=>  sanitize_text_field($_POST['term']),
                'post_status' => 'publish',
                'posts_per_page' => 15
        );
        add_filter( 'posts_search', 'themify_posts_where', 10, 2 );
        $terms = new WP_Query($args);
        $items = array();
        if($terms->have_posts()){
            while ($terms->have_posts()){
                $terms->the_post();
                $items[] = array('value'=>  get_the_ID(),'label'=>  get_the_title());
            }
        }
        echo wp_json_encode($items);
    }
    wp_die();
}

function  themify_clear_all_caches(){
    check_ajax_referer('ajax-nonce', 'nonce');
    // Clear the cache
    TFCache::removeDirectory(TFCache::get_cache_dir());
    wp_send_json_success('success');
}

//Search only by post title
function themify_posts_where($search, &$wp_query ){       
    if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
        global $wpdb;

        $q = $wp_query->query_vars;
        $n = ! empty( $q['exact'] ) ? '' : '%';

        $search = array();
        $search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $wpdb->esc_like( implode(' ',$q['search_terms']) ) . $n );

        if ( ! is_user_logged_in() )
            $search[] = "$wpdb->posts.post_password = ''";

        $search = ' AND ' . implode( ' AND ', $search );
    }
    return $search;
}
/**
 * AJAX - Plupload execution routines
 * @since 1.2.2
 * @package themify
 */
function themify_plupload() {
    $imgid = $_POST['imgid'];
    check_ajax_referer($imgid . 'themify-plupload');
	/** Check whether this image should be set as a preset. @var String */
	$haspreset = isset( $_POST['haspreset'] )? $_POST['haspreset'] : '';
	/** Decide whether to send this image to Media. @var String */
	$add_to_media_library = isset( $_POST['tomedia'] ) ? $_POST['tomedia'] : false;
	/** If post ID is set, uploaded image will be attached to it. @var String */
	$postid = isset( $_POST['topost'] )? $_POST['topost'] : '';
 
    /** Handle file upload storing file|url|type. @var Array */
    $file = wp_handle_upload($_FILES[$imgid . 'async-upload'], array('test_form' => true, 'action' => 'themify_plupload'));
	
	// if $file returns error, return it and exit the function
	if ( isset( $file['error'] ) && ! empty( $file['error'] ) ) {
		echo json_encode($file);
		exit;
	}

	//let's see if it's an image, a zip file or something else
	$ext = explode('/', $file['type']);
	
	// Import routines
	if( 'zip' == $ext[1] || 'rar' == $ext[1] || 'plain' == $ext[1] ){
		
		$url = wp_nonce_url('admin.php?page=themify');

		if (false === ($creds = request_filesystem_credentials($url) ) ) {
			return true;
		}
		if ( ! WP_Filesystem($creds) ) {
			request_filesystem_credentials($url, '', true);
			return true;
		}
		
		global $wp_filesystem;
		
		if( 'zip' == $ext[1] || 'rar' == $ext[1] ) {
			unzip_file($file['file'], THEME_DIR);
			if( $wp_filesystem->exists( THEME_DIR . '/data_export.txt' ) ){
				$data = $wp_filesystem->get_contents( THEME_DIR . '/data_export.txt' );
				themify_set_data( unserialize( $data ) );
				$wp_filesystem->delete(THEME_DIR . '/data_export.txt');
				$wp_filesystem->delete($file['file']);
			} else {
				_e('Data could not be loaded', 'themify');
			}
		} else {
			if( $wp_filesystem->exists( $file['file'] ) ){
				$data = $wp_filesystem->get_contents( $file['file'] );
				themify_set_data( unserialize( $data ) );
				$wp_filesystem->delete($file['file']);
			} else {
				_e('Data could not be loaded', 'themify');
			}
		}
		
	} else {
		//Image Upload routines
		if( 'tomedia' == $add_to_media_library ){
			
			// Insert into Media Library
			// Set up options array to add this file as an attachment
	        $attachment = array(
	            'post_mime_type' => sanitize_mime_type($file['type']),
	            'post_title' => str_replace('-', ' ', sanitize_file_name(pathinfo($file['file'], PATHINFO_FILENAME))),
	            'post_status' => 'inherit'
	        );
			
			if( $postid ){
				$attach_id = wp_insert_attachment( $attachment, $file['file'], $postid );
			} else {
				$attach_id = wp_insert_attachment( $attachment, $file['file'] );
			}
			$file['id'] = $attach_id;

			// Common attachment procedures
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		    $attach_data = wp_generate_attachment_metadata( $attach_id, $file['file'] );
		    wp_update_attachment_metadata($attach_id, $attach_data);
			
			if( $postid ) {
				
				$full = wp_get_attachment_image_src( $attach_id, 'full' );

				update_post_meta($postid, $_POST['fields'], $full[0]);
				update_post_meta($postid, '_'.$_POST['fields'] . '_attach_id', $attach_id);
				
				$thumb = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
				
				//Return URL for the image field in meta box
				$file['thumb'] = $thumb[0];
				
			}
		}
		/**
		 * Presets like backgrounds and such
		 */
		if( 'haspreset' == $haspreset ){
			// For the sake of predictability, we're not adding this to Media.
			$presets = get_option('themify_background_presets');
			$presets[ $file['file'] ] = $file['url'];
			update_option('themify_background_presets', $presets);
			
			/*$presets_attach_id = get_option('themify_background_presets_attach_id');
			$presets_attach_id[ $file['file'] ] = $attach_id;
			update_option('themify_background_presets_attach_id', $presets_attach_id);*/
		}
		
	}
	$file['type'] = $ext[1];
	// send the uploaded file url in response
	echo json_encode($file);
    exit;
}

/**
 * Sync post thumbnail and post_image field
 */
function themify_after_post_meta( $meta_id, $post_id, $meta_key, $meta_value ) {
    if ( '_thumbnail_id' == $meta_key ) {
        $attach_id = get_post_thumbnail_id($post_id);
		$full = wp_get_attachment_image_src( $attach_id, 'full' );
		//set_post_thumbnail($post_id, $attach_id);
		update_post_meta($post_id, 'post_image', $full[0]);
    }
}
/**
 * Delete post meta if post thumbnail was deleted
 */
function themify_deleted_post_meta( $deleted_meta_ids, $post_id, $meta_key, $only_delete_these_meta_values ){
    if ( '_thumbnail_id' == $meta_key ) {
    	delete_post_meta($post_id, 'post_image');
    }
}

function themify_save_post_image( $post_id ) {
	if ( !wp_is_post_revision( $post_id ) ) {
		if( '' != ($attach_id = get_post_meta($post_id, '_thumbnail_id', true)) ){
			$full = wp_get_attachment_image_src( $attach_id, 'full' );
			update_post_meta($post_id, 'post_image', $full[0]);
		}
	}
}
add_action( 'save_post', 'themify_save_post_image', 18 );

/**
 * AJAX - Delete preset image
 * @since 1.2.2
 * @package themify
 */
function themify_delete_preset(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	
	if( isset($_POST['file']) ){
		$file = $_POST['file'];
		$presets = get_option('themify_background_presets');
		
		if(file_exists(THEME_DIR . '/uploads/bg/' . $file)){
			// It's one of the presets budled with the theme
			unlink(THEME_DIR . '/uploads/bg/' . $file);
			echo 'Deleted ' . THEME_DIR . '/uploads/bg/' . $file;
		} else {
			// It's one of the presets uploaded by user to media
			$presets_attach_id = get_option('themify_background_presets_attach_id');
			//wp_delete_attachment($presets_attach_id[stripslashes($file)], true);
			@ unlink(stripslashes($file));
			unset($presets_attach_id[stripslashes($file)]);
			update_option('themify_background_presets_attach_id', $presets_attach_id);
		}
		unset($presets[ stripslashes($file) ]);
		update_option('themify_background_presets', $presets);
	}
	die();
}

/**
 * When user deletes image from gallery, it will delete the post_image custom field.
 * @since 1.2.2
 * @package themify
 */
function themify_delete_attachment($attach_id){
	$attdata = get_post( $attach_id );
	if ( isset( $attdata->post_parent ) && ! empty( $attdata->post_parent ) ) {
		delete_post_meta( $attdata->post_parent, 'post_image' );
	}
}

/**
 * AJAX - Remove image assigned in Themify custom panel. Clears post_image and _thumbnail_id field.
 * @since 1.1.5
 * @package themify
 */
function themify_remove_post_image(){
	check_ajax_referer( 'themify-custom-panel', 'nonce' );
	$is_post_thumbnail = (isset($_POST['attach_id'])) ? false : true ;
	
	if( isset($_POST['postid']) && isset($_POST['customfield'])){
		// Un attach image from custom field
		delete_post_meta($_POST['postid'], '_'.$_POST['customfield'].'_attach_id');
		
		// Clear Themify custom field for post image
		update_post_meta($_POST['postid'], $_POST['customfield'], '');
		
		if( $is_post_thumbnail ) {
			// Clear hidden custom field
			update_post_meta($_POST['postid'], '_thumbnail_id', array());
		}
	} else {
		_e('Missing vars: post ID and custom field.', 'themify');
	}
	die();
}

/**
 * AJAX - Remove image assigned in Themify custom panel. Clears post_image and _thumbnail_id field.
 * @since 1.7.4
 * @package themify
 */
function themify_remove_video() {
	check_ajax_referer( 'themify-custom-panel', 'nonce' );
	if ( isset( $_POST['postid'] ) && isset( $_POST['customfield'] ) ) {
		update_post_meta( $_POST['postid'], $_POST['customfield'], '' );
	} else {
		_e( 'Missing vars: post ID and custom field.', 'themify' );
	}
	die();
}

/**
 * AJAX - Save user settings
 * @since 1.1.3
 * @package themify
 */
function themify_save(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	$data = explode("&", $_POST['data']);
	$temp = array();
	foreach($data as $a){
		$v = explode("=", $a);
		$temp[$v[0]] = urldecode( str_replace("+"," ",preg_replace_callback('/%([0-9a-f]{2})/i', 'themify_save_replace_cb', urlencode($v[1]))) );
	}
	themify_set_data($temp);
	_e('Your settings were saved', 'themify');
       $remove = $temp['setting-script_minification'] === 'disable' || !isset($temp['setting-cache_gzip']);
	TFCache::removeDirectory(TFCache::get_cache_dir() . 'scripts/');
	TFCache::removeDirectory(TFCache::get_cache_dir() . 'styles/');
	TFCache::rewrite_htaccess($remove);
	wp_die();
}

/**
 * Replace callback for preg_replace_callback used in themify_save().
 * 
 * @since 2.2.5
 * 
 * @param array $matches 0 complete match 1 first match enclosed in (...)
 * 
 * @return string One character specified by ascii.
 */
function themify_save_replace_cb( $matches ) {
	// "chr(hexdec('\\1'))"
	return chr( hexdec( $matches[1] ) );
}

/**
 * AJAX - Reset Styling
 * @since 1.1.3
 * @package themify
 */
function themify_reset_styling(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	$data = explode("&", $_POST['data']);
	$temp_data = array();
	foreach($data as $a){
		$v = explode("=", $a);
		$temp_data[$v[0]] = str_replace("+"," ",preg_replace_callback('/%([0-9a-f]{2})/i', 'themify_save_replace_cb', $v[1]));
	}
	$temp = array();
	foreach($temp_data as $key => $val){
		if(strpos($key, 'styling') === false){
			$temp[$key] = $val;
		}
	}
	print_r(themify_set_data($temp));
	delete_option( 'themify_has_styling_data' );
	die();
}

/**
 * AJAX - Reset Settings
 * @since 1.1.3
 * @package themify
 */
function themify_reset_setting(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	$data = explode("&", $_POST['data']);
	$temp_data = array();
	foreach($data as $a){
		$v = explode("=", $a);
		$temp_data[$v[0]] = str_replace("+"," ",preg_replace_callback('/%([0-9a-f]{2})/i', 'themify_save_replace_cb', $v[1]));
	
	}
	$temp = array();
	foreach($temp_data as $key => $val){
		// Don't reset if it's not a setting or the # of social links or a social link or the Hook Contents
		if(strpos($key, 'setting') === false || strpos($key, 'hooks') || strpos($key, 'link_field_ids') || strpos($key, 'themify-link') || strpos($key, 'twitter_settings') || strpos($key, 'custom_css')){
			$temp[$key] = $val;
		}
	}
        $temp['setting-script_minification'] = 'disable';
	print_r(themify_set_data($temp));
	die();
}

/**
 * Export Settings to zip file and prompt to download
 * NOTE: This function is not called through AJAX but it is kept here for consistency. 
 * @since 1.1.3
 * @package themify
 */
function themify_export() {
	if ( isset( $_GET['export'] ) && 'themify' == $_GET['export'] ) {
		check_admin_referer( 'themify_export_nonce' );
		$theme = wp_get_theme();
		$theme_name = $theme->display('Name');

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		global $wp_filesystem;

		if(class_exists('ZipArchive')){
			$theme_name_lc = strtolower($theme_name);
			$datafile = 'data_export.txt';
			$wp_filesystem->put_contents( $datafile, serialize( themify_get_data() ) );
			$files_to_zip = array(
				'../wp-content/themes/' . $theme_name_lc . '/custom-modules.php',
				'../wp-content/themes/' . $theme_name_lc . '/custom-functions.php',
				'../wp-content/themes/' . $theme_name_lc . '/custom-config.php',
				'../wp-content/themes/' . $theme_name_lc . '/custom_style.css',
				$datafile
			);
			//print_r($files_to_zip);
			$file = $theme_name . '_themify_export_' . date('Y_m_d') . '.zip';
			$result = themify_create_zip( $files_to_zip, $file, true );
		}
		if(isset($result) && $result){
			if ( ( isset( $file ) ) && ( $wp_filesystem->exists( $file ) ) ) {
				ob_start();
				header('Pragma: public');
				header('Expires: 0');
				header("Content-type: application/force-download");
				header('Content-Disposition: attachment; filename="' . $file . '"');
				header("Content-Transfer-Encoding: Binary"); 
				header("Content-length: ".filesize($file));
				header('Connection: close');
				ob_clean();
				flush();
				echo $wp_filesystem->get_contents( $file );
				$wp_filesystem->delete( $datafile );
				$wp_filesystem->delete( $file );
				exit();
			} else {
				return false;
			}
		} else {
			if(ini_get('zlib.output_compression')) {
				/**
				 * Turn off output buffer compression for proper zip download.
				 * @since 2.0.2
				 */
				$srv_stg = 'ini' . '_' . 'set';
				call_user_func( $srv_stg, 'zlib.output_compression', 'Off');
			}
			ob_start();
			header('Content-Type: application/force-download');
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private',false);
			header('Content-Disposition: attachment; filename="'.$theme_name.'_themify_export_'.date("Y_m_d").'.txt"');
			header('Content-Transfer-Encoding: binary');
			ob_clean();
			flush();
			echo serialize(themify_get_data());
			exit();
		}
	}
	return false;
}
add_action('after_setup_theme', 'themify_export', 10);

/**
 * Pull data for inspection
 * @since 1.1.3
 * @package themify
 */
function themify_pull(){
	print_r(themify_get_data());
	die();
}

function themify_add_link_field(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	
	if( isset($_POST['fid']) ) {
		$hash = $_POST['fid'];
		$type = isset( $_POST['type'] )? $_POST['type'] : 'image-icon';
		echo themify_add_link_template( 'themify-link-'.$hash, array(), true, $type);
		exit();
	}
}

/**
 * Set image from wp library
 * @since 1.2.9
 * @package themify
 */
function themify_media_lib_browse() {
	if ( ! wp_verify_nonce( $_POST['media_lib_nonce'], 'media_lib_nonce' ) ) die(-1);

	$file = array();
	$postid = $_POST['post_id'];
	$attach_id = $_POST['attach_id'];

	$full = wp_get_attachment_image_src( $attach_id, 'full' );
	if( $_POST['featured'] ){
		//Set the featured image for the post
		set_post_thumbnail($postid, $attach_id);
	}
	update_post_meta($postid, $_POST['field_name'], $full[0]);
	update_post_meta($postid, '_'.$_POST['field_name'] . '_attach_id', $attach_id);

	$thumb = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
				
	//Return URL for the image field in meta box
	$file['thumb'] = $thumb[0];

	echo json_encode($file);

	exit();
}

/**
 * Delete WebFonts cache
 * @since 1.3.9
 */
function themify_refresh_webfonts() {
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	delete_transient( 'themify_google_fonts_transient' );
	echo 'WebFonts refreshed.';
	die();
}

/**
 * Imports sample contents to replicate the demo site.
 *
 * @since 1.7.6
 */
function themify_import_sample_content() {
	require_once( THEMIFY_DIR . '/themify-demo-import.php' );
	// check_ajax_referer( 'ajax-nonce', 'nonce' );
	themify_do_import_sample_contents();
	die( 'done' );
}

/**
 * Cleans up the sample content installed.
 *
 * @since 1.7.6
 */
function themify_erase_sample_content() {
	require_once( THEMIFY_DIR . '/themify-demo-import.php' );
	// check_ajax_referer( 'ajax-nonce', 'nonce' );
	themify_undo_import_sample_content();
	die( 'done' );
}

/**
 * Hide the import notice on the Themify screen.
 *
 * @since 1.8.2
 */
function themify_notice_dismiss() {
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	if ( isset( $_POST['notice'] ) && '' != $_POST['notice'] ) {
		update_option( 'themify_' . $_POST['notice'] . '_notice', 0 );
	}
	die();
}