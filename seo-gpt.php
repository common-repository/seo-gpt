<?php 

/*
Plugin Name: SEO GPT
Plugin URI: https://seovendor.co/seo-gpt/
Description: Use our SEO GPT AI technology to automatcially write SEO titles and meta descriptions.
Author: SEO Vendor
Version: 0.4.0
Author URI: https://seovendor.co/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/* Exit if accessed directly. */
if (!defined('ABSPATH')) {
	exit;
} 

define('SGPT_VERSION', '0.3.1');
define('SGPT_PATH', plugin_dir_path(__FILE__));

// Create settings page
add_action('admin_menu', 'sgpt_settings');
function sgpt_settings() {
    // Add the menu item and page
    $page_title = 'SEO GPT Settings';
    $menu_title = 'SEO GPT';
    $capability = 'manage_options';
    $slug = 'sgpt_settings';
    $callback = 'sgpt_settting_callback';
    $icon = 'dashicons-admin-plugins';
    $position = 100;
    // add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
    add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $slug, $callback );
}
function sgpt_settting_callback() {
    ?>
	<div class="wrap">
    <h2>SEO GPT</h2>
    <form method="post" action="options.php">
        <?php
            settings_fields( 'sgpt_settings' );
            do_settings_sections( 'sgpt_settings' );
            submit_button();
        ?>
    </form>
    </div>
    <?php
}
add_action( 'admin_init', 'sgpt_sections_setup');
function sgpt_sections_setup() {
    add_settings_section( 'sgpt_key_section', 'SEO GPT Settings', 'sgpt_section_callback', 'sgpt_settings' );
}
function sgpt_section_callback(){

}
add_action( 'admin_init', 'sgpt_register_my_seting');
function sgpt_register_my_seting(){
	register_setting( 'sgpt_settings', 'sgpt_api_key' );
}
add_action( 'admin_init', 'sgpt_setup_fields');
function sgpt_setup_fields() {
    add_settings_field( 'sgpt_api_key', 'API KEY:','sgpt_field_callback', 'sgpt_settings', 'sgpt_key_section' );
}
function sgpt_field_callback( $arguments ) {
    echo '<input name="sgpt_api_key" id="sgpt_api_key" type="text" value="' . esc_attr(get_option( 'sgpt_api_key' )) . '" style="width:100%;" /><br><br>
    If you don\'t have your API KEY, get a free one <a href="https://seovendor.co/seo-gpt/" target="_blank">here</a>. After you sign up for a free account, go to your profile page to find your API KEY.';
    
}
// Create settings page end

function sgpt_enqueue(){
	if (is_admin()) {
		$sgpt_plugin_url = plugin_dir_url( __FILE__ );
		wp_enqueue_style(
			'sgpt_admin_style',  $sgpt_plugin_url . "/css/style.css", 
			false, 
			SGPT_VERSION
		);
		
		wp_enqueue_script(
			'sgpt_script', 
			$sgpt_plugin_url . "/js/script.js", 
			['jquery'], 
			SGPT_VERSION, 
			true
		);
		wp_localize_script('sgpt_script', 'sgpt_ajax_url', array(
		 'url' => admin_url('admin-ajax.php'),
		 'nonce' => wp_create_nonce('ajaxnonce')
		 ));
	}

}

add_action( 'admin_print_styles', 'sgpt_enqueue' );

/* Add metabox for posts/pages */
function sgpt_metaBoxes($postType) {
		add_meta_box(
			'seo-gpt', 
			__('SEO GPT'), 
			'sgpt_renderMetaBox',
			$postType
		);
	}
add_action('add_meta_boxes', 'sgpt_metaBoxes');

