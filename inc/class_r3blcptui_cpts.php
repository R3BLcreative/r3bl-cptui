<?
class R3BLCPTUI_CPTS {

	public $CPTS = []; //Array of args for registering the cpts
	public $TAXS = []; //Array of args for registering taxonomies
	
	function __construct() {}
	
	/**
	 * * addCPT
	 *
	 * Add a CPT to the array to be registered
	 *
	 * @param args [ary] An array of CPT args for registry
	 * 
	 * @return null
	 */
	public function addCPT($args) {
		/**
		 * * $args
		 * @param k			[str] Key (slug) for CPT
		 * @param p			[str] Plural form of the CPT title
		 * @param s			[str] Singular form of the CPT title
		 * @param pos		[int] Admin menu position number
		 * @param icn		[str] Dash Icon label for the menu item
		 * @param taxs	[ary] Args for creating taxonomies for this CPT
		 * @param hier	[bool] Hierarchical CPT
		 * @param srch	[bool] Exclude this CPT from searches
		 * @param arch	[bool] Does this CPT have an archive page
		 * @param pub		[bool] Does this CPT have an archive page
		 */
		extract($args);

		$cpt = [
			'label'								=> ucwords($p),
			'labels'							=> $this->getLabels($s, $p),
			'description'					=> '',
			'public'							=> $pub,
			'hierarchical'				=> $hier,
			'exclude_from_search'	=> $srch,
			'publicly_queryable'	=> true,
			'show_ui'							=> true,
			'show_in_menu'				=> true,
			'show_in_nav_menus'		=> true,
			'show_in_admin_bar'		=> false,
			'show_in_rest'				=> true,
			'rest_base'						=> $k,
			'menu_position'				=> $pos,
			'menu_icon'						=> $icn,
			'capability_type'			=> 'post',
			'supports'						=> [
				'title',
				'revisions',
				'page-attributes',
				'thumbnail',
			],
			'taxonomies'					=> $this->getTaxList($taxs),
			'has_archive'					=> $arch,
			'rewrite'							=> [
				'with_front'	=> $arch,
				'feeds'				=> $arch,
				'pages'				=> $arch,
			],
			'query_var'						=> $k,
			'can_export'					=> true,
			'delete_with_user'		=> false,
		];

		$this->CPTS[$k] = $cpt;

		$this->addTAXS($k, $taxs);
	}


	/**
	 * * registerCPTS
	 *
	 * Handles the actual registry of the CPTS in WordPress
	 * 
	 * @return null
	 */
	public function registerCPTS() {
		foreach($this->CPTS as $k => $args) {
			register_post_type(sanitize_key($k), $args);
		}

		foreach($this->TAXS as $t => $tax) {
			register_taxonomy(sanitize_key($t), $tax['cpt'], $tax['args']);
		}

		$oldCPTS = get_option('r3blcptui_cpts');
		$newCPTS = $this->getOptList('cpt');

		$oldTAXS = get_option('r3blcptui_taxs');
		$newTAXS = $this->getOptList('tax');

		if($oldCPTS != $newCPTS || $oldTAXS != $newTAXS) {
			flush_rewrite_rules();
		}

		$this->versionControl();
	}


	/**
	 * * versionControl
	 *
	 * Stores a list of CPTS and TAXS in WP options table for version
	 * control and rewrite flushing
	 * 
	 * @return null
	 */
	public function versionControl() {
		update_option('r3blcptui_cpts', $this->getOptList('cpt'));

		update_option('r3blcptui_taxs', $this->getOptList('tax'));
	}


	// * HELPERS -------------------------------------------------

	/**
	 * * addTAXS
	 *
	 * A helper function that creates the labels array for custom post 
	 * types based off of the provided singular and plural strings of 
	 * the CPT.
	 *
	 * @param cpt	[str] Key (slug) for CPT
	 * @param taxs	[ary] Args for creating taxonomies
	 * 
	 * @return null
	 */
	private function addTAXS($cpt, $taxs = null) {
		if(is_null($taxs) || !is_array($taxs)) {
			return;
		}
		
		foreach($taxs as $tax) {
			/**
			 * * $tax
			 * @param k		[str] Key (slug) for CPT
			 * @param p		[str] Plural form of the CPT title
			 * @param s		[str] Singular form of the CPT title
			 * @param hier	[bool] Hierarchical CPT
			 */
			extract($tax);

			$args = [
				'labels'							=> $this->getTaxLabels($s, $p),
				'description'					=> '',
				'public'							=> true,
				'publicly_queryable'	=> true,
				'hierarchical'				=> (bool) $hier,
				'show_ui'							=> true,
				'show_in_menu'				=> true,
				'show_in_nav_menus'		=> true,
				'show_in_rest'				=> false,
				//'rest_base'				=> '',
				//'rest_controller_class'	=> '',
				'show_tagcloud'				=> false,
				'show_in_quick_edit'	=> true,
				'show_admin_column'		=> true,
				//'meta_box_cb'				=> '',
				//'meta_box_sanitize_cb'	=> '',
				//'capabilities'			=> [],
				'rewrite'							=> [
					'slug'					=> $k,
					'with_front'		=> true,
					'hierarchical'	=> (bool) $hier,
					//'ep_mask'			=> ''
				],
				'query_var'						=> $k,
				//'update_count_callback'	=> '',
				'default_term'				=> [
					'name'				=> 'Uncategorized',
					'slug'				=> 'uncategorized',
					'description'	=> sprintf('The default %s taxonomy for %s', $s, $cpt)
				],
				'sort'								=> true,
				//'args'							=> [],
				//'_builtin'					=> ''
			];
			
			$this->TAXS[$k] = [
				'cpt' 	=> $cpt,
				'args'	=> $args
			];
		}
	}


