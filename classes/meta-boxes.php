<?php
/**
 * Class registers meta boxes used by this plugin on the post page
 * 
 * @since 1.1
 * @package dnsprograms-plugins
 */
if ( !class_exists( 'dns_meta_boxes' ) ) {
	class dns_meta_boxes {
		private static $calendar_nonce_prefix		= 'program_calendar_style';
		private static $details_nonce_prefix		= 'program_details_style';
		private static $edit_screen_user_options	= 'manageedit-postcolumnshidden';
		private static $hide_columns_user_option	= 'dns-hide-custom-columns';
		private static $print_custom_column_nonce	= true;
		
		public function __construct() {
			// Register save and meta boxes
			add_action( 'admin_init', array( $this, 'save_post_handlers' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_admin_boxes' ) );
				
			// Limit program description character length
			add_action( 'edit_form_after_editor', array( $this, 'edit_form_character_count' ) );
			
			// Add Quick Edit functionality
			add_filter( 'manage_edit-post_columns', array( $this, 'edit_posts_columns' ) );
			add_action( 'manage_posts_custom_column', array( $this, 'list_program_columns' ), 10, 2 );
			add_action( 'quick_edit_custom_box', array( $this, 'add_quick_edit' ) );
			add_action( 'admin_print_scripts-edit.php', array( $this, 'quick_edit_scripts' ), 11 );
				
			// Hack to hide the extra program columns in the program listing by default
			add_action( 'wp_login', array( $this, 'hide_edit_program_columns' ) );
		}
		
		/**
		 * Register program meta boxes
		 */
		public function add_admin_boxes() {
			add_meta_box( 'dns-program-calendar-div', __( 'Program Calendar' ), 
				array( $this, 'program_calendar_style' ), 'post', 'normal', 'high', 2 );
			add_meta_box( 'dns-program-details-div', __( 'Program Details' ), 
				array( $this, 'program_details_style' ), 'post', 'normal', 'high', 1 );
		}
		
		/**
		 * Register post save routines
		 */
		public function save_post_handlers() {
			add_action( 'save_post', array( $this, 'program_calendar_save' ) );
			add_action( 'save_post', array( $this, 'program_details_save' ) );
		}
				
		/**
		 * Register Quick Edit JS in footer
		 */
		function quick_edit_scripts( ) {
			if ( 'post' == get_query_var( 'post_type' ) ) {
				wp_enqueue_script( 'dns-quick-edit', plugins_url( '../js/dns-quick-edit.js', __FILE__ ), array(), 
					DNS_PROGRAM_PLUGIN_VERSION, true );
			}
		}
		
		/******************************
		 * Quick Edit Functionality
		 ******************************/
		
		/**
		 * Handler to insert new Quick Edit fields
		 * 
		 * @param string $column_name Column Slug
		 * @param string $post_type Current post type
		 */
		public function add_quick_edit( $column_name ) {
		    if ( self::$print_custom_column_nonce ) {
		    	wp_nonce_field( 'dns-quick-edit-program', 'dns-quick-edit-program_nonce' );
				printf( '<fieldset class="inline-edit-col-right inline-edit-col-program">' );
				printf( '<legend>Program Number & Prices</legend>' );
				self::$print_custom_column_nonce = false;
		    }
		    
		    // Depends on the column order set in edit_post_columns to properly wrap
		    if ( $column_name == 'dns_start_date' ) {
				printf( '</fieldset>');
				printf( '<fieldset class="inline-edit-col-left inline-edit-col-program">' );
				printf( '<legend>Program Date/Time</legend>' );
			}
			if ( $column_name == 'dns_frequency' ) {
				printf( '</fieldset>');
				printf( '<fieldset class="inline-edit-col-center inline-edit-col-program">' );
				printf( '<legend>Program Calendar Control</legend>' );
			}
			
		    $label	= '';
			$type	= 'text';
			$class	= '';
			switch ( $column_name ) {
				case 'dns_program_number':
					$label = 'Program Number';
					$class = 'class="required"';
				break;
				case 'dns_price_adult':
					$label = 'Adult ($)';
				break;
				case 'dns_mem_price_adult':
					$label = 'Member Adult ($)';
				break;
				case 'dns_price_child':
					$label = 'Child ($)';
				break;
				case 'dns_mem_price_child':
					$label = 'Member Child ($)';
				break;
				case 'dns_start_date':
					$label = 'Start Date';
					$class = 'class="required"';
				break;
				case 'dns_end_date':
					$label = 'End Date';
					$class = 'class="required"';
				break;
				case 'dns_start_time':
					$label = 'Start Time';
					$class = 'class="required"';
				break;
				case 'dns_end_time':
					$label = 'End Time';
					$class = 'class="required"';
				break;
				case 'dns_frequency':
					$label = 'Frequency';
				break;
				case 'dns_days_week':
					$label = 'Days of Week';
				break;
				case 'dns_day_month':
					$label = 'Day of Month';
				break;
			}

			// Print out text inputs
			if ( !empty( $label ) && $type == 'text' ) {
				printf( '<label class="inline-edit-%s" for="%s">', $column_name, $column_name );
				printf( '<span class="title">%s</span><input name="%s" type="text" %s /></label>', $label, $column_name, $class );
			}
			
			// Depends on 'dns_day_month' being set last in edit_posts_columns array
			if ( $column_name == 'dns_day_month' ) {
				printf( '</fieldset>' );
			}
		}
		
		/**
		 * Handler to modify the WP_List_Table column array
		 * 
		 * @param array $columns Original column array
		 * @return array Updated column array
		 */
		public function edit_posts_columns( $columns ) {
			$new_columns = array(
				'dns_program_number'	=> __( 'Program Number', 'dnsprograms-plugins' ),
				'dns_price_adult'		=> __( 'Adult ($)', 'dnsprograms-plugins' ),
				'dns_mem_price_adult'	=> __( 'Member Adult ($)', 'dnsprograms-plugins' ),
				'dns_price_child'		=> __( 'Child ($)', 'dnsprograms-plugins' ),
				'dns_mem_price_child'	=> __( 'Member Child ($)', 'dnsprograms-plugins' ),
				'dns_start_date'		=> __( 'Start Date', 'dnsprograms-plugins' ),
				'dns_end_date'			=> __( 'End Date', 'dnsprograms-plugins' ),
				'dns_start_time'		=> __( 'Start Time', 'dnsprograms-plugins' ),
				'dns_end_time'			=> __( 'End Time', 'dnsprograms-plugins' ),
				'dns_frequency'			=> __( 'Frequency', 'dnsprograms-plugins' ),
				'dns_days_week'			=> __( 'Days of Week', 'dnsprograms-plugins' ),
				'dns_day_month'			=> __( 'Day of Month', 'dnsprograms-plugins' ),
			);
			return array_merge( $columns, $new_columns );
		}
		
		/**
		 * Create a one-shot login event to suppress the new Meta boxes created from column listings
		 * Doing it in this manner defaults to columns to off for each user, and allows them to turn on
		 * the columns without plugin interference
		 * 
		 * @param integer $user_id User Identifier
		 */
		public function hide_edit_program_columns( $user ) {
			// Retrieve the current users ID
			$user_id = get_user_by( 'login', $user );
						
			$hidden_oneshot = get_user_meta( $user_id, self::$hide_columns_user_option, true );
			
			if ( !$hidden_oneshot ) {
				// Get the hidden columns on the edit-post screen, default to empty array if non set
				$hidden_columns = get_user_meta( $user_id, self::$edit_screen_user_options, true );	
				if ( !is_array( $hidden_columns ) ) {
					$hidden_columns = array();
				}
					
				// List of new columns to hide
				$new_hidden_columns = array( 
					'dns_program_number',
					'dns_price_adult',
					'dns_mem_price_adult',
					'dns_price_child',
					'dns_mem_price_child',
					'dns_start_date', 
					'dns_end_date', 
					'dns_start_time', 
					'dns_end_time',
					'dns_frequency', 
					'dns_days_week', 
					'dns_day_month' 
				);
		
				// Merge and prune duplicates
				$update_hidden = array_filter( array_unique( array_merge( $new_hidden_columns, $hidden_columns ) ) );

				// Update user settings
				$success = update_user_meta( $user_id, self::$edit_screen_user_options, $update_hidden );
				if ( $success ) {
					update_user_meta( $user_id, self::$hide_columns_user_option, true );
				}
			}
		}
		
		/**
		 * Display the values of each column in the WP_List_Table
		 * @param unknown $column_name
		 * @param unknown $post_id
		 */
		function list_program_columns( $column_name, $post_id ) {
			switch ( $column_name ) {
				default:
					// Check !! means translate the following item to a boolean value
					$text = get_post_meta( $post_id , $column_name , true );
					echo $text;
				break;
			}
		}
				
		/**
		 * Register a message showing the program description character limit
		 */
		public function edit_form_character_count() { ?>
			<div id="edit-character-count">Character Count: 
				<span class="character-count">-</span> 
				<span class="character-limit"> (Maximum Allowed: <?php echo DNS_PROGRAM_CHARACTER_LIMIT; ?>)</span>
			</div>
		<?php
		}
		
		/*******************************
		 * Standard Edit Funcitonality
		 *******************************/
		
		/**
		 * Display the Program Calendar box
		 * @param WP_Post $post Post object
		 */
		public function program_calendar_style( $post ) {
			$settings = array(
				'dns_start_date'			=> '',
				'dns_end_date'				=> '',
				'dns_start_time'			=> '',
				'dns_end_time'				=> '',
				'dns_frequency'				=> 0,
				'dns_days_week'				=> array(),
				'dns_day_month'				=> 0
			);
			foreach( $settings as $key => $value ) {
				$post_meta = get_post_meta( $post->ID, $key, true );
				if ( !empty( $post_meta ) ) {
					$settings[ $key ] = $post_meta;
				}
			}
			$settings[ 'dns_frequency' ] = intval( $settings[ 'dns_frequency' ] );
			
			wp_nonce_field( self::$calendar_nonce_prefix, self::$calendar_nonce_prefix . '_nonce' );
			?>
			<div class="setting">
				<label for="dns_start_date">Start Date</label>
				<input type="text" id="dns_start_date" name="dns_start_date" class="required"
					value="<?php echo esc_html__( $settings[ 'dns_start_date' ] ); ?>" />
			</div>
			<div class="setting">
				<label for="dns_end_date">End Date</label>
				<input type="text" id="dns_end_date" name="dns_end_date" class="required"
					value="<?php echo esc_html__( $settings[ 'dns_end_date' ] ); ?>" />
			</div>
			<div class="setting">
				<label for="dns_start_time">Start Time</label>
				<input type="text" id="dns_start_time" name="dns_start_time" class="required"
					value="<?php echo esc_html__( $settings[ 'dns_start_time' ] ); ?>" />
			</div>
			<div class="setting">
				<label for="dns_end_time">End Time</label>
				<input type="text" id="dns_end_time" name="dns_end_time" class="required"
					value="<?php echo esc_html__( $settings[ 'dns_end_time' ] ); ?>" />
			</div>
			<div id="frequency_container" class="setting group">
				<label class="left" for="dns_frequency">Frequency</label>
				<div class="frequency_inner left">
					<input type="radio" name="dns_frequency" value="0" 
						<?php echo checked( $settings[ 'dns_frequency' ], 0 ); ?>>Once</input>
					<input type="radio" name="dns_frequency" value="1"
						<?php checked( $settings[ 'dns_frequency' ], 1 ); ?>>Weekly</input>
					<input type="radio" name="dns_frequency" value="2" 
						<?php checked( $settings[ 'dns_frequency' ], 2 ); ?>>Monthly</input>
				</div>
			</div>
			<div id="days_container" class="setting group <?php 
				if ( $settings[ 'dns_frequency' ] != 1 ) { echo 'hide'; } ?>">
				<label class="left" for="dns_weekdays">Day(s) of Week</label>
				<div class="days_inner left">
				<?php
				$set_days = $settings[ 'dns_days_week' ];
				$dow_array = array( 1 => 'Monday', 'Tuesday', 'Wedsnesday', 'Thursday',
						'Friday', 'Saturday', 'Sunday' );
				foreach( $dow_array as $key => $dow ) {
					$checked = checked( in_array( $key, $set_days ), true, false );
					$format = '<label><input type="checkbox" name="dns_days_week[]" value="%s" %s />%s</label>';
					printf( $format, esc_attr__( $key ), $checked, esc_html__( $dow ) );
				}	
				?>
				</div>
			</div>
			<div id="month_container" class="setting group <?php 
				if ( $settings[ 'dns_frequency' ] != 2 ) { echo 'hide'; } ?>">
				<div class="group">
					<label class="left" for="dns_day_month">Day of Month</label>
					<div class="month_inner left">
						<select name="dns_day_month" >
							<option value="0" />
						<?php 
							$month_day = $settings[ 'dns_day_month' ];
							for ( $i = 0; $i <= 31; $i++ ) {
								$selected = selected( $month_day, $i, false );
								printf( '<option value="%s" %s>%s</option>', esc_attr( $i ), $selected, 
									esc_html__( $i ) );
							}
						?>
						</select>
					</div>
				</div>
				<div class="notice group"><strong>Note:</strong> If the date falls after the end of a month, 
					the last day of that month will be selected</div>
			</div>
			<?php
		}
		
		/**
		 * Display the Program Details box
		 * @param WP_Post $post Post object
		 */
		public function program_details_style( $post ) {
			$settings = array( 
				'dns_program_number'	=> '',
				'dns_pa_enable'			=> false,
				'dns_pc_enable'			=> false,
				'dns_price_child'		=> '',
				'dns_price_adult'		=> '',
				'dns_mpa_enable'		=> false,
				'dns_mpc_enable'		=> false,
				'dns_mem_price_child'	=> '',
				'dns_mem_price_adult'	=> '',
				'dns_price_details'		=> ''
			 );
			foreach( $settings as $key => $value ) {
				$post_meta = get_post_meta( $post->ID, $key, true );
				if ( !empty( $post_meta ) ) {
					$settings[ $key ] = $post_meta;
				}
			}

			wp_nonce_field( self::$details_nonce_prefix, self::$details_nonce_prefix . '_nonce' );
			?>
			<div class="setting">
				<label for="dns_program_number">Program Number</label>
				<input type="text" name="dns_program_number"
					value="<?php echo esc_html__( $settings[ 'dns_program_number' ] ); ?>" />
			</div>
			<div class="setting">
				<label for="dns_price_adult">Price - Adult</label>
				<input type="checkbox" id="dns_pa_enable" name="dns_pa_enable"
					<?php checked( $settings[ 'dns_pa_enable' ], 'on' ); ?> />
				<input type="text" name="dns_price_adult" class="required" 
					value="<?php echo esc_html__( $settings[ 'dns_price_adult' ] ); ?>" 
					<?php disabled( $settings[ 'dns_pa_enable' ], false )?> />
			</div>
			<div class="setting">
				<label for="dns_price_child">Price - Child</label>
				<input type="checkbox" id="dns_pc_enable" name="dns_pc_enable"
					<?php checked( $settings[ 'dns_pc_enable' ], 'on' ); ?> />
				<input type="text" name="dns_price_child" class="required" 
					value="<?php echo esc_html__( $settings[ 'dns_price_child' ] ); ?>" 
					<?php disabled( $settings[ 'dns_pc_enable' ], false )?> />
			</div>
			<div class="setting">
				<label for="dns_mem_price_adult">Member Price - Adult</label>
				<input type="checkbox" id="dns_mpa_enable" name="dns_mpa_enable"
					<?php checked( $settings[ 'dns_mpa_enable' ], 'on' ); ?>  />
				<input type="text" name="dns_mem_price_adult" class="required" 
					value="<?php echo esc_html__( $settings[ 'dns_mem_price_adult' ] ); ?>" 
					<?php disabled( $settings[ 'dns_mpa_enable' ], false )?> />
			</div>
			<div class="setting">
				<label for="dns_mem_price_child">Member Price - Child</label>
				<input type="checkbox" id="dns_mpc_enable" name="dns_mpc_enable"
					<?php checked( $settings[ 'dns_mpc_enable' ], 'on' ); ?> />
				<input type="text" name="dns_mem_price_child" class="required" 
					value="<?php echo esc_html__( $settings[ 'dns_mem_price_child' ] ) ; ?>" 
					<?php disabled( $settings[ 'dns_mpc_enable' ], false )?> />
			</div>
			<div class="setting">
				<label for="dns_price_details">More Price Details</label>
				<input type="text" name="dns_price_details" 
					value="<?php echo esc_html__( $settings[ 'dns_price_details' ] ) ; ?>" />
			</div>
			<ul class="notice">
				<li>Check an age range to enable it</li>
				<li>For a Free age range, check and enter 0</li>
				<li>Enter amounts in dollars and cents, $ signs are not needed.</li>
			</ul>
			<?php 
		}
		
		/*****************
		 * Save Routines
		 *****************/

		/**
		 * Save handler for the Program Calendar information
		 * @param int $post_id Post ID
		 * @return number Post ID
		 */
		public function program_calendar_save( $post_id ) {
			// Run security check for this save attempt
			if ( false === $this->security_check( $post_id, self::$calendar_nonce_prefix ) &&
					false === $this->security_check( $post_id, 'dns-quick-edit-program' ) ) {
				return $post_id;
			}
				
			// Verify and save the calendar start and end dates and times
			$this->field_updater( $post_id, 'dns_start_date', DATE_FORMAT );
			$this->field_updater( $post_id, 'dns_end_date', DATE_FORMAT );
			$this->field_updater( $post_id, 'dns_start_time' );
			$this->field_updater( $post_id, 'dns_end_time' );
				
			// Validate and save the date schedule, reseting any inactive schedules
			$frequency = $this->field_updater( $post_id, 'dns_frequency', '/[0-2]/', '', 'intval' );
			$dow_array = array();
			$dom = 0;
			switch ( $frequency ) {
				// Saving Weekly schedules
				case 1:
					$input_array = $_POST[ 'dns_days_week' ];
					if ( is_array( $input_array ) ) {
						foreach ( $input_array as $day ) {
							$in_day = intval( $day );
								
							// Verify the value isn't a duplicate and is a day value between 1 and 7
							if ( !array_search( $in_day, $dow_array ) && $in_day > 0 && $in_day < 8  ) {
								$dow_array[] = $in_day;
							}
						}
					}
					break;
					// Saving Monthly schedules
				case '2':
					$dom = intval( $_POST[ 'dns_day_month' ] );
					break;
				default:
					break;
			}
				
			update_post_meta( $post_id, 'dns_days_week', $dow_array );
			update_post_meta( $post_id, 'dns_day_month', $dom );
		}
		
		/**
		 * Save handler for the Program Details information
		 * @param int $post_id Post ID
		 * @return number Post ID
		 */
		public function program_details_save( $post_id ) {
			// Run security check for this save attempt
			if ( false === $this->security_check( $post_id, self::$details_nonce_prefix ) &&
					false === $this->security_check( $post_id, 'dns-quick-edit-program' ) ) {
				return $post_id;
			}
			
			// Verify and save the program number matches the DNS Program Number format, if clear, save blank
			$program_number = $_POST[ 'dns_program_number' ];
			if ( empty( $program_number ) ) {
				update_post_meta( $post_id, 'dns_program_number', '' );
			}
			else {
				$this->field_updater( $post_id, 'dns_program_number', DNS_PROGRAM_NUMBER_FORMAT );
			}
			
			// Verify and save the currency fields for member prices
			$field_array = array( 'dns_price_adult', 'dns_price_child', 'dns_mem_price_adult', 
				'dns_mem_price_child' );
			
			foreach ( $field_array as $field_index ) {
				$this->field_updater( $post_id, $field_index, CURRENCY_FORMAT, '$', 
					array( $this, 'currency_format') );
			}
			
			update_post_meta( $post_id, 'dns-price-details', 
				sanitize_text_field( $_POST[ 'dns-price-details']));
			
			// Save price enables values
			$enable_array = array( 'dns_pa_enable', 'dns_pc_enable', 'dns_mpa_enable', 'dns_mpc_enable' );
			
			foreach ( $enable_array as $enable ) {
				update_post_meta( $post_id, $enable, $_POST[ $enable ] );
			}
		}
		
		/**
		 * Run security checks on a menu section based a particular post/item, nonce,
		 * and the users capabilities
		 * @param int $post_id Selected post/item ID
		 * @param string $section Section Nonce Prefix - without _nonce
		 * @return boolean True if the save is valid
		 */
		public function security_check( $post_id, $section ) {
			// Verify there is a valid nonce
			if ( empty( $_POST[ "{$section}_nonce" ] ) ) {
				return false;
			}
				
			$nonce = $_POST[ "{$section}_nonce" ];
				
			// Cancel saving data for: Invalid nonce, Auto-save, Not a post, user doesn't have edit capabilities
			if ( !wp_verify_nonce( $nonce, $section ) ||
					defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE  ||
					( !isset( $_POST[ 'post_type' ] ) && $_POST[ 'post_type' ] != 'post' ) ||
					!current_user_can( 'edit_post', $post_id ) ) {
				return false;
			}
			
			// If everything is correct, return true 
			return true;
		}
		
		/** 
		 * Format input number into US currency
		 * @param number $value
		 * @return string
		 */
		function currency_format( $value = 0 ) {
			return number_format( floatval( $value ), 2 );
		}
		
		/**
		 * Verify a currency value in the $_POST object, if valid save to the post. Updater allows options 
		 * to trim a character set from the string, match against a regular expression, and call an output
		 * format function.
		 * 
		 * @param int $post_id Post ID
		 * @param int $index Value index name
		 * @param string $regex (Optional) Regex expression string to check field value against
		 * @param string $strip (Optional) Characters to trim() from the field in addition to whitespace
		 * @param string $output_callback (Optional) Callback to format the output prior to saving
		 * @return mixed Sanitized value saved to post meta at $index, or boolean false on an error/mismatch
		 */
		private function field_updater( $post_id, $index, $regex = '/.*/', $strip = '', 
				$output_callback = '' ) {
			if ( isset( $_POST[ $index ] ) ) {
				$value = trim( $_POST[ $index ], $strip );
				if ( !empty( $output_callback )) {
					$value = call_user_func( $output_callback, $value );
				}
				if ( preg_match( $regex, $value ) ) {
					update_post_meta( $post_id, $index, $value );
					return $value;
				}
			}
			return false;
		}
	}
}