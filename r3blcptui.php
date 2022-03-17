<?
/**
 * @package r3blcptui
 * 
 * Plugin Name: R3BL CPT UI
 * Plugin URI: https://r3blcreative.com
 * Description: Enables a quick and easy UI that creates custom post types.
 * Version: 1.0.0
 * Author: James Cook
 * Author URI: https://r3blcreative.com
 * License: GPL2
 * Text Domain: r3blcptui
 * 
 * Copyright 2018  James Cook  (email : jcook@r3blcreative.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 */

if(!class_exists('WP_List_Table')) {
	require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

if(!class_exists('R3BLCPTUI_List_Table')) {
	require_once(plugin_dir_path(__FILE__).'/inc/class_r3blcptui_list_table.php');
}

if(!class_exists('R3BLCPTUI_CPTS')) {
	require_once(plugin_dir_path(__FILE__).'/inc/class_r3blcptui_cpts.php');
}

require_once __DIR__ . '/vendor/fortawesome/wordpress-fontawesome/index.php';
use function FortAwesome\fa;

if(!class_exists('R3BLCPTUI')) {
	class R3BLCPTUI {
		public $version = '1.0.1';
		public $voption = 'r3blcptui_version';
		public $table = 'r3blcptui';
		public $CPTS = [];
		public $CPTSwIMGS = [];

		/**
		 * * Constructor Method
		 */
		public function __construct() {
			if(defined('WP_UNINSTALL_PLUGIN')) {
				$this->uninstall();
			}else{
				// HOOKS
				register_activation_hook(__FILE__, [$this, 'activate']);
				register_activation_hook(__FILE__, 'FortAwesome\FontAwesome_Loader::initialize');

				register_deactivation_hook(__FILE__, [$this, 'deactivate']);
				register_deactivation_hook(__FILE__, 'FortAwesome\FontAwesome_Loader::maybe_deactivate');

				register_uninstall_hook(__FILE__,'FortAwesome\FontAwesome_Loader::maybe_uninstall');

				// ACTIONS
				add_action('admin_init', [$this, 'settingsFields']);
				add_action('init', [$this, 'registerCPTS']);
				add_action('plugins_loaded', [$this, 'update']);
				add_action('admin_menu', [$this, 'adminMenu']);
				add_action('admin_head', [$this, 'adminHead']);
				add_action('admin_enqueue_scripts', [$this, 'adminScripts']);
				add_action('wp_ajax_r3blcptui_validate', [$this, 'AJAX_validate']);
				add_action('wp_ajax_r3blcptui_getAPItoken', [$this, 'AJAX_get_apiTokenFA']);
				add_action('wp_ajax_r3blcptui_validate_inline', [$this, 'AJAX_validate_inline']);
				// Custom Columns
				add_action('manage_posts_custom_column',[$this, 'showColumn'],5,2);
				if(get_option('r3blcptui_custom_columns_pages') == true) {
					add_action('manage_pages_custom_column',[$this, 'showColumn'],5,2);
				}

				// FILTERS
				add_filter('set-screen-option', [$this,'r3blcptui_set_option'], 10, 3);
				add_filter('default_hidden_columns', [$this, 'default_hidden_columns'], 10, 2);
				add_filter('plugin_action_links_r3blcptui/r3blcptui.php',[$this,'settingsLink']);
				// Custom Columns
				add_filter('manage_posts_columns', [$this, 'addColumns'], 2);
				add_filter('manage_posts_columns', [$this, 'columnOrder']);
				add_filter('manage_edit-post_sortable_columns',[$this, 'columnSortable']);
				if(get_option('r3blcptui_custom_columns_pages') == true) {
					add_filter('manage_pages_columns', [$this, 'addColumns'], 2);
					add_filter('manage_pages_columns', [$this, 'columnOrder']);
					add_filter('manage_edit-page_sortable_columns',[$this, 'columnSortable']);
				}

				// FONT AWESOME
				add_action(
					'font_awesome_preferences',
					function() {
						fa()->register([
							'name' => 'R3BL CPT UI'
						]);
					}
				);

				add_image_size('r3blcptui-featured-image', 60, 60, false);
			}
		}

		/**
		 * * Register CPTS
		 * 
		 * This method uses the R3BLCPTUI_CPTS class to register the saved CPTS
		 */
		public function registerCPTS() {
			global $wpdb;

			$CPT = new R3BLCPTUI_CPTS();

			// get cpts from db
			$table = $wpdb->prefix.$this->table;
			$query = "SELECT * FROM $table WHERE status = 'active'";
			$results = $wpdb->get_results($query, 'ARRAY_A');

			if(!empty($results)) {
				foreach($results as $cpt) {
					$icon = json_decode($cpt['icon'], true);
					$style = $icon['style'];

					$theCPT = [
						'k'			=> $cpt['slug'],
						's'			=> $cpt['singular'],
						'p'			=> $cpt['plural'],
						'pos'		=> (int) $cpt['position'],
						'icn'		=> $style,
						'taxs'	=> json_decode($cpt['taxonomies'], true),
						'hier'	=> $cpt['hierarchical'],
						'srch'	=> $cpt['search'],
						'arch'	=> $cpt['archive'],
						'pub'		=> $cpt['public']
					];
					$this->CPTS[] = $cpt;
					if($cpt['img'] == true) {
						$this->CPTSwIMGS[] = $cpt['slug'];
					}

					// use class to setup CPTS & TAXS
					$CPT->addCPT($theCPT);
				}
			}

			// register the CPTS & TAXS
			$CPT->registerCPTS();
		}

		/**
		 * * Activation Method
		 */
		public function activate() {
			$this->addTables();

			add_option($this->voption, $this->version);
		}

		/**
		 * * Deactivation Method
		 */
		public function deactivate() {
			// Something
		}

		/**
		 * * Uninstall Method
		 * 
		 * This method is called by the constructor method if the WP_UNINSTALL_PLUGIN 
		 * constant has been defined. We use the uninstall.php method that includes this 
		 * class file to handle uninstallation with access to constants and other helper 
		 * methods.
		 */
		public function uninstall() {
			global $wpdb;

			// Delete Tables
			$table = $wpdb->prefix.$this->table;
			$wpdb->query("DROP TABLE IF EXISTS $table");

			// Delete Options
			delete_option($this->voption);
		}

		/**
		 * * Update Method
		 * 
		 * This method allows for updating the plugin's tables, and other settings that 
		 * otherwise would have been handled during activation.
		 */
		public function update() {
			if(get_option($this->voption) != $this->version) {
				$this->addTables();
				$this->updateTables();

				update_option($this->voption, $this->version);
			}
		}

		/**
		 * * WP DB Table Creation/Update Method
		 * 
		 * Handles the creation of the plugin's custom tables in the WP DB. It also handles 
		 * updating the table schema unless a table or column needs to be deleted.
		 * 
		 * ! If deletion is required, then use the updateTables method.
		 */
		public function addTables() {
			global $wpdb;

			$charset = $wpdb->get_charset_collate();
			$table = $wpdb->prefix.$this->table;

			$sql = "CREATE TABLE $table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				title varchar(30) NOT NULL,
				slug varchar(30) NOT NULL,
				singular varchar(30) NOT NULL,
				plural varchar(30) NOT NULL,
				position tinyint(2) NOT NULL,
				icon json DEFAULT (JSON_OBJECT()) NOT NULL,
				taxonomies json DEFAULT (JSON_OBJECT()) NOT NULL,
				hierarchical boolean DEFAULT 0 NOT NULL,
				search boolean DEFAULT 0 NOT NULL,
				archive boolean DEFAULT 0 NOT NULL,
				public boolean DEFAULT 1 NOT NULL,
				image boolean DEFAULT 0 NOT NULL,
				status varchar(30) DEFAULT 'active' NOT NULL,
				created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
				UNIQUE KEY slug (slug),
				PRIMARY KEY  (id)
			) $charset;";

			require_once(ABSPATH.'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}

		/**
		 * * WP DB Table/Column Deletion Method
		 * 
		 * Handles the deletion and other custom changes to the plugin's tables/columns 
		 * schema since dbDelta doesn't allow certain processes.
		 * 
		 * ! Make sure you update the addTables method so as to handle the updated 
		 * ! schema for fresh plugin installs.
		 */
		public function updateTables() {
			global $wpdb;

			$table = $wpdb->prefix.$this->table;
			$sql = "";

			if(!empty($sql)) $wpdb->query($sql);
		}

		/**
		 * * WP Admin Enqueue Scripts Method
		 * 
		 * Enqueue's JS and CSS assets on admin pages.
		 */
		public function adminScripts() {
			// SCRIPTS
			wp_enqueue_script(
				'icon-picker',
				plugin_dir_url( __FILE__ ).'assets/js/iconPicker.min.js',
				['jquery'],
				strtotime('now')
			);
			wp_enqueue_script(
				'r3blcptui-js',
				plugin_dir_url( __FILE__ ).'assets/js/r3blcptui.min.js',
				['jquery','spectrum-color-picker-js'],
				strtotime('now'),
				true
			);
			wp_enqueue_script(
				'r3blcptui-ajax',
				plugin_dir_url( __FILE__ ).'assets/js/r3blcptui-ajax.min.js',
				['jquery'],
				strtotime('now'),
				true
			);
			wp_enqueue_script(
				'spectrum-color-picker-js',
				plugin_dir_url( __FILE__ ).'assets/js/spectrum.min.js',
				['jquery'],
				strtotime('now'),
				true
			);

			wp_localize_script(
				'r3blcptui-ajax',
				'r3blcptui_object',
				['ajax_url' => admin_url( 'admin-ajax.php' )]
			);

			// STYLES
			wp_enqueue_style(
				'r3blcptui-css',
				plugin_dir_url(__FILE__).'assets/css/r3blcptui.css',
				['dashicons'],
				strtotime('now')
			);
		}

		/**
		 * * WP Admin Menu Method
		 * 
		 * Adds the neccassary admin menu items for this plugin.
		 */
		public function adminMenu() {
			// Top Menu Item
			$hook = add_menu_page(
				'CPT UI - List',
				'CPT UI',
				'manage_options',
				'r3blcptui-list',
				[$this, 'adminList'],
				'dashicons-welcome-add-page',
				150
			);
			add_action("load-$hook", [$this, 'adminScreenOptions']);

			// Sub Menu Items
			add_submenu_page(
				'r3blcptui-list',
				'CPT UI - List',
				'All CPTs',
				'manage_options',
				'r3blcptui-list',
				[$this, 'adminList']
			);

			add_submenu_page(
				'r3blcptui-list',
				'CPT UI - Add CPT',
				'Add New',
				'manage_options',
				'r3blcptui-add',
				[$this, 'adminAdd']
			);

			add_submenu_page(
				'r3blcptui-list',
				'CPT UI - Edit CPT',
				'Edit CPT',
				'manage_options',
				'r3blcptui-edit',
				[$this, 'adminEdit']
			);

			add_submenu_page(
				'r3blcptui-list',
				'CPT UI - Settings',
				'Settings',
				'manage_options',
				'r3blcptui-settings',
				[$this, 'adminSettings']
			);
		}

		/**
		 * * WP Admin Head Method
		 * 
		 * Adds neccassary styles and other funtionality
		 */
		public function adminHead() {
			// Hides the Edit page from the plugin menu
			remove_submenu_page('r3blcptui-list','r3blcptui-edit');

			echo '<style type="text/css" media="screen">';

			// Add styles for custom Font Awesome menu icons
			if(!empty($this->CPTS)) {
				$color = get_option('r3blcptui_icon_color', '#668edd');
				foreach($this->CPTS as $cpt) {
					$icon = json_decode($cpt['icon'], true);
					echo '#menu-posts-'.$cpt['slug'].' .menu-top div.wp-menu-image:before {
						font-family: "Font Awesome 5 Pro" !important;
						content: "\\'.$icon['unicode'].'";
						font-size: 15px;
						font-weight: 800;
						line-height: 18px;
						color: '.$color.' !important;
					}
					#menu-posts-'.$cpt['slug'].' .menu-top div.wp-menu-image img {
						display: none !important;
						visibility: hidden !important;
					}';
				}
			}
			// Custom Columns
			echo '.column-r3blcptui_row {width: 20px;font-weight:800;}
			.column-r3blcptui_thumb {width: 60px;}
			.column-r3blcptui_date {width: 200px;}
			.column-r3blcptui_updated {width: 200px;}';
			echo '</style>';
		}

		/**
		 * * WP Admin Screen Options
		 * 
		 * Adds screen options to the Admin List page
		 */
		function adminScreenOptions() {
			$option = 'per_page';
			$args = [
				'lable'			=> 'CPTs',
				'default'		=> 10,
				'option'		=> 'cpts_per_page'
			];

			add_screen_option($option, $args);

			$this->wp_list_table = new R3BLCPTUI_List_Table();
		}

		/**
		 * * WP Admin Set Option
		 * 
		 * Handles the screen option form submission
		 */
		function r3blcptui_set_option($status, $option, $value) {
			return $value;
		}

		/**
		 * * WP Admin Hidden Columns
		 * 
		 * Handles the screen option form submission
		 */
		function default_hidden_columns($hidden, $screen) {
			return $hidden = [
				'id',
				'singular',
				'plural'
			];
		}

		/**
		 * * WP Admin Main Page
		 * 
		 * This function handles the display of the main admin page 
		 * where users can manage the currently created/added CPT's.
		 */
		public function adminList() {
			require_once(dirname(__FILE__).'/templates/adminList.php');
		}

		/**
		 * * WP Admin Add Page
		 * 
		 * This function handles the display of the add CPT admin page 
		 * where users can add new CPT's.
		 * 
		 */
		public function adminAdd() {
			require_once(dirname(__FILE__).'/templates/adminAdd.php');
		}

		/**
		 * * WP Admin Edit Page
		 * 
		 * This function handles the display of the edit admin page 
		 * where users can edit the a CPT. Uses the adminAdd.php template.
		 * 
		 */
		public function adminEdit() {
			global $wpdb;

			if(!$_GET['id'] || empty($_GET['id'])) {
				// Redirect to List page
				wp_safe_redirect(admin_url('admin.php').'?page=r3blcptui-list');
			}else{
				$id = $_GET['id'];
				$table = $wpdb->prefix.$this->table;
				$query = "SELECT * FROM $table WHERE id=%s";
				$query = $wpdb->prepare($query, $id);
				$results = $wpdb->get_results($query, 'ARRAY_A');

				if(empty($results)) {
					wp_safe_redirect(admin_url('admin.php').'?page=r3blcptui-list');
				}else{
					$item = $results[0];
					require_once(dirname(__FILE__).'/templates/adminEdit.php');
				}
			}
		}

		/**
		 * * WP Admin Settings Page
		 * 
		 * This function handles the display of the settings admin page 
		 * where users can set the plugin specific settings. Uses the 
		 * adminSettings.php template.
		 * 
		 */
		public function adminSettings() {
			if(isset($_GET['error_message'])) {
				add_action('admin_notices', [$this,'settingsNotices']);
				do_action( 'admin_notices', $_GET['error_message'] );
			}

			require_once(dirname(__FILE__).'/templates/adminSettings.php');
		}

		/**
		 * * WP Admin Plugin Page Settings Link
		 * 
		 * This function handles the display of the settings admin page 
		 * where users can set the plugin specific settings. Uses the 
		 * adminSettings.php template.
		 * 
		 */
		public function settingsLink($links) {
			$url = esc_url(add_query_arg(
				'page',
				'r3blcptui-settings',
				get_admin_url().'admin.php'
			));

			$settings_link = '<a href="'.$url.'">Settings</a>';

			array_push($links, $settings_link);
			return $links;
		}

		/**
		 * * WP Admin Settings Notices
		 * 
		 * This function handles the display of the settings admin page 
		 * error/success notices.
		 * 
		 */
		public function settingsNotices($notice) {
			switch ($notice) {
				case '1':
					$message = 'There was an error adding this setting. Please try again.  If this persists, shoot us an email.';
					$err_code = esc_attr( '' );
					$setting_field = '';
					break;
			}
			$type = 'error';
			add_settings_error(
				$setting_field,
				$err_code,
				$message,
				$type
			);
		}

		/**
		 * * WP Admin Settings Fields
		 * 
		 * This function handles the display of the settings admin page 
		 * settings fields and section details.
		 * 
		 */
		public function settingsFields() {
			add_settings_section(
				'r3blcptui_settings_section',
				'General Settings',
				[$this, 'settingsSectionDescription'],
				'r3blcptui_settings'
			);

			unset($fields);
			$fields = [
				[
					'id'		=> 'r3blcptui_fa_token',
					'label'	=> 'Font Awesome Pro API Token',
					'args'	=> [
						'type'							=> 'input',
						'subtype'						=> 'text',
						'id'								=> 'r3blcptui_fa_token',
						'name'							=> 'r3blcptui_fa_token',
						'required'					=> false,
						'get_options_list'	=> '',
						'value_type'				=> 'normal',
						'wp_data'						=> 'option',
						'default'						=> ''
					]
				],
				[
					'id'		=> 'r3blcptui_icon_color',
					'label'	=> 'Admin Custom Icon Color',
					'args'	=> [
						'type'							=> 'input',
						'subtype'						=> 'text',
						'id'								=> 'color-picker',
						'name'							=> 'r3blcptui_icon_color',
						'required'					=> false,
						'get_options_list'	=> '',
						'value_type'				=> 'normal',
						'wp_data'						=> 'option',
						'default'						=> '#668edd'
					]
				],
				[
					'id'		=> 'r3blcptui_custom_columns_posts',
					'label'	=> 'Custom Columns for Posts',
					'args'	=> [
						'type'							=> 'input',
						'subtype'						=> 'checkbox',
						'id'								=> 'r3blcptui_custom_columns_posts',
						'name'							=> 'r3blcptui_custom_columns_posts',
						'required'					=> false,
						'get_options_list'	=> '',
						'value_type'				=> 'normal',
						'wp_data'						=> 'option'
					]
				]
				,
				[
					'id'		=> 'r3blcptui_custom_columns_pages',
					'label'	=> 'Custom Columns for Pages',
					'args'	=> [
						'type'							=> 'input',
						'subtype'						=> 'checkbox',
						'id'								=> 'r3blcptui_custom_columns_pages',
						'name'							=> 'r3blcptui_custom_columns_pages',
						'required'					=> false,
						'get_options_list'	=> '',
						'value_type'				=> 'normal',
						'wp_data'						=> 'option'
					]
				]
			];

			foreach($fields as $field) {
				add_settings_field(
					$field['id'],
					$field['label'],
					[$this, 'settingsField'],
					'r3blcptui_settings',
					'r3blcptui_settings_section',
					$field['args']
				);

				register_setting(
					'r3blcptui_settings',
					$field['id']
				);
			}
		}

		/**
		 * * WP Admin Settings Section Description
		 * 
		 * This function handles the display of the settings admin page 
		 * settings fields section description content.
		 * 
		 */
		public function settingsSectionDescription() {
			echo '<p></p>';
		}

		/**
		 * * WP Admin Settings Field
		 * 
		 * This function handles the display of the settings admin page 
		 * settings individual field content.
		 * 
		 */
		public function settingsField($args) {
			if($args['wp_data'] == 'option') {
				$wp_data_value = get_option($args['name'], $args['default']);
			}elseif($args['wp_data'] == 'post_meta'){
				$wp_data_value = get_post_meta($args['post_id'], $args['name'], true );
			}

			switch ($args['type']) {
				case 'input':
					$value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;

					if(!in_array($args['subtype'], ['checkbox'])) {
						$prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">'.$args['prepend_value'].'</span>' : '';
						$prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
						$step = (isset($args['step'])) ? 'step="'.$args['step'].'"' : '';
						$min = (isset($args['min'])) ? 'min="'.$args['min'].'"' : '';
						$max = (isset($args['max'])) ? 'max="'.$args['max'].'"' : '';

						if(isset($args['disabled'])) {
							// hide the actual input bc if it was just a disabled input the informaiton saved in the database would be wrong - bc it would pass empty values and wipe the actual information
							echo $prependStart.'<input type="'.$args['subtype'].'" id="'.$args['id'].'_disabled" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="'.$args['id'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$prependEnd;
						}else{
							echo $prependStart.'<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$prependEnd;
						}
						/*<input required="required" '.$disabled.' type="number" step="any" id="'.$this->plugin_name.'_cost2" name="'.$this->plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->plugin_name.'_cost" step="any" name="'.$this->plugin_name.'_cost" value="' . esc_attr( $cost ) . '" />*/
					}else{
						$checked = ($value) ? 'checked' : '';
						echo '<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" name="'.$args['name'].'" size="40" value="1" '.$checked.' />';
					}
					break;
				default:
					# code...
					break;
			}
		}

		/**
		 * * AJAX Validate
		 * 
		 * Method that handles logic for validating user input via AJAX
		 * 
		 */
		public function AJAX_validate() {
			global $wpdb;

			$action = 'r3blcptui_validate';
			$nonce = 'nonce';
			if(!check_ajax_referer($action, $nonce, false)) {
				wp_send_json_error('Nonce Failed', 500);
			}

			$errors 	= [];
			foreach($_REQUEST as $key => $value) {
				switch($key) {
					case 'title':
						// Validate not empty
						if(empty($value)) {
							$errors['txt'][] = 'Admin Title is a required field.';
							$errors['field'][] = 'admin_title';
						}
						break;
					case 'slug':
						// Validate not empty
						if(empty($value)) {
							$errors['txt'][] = 'Slug is a required field.';
							$errors['field'][] = 'cpt_slug';
						}

						// Validate no special characters or spaces (A-Za-z0-9/-/_)
						if(!$this->checkPattern($value) && $_REQUEST['page'] != 'edit') {
							$errors['txt'][] = 'Slug should start with a letter and only contain letters, numbers, dashes, and/or underscores.';
							$errors['field'][] = 'cpt_slug';
						}

						// Validate unique
						if(!$this->checkUnique($value) && $_REQUEST['page'] != 'edit') {
							$errors['txt'][] = 'The slug you entered is not available.';
							$errors['field'][] = 'cpt_slug';
						}
						break;
					case 'singular':
					case 'plural':
						// Validate not empty
						if(empty($value)) {
							$errors['txt'][] = ucfirst($key).' is a required field.';
							$errors['field'][] = 'cpt_'.$key;
						}
						break;
					case 'position':
						// Validate not empty
						if(empty($value)) {
							$errors['txt'][] = 'Menu Position is a required field.';
							$errors['field'][] = 'cpt_position';
						}

						// validate int
						if(!is_int(intval($value)) && intval($value) != 0) {
							$errors['txt'][] = 'Menu Position must be an integer.';
							$errors['field'][] = 'cpt_position';
						}
						break;
					case 'icon':
						// Validate not empty
						if(empty($value['id'])) {
							$errors['txt'][] = 'Menu Icon is a required field.';
							$errors['field'][] = 'cpt_icon_id';
						}
						break;
					case 'taxonomies':
						$taxs = [];
						if( is_array($value)) {
							foreach($value as $gid => $group) {
								$i = $gid + 1;
								foreach($group as $field) {
									// $field[0]: slug (str/req/lower/regex)
									// $field[1]: singular (str/req/lower)
									// $field[2]: plural (str/req/lower)
									// $field[3]: hierarchical (on/off:bool)

									foreach($field as $fid => $fval) {
										$sub = substr($fid,15,-2);
										switch($sub) {
											case 'slug':
												$taxK = strtolower($fval);
												// Validate not empty
												if(empty($fval)) {
													$errors['txt'][] = 'Taxonomy Slug '.$i.' is a required field.';
													$errors['field'][] = $fid;
												}
												// Validate REGEX
												if(!$this->checkPattern($fval)) {
													$errors['txt'][] = 'Taxonomy Slug '.$i.' should start with a letter and only contain letters, numbers, dashes, and/or underscores.';
													$errors['field'][] = $fid;
												}

												// Validate unique
												// TODO: Need to be able to validate unique tax slug
												break;
											case 'singular':
												$taxS = strtolower($fval);
												// Validate not empty
												if(empty($fval)) {
													$errors['txt'][] = 'Taxonomy Singular '.$i.' is a required field.';
													$errors['field'][] = $fid;
												}
												break;
											case 'plural':
												$taxP = strtolower($fval);
												// Validate not empty
												if(empty($fval)) {
													$errors['txt'][] = 'Taxonomy Plural '.$i.' is a required field.';
													$errors['field'][] = $fid;
												}
												break;
											case 'hier':
												$taxHier = $this->checked($fval);
												// No validation needed
												break;
											default:
												// Debugging check
												$errors['txt'][] = 'Something went wrong with taxonomies.';
												break;
										}
									}
								}

								$taxs[] = [
									'k'			=> $taxK,
									's'			=> $taxS,
									'p'			=> $taxP,
									'hier'	=> $taxHier
								];
							}
						}
						break;
					case 'hierarchical':
					case 'search':
					case 'archive':
					case 'public':
					case 'image':
					default:
						// No validation needed
						break;
				}
			}

			if(!empty($errors)) {
				wp_send_json_error($errors, 500);
			}else{
				// Format Data
				$item = [
					'title'					=> $_REQUEST['title'],
					'slug'					=> strtolower($_REQUEST['slug']),
					'singular'			=> strtolower($_REQUEST['singular']),
					'plural'				=> strtolower($_REQUEST['plural']),
					'position'			=> intval($_REQUEST['position']),
					'icon'					=> json_encode($_REQUEST['icon']),
					'taxonomies'		=> json_encode($taxs),
					'hierarchical'	=> $this->checked($_REQUEST['hierarchical']),
					'search'				=> $this->checked($_REQUEST['search']),
					'archive'				=> $this->checked($_REQUEST['archive']),
					'public'				=> $this->checked($_REQUEST['public']),
					'image'					=> $this->checked($_REQUEST['image']),
				];

				// Submit to DB
				$table = $wpdb->prefix.$this->table;

				if($_REQUEST['page'] == 'add') {
					$results = $wpdb->insert($table, $item);
					$item['redirect'] = admin_url('admin.php').'?page=r3blcptui-edit&id='.$wpdb->insert_id.'&ref=add';
				}else{
					$where = ['id' => $_REQUEST['id']];
					$results = $wpdb->update($table, $item, $where);
				}

				$item['request'] = $_REQUEST;

				// Check for DB errors
				if($results === false) {
					$dbErrors = [
						'txt' => ['DB said there was an error.'],
						'field' => []
					];
					wp_send_json_error($dbErrors, 500);
				}else{
					wp_send_json_success($item, 200);
				}
			}

			wp_die();
		}

		/**
		 * * AJAX Validate Inline
		 * 
		 * Method that checks user input for slug's against the DB table to ensure the 
		 * user input is unique and matches REGEX when the field's blur event is fired.
		 */
		public function AJAX_validate_inline() {
			$action = 'r3blcptui_validate_inline';
			$nonce = 'nonce';
			if(!check_ajax_referer($action, $nonce, false)) {
				wp_send_json_error('Nonce Failed', 500);
			}

			$type 	= $_REQUEST['type'];
			$slug 	= $_REQUEST['slug'];
			$errors = [];

			// Check Unique
			if(!$this->checkUnique($slug) && $type == 'cpt') {
				$errors[] = 'The slug you entered is not available.';
			}

			// Validate Pattern
			if(!$this->checkPattern($slug)) {
				$errors[] = 'Slug should start with a letter and only contain letters, numbers, dashes, and/or underscores.';
			}

			if(!empty($errors)) {
				wp_send_json_error($errors, 500);
			}else{
				wp_send_json_success('', 200);
			}

			wp_die();
		}

		/**
		 * * AJAX Get Font Awesome API Token
		 * 
		 * Method that retrieves the FA API Token from the options DB.
		 */
		public function AJAX_get_apiTokenFA() {
			$action = 'r3blcptui_validate';
			$nonce = 'nonce';
			if(!check_ajax_referer($action, $nonce, false)) {
				wp_send_json_error('Nonce Failed', 500);
			}

			$apiToken = get_option('r3blcptui_fa_token','');
			wp_send_json_success($apiToken, 200);
		}

		/**
		 * * Check Unique
		 * 
		 * Method that checks if the submitted slug is unique against the DB. 
		 */
		public function checkUnique($slug) {
			global $wpdb;

			$unique = true;
			$table = $wpdb->prefix.$this->table;

			$query = "SELECT * FROM {$table} WHERE slug=%s";
			$query = $wpdb->prepare($query, $slug);
			$results = $wpdb->get_results($query);

			if(!empty($results)) {
				$unique = false;
			}

			return $unique;
		}

		/**
		 * * Check Pattern
		 * 
		 * Method that checks if the submitted slug matches the REGEX pattern. 
		 */
		public function checkPattern($slug) {
			return preg_match('/^[A-Za-z]+[A-Za-z0-9\-\_]+$/', $slug);
		}

		/**
		 * * Checked
		 * 
		 * Method that handles logic for checkboxes
		 */
		public function checked($value = 'false', $flag = false) {
			if($value == '1' || $value == 'true' || $value === true) {
				return ($flag === true) ? 'checked' : true;
			}else{
				return ($flag === true) ? '' : false;
			}
		}

		/**
		 * * Add Custom Columns to POSTS & PAGES
		 * 
		 * Method that adds a custom column structure for admin list pages.
		 * 
		 */
		public function addColumns($columns) {
			$PT = (isset($_GET['post_type'])) ? $_GET['post_type'] : 'post';
			$pages = get_option('r3blcptui_custom_columns_pages');
			$posts = get_option('r3blcptui_custom_columns_posts');

			if($PT == 'page' && $pages == false || $PT == 'post' && $posts == false) {
				return $columns;
			}

			$pubs = get_post_types(['public'=>true]);
			unset($pubs[array_search('attachment', $pubs)]);
			unset($columns['date']);
			unset($columns['tags']);
			unset($columns['comments']);
			$pubs[] = 'people';

			if(in_array($PT, $this->CPTSwIMGS) || $pages == true || $posts == true) {
				$columns['r3blcptui_thumb'] = __('Image');
			}

			if($PT != 'page') {
				$columns['r3blcptui_row'] = '#';
			}

			$columns['r3blcptui_date'] = 'Created';
			$columns['r3blcptui_updated'] = 'Modified';

			return $columns;
		}

		/**
		 * * Show custom column
		 * 
		 * Method that formats the data for each column
		 */
		public function showColumn($columns, $id) {
			global $wp_query;
			$posts = $wp_query->get_posts();
			//var_dump($posts);
			//var_dump($id);
		
			$format = 'm/d/Y \a\t '.get_option( 'time_format' );
		
			switch($columns){
				case 'r3blcptui_row':
					echo $this->getPostIndexValue($posts, $id);
					break;
				case 'r3blcptui_thumb':
					if( function_exists( 'the_post_thumbnail' ) ) {
						echo the_post_thumbnail( 'r3blcptui-featured-image' );
					}
					break;
				case 'r3blcptui_date':
					echo get_the_date($format);
					break;
				case 'r3blcptui_updated':
					echo get_the_modified_date($format);
					break;
			}
		}

		/**
		 * * Get POST index value
		 * 
		 * Method that allows for showing row numbers in the admin 
		 * backend for posts and pages list tables.
		 */
		public function getPostIndexValue($posts, $id) {
			$i = 1;
			foreach($posts as $k => $post) {
				if(isset($post->ID)) {
					if($id == $post->ID) {
						return $k + 1;
					}
				}else{
					if($id == $k) {
							return $i;
					}
				}
				$i++;
			}
			return false;
		}

		/**
		 * * Column Order
		 * 
		 * Method that re-arranges the column order
		 */
		public function columnOrder($columns) {
			$PT = (isset($_GET['post_type'])) ? $_GET['post_type'] : 'post';
			$pages = get_option('r3blcptui_custom_columns_pages');
			$posts = get_option('r3blcptui_custom_columns_posts');

			if($PT == 'page' && $pages == false || $PT == 'post' && $posts == false) {
				return $columns;
			}

			$pubs = get_post_types(['public'=>true]);
			unset($pubs[array_search('attachment', $pubs)]);// Remove attachment
			$pubs[] = 'people';

			if($PT != 'page') {
				$columns = $this->moveColumns($columns, 'r3blcptui_row', 'title');
			}

			if(in_array($PT, $this->CPTSwIMGS) || $pages == true || $posts == true) {
				$columns = $this->moveColumns($columns, 'r3blcptui_thumb', 'title');
			}

			$columns = $this->moveColumns($columns, 'r3blcptui_date', 'wpseo-score');
			$columns = $this->moveColumns($columns, 'r3blcptui_updated', 'wpseo-score');

			return $columns;
		}

		/**
		 * * Move columns
		 * 
		 * Method that handles the column arrangement
		 * 
		 */
		public function moveColumns($columns, $move, $before) {
			foreach( $columns as $key => $value ) {
				if($key == $before) {
					$n_columns[$move] = $move;
				}
				
				$n_columns[$key] = $value;
			}
			
			return $n_columns;
		}

		/**
		 * * Sortable Columns
		 * 
		 * Method that sets the new custom columns as sortable
		 */
		function columnSortable( $columns ) {
			$columns['r3blcptui_date'] = 'date';
			$columns['r3blcptui_updated'] = 'modified';
		
			return $columns;
		}
	}

	$R3BLCPTUI = new R3BLCPTUI();
}