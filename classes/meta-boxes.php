<?php
/**
 * Class registers meta boxes used by this plugin on the post page
 */
if ( !class_exists( 'dns_meta_boxes' ) ) {
	class dns_meta_boxes {
		public static $calendar_nonce_prefix = 'program_calendar_style';
		public static $details_nonce_prefix = 'program_details_style';
		
		public function __construct() {
			add_action( 'admin_init', array( $this, 'save_post_handlers' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_admin_boxes' ) );
			add_action( 'edit_form_after_editor', array( $this, 'edit_form_character_count' ) );
		}
		
		public function add_admin_boxes() {
			add_meta_box( 'dns-program-calendar-div', __( 'Program Calendar' ), 
				array( $this, 'program_calendar_style' ), 'post', 'normal', 'high', 2 );
			add_meta_box( 'dns-program-details-div', __( 'Program Details' ), 
				array( $this, 'program_details_style' ), 'post', 'normal', 'high', 1 );
		}
		
		public function save_post_handlers() {
			add_action( 'save_post', array( $this, 'program_calendar_save' ) );
			add_action( 'save_post', array( $this, 'program_details_save' ) );
		}
		
		public function edit_form_character_count() { ?>
			<div id="edit-character-count">Character Count: 
				<span class="character-count">-</span> 
				<span class="character-limit"> (Maximum Allowed: <?php echo DNS_PROGRAM_CHARACTER_LIMIT; ?>)</span>
			</div>
		<?php
		}
		
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
		 * Save handler for the Program Calendar information
		 * @param int $post_id Post ID
		 * @return number Post ID
		 */
		public function program_calendar_save( $post_id ) {
			// Run security check for this save attempt
			if ( false === $this->security_check( $post_id, self::$calendar_nonce_prefix ) ) {
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

		/**
		 * Save handler for the Program Details information
		 * @param int $post_id Post ID
		 * @return number Post ID
		 */
		public function program_details_save( $post_id ) {
			// Run security check for this save attempt
			if ( false === $this->security_check( $post_id, self::$details_nonce_prefix ) ) {
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