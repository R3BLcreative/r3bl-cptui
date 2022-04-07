<?
class R3BLCPTUI_List_Table extends WP_List_Table {
	/**
	 * * Constructor Method
	 * 
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, 
	 * as well as whether the class supports AJAX.
	 */
	function __construct() {
		global $status, $page;

		parent::__construct([
			'singular'	=> 'wp_list_r3blcptui',
			'plural'		=> 'wp_list_r3blcptui',
			'ajax'			=> false
		]);
	}

	/**
	 * * Extra Table Nav
	 * 
	 * Add extra markup in the toolbars before or after the list
	 * 
	 * @param	which	[STR] Helps you decide before/top or after/bottom
	 * 
	 */
	function extra_tablenav($which) {
		if($which == 'top') {
			if(isset($_REQUEST['view']) && $_REQUEST['view'] == 'trash'):
			?>
			<a href="<? echo admin_url('admin.php'); ?>?page=r3blcptui-list&view=all&action=empty" class="r3bl-admin-empty-btn
			button apply">Empty Trash</a>
			<?
			endif;
		}

		if($which == 'bottom') {
			// Something
		}
	}

	/**
	 * * Get Columns
	 * 
	 * Define the columns that are going to be used in the table
	 * 
	 * @return	columns	[ARR] The array of the columns to use with the table
	 * 
	 */
	function get_columns() {
		return $columns = [
			'cb'    			=> '<input type="checkbox" />',
			'id'					=> 'ID',
			'icon'				=> 'Icon',
			'title'				=> 'Title',
			'singular'		=> 'Singular',
			'plural'			=> 'Plural',
			'taxonomies'	=> 'Taxonomies',
			'slug'				=> 'Slug',
			'options'			=> 'Options',
			'created'			=> 'Created',
			'modified'		=> 'Modified'
		];
	}

