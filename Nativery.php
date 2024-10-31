<?php
/*
Plugin Name: Nativery
Plugin URI: http://www.nativery.com
Description: A plugin to add widgets created with nativery
Version: 0.1.6
Author: Nativery Developer
License: GPL2
*/

/*  Copyright 2018-2021  Nativery Srl  (email : developer@nativery.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?><?php

// some definition we will use
define( 'NATIVERY_PUGIN_NAME', 'NATIVERY Plugin');
define( 'NATIVERY_PLUGIN_DIRECTORY', 'nativery');
define( 'NATIVERY_CURRENT_VERSION', '0.1.6' );
define( 'NATIVERY_REQUEST_URL', 'http://nativery.com/service.php'); 
define( 'NATIVERY_DEBUG', false); 
define( 'NATIVERY_I18N_DOMAIN', 'nativery' );
define( 'NATIVERY_MULTIPLE_W', true);

//------------

class Nativery {
	
	var $pluginPath;
	var $pluginUrl;
	
	// url to Nativery API (next release)
	//var $apiUrl = NATIVERY_REQUEST_URL;
	
	var $widgets;
	var $options;
	
	
	public function __construct()
	{
		// Set Plugin Path
		$this->pluginPath = dirname(__FILE__);
		
		// Set Plugin URL
		$this->pluginUrl = WP_PLUGIN_URL . '/nativery/';
		
		$this->widgets = get_option('nativery_widgets');
		
		
		$this->nativery_set_lang_file();
		//add_action('plugins_loaded', array($this, 'nativery_wt_load_textdomain'));
		
		register_activation_hook(__FILE__, array($this, 'nativery_activate') );
		register_deactivation_hook(__FILE__, array($this, 'nativery_deactivate') );
		register_uninstall_hook(__FILE__, array(__CLASS__, 'nativery_uninstall') );
		
		add_action( 'admin_head', array($this, 'nativery_register_style') );
		
		// create custom plugin settings menu
		add_action( 'admin_menu', array($this, 'nativery_create_menu') );
		
		//call register settings function
		add_action( 'admin_init', array($this, 'nativery_register_settings') );
		
		add_action( 'widgets_init', array($this, 'nativery_widgets_init') );

		add_action( 'init', array($this, 'nativery_shortcode_init') );

		add_action( 'wp_enqueue_scripts', array($this, 'nativery_css_inline') );

		add_action( 'wp_footer', array($this, 'nativery_natjs') );
		
		add_filter("the_content", array( $this, 'nativery_addToContent'));

		add_filter("comment_form_after", array( $this, 'nativery_addAfterComment'));
		add_filter("comment_form_before", array( $this, 'nativery_addBeforeComment'));
		
		add_option('nativery_css', '');
	}
	
	public function nativery_register_style() {
		wp_register_style( 'nativery_css', $this->pluginUrl.'css/nativery.css', null, '1.0', 'screen' );
		wp_enqueue_style( 'nativery_css' );
	}
	
	// load language files
	public function nativery_set_lang_file() {
		# set the language file
		$currentLocale = get_locale();
		if(!empty($currentLocale)) {
			$moFile = $this->pluginPath . "/lang/" . $currentLocale . ".mo";
			if (@file_exists($moFile) && is_readable($moFile)) {
				load_textdomain(NATIVERY_I18N_DOMAIN, $moFile);
			}
		}
	}
	public function nativery_wt_load_textdomain() {
		load_plugin_textdomain( 'nativery', false, $this->pluginUrl . '/lang/' );
	}
	
	// activating the default values
	public function nativery_activate() {
		
		add_option('nativery_enabled', '');
		add_option('nativery_widgets', '');
		
		
		
	}
	
	// deactivating
	public function nativery_deactivate() {
	}
	
	// uninstalling
	public static function nativery_uninstall() {
		// delete all data stored
		delete_option('nativery_enabled');
		delete_option('nativery_widgets', '');
		
		if (method_exists($this, 'deleteLogFolder')) $this->deleteLogFolder();
	}
	
	//create Nativery Menu
	public function nativery_create_menu() {
	
		// create new top-level menu
		add_menu_page(
			__('Nativery', NATIVERY_I18N_DOMAIN),
			__('Nativery', NATIVERY_I18N_DOMAIN),
			'edit_pages',
			NATIVERY_PLUGIN_DIRECTORY.'/nativery_settings.php',
			'',
			$this->pluginUrl.'images/icon.png'
		);
	
	
		add_submenu_page(
			NATIVERY_PLUGIN_DIRECTORY.'/nativery_settings.php',
			__("Settings", NATIVERY_I18N_DOMAIN),
			__("Settings", NATIVERY_I18N_DOMAIN),
			'edit_pages',
			NATIVERY_PLUGIN_DIRECTORY.'/nativery_settings.php'
		);
		
		$page = '';//add_submenu_page(NATIVERY_PLUGIN_DIRECTORY.'/nativery_settings.php', __("Bulk optimize", NATIVERY_I18N_DOMAIN), __("Bulk optimize", NATIVERY_I18N_DOMAIN), 'manage_options', 'bulk_page_slug', array($this, 'bulk_page_handler') );
		add_action('admin_print_scripts-' . $page, array($this, 'bulk') );
		
		
	}
	
	
	public function nativery_register_settings() {
		//register settings
		register_setting( 'nativery-settings-group', 'nativery_widgets');		
		register_setting( 'nativery-settings-group', 'nativery_enabled' );
		register_setting( 'nativery-settings-group', 'nativery_css');
	}
	public static function nativery_createCodeNat($cod) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if (
				( is_plugin_active( 'amp/amp.php' ) && is_amp_endpoint() ) ||
				( is_plugin_active( 'accelerated-mobile-pages/accelerated-moblie-pages.php' ) && ampforwp_is_amp_endpoint() )
		) {
			$add = "
			<amp-ad width=\"400\" height=\"600\"
				heights=\"(min-width:1907px) 39%, (min-width:1200px) 46%, (min-width:780px) 64%, (min-width:480px) 98%, (min-width:460px) 167%, 196%\"
    			layout=\"responsive\"
    			type=\"nativery\"
    			data-wid=\"".$cod."\">
  			</amp-ad>";
		} else {
			$nat_index = ', {noindex:true}';
			if(((is_page())and(!is_404())) or (is_single()and(!is_404())) and(!is_search())and( !is_attachment() ) ){
				$nat_index = '';
			}				
			$add = "
				<div id=\"nat_".$cod."\"></div>
				<!-- nativery wp vers.".NATIVERY_CURRENT_VERSION."-->
				<script type=\"text/javascript\">
				var _nat = _nat || [];
				_nat.push(['id', '".$cod."'".$nat_index."]);
				</script>
				";
		}
		return $add;
	}
	public static function nativery_addToContent($content) {
		
		$add_before = '';
		$add_after = '';
		
		//if((is_single()or is_page())and(!is_front_page())){
		if(is_single() or is_page() ){
		
			$opt_natwidget = get_option('nativery_widgets');
			if (!empty( $opt_natwidget ) ){
				foreach ($opt_natwidget as $kw => $vw){
					
					if( isset( $vw['act'] ) && 1 == $vw['act'] && (
							(is_single() && isset($vw['vis']['art']))
						||  (is_page() && isset($vw['vis']['pag']))
						||  (is_archive() && isset($vw['vis']['arc']))
						)){
						
						
					
						$add = Nativery::nativery_createCodeNat($vw['cod']);
					
						if($vw['pos']==1){
							$add_after .= $add;
						}else if($vw['pos']==2){
							$add_before .= $add;
						}
					}
				}
			}
		}
		
		
		return $add_before.$content.$add_after;
		
	}

	public static function nativery_addBeforeComment() {
		
		$add_before = '';

		//if((is_single()or is_page())and(!is_front_page())){
		if(is_single() or is_page() ){
		
			$opt_natwidget = get_option('nativery_widgets');
			if (!empty( $opt_natwidget ) ){
				foreach ($opt_natwidget as $kw => $vw){
					
					if( isset( $vw['act'] ) && 1 == $vw['act'] && (
							(is_single() && isset($vw['vis']['art']))
						||  (is_page() && isset($vw['vis']['pag']))
						||  (is_archive() && isset($vw['vis']['arc']))
						)){
						
						
					
						$add = Nativery::nativery_createCodeNat($vw['cod']);
					
						if($vw['pos']==4){
							$add_before .= $add;
						}
					}
				}
			}
		}
		
		echo $add_before;
	
	}

	public static function nativery_addAfterComment() {
		
		$add_after = '';

		//if((is_single()or is_page())and(!is_front_page())){
		if(is_single() or is_page() ){
		
			$opt_natwidget = get_option('nativery_widgets');
			if (!empty( $opt_natwidget ) ){
				foreach ($opt_natwidget as $kw => $vw){
					
					if( isset( $vw['act'] ) && 1 == $vw['act'] && (
							(is_single() && isset($vw['vis']['art']))
						||  (is_page() && isset($vw['vis']['pag']))
						||  (is_archive() && isset($vw['vis']['arc']))
						)){
						
						
					
						$add = Nativery::nativery_createCodeNat($vw['cod']);
					
						if($vw['pos']==3){
							$add_after .= $add;
						}
					}
				}
			}
		}
		
		echo $add_after;
		
	}

	public static function nativery_widgets_init() {
		/*$num_ist = 0;
		if (is_array(get_option('nativery_widgets'))){
			foreach (get_option('nativery_widgets') as $kw => $vw){
				if((isset($vw['pos']))and($vw['pos']==3)and(isset($vw['act']))and($vw['act']==1)){
					$num_ist++;
				}
			}
		}
		if($num_ist==0){
			//unregister_widget('nativery_widget');
		}
		else {
			register_widget('nativery_widget');
		}*/
		register_widget('nativery_widget');
	}

	public static function nativery_shortcode_init() {
		add_shortcode('natWidget', 'nativery_shortcode_function');
		function nativery_shortcode_function($atts){
		   extract(shortcode_atts(array(
		      'cod' => '',
		   ), $atts));

		   $return_string = Nativery::nativery_createCodeNat($cod);
		   return $return_string;
		}

	}
	
	public static function nativery_css_inline() {
		if (get_option('nativery_css')&&get_option('nativery_css')!=''){
			wp_enqueue_style(
				'custom-style',
				WP_PLUGIN_URL . '/nativery/css/natcustom.css'
			);
			$custom_css = get_option('nativery_css');
        	wp_add_inline_style( 'custom-style', $custom_css );
		}
	}

	public static function nativery_natjs() {
		?>
			<script type="text/javascript">
				console.log('natjs');
				var _nat = _nat || [];
				(function() {
					var nat = document.createElement('script'); nat.type = 'text/javascript'; nat.async = true;
					nat.src = '//cdn.nativery.com/widget/js/nat.js';
					var nats = document.getElementsByTagName('script')[0]; nats.parentNode.insertBefore(nat, nats);
				})();
				</script>
		<?php
	}
	
}

$wpNativery = new Nativery();


include_once ( "widget.class.php" );
//----------------

// Filter Functions with Hooks
function nativery_natcustom_mce_button() {
  // Check if user have permission
  if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
    return;
  }
  // Check if WYSIWYG is enabled
  if ( 'true' == get_user_option( 'rich_editing' ) ) {
    add_filter( 'mce_external_plugins', 'nativery_natcustom_tinymce_plugin' );
    add_filter( 'mce_buttons', 'nativery_register_natmce_button' );
  }
}
add_action('admin_head', 'nativery_natcustom_mce_button');



// Function for new button
function nativery_natcustom_tinymce_plugin( $plugin_array ) {



  $plugin_array['natcustom_mce_button'] = WP_PLUGIN_URL . '/nativery/js/editorShortcode.js';
  return $plugin_array;
}


// Register new button in the editor
function nativery_register_natmce_button( $buttons ) {
  array_push( $buttons, 'natcustom_mce_button' );
  return $buttons;
}