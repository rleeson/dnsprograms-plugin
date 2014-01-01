<?php
/**
 * Class registers all taxonomies used by this plugin and their associated meta boxes
 */
if ( !class_exists( 'dns_taxonomies' ) ) {
	class dns_taxonomies {
		function __construct() {
			add_action( 'init', array( $this, 'age_ranges' ) );
			add_action( 'init', array( $this, 'brochure_editions' ) );
			add_action( 'init', array( $this, 'locations' ) );
			add_action( 'init', array( $this, 'series' ) );
			add_action( 'init', array( $this, 'teachers' ) );
			add_action( 'admin_init', array( $this, 'change_post_object_label' ) );
			add_action( 'admin_menu', array( $this, 'change_post_menu_label' ) );
			add_action( 'admin_init', array( $this, 'save_post_handlers' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_admin_boxes' ) );
			add_action( 'admin_head', array( $this, 'hide_admin_boxes' ) );
			add_filter( 'wp_insert_post_data', array( $this, 'limit_character_length' ) );
			add_filter( 'manage_edit-post_columns', array( $this, 'columns_filter' ), 10, 1 );
			add_filter( 'manage_edit-post_sortable_columns', array( $this, 'columns_sortable' ) );
			add_filter( 'posts_clauses', array( $this, 'age_range_sort_column' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'brochure_editions_sort_column' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'program_locations_sort_column' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'series_sort_column' ), 10, 2 );
		}	
		
		function limit_character_length( $data ) {
			$data['post_content'] = substr( $data['post_content'], 0, DNS_PROGRAM_CHARACTER_LIMIT );
			return $data;
		}
		
		function add_admin_boxes() {
			add_meta_box( 'dns-age-ranges-div', __( 'Age Ranges' ), 
				array( $this, 'age_ranges_meta_box_style' ), 'post', 'side', 'core');
			add_meta_box( 'dns-brochure-editions-div', __( 'Brochure Editions' ), 
				array( $this, 'brochure_editions_meta_box_style' ), 'post', 'side', 'core');
			add_meta_box( 'dns-program-locations-div', __( 'Program Locations' ), 
				array( $this, 'locations_meta_box_style' ), 'post', 'side', 'core');
			add_meta_box( 'dns-series-div', __( 'Series' ), 
				array( $this, 'series_meta_box_style' ), 'post', 'side', 'core');
			add_meta_box( 'dns-teachers-div', __( 'Teachers' ), 
				array( $this, 'teachers_meta_box_style' ), 'post', 'normal', 'high');
		}
		
		function hide_admin_boxes() {
			remove_meta_box( 'commentsdiv', 'post', 'normal' );
			remove_meta_box( 'formatdiv', 'post', 'side' );
			remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' );
			remove_meta_box( 'tagsdiv-age-ranges', 'post', 'side' );
			remove_meta_box( 'tagsdiv-brochure-editions', 'post', 'side' );
			remove_meta_box( 'tagsdiv-program-locations', 'post', 'side' );
			remove_meta_box( 'tagsdiv-series', 'post', 'side' );
			remove_meta_box( 'tagsdiv-teachers', 'post', 'side' );
		}

		function save_post_handlers() {
			add_action( 'save_post', array( $this, 'age_ranges_save' ) );
			add_action( 'save_post', array( $this, 'brochure_editions_save' ) );
			add_action( 'save_post', array( $this, 'locations_save' ) );
			add_action( 'save_post', array( $this, 'series_save' ) );
			add_action( 'save_post', array( $this, 'teachers_save' ) );
		}
		
		/**
		 * Filter for WP_List_Table
		 * Removes unwanted columns
		 * 
		 * @param array $columns Column array
		 * @return array Updated Columns array
		 */
		function columns_filter( $columns ) {
			unset( $columns[ 'categories' ] );
			unset( $columns[ 'tags' ] );
			unset( $columns[ 'date' ] );
			unset( $columns[ 'comments' ] );
			return $columns;
		}
		
		/**
		 * Filter for WP_List_Table
		 * Adds new columns which can be sorted to the table
		 * To set a column to start sorted, set the columns boolean value to true
		 * 
		 * @param array $sortable_columns Sortable columns array
		 * @return array Updated Sortable columns array
		 */
		function columns_sortable( $sortable_columns ) {
			$sortable_columns[ 'author' ] = array( 'author', false );
			$sortable_columns[ 'taxonomy-age-ranges' ] = array( 'age-ranges', false );
			$sortable_columns[ 'taxonomy-brochure-editions' ] = array( 'brochure-editions', false );
			$sortable_columns[ 'taxonomy-program-locations' ] = array( 'program-locations', false );
			$sortable_columns[ 'taxonomy-series' ] = array( 'series', false );
			return $sortable_columns;
		}
		
		/**
		 * Generic taxonomy post clause filter
		 * Uses a supplied term to write ordering clauses based on term name
		 * 
		 * @param string $term Taxonomy term
		 * @param mixed $clauses Array of query clauses
		 * @param WP_Query $wp_query Query being processed
		 * @return string
		 */
		function taxonomy_custom_sort( $term, $clauses, $wp_query ) {
			global $wpdb;
			if( isset( $wp_query->query[ 'orderby' ] ) && $wp_query->query[ 'orderby' ] == $term ){
				$clauses[ 'join' ] .=
					"LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
					 LEFT OUTER JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
					 LEFT OUTER JOIN {$wpdb->terms} USING (term_id)";
				$clauses[ 'where' ] .= "AND (taxonomy = '{$term}' OR taxonomy IS NULL)";
				$clauses[ 'groupby' ] = "object_id";
				$clauses[ 'orderby' ] = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC)";
				
				if( strtoupper( $wp_query->get( 'order' ) ) == 'ASC' ){
					$clauses['orderby'] .= 'ASC';
				}
				else{
					$clauses['orderby'] .= 'DESC';
				}
			}
			return $clauses;
		}

		/***********************************
		 * Custom Column Sort Query Filters
		 ***********************************/
		
		function age_range_sort_column( $clauses, $wp_query ){
			$clauses = $this->taxonomy_custom_sort( 'age-ranges', $clauses, $wp_query );
			return $clauses;
		}
		
		function brochure_editions_sort_column( $clauses, $wp_query ){
			return $this->taxonomy_custom_sort( 'brochure-editions', $clauses, $wp_query);
		}
		
		function program_locations_sort_column( $clauses, $wp_query ){
			return $this->taxonomy_custom_sort( 'program-locations', $clauses, $wp_query);
		}
		
		function series_sort_column( $clauses, $wp_query ){
			return $this->taxonomy_custom_sort( 'series', $clauses, $wp_query);
		}
		
		// Replace Posts label as Programs in Admin Panel
		function change_post_menu_label() {
			global $menu;
			global $submenu;
			$menu[5][0] = 'Programs';
			$submenu['edit.php'][5][0] = 'Programs';
			$submenu['edit.php'][10][0] = 'Add Programs';
			echo '';
		}
		function change_post_object_label() {
			global $wp_post_types;
			$labels = &$wp_post_types['post']->labels;
			$labels->name = 'Programs';
			$labels->singular_name = 'Program';
			$labels->add_new = 'Add Program';
			$labels->add_new_item = 'Add Program';
			$labels->edit_item = 'Edit Program';
			$labels->new_item = 'Program';
			$labels->view_item = 'View Program';
			$labels->search_items = 'Search Programs';
			$labels->not_found = 'No Programs found';
			$labels->not_found_in_trash = 'No Programs found in Trash';
		}
		
		/**
		 * Create a taxonomy to register Age Ranges
		 */
		function age_ranges() {
			$labels = array(
				'name'              => _x( 'Age Ranges', 'taxonomy general name' ),
				'singular_name'     => _x( 'Age Range', 'taxonomy singular name' ),
				'search_items'      => __( 'Search Age Ranges' ),
				'all_items'         => __( 'All Age Ranges' ),
				'parent_item'       => __( 'Parent Age Range' ),
				'parent_item_colon' => __( 'Parent Age Range:' ),
				'edit_item'         => __( 'Edit Age Range' ),
				'update_item'       => __( 'Update Age Range' ),
				'add_new_item'      => __( 'Add New Age Ranges' ),
				'new_item_name'     => __( 'New Age Range' ),
				'menu_name'         => __( 'Age Ranges' )
			);
		
			register_taxonomy(
				'age-ranges',
				'post',
				array(
					'hierarchical'		=> false,
					'labels'			=> $labels,
					'public'			=> true,
					'query_var'			=> true,
					'rewrite' 			=> array( 'slug' => 'age-ranges' ),
					'show_admin_column'	=> true,
					'show_in_nav_menus'	=> true,
					'show_tagcloud'		=> true,
					'show_ui'			=> true,
					'capabilities' 		=> array(
						'assign_terms' 	=> 'edit_posts',
						'delete_terms' 	=> 'edit_posts',
						'edit_terms' 	=> 'edit_posts',
						'manage_terms' 	=> 'edit_posts'
				)
			)
			);
		}
		
		/**
		 * Display drop-down meta box on the post edit page for Age Ranges
		 * @param object $post Post object
		 */
		function age_ranges_meta_box_style( $post ) {
			echo '<input type="hidden" name="age_ranges_nonce" id="age_ranges_nonce" value="' .
					wp_create_nonce( 'taxonomy_age_ranges' ) . '" />';
		
			// Get all theme taxonomy terms
			$terms = get_terms( 'age-ranges', array( 'hide_empty' => '0' ) );
		
			?>
			<select name='post_age_ranges' id='post_age_ranges' class='required'>
			<?php 
		        $names = wp_get_object_terms( $post->ID, 'age-ranges' ); 
		        ?>
		        <option class='age-ranges-option' value='none' 
		        	<?php if ( !count( $names ) ) echo 'selected';?>>None</option>
		        <?php
				foreach ( $terms as $term ) {
					if ( !is_wp_error( $names ) && !empty( $names ) && !strcmp( $term->slug, $names[0]->slug )) {
						echo "<option class='age-ranges-option' value='" . esc_attr( $term->slug ) . "' selected>" 
							. esc_html( $term->name ) . "</option>"; 
					}
					else {
						echo "<option class='age-ranges-option' value='" . esc_attr( $term->slug ) . "'>" 
							. esc_html( $term->name ) . "</option>"; 
					}
				}
			?>
			</select>
		<?php
		}
		
		/** 
		 * Handles saving the Age Range for a program
		 * @param int $post_id Associated Post ID
		 * @return unknown
		 */
		function age_ranges_save( $post_id ) {
			$post = get_post( $post_id );

			// Verify the nonce, capabilites, and this is not an auto-save, otherwise cancel save
			if ( wp_verify_nonce( $_POST['age_ranges_nonce'], 'taxonomy_age_ranges' ) &&
				 current_user_can( 'edit_post', $post_id ) &&
				 !( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) ) {
				if ( $post->post_type == 'post' ) {
					$term = sanitize_html_class( $_POST[ 'post_age_ranges' ] );
					// Cancel save on default option to avoid different case term duplication
					if ( 'none' === strtolower( $term ) ) {
						return $post_id;
					}
					wp_set_object_terms( $post_id, $term, 'age-ranges' );
					return $term;
				}
			}
			
			// Cancel save on any invalid criteria
			return $post_id;
		}

		/**
		 * Create a taxonomy to register Brochure Editions
		 */
		function brochure_editions() {
			$labels = array(
					'name'              => _x( 'Brochures', 'taxonomy general name' ),
					'singular_name'     => _x( 'Brochure', 'taxonomy singular name' ),
					'search_items'      => __( 'Search Brochures' ),
					'all_items'         => __( 'All Brochures' ),
					'parent_item'       => __( 'Parent Brochure' ),
					'parent_item_colon' => __( 'Parent Brochure:' ),
					'edit_item'         => __( 'Edit Brochure' ),
					'update_item'       => __( 'Update Brochure' ),
					'add_new_item'      => __( 'Add New Brochure' ),
					'new_item_name'     => __( 'New Brochure' ),
					'menu_name'         => __( 'Brochure Editions' )
			);
		
			register_taxonomy(
				'brochure-editions',
				'post',
				array(
					'hierarchical'		=> false,
					'labels'			=> $labels,
					'public'			=> true,
					'query_var'			=> true,
					'rewrite' 			=> array( 'slug' => 'brochure-editions' ),
					'show_admin_column'	=> true,
					'show_in_nav_menus'	=> true,
					'show_tagcloud'		=> true,
					'show_ui'			=> true,
					'capabilities' 		=> array(
						'assign_terms' 	=> 'edit_posts',
						'delete_terms' 	=> 'edit_posts',
						'edit_terms' 	=> 'edit_posts',
						'manage_terms' 	=> 'edit_posts'
					)
				)
			);
		}
		
		/**
		 * Display drop-down meta box on the post edit page for Brochure Editions
		 * @param object $post Post object
		 */
		function brochure_editions_meta_box_style( $post ) {
			echo '<input type="hidden" name="brochure_editions_nonce" id="brochure_editions_nonce" value="' .
					wp_create_nonce( 'taxonomy_brochure_editions' ) . '" />';
		
			// Get all theme taxonomy terms
			$terms = get_terms( 'brochure-editions', array( 'hide_empty' => '0' ) );
		
			?>
			<select name='post_brochure_editions' id='post_brochure_editions' class='required'>
			<?php 
		        $names = wp_get_object_terms( $post->ID, 'brochure-editions' ); 
		        ?>
		        <option class='brochure-editions-option' value='none' 
		        	<?php if ( !count( $names ) ) echo 'selected';?>>None</option>
		        <?php
				foreach ( $terms as $term ) {
					if ( !is_wp_error( $names ) && !empty( $names ) && !strcmp( $term->slug, $names[0]->slug )) {
						echo "<option class='brochure-editions-option' value='" . esc_attr( $term->slug ) . "' selected>" 
							. esc_html( $term->name ) . "</option>"; 
					}
					else {
						echo "<option class='brochure-editions-option' value='" . esc_attr( $term->slug ) . "'>" 
							. esc_html( $term->name ) . "</option>"; 
					}
				}
			?>
			</select>
		<?php
		}
		
		/** 
		 * Handles saving the Brochure Edition for a program
		 * @param int $post_id Associated Post ID
		 * @return unknown
		 */
		function brochure_editions_save( $post_id ) {
			$post = get_post( $post_id );

			// Verify the nonce, capabilites, and this is not an auto-save, otherwise cancel save
			if ( wp_verify_nonce( $_POST['brochure_editions_nonce'], 'taxonomy_brochure_editions' ) &&
				 current_user_can( 'edit_post', $post_id ) &&
				 !( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) ) {
				if ( $post->post_type == 'post' ) {
					$term = sanitize_html_class( $_POST[ 'post_brochure_editions' ] );
					// Cancel save on default option to avoid different case term duplication
					if ( 'none' === strtolower( $term ) ) {
						return $post_id;
					}
					wp_set_object_terms( $post_id, $term, 'brochure-editions' );
					return $term;
				}
			}
			
			// Cancel save on any invalid criteria
			return $post_id;
		}
		
	
		/**
		 * Create a taxonomy to register Program Locations
		 */
		function locations() {
			$labels = array(
					'name'              => _x( 'Locations', 'taxonomy general name' ),
					'singular_name'     => _x( 'Location', 'taxonomy singular name' ),
					'search_items'      => __( 'Search Locations' ),
					'all_items'         => __( 'All Locations' ),
					'parent_item'       => __( 'Parent Locations' ),
					'parent_item_colon' => __( 'Parent Locations:' ),
					'edit_item'         => __( 'Edit Location' ),
					'update_item'       => __( 'Update Location' ),
					'add_new_item'      => __( 'Add New Locations' ),
					'new_item_name'     => __( 'New Location' ),
					'menu_name'         => __( 'Locations' )
			);
		
			register_taxonomy(
				'program-locations',
				'post',
				array(
					'hierarchical'		=> false,
					'labels'			=> $labels,
					'public'			=> true,
					'query_var'			=> true,
					'rewrite' 			=> array( 'slug' => 'program-locations' ),
					'show_admin_column'	=> true,
					'show_in_nav_menus'	=> true,
					'show_tagcloud'		=> true,
					'show_ui'			=> true,
					'capabilities' 		=> array(
						'assign_terms' 	=> 'edit_posts',
						'delete_terms' 	=> 'edit_posts',
						'edit_terms' 	=> 'edit_posts',
						'manage_terms' 	=> 'edit_posts'
					)
				)
			);
		}

		/**
		 * Display drop-down meta box on the post edit page
		 * @param object $post Post object
		 */
		function locations_meta_box_style( $post ) {
			echo '<input type="hidden" name="program_locations_nonce" id="program_locations_nonce" value="' .
					wp_create_nonce( 'taxonomy_program_locations' ) . '" />';
		
			// Get all theme taxonomy terms
			$terms = get_terms( 'program-locations', array( 'hide_empty' => '0' ) );
		
			?>
			<select name='post_program_locations' id='post_program_locations'>
			<?php 
		        $names = wp_get_object_terms( $post->ID, 'program-locations' ); 
		        ?>
		        <option class='program-locations-option' value='none' 
		        	<?php if ( !count( $names ) ) echo 'selected';?>>None</option>
		        <?php
				foreach ( $terms as $term ) {
					if ( !is_wp_error( $names ) && !empty( $names ) && !strcmp( $term->slug, $names[0]->slug)) {
						echo "<option class='program-locations-option' value='" . $term->slug . "' selected>" 
							. $term->name . "</option>"; 
					}
					else {
						echo "<option class='program-locations-option' value='" . $term->slug . "'>" 
							. $term->name . "</option>"; 
					}
				}
		   ?>
			</select> 
		<?php
		}
		
		/**
		 * Handles saving the Locations for a program
		 * @param int $post_id Associated Post ID
		 * @return unknown
		 */
		function locations_save( $post_id ) {
			$post = get_post( $post_id );
		
			// Verify the nonce, capabilites, and this is not an auto-save, otherwise cancel save
			if ( wp_verify_nonce( $_POST[ 'program_locations_nonce' ], 'taxonomy_program_locations' ) &&
					current_user_can( 'edit_post', $post_id ) &&
					!( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
				if ( $post->post_type == 'post' ) {
					$term = sanitize_html_class( $_POST[ 'post_program_locations' ] );
					// Cancel save on default option to avoid different case term duplication
					if ( 'none' === strtolower( $term ) ) {
						return $post_id;
					}
					wp_set_object_terms( $post_id, $term, 'program-locations' );
					return $term;
				}
			}
				
			// Cancel save on any invalid criteria
			return $post_id;
		}
		
		/**
		 * Create a taxonomy to register Series
		 */
		function series() {
			$labels = array(
					'name'              => _x( 'Series', 'taxonomy general name' ),
					'singular_name'     => _x( 'Series', 'taxonomy singular name' ),
					'search_items'      => __( 'Search Series' ),
					'all_items'         => __( 'All Series' ),
					'parent_item'       => __( 'Parent Series' ),
					'parent_item_colon' => __( 'Parent Series:' ),
					'edit_item'         => __( 'Edit Series' ),
					'update_item'       => __( 'Update Series' ),
					'add_new_item'      => __( 'Add New Series' ),
					'new_item_name'     => __( 'New Series' ),
					'menu_name'         => __( 'Series' )
			);
		
			register_taxonomy(
				'series',
				'post',
				array(
					'hierarchical'		=> false,
					'labels'			=> $labels,
					'public'			=> true,
					'query_var'			=> true,
					'rewrite' 			=> array( 'slug' => 'series' ),
					'show_admin_column'	=> true,
					'show_in_nav_menus'	=> true,
					'show_tagcloud'		=> true,
					'show_ui'			=> true,
					'capabilities' 		=> array(
						'assign_terms' 	=> 'edit_posts',
						'delete_terms' 	=> 'edit_posts',
						'edit_terms' 	=> 'edit_posts',
						'manage_terms' 	=> 'edit_posts'
					)
				)
			);
		}
		
		/**
		 * Display drop-down meta box on the post edit page
		 * @param object $post Post object
		 */
		function series_meta_box_style( $post ) {
			echo '<input type="hidden" name="series_nonce" id="series_nonce" value="' .
					wp_create_nonce( 'taxonomy_series' ) . '" />';
		
			// Get all theme taxonomy terms
			$terms = get_terms( 'series', array( 'hide_empty' => '0' ) );
		
			?>
			<select name='post_series' id='post_series'>
			<?php 
		        $names = wp_get_object_terms( $post->ID, 'series' ); 
		        ?>
		        <option class='series-option' value='none' 
		        	<?php if ( !count( $names ) ) echo 'selected';?>>None</option>
		        <?php
				foreach ( $terms as $term ) {
					if ( !is_wp_error( $names ) && !empty( $names ) && !strcmp( $term->slug, $names[0]->slug)) {
						echo "<option class='series-option' value='" . $term->slug . "' selected>" 
							. $term->name . "</option>"; 
					}
					else {
						echo "<option class='series-option' value='" . $term->slug . "'>" 
							. $term->name . "</option>"; 
					}
				}
		   ?>
			</select> 
			<a href="edit-tags.php?taxonomy=series">Add Series</a>
		<?php
		}
		
		/**
		 * Handles saving the Series for a program
		 * @param int $post_id Associated Post ID
		 * @return unknown
		 */
		function series_save( $post_id ) {
			$post = get_post( $post_id );
		
			// Verify the nonce, capabilites, and this is not an auto-save, otherwise cancel save
			if ( wp_verify_nonce( $_POST[ 'series_nonce' ], 'taxonomy_series' ) &&
					current_user_can( 'edit_post', $post_id ) &&
					!( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
				if ( $post->post_type == 'post' ) {
					$term = sanitize_html_class( $_POST[ 'post_series' ] );
					// Cancel save on default option to avoid different case term duplication
					if ( 'none' === strtolower( $term ) ) {
						return $post_id;
					}
					wp_set_object_terms( $post_id, $term, 'series' );
					return $term;
				}
			}
				
			// Cancel save on any invalid criteria
			return $post_id;
		}
		
		/**
		 * Create a taxonomy to register Teachers
		 */
		function teachers() {
			$labels = array(
					'name'              => _x( 'Teachers', 'taxonomy general name' ),
					'singular_name'     => _x( 'Teacher', 'taxonomy singular name' ),
					'search_items'      => __( 'Search Teachers' ),
					'all_items'         => __( 'All Teachers' ),
					'parent_item'       => __( 'Parent Teachers' ),
					'parent_item_colon' => __( 'Parent Teachers:' ),
					'edit_item'         => __( 'Edit Teacher' ),
					'update_item'       => __( 'Update Teacher' ),
					'add_new_item'      => __( 'Add New Teachers' ),
					'new_item_name'     => __( 'New Teacher' ),
					'menu_name'         => __( 'Teachers' )
			);
		
			register_taxonomy(
				'teachers',
				'post',
				array(
					'hierarchical'		=> false,
					'labels'			=> $labels,
					'public'			=> true,
					'query_var'			=> true,
					'rewrite' 			=> array( 'slug' => 'teachers' ),
					'show_admin_column'	=> false,
					'show_in_nav_menus'	=> true,
					'show_tagcloud'		=> true,
					'show_ui'			=> true,
					'capabilities' 		=> array(
						'assign_terms' 	=> 'edit_posts',
						'delete_terms' 	=> 'edit_posts',
						'edit_terms' 	=> 'edit_posts',
						'manage_terms' 	=> 'edit_posts'
					)
				)
			);
		}
		
		/**
		 * Display multiple select checkboxes for Teachers on the post edit page
		 * @param object $post Post object
		 */
		function teachers_meta_box_style( $post ) {
			echo '<input type="hidden" name="teachers_nonce" id="teachers_nonce" value="' .
					wp_create_nonce( 'taxonomy_teachers' ) . '" />';
		
			// Get all theme taxonomy terms
			$terms = get_terms( 'teachers', array( 'hide_empty' => '0' ) );
		    $teachers = wp_get_object_terms( $post->ID, 'teachers' );
		    
		    // Create an array of all teachers associated with the post for comparison
		    $match = array();
		    if ( !is_wp_error( $teachers ) && !empty( $teachers ) ) {
			    foreach ( $teachers as $teacher ) {
					$match[] = $teacher->slug;
				}
			}
			?>
			<ul id='teacherschecklist' class='categorycheckist group form-no-clear'>
			<?php 
	        foreach ( $terms as $term ) {
				?>
				<li class="left">
				<?php 
				$format = '<input type="checkbox" class="teachers_required" name="teachers_list[]" value="%s" %s>%s</input>';
				$checked = '';
				// Check the box if the teacher is already associated
				if ( in_array( $term->slug, $match ) ) {
					$checked = 'checked';
				}
				printf( $format, esc_attr( $term->slug ), $checked, esc_html( $term->name ) );
				?>
				</li>
				<?php 
			}
			?>			
			</ul>
			<div class="teachers-other group">
				<input type="checkbox" class="teachers_required" name="teachers_list[]" value="dns-other">Other</input>
				<input type="text" name="teacher-other" value=""></input>
			</div>
			<div class="notice">Check Other and enter a name to create a new teacher on publish.</div>
			<?php		
	   	}
		
		/**
		 * Handles saving the Teachers for a program
		 * @param int $post_id Associated Post ID
		 * @return unknown
		 */
		function teachers_save( $post_id ) {
			$post = get_post( $post_id );
		
			// Verify the nonce, capabilites, and this is not an auto-save, otherwise cancel save
			if ( wp_verify_nonce( $_POST[ 'teachers_nonce' ], 'taxonomy_teachers' ) &&
					current_user_can( 'edit_post', $post_id ) &&
					!( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
				if ( $post->post_type == 'post' ) {
					$list = $_POST[ 'teachers_list' ];
					
					$save = array();
					foreach ( $list as $teacher ) {
						$teacher = sanitize_html_class( $teacher );
						// If Other is checked, make sure there's a valid name and register it
						if ( $teacher == 'dns-other' ) {
							$value = sanitize_text_field( $_POST[ 'teacher-other' ] );
							if ( !empty( $value ) ) {
								wp_insert_term( $value, 'teachers' );
								$save[] = $value;
							}
						} 
						// Otherwise, just save it to the array
						else {
							$save[] = $teacher;
						}
 					}
 					// Associate all selected terms with the post
 					wp_set_object_terms( $post_id, $save, 'teachers' );
					return $term;
				}
			}
				
			// Cancel save on any invalid criteria
			return $post_id;
		}
	}
}