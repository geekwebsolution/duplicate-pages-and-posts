<?php
if(!defined('ABSPATH')) exit;

if(!defined("GWDP_PLUGIN_DIR_PATH"))
	
	define("GWDP_PLUGIN_DIR_PATH",plugin_dir_path(__FILE__));	
	
	
if(!defined("GWDP_PLUGIN_URL"))
	
	define("GWDP_PLUGIN_URL",plugins_url().'/'.basename(dirname(__FILE__)));	

/* Get options type */
function gwdp_allowed_options_callback(){
	return get_option('gwdp_allowed_options');
}

/* error message */
function gwdp_failure_option_msg($msg){	
	return '<div class="notice notice-error pwcgk-error-msg is-dismissible"><p>' . __($msg,'duplicate-pages-and-posts') . '</p></div>';
}
/* Success message */
function  gwdp_success_option_msg($msg){	
	return '<div class="notice notice-success pwcgk-success-msg is-dismissible"><p>'. __($msg,'duplicate-pages-and-posts') . '</p></div>';
}