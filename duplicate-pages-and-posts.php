<?php
/*
Plugin Name: Duplicate Pages and Posts
Description: A plugin to add Duplicate Page, post and custom post
Author: Geek Code Lab
Author URI: https://geekcodelab.com/
Version: 1.4.1
Text Domain: duplicate-pages-and-posts
*/

if(!defined('ABSPATH')) exit;

define("GWDP_BUILD","1.4.1");

if(!defined("GWDP_PLUGIN_DIR_PATH"))
	
	define("GWDP_PLUGIN_DIR_PATH",plugin_dir_path(__FILE__));	
	
if(!defined( "GWDP_PLUGIN_URL"))
	
	define( "GWDP_PLUGIN_URL",plugins_url().'/'.basename(dirname(__FILE__)));

if (!defined("GWDP_PLUGIN_BASENAME"))
define("GWDP_PLUGIN_BASENAME", plugin_basename(__FILE__));

if (!defined("GWDP_PLUGIN_DIR"))
	define("GWDP_PLUGIN_DIR", plugin_basename(__DIR__));

if (!defined("GWDP_PLUGIN_DIR_PATH"))
define("GWDP_PLUGIN_DIR_PATH", plugin_dir_path(__FILE__));

$plugin = plugin_basename(__FILE__);
add_filter( "plugin_action_links_$plugin", 'gwdp_add_plugin_link');
function gwdp_add_plugin_link( $links ) {
	$support_link = '<a href="https://geekcodelab.com/contact/" target="_blank" >' . __( 'Support', 'duplicate-pages-and-posts' ) . '</a>';
	array_unshift( $links, $support_link );

	$settings = '<a href="'. admin_url('options-general.php?page=options-duplicate-page-post') .'">' . __( 'Settings', 'duplicate-pages-and-posts' ) . '</a>';
	array_unshift( $links, $settings );

	return $links;
}

require_once( GWDP_PLUGIN_DIR_PATH .'functions.php' );
require_once(GWDP_PLUGIN_DIR_PATH . 'updater/updater.php');

add_action('admin_action_gwdp_duplicate_post_action', 'gwdp_duplicate_post_callback');
add_filter('post_row_actions', 'gwdp_duplicate_post_link', 10, 2);
add_filter('page_row_actions','gwdp_duplicate_post_link', 10, 2);
add_action('post_submitbox_start', 'gwdp_edit_post_link_callback' );
add_action('admin_bar_menu', 'gwdp_admin_bar_link_callback', 80);
add_action('admin_menu', 'gwdp_duplicate_post_options_callback');
add_action('admin_enqueue_scripts', 'gwdp_admin_scripts');
add_action('upgrader_process_complete', 'gwdp_updater_activate'); // remove  transient  on plugin  update


register_activation_hook( __FILE__, 'gwdp_plugin_duplicate_page' );


/* Default Setting on Installation */
function gwdp_plugin_duplicate_page(){
	gwdp_updater_activate();
	$gwdp_default_options = array(
		'gwdp_role_type' => array('administrator' => 'administrator'),
		'gwdp_post_type' => array('post' => 'post','page' => 'page'),
		'gwdp_shown_link' => array('to_post_list' => 'to_post_list'),
		'gwdp_duplicate_post_status' => 'draft',
		'gwdp_duplicate_post_redirect' => 'to_list_post',
		'gwdp_post_link_text' => 'Duplicate This'
	);
	$gwdp_options= (gwdp_allowed_options_callback() !== null);
	if($gwdp_options &&  current_user_can( 'manage_options' ))
	{
		add_option('gwdp_allowed_options', $gwdp_default_options);
	}
}