function sgpt_renderMetaBox($post) {
    $post_status = get_post_status( $post->ID );
	wp_nonce_field(SGPT_PATH, 'sgpt_nonce');
	$seo_gpt_keyword = esc_html(get_post_meta($post->ID, 'seo_gpt_keyword', true));
	$seo_gpt_brand = esc_html(get_post_meta($post->ID, 'seo_gpt_brand', true));
	$sg_content = "";
	$sg_content .= '<div id="seo-gpt-preview" class="seo-gpt-box">';
	$sg_content .= '<div class="seo-gpt-section">';
	$sg_content .= '<p>SEO GPT will automatically write your title tags and meta descriptions for you inside of your SEO plugin. Make sure you have one of these: Yoast SEO, All in One SEO, or Simple SEO plugin activated. If you use Yoast or All in One SEO, place the keyowrd that you want to optimize in the plugin\'s focus keyword field.</p>';

	if (in_array('all-in-one-seo-pack/all_in_one_seo_pack.php', apply_filters('active_plugins', get_option('active_plugins')))){
		$plugin_priority = 1; // all in one seo
	}else if(in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins')))){
		$plugin_priority = 2; // yoast seo
	}else{
		$sg_content .= '<div class="input text">';
		$sg_content .= '<p class="post-attributes-label-wrapper">';
		$sg_content .= '<label for="seoGptKeyword" class="post-attributes-label">Focus keyword (Optional)</label>';
		$sg_content .= '</p>';
		$sg_content .= '<input id="seoGptKeyword" type="text" name="seo_gpt_keyword" value="'.esc_attr($seo_gpt_keyword).'">';
		$sg_content .= '</div>';
		$sg_content .= __('<p class="mt_0">Place the target keyword to optimize inside the content. Keywords have to be longer than 3 letters.</p>');
		$plugin_priority = 3; // simple seo
	}

	$sg_content .= '<div class="input text">';
	$sg_content .= '<p class="post-attributes-label-wrapper">';
	$sg_content .= '<label for="seoGptBrand" class="post-attributes-label">Brand (optional)</label>';
	$sg_content .= '</p>';
	$sg_content .= '<input id="seoGptBrand" type="text" name="seo_gpt_brand" value="'.esc_attr($seo_gpt_brand).'">';
	$sg_content .= '</div>';
	$sg_content .= __('<p class="mt_0">Enter a brand name if you want to emphasize it in the content.</p>');
	$sg_content.= '<p class="seo_gpt_error" style="color:red;"></p>';
	$sg_content.= '<input type="button" name="get_seo_gpt" id="get_seo_gpt" class="button button-primary" value="Write It" data-post_id="'.$post->ID.'" data-is_term="0" data-plugin_priority="'.$plugin_priority.'" data-post_type="'.$post->post_type.'">';
	if($post_status != "publish" && $post_status != "draft"){
	    $sg_content.= __('<p class="mt_1" style="color:red;">To use SEO GPT to create your optimized title and meta description, please save as draft or publish the content first.</p>');   
	}
	$sg_content .= '</div>';
	$sg_content .= '</div>';
	echo $sg_content;
}

