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
		public $version = '1.0.0';
		public $voption = 'r3blcptui_version';
		public $table = 'r3blcptui';

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
				add_action('init', [$this, 'registerCPTS']);
				add_action('plugins_loaded', [$this, 'update']);
				add_action('admin_menu', [$this, 'adminMenu']);
				add_action('admin_head',function(){remove_submenu_page('r3blcptui-list','r3blcptui-edit');});
				add_action('admin_enqueue_scripts', [$this, 'adminScripts']);
				add_action('wp_ajax_r3blcptui_validate', [$this, 'AJAX_validate']);
				add_action('wp_ajax_r3blcptui_validate_inline', [$this, 'AJAX_validate_inline']);

				// FILTERS
				add_filter('set-screen-option', [$this,'r3blcptui_set_option'], 10, 3);
				add_filter('default_hidden_columns', [$this, 'default_hidden_columns'], 10, 2);

				// FONT AWESOME
				add_action(
					'font_awesome_preferences',
					function() {
						fa()->register([
							'name' => 'R3BL CPT UI'
						]);
					}
				);
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
			$results = $wpdb->get_results($query);

			$cpts = [];
			if(!empty($results)) {
				foreach($results as $cpt) {
					$theCPT = [
						'k'			=> $cpt->slug,
						's'			=> $cpt->singular,
						'p'			=> $cpt->plural,
						'pos'		=> (int) $cpt->position,
						'icn'		=> $cpt->icon,
						'taxs'	=> json_decode($cpt->taxonomies, true),
						'hier'	=> $cpt->hierarchical,
						'srch'	=> $cpt->search,
						'arch'	=> $cpt->archive,
						'pub'		=> $cpt->public
					];

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
				icon varchar(150) NOT NULL,
				taxonomies json DEFAULT (JSON_OBJECT()) NOT NULL,
				hierarchical boolean DEFAULT 0 NOT NULL,
				search boolean DEFAULT 0 NOT NULL,
				archive boolean DEFAULT 0 NOT NULL,
				public boolean DEFAULT 1 NOT NULL,
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
				'dashicons-picker',
				plugin_dir_url( __FILE__ ).'assets/js/dashicons-picker.min.js',
				['jquery'],
				strtotime('now')
			);
			wp_enqueue_script(
				'r3blcptui-js',
				plugin_dir_url( __FILE__ ).'assets/js/r3blcptui.min.js',
				['jquery'],
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
			wp_enqueue_style(
				'dashicons-picker',
				plugin_dir_url(__FILE__).'assets/css/dashicons-picker.css',
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
				$results = $wpdb->get_results($query);

				if(empty($results)) {
					wp_safe_redirect(admin_url('admin.php').'?page=r3blcptui-list');
				}else{
					$item = $results[0];
					require_once(dirname(__FILE__).'/templates/adminEdit.php');
				}
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
						if(empty($value)) {
							$errors['txt'][] = 'Menu Icon is a required field.';
							$errors['field'][] = 'cpt_icon';
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
					'icon'					=> strtolower($_REQUEST['icon']),
					'taxonomies'		=> json_encode($taxs),
					'hierarchical'	=> $this->checked($_REQUEST['hierarchical']),
					'search'				=> $this->checked($_REQUEST['search']),
					'archive'				=> $this->checked($_REQUEST['archive']),
					'public'				=> $this->checked($_REQUEST['public']),
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
	}

	$R3BLCPTUI = new R3BLCPTUI();
}