/* Duplicate Post Add Function */
function gwdp_duplicate_post_callback(){

	/* Get a nonce value */
	$nonce = $_REQUEST['nonce'];

	$post_id = (isset($_GET['post']) ? intval($_GET['post']) : intval($_POST['post']));

	if(wp_verify_nonce( $nonce, 'gwdp-duplicate-page-'.$post_id) && current_user_can('edit_posts')){
		global $wpdb;
		$gwdp_options=gwdp_allowed_options_callback();
		$prefix = isset($gwdp_options['gwdp_post_link_prefix']) && !empty($gwdp_options['gwdp_post_link_prefix']) ? $gwdp_options['gwdp_post_link_prefix'] . ' ' : '';
		$suffix = isset($gwdp_options['gwdp_post_link_suffix']) && !empty($gwdp_options['gwdp_post_link_suffix']) ? ' ' . $gwdp_options['gwdp_post_link_suffix'] : '';
		$selected_post_status = !empty($gwdp_options['gwdp_duplicate_post_status']) ? $gwdp_options['gwdp_duplicate_post_status'] : 'draft';		
		$selected_redirection = !empty($gwdp_options['gwdp_duplicate_post_redirect']) ? $gwdp_options['gwdp_duplicate_post_redirect'] : 'to_list_post';

		$post = get_post($post_id);
		
		$current_user = wp_get_current_user();
		$new_post_author = !empty($gwdp_options['gwdp_duplicate_post_author']) ? $gwdp_options['gwdp_duplicate_post_author'] : $current_user->ID;

		if (isset($post) && $post != null){
			
			$args = array(
				'comment_status' => $post->comment_status,
				'ping_status' => $post->ping_status,
				'post_author' => $new_post_author,
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_parent' => $post->post_parent,
				'post_password' => $post->post_password,
				'post_status' => $selected_post_status,
				'post_title' => $prefix.$post->post_title.$suffix,
				'post_type' => $post->post_type,
				'to_ping' => $post->to_ping,
				'menu_order' => $post->menu_order,
			);

			$new_post_id = wp_insert_post($args);
			$get_new_post = get_post($new_post_id);

			/* Get all current post terms ad set them to the new post draft */
			$taxonomies = get_object_taxonomies($post->post_type);
			if (!empty($taxonomies) && is_array($taxonomies)){
				foreach ($taxonomies as $taxonomy) {
					$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                    wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
				}
			}

			/* Duplicate all post meta */
			$post_meta_data = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
			if(count($post_meta_data)!=0){
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach($post_meta_data as $post_meta){
					$meta_key = sanitize_text_field($post_meta->meta_key);
					$meta_value = addslashes($post_meta->meta_value);
					$sql_query_insert[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
				}
				$sql_query.= implode(" UNION ALL ", $sql_query_insert);
				$wpdb->query($sql_query);
			}

			/* Redirecting to your choice */
			$returnpage = "";
			if ($post->post_type != 'post'){
				$returnpage = '?post_type='.$post->post_type;
			}
			if(!empty($selected_redirection) && $selected_redirection == 'to_list_post'){
				wp_redirect(admin_url('edit.php'.$returnpage));
			}elseif(!empty($selected_redirection) && $selected_redirection == 'to_edit_post'){
				wp_redirect(admin_url('post.php?action=edit&post='.$new_post_id));
			}elseif(!empty($selected_redirection) && $selected_redirection == 'to_front_post'){
				wp_redirect(get_permalink($new_post_id));
			}else{
				wp_redirect(admin_url('edit.php'.$returnpage));
			}

		}else {
			wp_die('Error! Post creation failed, could not find original post: '.$post_id);
		}

	}else {
		wp_die('Security check issue, Please try again.');
	}
}

/**
 * Check if user have access of duplicate post link
 */
function gwdp_have_access_of_duplicate_post() {
	$gwdp_options = get_option('gwdp_allowed_options');
	$selected_role_type = "";	if(isset($gwdp_options['gwdp_role_type'])) { $selected_role_type = $gwdp_options['gwdp_role_type']; }
	$selected_post_type = "";   if(isset($gwdp_options['gwdp_post_type'])) { $selected_post_type = $gwdp_options['gwdp_post_type']; }
	$selected_shown_link = "";  if(isset($gwdp_options['gwdp_shown_link'])) { $selected_shown_link = $gwdp_options['gwdp_shown_link']; }

	
	if(!in_array( "to_post_edit", $selected_shown_link ))	return false;

	// echo "first  --- "; die;

	$current_screen = get_current_screen();

	$allow_role = $allow_post = false;
	$user = wp_get_current_user();

	// echo '<pre>'; print_r( $current_screen ); echo '</pre>';

	if ( isset($current_screen->is_block_editor) && $current_screen->is_block_editor == 1 ) {
		// echo "first  ";
		if(isset($selected_role_type) && !empty($selected_role_type)) {
			// echo "two  ";
			if(isset($user->roles) && !empty($user->roles)) {
				// echo "three  ";
				foreach ($user->roles as $current_role) {
					
					if ( in_array( $current_role, $selected_role_type ) ) {
						$allow_role = true;
					}
				}
			}
		}

		// var_dump($allow_role); die;

		if($allow_role) {
			if(isset($selected_post_type) && !empty($selected_post_type)) {
				
				if(isset($current_screen->post_type) && !empty($current_screen->post_type) && in_array($current_screen->post_type, $selected_post_type)) {
					$allow_post = true;
				}
			}
		}
	}

	// var_dump($allow_role);

	if($allow_post)		return true;

	return false;
}

/* Enque Styles Admin Side */
function gwdp_admin_scripts( $hook ) {

	$css = GWDP_PLUGIN_URL . '/assets/css/gwdp_admin_style.css';
	wp_enqueue_style( 'gwdp_admin_style', $css, array(), GWDP_BUILD);

	if( gwdp_is_gutenberg_editor() ) {
		if( gwdp_have_access_of_duplicate_post() ) {
			global $post;

			$handle = "gwdp_duplicate_post_edit_script";
			wp_register_script(
				$handle,
				GWDP_PLUGIN_URL . '/assets/js/gwdp-post-edit.js',
				[
					'wp-components',
					'wp-element',
					'wp-i18n',
					'wp-edit-post'
				],
				GWDP_BUILD,
				true
			);
			wp_enqueue_script( $handle );

			wp_localize_script(
				$handle,
				'gwdpObj',
				array(
					'duplicatepostlink' => admin_url('admin.php?action=gwdp_duplicate_post_action&amp;post='.$post->ID.'&amp;nonce='.wp_create_nonce( 'gwdp-duplicate-page-'.$post->ID )),
					'duplicatepostlinktext' => !empty($gwdp_options['gwdp_post_link_text']) ? $gwdp_options['gwdp_post_link_text'] : 'Duplicate this'
				)
			);
		}
	}
}

/* Add the duplicate link to -- all list screen */
function gwdp_duplicate_post_link($actions, $post){
	$gwdp_options=gwdp_allowed_options_callback();
	$selected_link_text = !empty($gwdp_options['gwdp_post_link_text']) ? $gwdp_options['gwdp_post_link_text'] : 'Duplicate this';
	$selected_user_role = !empty($gwdp_options['gwdp_role_type']) ? $gwdp_options['gwdp_role_type'] : array();
	$user = wp_get_current_user();
	$user_role = $user->roles;	
	$where_shown_link = !empty($gwdp_options['gwdp_shown_link']) ? $gwdp_options['gwdp_shown_link'] : array();	

	if (in_array('to_post_list', $where_shown_link)) {
		if(in_array($user_role[0], $selected_user_role)){
			$selected_post_type = $gwdp_options['gwdp_post_type'];
			if(isset($selected_post_type) && !empty($selected_post_type))
				foreach($selected_post_type as $select_post_type){
					if ($post->post_type==$select_post_type){	
						$actions['duplicate'] = '<a href="admin.php?action=gwdp_duplicate_post_action&amp;post='.$post->ID.'&amp;nonce='.wp_create_nonce( 'gwdp-duplicate-page-'.$post->ID ).'" title="'. $selected_link_text .'" rel="permalink">'. $selected_link_text .'</a>';
					}
				}
		}
	}	

	return $actions;
}

/* Add the duplicate link to -- post edit screen */
function gwdp_edit_post_link_callback(){	
	global $post;
	$gwdp_options=gwdp_allowed_options_callback();
	$selected_link_text = !empty($gwdp_options['gwdp_post_link_text']) ? esc_attr($gwdp_options['gwdp_post_link_text'],"duplicate-pages-and-posts") : esc_attr('Duplicate this','duplicate-pages-and-posts');
	$selected_user_role = !empty($gwdp_options['gwdp_role_type']) ? $gwdp_options['gwdp_role_type'] : array(); 
	$user = wp_get_current_user();
	$user_role = $user->roles;
	$html = '<div id="gwdp-duplicate-this-action">';
	$html .= '<div id="export-action">';
	$html .= '<a class="button button-primary button-large" href="admin.php?action=gwdp_duplicate_post_action&amp;post='.$post->ID.'&amp;nonce='.wp_create_nonce( 'gwdp-duplicate-page-'.$post->ID ).'" title="'.$selected_link_text.'" rel="permalink">'. $selected_link_text .'</a>';
	$html .= '</div>';
	$html .= '</div>';
	$where_shown_link = !empty($gwdp_options['gwdp_shown_link']) ? $gwdp_options['gwdp_shown_link'] : array();
	if (in_array('to_post_edit', $where_shown_link)){
		if(in_array($user_role[0], $selected_user_role)){
			$selected_post_type = $gwdp_options['gwdp_post_type'];
			foreach($selected_post_type as $select_post_type){
				if ($post->post_type==$select_post_type){	
					echo $html;
				}
			}
		}
	}
}

/* Add the duplicate link to -- admin bar screen */
function gwdp_admin_bar_link_callback($wp_admin_bar){
	global $post;
	$gwdp_options=gwdp_allowed_options_callback();
	$selected_link_text = !empty($gwdp_options['gwdp_post_link_text']) ? $gwdp_options['gwdp_post_link_text'] : 'Duplicate this';
	$where_shown_link = !empty($gwdp_options['gwdp_shown_link']) ? $gwdp_options['gwdp_shown_link'] : array();
	$selected_user_role = !empty($gwdp_options['gwdp_role_type']) ? $gwdp_options['gwdp_role_type'] : array(); 
	$selected_post_type = !empty($gwdp_options['gwdp_post_type']) ? $gwdp_options['gwdp_post_type'] : array();
	$user = wp_get_current_user();
	$user_role = $user->roles;

	if(isset($GLOBALS['current_screen'])){
		if(isset($post->post_type)){
			if(isset($selected_post_type) && !empty($selected_post_type) && $post->post_type != 'attachment' && $post->filter == 'edit'){
				if(in_array($user_role[0], $selected_user_role) && in_array($post->post_type, $selected_post_type) && in_array('to_admin_bar', $where_shown_link)){
					$icon = '<span class="gwdp-admin-bar-icon"></span>';
					$args = array(
						'id' => 'duplicate_post',
						'parent' => null,
						'group' => null,
						'title' => $icon . $selected_link_text,
						'href' => admin_url().'admin.php?action=gwdp_duplicate_post_action&amp;post='.$post->ID.'&amp;nonce='.wp_create_nonce( 'gwdp-duplicate-page-'.$post->ID ),
						'meta' => array('class' => 'duplicate-link')			
					);
					$wp_admin_bar->add_node($args);
				}
			}
		}
	}else{
		$post_query_object = get_queried_object();
		if(isset($post_query_object->post_type)){
			if(isset($selected_post_type) && !empty($selected_post_type) && $post_query_object->post_type != 'attachment'){
				if(in_array($user_role[0], $selected_user_role) && in_array($post_query_object->post_type, $selected_post_type) && in_array('to_admin_bar', $where_shown_link)){
					$icon = '<span class="gwdp-admin-bar-icon"></span>';
					$args = array(
						'id' => 'duplicate_post',
						'parent' => null,
						'group' => null,
						'title' => $icon . $selected_link_text,
						'href' => admin_url().'admin.php?action=gwdp_duplicate_post_action&amp;post='.$post_query_object->ID.'&amp;nonce='.wp_create_nonce( 'gwdp-duplicate-page-'.$post_query_object->ID ),
						'meta' => array('class' => 'duplicate-link')
					);
					$wp_admin_bar->add_node($args);
				}
			}
		}
	}
}

function gwdp_front_style() {
    ?>
        <style>
            .gwdp-admin-bar-icon {
    			line-height: 1!important;
			}

			.gwdp-admin-bar-icon::before {
				font-family: dashicons;
				content: '\f105';
				top: 4px;
				position: relative;
				color: rgba(240,245,250,.6);
				margin-right: 3px;
				font-size: 18px;
			}	

			li:hover .gwdp-admin-bar-icon::before{
				color: #72aee6;
			}
        </style>
    <?php
}
add_action('wp_head', 'gwdp_front_style');

/* Add submenu page */
function gwdp_duplicate_post_options_callback()
{
    add_options_page('Duplicate Page & Post', 'Duplicate Page & Post', 'manage_options', 'options-duplicate-page-post', 'gwdp_duplicate_page_settings');
}

/* Include options file */
function gwdp_duplicate_page_settings()
{
    if(!current_user_can('manage_options') ){
		wp_die( __('You do not have sufficient permissions to access this page.', 'duplicate-pages-and-posts') );
	}
    include( GWDP_PLUGIN_DIR_PATH . 'options.php' );
}

function gwdp_is_gutenberg_editor() {
    if( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) { 
        return true;
    }   
    
    $current_screen = get_current_screen();
    if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
        return true;
    }
    return false;
}