/* Taxonomies */
	// add_action('category_add_form_fields', 'renderNewTaxonomyMetaBox');
	add_action('category_edit_form_fields', 'sgpt_renderEditTaxonomyMetaBox');
	// add_action('post_tag_add_form_fields', 'renderNewTaxonomyMetaBox');
	add_action('post_tag_edit_form_fields', 'sgpt_renderEditTaxonomyMetaBox');
	add_action('edited_category', 'sgpt_saveTaxonomy');
	add_action('edited_post_tag', 'sgpt_saveTaxonomy');

	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		// add_action('product_cat_add_form_fields', 'renderNewTaxonomyMetaBox');
		add_action('product_cat_edit_form_fields', 'sgpt_renderEditTaxonomyMetaBox');
		// add_action('product_tag_add_form_fields', 'renderNewTaxonomyMetaBox');
		add_action('product_tag_edit_form_fields', 'sgpt_renderEditTaxonomyMetaBox');
		add_action('edited_product_cat', 'sgpt_saveTaxonomy');
		add_action('edited_product_tag', 'sgpt_saveTaxonomy');
	}

	// function renderNewTaxonomyMetaBox() {
	// 	echo '<div class="form-field" style="border: 1px solid #b3a7a7; padding: 5px; border-radius: 5px;">
	// 		<label for="term_meta[seo_gpt_title]">'.__('Focus keyword (Optional)').'</label>
	// 		<input type="text" name="term_meta[seo_gpt_title]" id="term_meta[seo_gpt_title]" value="">
	// 		<input type="button" name="get_seo_gpt" id="get_seo_gpt" class="button button-primary" value="Write It" data-post_id="0">
	// 	</div>';
	// }

	function sgpt_renderEditTaxonomyMetaBox($term) {
		$term_meta = get_option("taxonomy_".$term->term_id);
		$seo_gpt_meta_keyword = null;
		if (isset($term_meta['seo_gpt_meta_keyword'])) {
			$seo_gpt_meta_keyword = $term_meta['seo_gpt_meta_keyword'];
		}
		$seo_gpt_meta_brand = null;
		if (isset($term_meta['seo_gpt_meta_brand'])) {
			$seo_gpt_meta_brand = $term_meta['seo_gpt_meta_brand'];
		}
		if (in_array('all-in-one-seo-pack/all_in_one_seo_pack.php', apply_filters('active_plugins', get_option('active_plugins')))){
			$plugin_priority = 1; // all in one seo
			$seo_gpt_keyword_field = 'none';
		}else if(in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins')))){
			$plugin_priority = 2; // yoast seo
			$seo_gpt_keyword_field = 'none';
		}else{
			$plugin_priority = 3; // simple seo
			$seo_gpt_keyword_field = 'table-row';
		}
		echo '
		<tr class="form-field sgpt_term_block">
		    <td colspan="2" class="sgpt_td">
    			<table width="100%">
    				<tr>
    					<td colspan="2" class="sgpt_title">
    						<h2>SEO GPT</h2>
    						<hr>
    					</td>
    				</tr>
    				<tr class="form-field field_seo_gpt_keyword" style="display:'.$seo_gpt_keyword_field.'">
            			<th scope="row" valign="top" style="padding-bottom:5px;padding-left: 15px;"><label for="term_meta_seo_gpt[seo_gpt_meta_keyword]">'.__('Focus keyword (Optional)').'</label></th>
            			<td style="padding-bottom:5px;"><input type="text" name="term_meta_seo_gpt[seo_gpt_meta_keyword]" id="term_meta_seo_gpt[seo_gpt_meta_keyword]" class="seo_gpt_meta_keyword" value="'.esc_attr($seo_gpt_meta_keyword).'">
            				<p class="mt_0">Place the target keyword to optimize inside the content. Keywords have to be longer than 3 letters.</p>
            			</td>
            		</tr>
            		<tr class="form-field">
            			<th scope="row" valign="top" style="padding-bottom:5px;padding-left: 15px;"><label for="term_meta_seo_gpt[seo_gpt_meta_brand]">'.__('Brand (Optional)').'</label></th>
            			<td style="padding-bottom:5px;"><input type="text" name="term_meta_seo_gpt[seo_gpt_meta_brand]" id="term_meta_seo_gpt[seo_gpt_meta_brand]" class="seo_gpt_meta_brand" value="'.esc_attr($seo_gpt_meta_brand).'">
            				<p class="mt_0">Enter a brand name if you want to emphasize it in the content.</p>
            			</td>
            		</tr>
            		<tr class="form-field" style="padding-bottom:20px;">
                		<td></td>
                		<td style="padding: 0 0 12px 12px;">
                		<p class="seo_gpt_term_error" style="color:red;"></p>
                		<input type="button" name="get_seo_gpt" id="get_seo_gpt" class="button button-primary" value="Write It" data-post_id="'.$term->term_id.'" data-is_term="1" data-plugin_priority="'.$plugin_priority.'" data-post_type="'.$term->taxonomy.'"></td>
                	</tr>
    			</table>
			</td>
		</tr>';
	}