	/**
	 * * Prepare Items
	 * 
	 * Prepare the table with different parameters, pagination, columns, 
	 * and table elements.
	 * 
	 */
	function prepare_items() {
		global $wpdb;

		$view = (!empty($_REQUEST['view'])) ? $_REQUEST['view'] : 'active';
		$table = $wpdb->prefix.'r3blcptui';
		$query = "SELECT * FROM $table";
		$query .= ($view != 'all') ? " WHERE status = '$view'" : '';
		$data = $wpdb->get_results($query, 'ARRAY_A');
		usort($data, [&$this, 'sort_data']);

		$per_page = $this->get_items_per_page('cpts_per_page', 10);
		$current_page = $this->get_pagenum();
		$total_items = count($data);

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page
		]);

		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);

		$this->_column_headers = $this->get_column_info();
		$this->process_bulk_action();
		$this->items = $data;
	}

	/**
	 * * Column Default
	 * 
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) { 
			case 'cb':
			case 'id':
			case 'icon':
			case 'title':
			case 'singular':
			case 'plural':
			case 'taxonomies':
			case 'slug':
			case 'options':
			case 'created':
			case 'modified':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * * Get Sortable Columns
	 * 
	 * Decide which columns to activate the sorting functionality on 
	 * 
	 * @return	sortable	[ARR] The array of columns that can be sorted
	 * 
	 */
	public function get_sortable_columns() {
		return $sortable = [
			'id'					=> ['id', true],
			'title'				=> ['title',true],
			'created'			=> ['created',true],
			'modified'		=> ['modified',true]
		];
	}

	/**
	 * * Sort Data
	 * 
	 * Handles the sortable functionality of the table
	 * 
	 */
	function sort_data($a, $b) {
		// If no sort, default to title
		$orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'title';

		// If no order, default to ASC
		$order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

		// Determine sort order
		$result = strcmp($a[$orderby], $b[$orderby]);

		// Send final sort direction to usort
		return ($order === 'asc') ? $result : -$result;
	}

	/**
	 * * Get Views
	 * 
	 * Filtered view options
	 * 
	 * @return	views	[ARR] Array of filtered view options
	 * 
	 */
	protected function get_views() {
		$base = admin_url('admin.php').'?page=r3blcptui-list&view=';
		$linked = '<a href="'.$base.'%s">%s</a> (%d)';
		$unlinked = '<strong>%s</strong> (%d)';
		$current = (!empty($_REQUEST['view'])) ? $_REQUEST['view'] : 'active';
		$views = ['all', 'active', 'trash'];
		$return = [];

		foreach($views as $view) {
			$count = $this->get_counts($view);
			if($current == $view) {
				$return[$view] = sprintf($unlinked, ucfirst($view), $count);
			}else{
				$return[$view] = sprintf($linked, $view, ucfirst($view), $count);
			}
		}

		return $return;
	}

	/**
	 * * Get Counts
	 * 
	 * Item counts for the filtered view options
	 * 
	 * @return	results	[INT] Number of items
	 * 
	 */
	function get_counts($type = null) {
		global $wpdb;
		
		$table = $wpdb->prefix.'r3blcptui';
		$query = "SELECT * FROM $table";
		$query .= (isset($type) && $type != 'all') ? " WHERE status = '$type'" : '';
		$results = $wpdb->get_results($query, 'ARRAY_A');

		return count($results);
	}

	/**
	 * * COLUMN: CB
	 */
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="cptrow[]" value="%s" />', $item['id']
		);
	}

	/**
	 * * COLUMN: Icon
	 */
	function column_icon($item) {
		$icon = json_decode($item['icon'], true);
		// return '
		// <style type="text/css" media="screen">
		// #icon-'.$item['id'].':before {
		// 	content:"'.stripslashes($icon['unicode']).'";
		// }
		// </style>
		// 	<div id="icon-'.$item['id'].'" style="" class="r3bl-admin-item-icon "></div>
		// ';
		return '
		<div id="icon-'.$item['id'].'" style="" class="r3bl-admin-item-icon"><i class="'.$icon['style'].'"></i></div>
		';
	}

	/**
	 * * COLUMN: Title
	 */
	function column_title($item) {
		$alink = '<a href="'.admin_url('admin.php').'?page=%s&action=%s&id=%d">%s</a>';
		$vlink = '<a href="'.site_url().'/%s" target="_blank">%s</a>';

		// Edit Action
		$actions['edit'] = sprintf($alink, 'r3blcptui-edit', 'edit', $item['id'], 'Edit');

		// View Archive Action
		if($item['archive'] == true) {
			$actions['view'] = sprintf($vlink, $item['slug'], 'View Archive');
		}

		// Trash/Delete Permanently Action
		if(isset($_REQUEST['view']) && $_REQUEST['view'] == 'trash') {
			$actions['restore'] = sprintf($alink, 'r3blcptui-list', 'restore', $item['id'], 'Restore');
			$actions['delete'] = sprintf($alink, 'r3blcptui-list', 'delete', $item['id'], 'Delete Permanently');
		}else{
			$actions['trash'] = sprintf($alink, 'r3blcptui-list', 'trash', $item['id'], 'Trash');
		}

		$tlink = '<a href="'.admin_url('admin.php').'?page=r3blcptui-edit&id=%d">%s</a>%s%s';
		$trashed = (isset($_REQUEST['view']) && $_REQUEST['view'] != 'trash' && $item['status'] == 'trash') ? ' - <em>Trashed</em>' : '';
		return sprintf($tlink, $item['id'], $item['title'], $trashed, $this->row_actions($actions));
	}

	/**
	 * * COLUMN: Taxonomies
	 */
	function column_taxonomies($item) {
		$return = '';
		$list = [];

		$taxs = json_decode($item['taxonomies'], true);
		if(!empty($taxs) && is_array($taxs)) {
			foreach($taxs as $tax) {
				$list[] = $tax['k'];
			}

			$return = implode(', ', $list);
		}

		return $return;
	}

	/**
	 * * COLUMN: Slug
	 */
	function column_slug($item) {
		if($item['archive'] == true) {
			$slink = '
				<a href="'.site_url().'/%1$s" target="_blank" title="View Archive">%1$s</a>
			';
			return sprintf($slink, $item['slug']);
		}else{
			return $item['slug'];
		}
	}

	/**
	 * * COLUMN: Options
	 */
	function column_options($item) {
		$options = '<div class="r3bl-admin-list-options">';

		// Hierarchical
		if($item['hierarchical'] == true) {
			$options .= '<span class="dashicons dashicons-networking r3bl-option-icon" title="Hierarchical"></span>';
		}

		// Search
		if($item['search'] == true) {
			$options .= '<span class="dashicons dashicons-search r3bl-option-icon" title="Searchable"></span>';
		}

		// Archive
		if($item['archive'] == true) {
			$options .= '<span class="dashicons dashicons-media-archive r3bl-option-icon" title="Has Archive"></span>';
		}

		// Public
		if($item['public'] == true) {
			$options .= '<span class="dashicons dashicons-unlock r3bl-option-icon" title="Public"></span>';
		}

		// Admin Only
		if($item['hierarchical'] != true && $item['search'] != true && $item['archive'] != true && $item['public'] != true) {
			$options .= '<span class="dashicons dashicons-lock r3bl-option-icon" title="Admin Only"></span>';
		}

		// Featured Image
		if($item['image'] == true) {
			$options .= '<span class="dashicons dashicons-format-image r3bl-option-icon" title="Has Featured Image"></span>';
		}

		$options .= '</div>';
		return $options;
	}

	/**
	 * * COLUMN: Created
	 */
	function column_created($item) {
		return date('m/d/Y', strtotime($item['created'])).' at '.date('h:i a', strtotime($item['created']));
	}

	/**
	 * * COLUMN: Modified
	 */
	function column_modified($item) {
		return date('m/d/Y', strtotime($item['modified'])).' at '.date('h:i a', strtotime($item['modified']));
	}

	/**
	 * * Get Bulk Actions
	 * 
	 * Bulk actions
	 * 
	 * @return	actions	[ARR] Array of bulk action options
	 * 
	 */
	public function get_bulk_actions() {
		if(isset($_REQUEST['view']) && $_REQUEST['view'] != 'trash') {
			return $actions = [
				'trash'	=> 'Move to Trash'
			];
		}else{
			return $actions = [
				'restore'	=> 'Restore Items',
				'delete'	=> 'Delete Permanently'
			];
		}
	}

	/**
	 * * Process Bulk Actions
	 * 
	 * Bulk action processes
	 * 
	 * @return	actions	[ARR] Array of bulk action options
	 * 
	 */
	public function process_bulk_action() {
		global $wpdb;

		if(isset($_REQUEST['_wpnonce']) && !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'])) {
			wp_die('Nonce verification failed for bulk action processing');
		}

		$table = $wpdb->prefix.'r3blcptui';

		switch($this->current_action()) {
			case 'trash':
				if(!empty($_REQUEST['id'])) {
					$wpdb->update( $table, ['status' => 'trash'], ['id' => (int)$_REQUEST['id']] );
				}else{
					foreach($_REQUEST['cptrow'] as $id) {
						$wpdb->update( $table, ['status' => 'trash'], ['id' => (int)$id] );
					}
				}
				$redirect_url = admin_url('admin.php').'?page=r3blcptui-list';
				wp_safe_redirect($redirect_url);
				break;
			case 'delete':
				if(!empty($_REQUEST['id'])) {
					$wpdb->delete( $table, ['id' => (int)$_REQUEST['id']] );
				}else{
					foreach($_REQUEST['cptrow'] as $id) {
						$wpdb->delete( $table, ['id' => (int)$id] );
					}
				}
				$redirect_url = admin_url('admin.php').'?page=r3blcptui-list';
				wp_safe_redirect($redirect_url);
				break;
			case 'empty':
				$wpdb->delete( $table, ['status' => 'trash'] );
				$redirect_url = admin_url('admin.php').'?page=r3blcptui-list';
				wp_safe_redirect($redirect_url);
				break;
			case 'restore':
				if(!empty($_REQUEST['id'])) {
					$wpdb->update( $table, ['status' => 'active'], ['id' => (int)$_REQUEST['id']] );
				}else{
					foreach($_REQUEST['cptrow'] as $id) {
						$wpdb->update( $table, ['status' => 'active'], ['id' => (int)$id] );
					}
				}
				$redirect_url = admin_url('admin.php').'?page=r3blcptui-list';
				wp_safe_redirect($redirect_url);
				break;
		}
	}

	/**
	 * * No Items Found
	 */
	function no_items() {
		_e('Yooooo... There weren\'t any custom post types found my guy! Maybe you should add some?');
	}
}