	/**
	 * * getOptList
	 *
	 * A helper function that creates an array of keys for the 
	 * designated list request for creating the option in the 
	 * WP options table.
	 * 
	 * @param type [str] cpt|tax A flag for which list to return
	 * 
	 * @return 		[ary] An array of cpt|tax slugs
	 */
	private function getOptList($type) {
		$list = [];
		$opts = ($type == 'tax') ? $this->TAXS : $this->CPTS;

		foreach($opts as $opt) {
				$list[] = $opt;
		}
		return $list;
	}


	/**
	 * * getTaxList
	 *
	 * A helper function that creates an array of taxonomy keys for 
	 * association with the CPT being created.
	 *
	 * @param taxs	[ary] Args for creating taxonomies for this CPT
	 * 
	 * @return		[ary] An array of tax slugs
	 */
	private function getTaxList( $taxs = null) {
		$list = [];
		if(is_null($taxs) || !is_array($taxs)) {
			return $list;
		}else{
			$taxs = $taxs;
			foreach($taxs as $tax) {
				$list[] = $tax['k'];
			}
			return $list;
		}
	}


	/**
	 * * getLabels
	 *
	 * A helper function that creates the labels array for custom post 
	 * types based off of the provided singular and plural strings of 
	 * the CPT.
	 *
	 * @param s [str] The singular version of the CPT label]
	 * @param p	[str] The plural version of the CPT label]
	 * 
	 * @return	[ary] An array of labels for the CPT]
	 */
	private function getLabels($s, $p) {
		$s = ucwords(strtolower($s));
		$p = ucwords(strtolower($p));

		return [
			'name'											=> $p,
			'singular_name'							=> $s,
			'add_new'										=> sprintf('Add New %s', $s),
			'add_new_item'							=> sprintf('Add New %s', $s),
			'edit_item'									=> sprintf('Edit %s', $s),
			'new_item'									=> sprintf('New %s', $s),
			'view_item'									=> sprintf('View %s', $s),
			'view_items'								=> sprintf('View %s', $p),
			'search_items'							=> sprintf('Search %s', $p),
			'not_found'									=> sprintf('No %s Found', $p),
			'not_found_in_trash'				=> sprintf('No %s Found in Trash', $p),
			'parent_item_colon'					=> sprintf('Parent %s:', $s),
			'all_items'									=> sprintf('All %s', $p),
			'archives'									=> sprintf('%s Archives', $s),
			'attributes'								=> sprintf('%s Attributes', $s),
			'insert_into_item'					=> sprintf('Insert into %s', $s),
			'uploaded_to_this_item'			=> sprintf('Uploaded to this %s', $s),
			'featured_image'						=> sprintf('%s Featured Image', $s),
			'set_featured_image'				=> 'Set Featured Image',
			'remove_featured_image'			=> 'Remove Featured Image',
			'use_featured_image'				=> 'Use as Featured Image',
			'filter_items_list'					=> sprintf('Filter %s Lists', $p),
			'filter_by_date'						=> 'Filter By Date',
			'items_list_navigation'			=> sprintf('%s List Navigation', $p),
			'items_list'								=> sprintf('%s List', $p),
			'item_published'						=> sprintf('%s Published', $s),
			'item_published_privately' 	=> sprintf('%s Published Privately', $s),
			'item_reverted_to_draft'   	=> sprintf('%s Reverted to Draft', $s),
			'item_scheduled'						=> sprintf('%s Scheduled', $s),
			'item_updated'							=> sprintf('%s Updated', $s)
		];
	}


	/**
	 * * getTaxLabels
	 *
	 * A helper function that creates the labels array for custom post 
	 * type taxonomies based off of the provided singular and plural 
	 * strings of the taxonomy.
	 * 
	 * @param s [str] The singular version of the taxonomy
	 * @param p [str] The plural version of the taxonomy
	 * 
	 * @return 	[ary] An array of the labels for the taxonomy
	 */
	private function getTaxLabels($s, $p) {
		$s = ucwords(strtolower($s));
		$p = ucwords(strtolower($p));

		return [
			'name'                       => $p,
			'singular_name'              => $s,
			'search_items'               => sprintf('Search %s', $p),
			'popular_items'              => sprintf('Popular %s', $p),
			'all_items'                  => sprintf('All %s', $p),
			'parent_item'                => sprintf('Parent %s', $s),
			'parent_item_colon'          => sprintf('Parent %s:', $s),
			'edit_item'                  => sprintf('Edit %s', $s),
			'view_item'                  => sprintf('View %s', $s),
			'update_item'                => sprintf('Update %s', $s),
			'add_new_item'               => sprintf('Add New %s', $s),
			'new_item_name'              => sprintf('New %s Name', $s),
			'separate_items_with_commas' => sprintf('Separate %s With Commas', $p),
			'add_or_remove_items'        => sprintf('Add or Remove %s', $p),
			'choose_from_most_used'      => 'Choose From The Most Used',
			'not_found'                  => sprintf('No %s Found', $p),
			'no_terms'                   => sprintf('No %s', $p),
			'filter_by_item'             => sprintf('Filter by %s', $s),
			'items_list_navigation'      => sprintf('%s List Navigation', $p),
			'items_list'                 => sprintf('%s List', $p),
			'most_used'                  => 'Most Used',
			'back_to_items'              => sprintf('&larr; Go to %s', $p)
		];
	}
}