// handel ajax call action
add_action('wp_ajax_sgpt_get_content', 'sgpt_get_content');
function sgpt_get_content(){
	$api_key = get_option('sgpt_api_key')?get_option('sgpt_api_key'):'';
	if($api_key != ""){
		if (in_array('cds-simple-seo/cds-simple-seo.php', apply_filters('active_plugins', get_option('active_plugins'))) || in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins'))) || in_array('all-in-one-seo-pack/all_in_one_seo_pack.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			$post_id = sanitize_text_field($_POST['post_id']);
			$keyword = sanitize_text_field($_POST['keyword']);
			$is_term = sanitize_text_field($_POST['is_term']);
			$post_type = sanitize_text_field($_POST['post_type']);
			if($post_type == "page"){
				$type = "Page";
			}else if($post_type == "post"){
				$type = "Post";
			}else if($post_type == "product"){
				$type = "Product";
			}else if($post_type == "category"){
				$type = "Post Category";
			}else if($post_type == "post_tag"){
				$type = "Post Tag";
			}else if($post_type == "product_cat"){
				$type = "Product Category";
			}else if($post_type == "product_tag"){
				$type = "Product Tag";
			}else{
				$type = "";
			}
			if($is_term == 1){
				$URL = urlencode(trim(get_category_link($post_id)));
				if($keyword != ""){
					$KW = urlencode(trim($keyword));
				}else{
					$KW = urlencode(trim(get_the_category_by_ID($post_id)));
				}
			}else{
				$URL = urlencode(trim(get_permalink($post_id)));
				if($keyword != ""){
					$KW = urlencode(trim($keyword));
				}else{
					$KW = urlencode(trim(get_the_title($post_id)));	
				}
			}

			$data = array();
			$ContentTypeTitle = 0;
			$ContentTypeDescription = 1;
			$ContentLengthTitle = 10;
			$ContentLengthDescription = 25;
			$qty = 1;
			$brand = sanitize_text_field(urlencode(trim($_POST['brand'])));
			$is_error = false;
			
			$post_status = get_post_status( $post_id );
			if($post_type == "category" || $post_type == "post_tag" || $post_type == "product_cat" || $post_type == "product_tag"){
			    $post_status = "publish";
			}
            if($post_status == "publish" || $post_status == "draft"){
    			if ( strlen($brand) < 60 && strlen($URL) < 200 && strlen($KW) < 60 ) {
    				if ( strlen($KW) > 3 && stripos( $URL,"http" ) !== false) {
    					
    					$gpturl = 'https://ai.seovendor.co/gpt-get.php?web=' . $URL . '&kw=' . $KW . '&typeid=' . $ContentTypeTitle . '&brand=' . $brand .'&len=' . $ContentLengthTitle . '&k='. $api_key;
    
    					$user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A";
    
    					$responseArray = wp_remote_get($gpturl, array(
    					  'timeout' => 30,
    					  'user-agent' => $user_agent,
    					  'sslverify' => false,
    					));
    					$contentforTitle = wp_remote_retrieve_body($responseArray);
    					if(!is_wp_error( $responseArray) && trim($contentforTitle,'"') != "") {
    						$data['title'] = trim($contentforTitle,'"');
    					}else{
    						$is_error = true;
    						$msg_text = "Error occurred while generating content. Please try again.";
    						$returnResponse = array("status" => "error", "message"=> $msg_text, "data" => $data);	
    					}
    				}else{
    					if(strlen($KW) <= 3){
    						$is_error = true;
    						$msg_text = "Please check the title of ".$type." or Focus Keyword. Minimum of 4 characters required.";
    						$returnResponse = array("status" => "error", "message"=> $msg_text, "data" => $data);	
    					}elseif(stripos( $URL,"http" ) === false){
    						$msg_text = "Please check the URL of ".$type.".";
    						$returnResponse = array("status" => "error", "message"=> $msg_text, "data" => $data);
    						$is_error = true;
    					}else{
    						$is_error = true;
    						$msg_text = "Please check the URL of ".$type.".";
    						$returnResponse = array("status" => "error", "message"=> $msg_text, "data" => $data);
    					}
    				}
    			}else{
    				if(strlen($URL) > 200 ){
    					$is_error = true;
    					$msg_text = "Please check the URL of ".$type." . Maximum 200 characters allowed in the URL.";
    					$returnResponse = array("status" => "error", "message"=> $msg_text, "data" => $data);
    				}elseif(strlen($KW) > 60){
    					$is_error = true;
    					$msg_text = "Please check the title of ".$type." or Focus Keyword. Maximum 60 characters allowed in the Keyword.";
    					$returnResponse = array("status" => "error", "message"=> $msg_text, "data" => $data);	
    				}else{
    					$is_error = true;
    					$msg_text = "Please check the title and URL of ".$type.". Maximum 60 characters allowed in the Keyword, Maximum 200 characters allowed in the URL.";
    					$returnResponse = array("status" => "error", "message"=> $msg_text, "data" => $data);
    				}
    			}
    
    			if($is_error == false){
    				$is_error_desc = false;
    				if ( strlen($brand) < 60 && strlen($URL) < 200 && strlen($KW) < 60 ) {
    					if ( strlen($KW) > 3 && stripos( $URL,"http" ) !== false) {
    
    						$gpturl_desc = 'https://ai.seovendor.co/gpt-get.php?web=' . $URL . '&kw=' . $KW . '&typeid=' . $ContentTypeDescription . '&brand=' . $brand .'&len=' . $ContentLengthDescription . '&k='. $api_key;	
    
    						$user_agent_desc = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A";
    
    						$responseArrayDesc = wp_remote_get($gpturl_desc, array(
    						  'timeout' => 30,
    						  'user-agent' => $user_agent_desc,
    						  'sslverify' => false,
    						));
    						$contentforDesc = wp_remote_retrieve_body($responseArrayDesc);
    						if(!is_wp_error( $responseArrayDesc ) && trim($contentforDesc,'"') != "") {
    							$data['description'] = trim($contentforDesc,'"');
    						}else{
    							$is_error_desc = true;
    							$returnResponse = array("status" => "error", "message"=> "Error occurred while generating content. Please try again.", "data" => $data);
    						}
    					}else{
    						if(strlen($KW) <= 3){
    							$is_error_desc = true;
    							$returnResponse = array("status" => "error", "message"=> "Please enter a minimum of 3 characters.", "data" => $data);	
    						}elseif(stripos( $URL,"http" ) === false){
    							$returnResponse = array("status" => "error", "message"=> "Please enter a valid URL.", "data" => $data);
    							$is_error_desc = true;
    						}else{
    							$is_error_desc = true;
    							$returnResponse = array("status" => "error", "message"=> "Please enter valid data.", "data" => $data);
    						}
    					}
    				}else{
    					if(strlen($URL) > 200 ){
    						$is_error_desc = true;
    						$returnResponse = array("status" => "error", "message"=> "Maximum 200 characters allowed in the URL.", "data" => $data);
    					}elseif(strlen($KW) > 60){
    						$is_error_desc = true;
    						$returnResponse = array("status" => "error", "message"=> "Maximum 60 characters allowed in the Keyword.", "data" => $data);	
    					}else{
    						$is_error_desc = true;
    						$returnResponse = array("status" => "error", "message"=> "Maximum 200 characters allowed in the URL, and maximum 60 characters allowed in the Keyword.", "data" => $data);
    					}
    				}
    				if($is_error_desc == false){
    					$returnResponse = array("status" => "success", "message"=> "", "data" => $data);
    					echo json_encode($returnResponse);
    		        	die();
    				}else{
    					echo json_encode($returnResponse);
    		        	die();
    				}
    			}else{
    				echo json_encode($returnResponse);
    		        die();
    			}
			}else{
                $returnResponse = array("status" => "sseo_error", "message"=> "To use SEO GPT to create your optimized title and meta description, please save as draft or publish the content first.");
        		echo json_encode($returnResponse);
        	    die();
            }
		}else{
			$returnResponse = array("status" => "sseo_error", "message"=> "Required plugin not found, Please activate one of these SEO plugins: Yoast SEO, All in One SEO or Simple SEO plugin.");
			echo json_encode($returnResponse);
		        die();
		}
	}else{
		$returnResponse = array("status" => "sseo_error", "message"=> "Please add your API KEY in the SEO GPT settings page.");
		echo json_encode($returnResponse);
	    die();
	}
	
}
// save focus keyword value in database
	add_action('save_post', 'sgpt_saveSeoGptMeta');

	function sgpt_saveSeoGptMeta($postId){
		$seo_gpt_keyword = get_post_meta($postId, 'seo_gpt_keyword', true);
        if (isset($_POST['seo_gpt_keyword'])) {
            update_post_meta($postId, 'seo_gpt_keyword', sanitize_text_field($_POST['seo_gpt_keyword']));
        }
		if (isset($_POST['seo_gpt_brand'])) {
            update_post_meta($postId, 'seo_gpt_brand', sanitize_text_field($_POST['seo_gpt_brand']));
        }
	}

	function sgpt_saveTaxonomy($term_id) {
		if (isset($_POST['term_meta_seo_gpt'])) {
			$term_meta = get_option("taxonomy_".$term_id);
			$cat_keys = array_keys($_POST['term_meta_seo_gpt']);
			foreach ($cat_keys as $key) {
				if (isset($_POST['term_meta_seo_gpt'][$key])) {
					$term_meta[$key] = sanitize_text_field($_POST['term_meta_seo_gpt'][$key]);
				}
			}
			update_option("taxonomy_".$term_id, stripslashes_deep($term_meta));
		}
	}
	
	// set default slug to title
function myplugin_update_slug( $data, $postarr ) {
	$slug_array = array('1','1-1','1-2','1-3','1-4','1-5','2','2-1','2-2','2-3','2-4','2-5','2-6','2-7','2-8','2-9','3','3-1','3-2','3-3','3-4','3-5','4','4-1','4-2','4-3','4-4','4-5','5','5-1','6','6-1','7','7-1','8','8-1','9','9-1');
    if ($data['post_type'] === 'post' && in_array( $data['post_name'], $slug_array ) ) {
        $data['post_name'] = sanitize_title( $data['post_title'] );
    }
    return $data;
}
add_filter( 'wp_insert_post_data', 'myplugin_update_slug', 99, 2 );
?>