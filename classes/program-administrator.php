<?php

class dns_program_admin {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'export_csv_handler' ) );
		add_action( 'admin_menu', array( $this, 'program_csv_menu' ) );
	}
	
	/**
	 * Create the Menu Page
	 */
	public function program_csv_menu() {
		add_submenu_page( 'edit.php', 'Export Programs', 'Export Programs', 'manage_options', 
			'export-programs', array( $this, 'export_csv_page' ) );
		add_submenu_page( 'edit.php', 'Update Programs', 'Update Programs', 'manage_options',
			'update-programs', array( $this, 'update_csv_callback' ) );
	}

	/****************************************
	 * Import Functionality					*
	****************************************/
	
	public function update_csv_callback() {
		global $pagenow;
		
		$key_array = array( 
			'postid'			=> 0,
			'programnumber' 	=> 0, 
			'price' 			=> 0, 
			'childprice' 		=> 0, 
			'memberprice' 		=> 0, 
			'memberchildprice' 	=> 0, 
			'pricedetails' 		=> 0,
			'maxparticipants'	=> 0, 
		);
		
		// Process the file upload on form submission
		if ( $pagenow == 'edit.php' && isset( $_POST[ 'dns_update_action' ] ) ) {
			if ( isset( $_FILES[ 'dns_update_file' ] ) ) {
				// Use the WordPress Uploader utility to store the file to be processed
				$upload_file = wp_handle_upload( $_FILES[ 'dns_update_file' ], array( 'test_form' => false ) );
				if ( ( $handle = fopen( $upload_file[ 'file' ], 'r' ) ) !== FALSE ) {
					$file_marker = array( 'row' => 0, 'column' => 0 );
					while ( ( $data = fgetcsv( $handle, 2048, ',' ) ) !== FALSE ) {
						// Determine the column each column of data defined in the key array is stored
						if ( $file_marker[ 'row' ] == 0 ) {
							$file_marker[ 'column' ] = 0;
							foreach ( $data as $label ) {
								if ( array_key_exists( strtolower( $label ), $key_array ) ) {
									$key_array[ strtolower( $label ) ] = $file_marker[ 'column' ];
								}
								$file_marker[ 'column' ]++;
							}
						}
						else {
							// Retrieve post id and load post
							$post_id = $data[ $key_array[ 'postid' ] ];
							$post = get_post( $post_id );
							
							// Process program data if it exists
							if ( !empty( $post ) ) {
								// Update the program number, if valid overwrite, if blank empty, else leave alone
								$program_number = trim( $data[ $key_array[ 'programnumber' ] ] );
								if ( preg_match( DNS_PROGRAM_NUMBER_FORMAT, $program_number ) ) {
									update_post_meta( $post_id, 'dns_program_number', $program_number );
								}
								elseif ( empty( $program_number ) ) {
									update_post_meta( $post_id, 'dns_program_number', '' );
								}
								
								$max_participants = trim( $data[ $key_array[ 'maxparticipants' ] ] );
								if ( is_numeric( $max_participants ) && intval( $max_participants ) > 0 ) {
									update_post_meta( $post_id, 'dns_max_participants', intval( $max_participants ) );
								}
								elseif ( empty( $max_participants ) ) {
									update_post_meta( $post_id, 'dns_max_participants', '' );
								}
								
								$price_adult = trim( $data[ $key_array[ 'price' ] ], '$' );
								if ( preg_match( CURRENCY_FORMAT, $price_adult ) ) {
									update_post_meta( $post_id, 'dns_price_adult', number_format( floatval( $price_adult ), 2 ) );
								if ( floatval( $price_adult ) == 0 ) {
										update_post_meta( $post_id, 'dns_pa_enable', '' );
									}
									else {
										update_post_meta( $post_id, 'dns_pa_enable', 'on' );
									}									
								}
								elseif ( empty( $price_adult ) ) {
									update_post_meta( $post_id, 'dns_price_adult', '0' );
									update_post_meta( $post_id, 'dns_pa_enable', '' );
								}
								
								$price_child = trim( $data[ $key_array[ 'childprice' ] ], '$' );
								if ( preg_match( CURRENCY_FORMAT, $price_child ) ) {
									update_post_meta( $post_id, 'dns_price_child', number_format( floatval( $price_child ), 2 ) );
									if ( floatval( $price_child ) == 0 ) {
										update_post_meta( $post_id, 'dns_pc_enable', '' );
									}
									else {
										update_post_meta( $post_id, 'dns_pc_enable', 'on' );
									}									
								}
								elseif ( empty( $price_child ) ) {
									update_post_meta( $post_id, 'dns_price_child', '0' );
									update_post_meta( $post_id, 'dns_pc_enable', '' );
								}
								
								$mem_price_adult = trim( $data[ $key_array[ 'memberprice' ] ], '$' );
								if ( preg_match( CURRENCY_FORMAT, $mem_price_adult ) ) {
									update_post_meta( $post_id, 'dns_mem_price_adult', number_format( floatval( $mem_price_adult ), 2 ) );
									if (  floatval( $mem_price_adult ) == 0 ) {
										update_post_meta( $post_id, 'dns_mpa_enable', '' );
									}
									else {
										update_post_meta( $post_id, 'dns_mpa_enable', 'on' );
									}
																	}
								elseif ( empty( $mem_price_adult ) ) {
									update_post_meta( $post_id, 'dns_mem_price_adult', '0' );
									update_post_meta( $post_id, 'dns_mpa_enable', '' );
								}
								
								$mem_price_child = trim( $data[ $key_array[ 'memberchildprice' ] ], '$' );
								if ( preg_match( CURRENCY_FORMAT, $mem_price_child ) ) {
									update_post_meta( $post_id, 'dns_mem_price_child', number_format( floatval( $mem_price_child ), 2 ) );
									if ( floatval( $mem_price_child ) == 0 ) {
										update_post_meta( $post_id, 'dns_mpc_enable', '' );
									}
									else {
										update_post_meta( $post_id, 'dns_mpc_enable', 'on' );
									}
								}
								elseif ( empty( $mem_price_child ) ) {
									update_post_meta( $post_id, 'dns_mem_price_child', '0' );
									update_post_meta( $post_id, 'dns_mpc_enable', '' );
								}
								
							}
							// Skip an unlisted program id
							else {
								continue;
							}
						}
						
						$file_marker[ 'row' ]++;
					}
					fclose($handle);
				}
			}
		}
		?>
			<form method="post" action="" enctype="multipart/form-data">
				<h2>Update Program Data</h2>
				<p>Supply a CSV file with a column labeled 'postid' and columns for 'programnumber' and the prices</p>
				<input type="file" name="dns_update_file" size="255" tabindex="1" />
				<input type="submit" name="dns_update_action" text="Update" tabindex="2" />
			</form>
		<?php 
	}
	
	
	/****************************************
	 * Export Functionality					*
	 ****************************************/

	/**
	 * Callback for the Export CSV action
	 */ 
	function export_csv_handler() {
		global $pagenow;
		
		if ( $pagenow == 'edit.php' && isset( $_GET[ 'export' ] ) && $_GET[ 'export' ] == 'programs' 
				&& isset( $_GET[ 'brochure' ] ) ) {
			$brochure = ( $_GET[ 'brochure' ] === 'all' ) ? '' : $_GET[ 'brochure' ];
			$this->export_csv_callback( $brochure );
			
			// Required to terminate request and download file
			die();
		}
	}
	
	
	function export_csv_page() {	
		$brochure_editions = get_terms( array( 'brochure-editions' ) );
		?>
			<form method="post" action="" enctype="multipart/form-data">
				<h2>Export Program Data</h2>
				<p>Select 'All' to export all programs, or an individual Brochure Edition for all programs in that edition.</p>
				<label for="brochure">
					Brochure Edition
					<select id="brochure" name="brochure">
						<option value="all">All</option>
					<?php 
						foreach ( $brochure_editions as $edition ) {
							printf( '<option value="%s">%s (%s)</option>', $edition->slug, $edition->name, $edition->count );
						}
					?>
					</select>
				</label>
				<a href="edit.php?export=programs" id="dns_export_program_csv">Export</a>
			</form>
		<?php 
	}
	
	/**
	 * Callback for the Export CSV action
	 */
	function export_csv_callback( $brochure ) {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( 'You are not alowed to export the program list.' );
		}

		$filedate = date( 'Ymd-gia' );
		$filename = sprintf( 'program-export-%s.csv', $filedate );
		
		if ( !empty( $brochure ) ) {
			$filename = sprintf( 'program-export-%s-%s.csv', $brochure, $filedate );
		}
		
		$this->send_headers( $filename );
		echo $this->process_program_csv_export( $brochure );
	}
	
	/**
	 * Send response headers to prompt a CSV download
	 * Re: http://stackoverflow.com/questions/4249432/export-to-csv-via-php
	 */
	function send_headers( $filename ) {
		// Disable Caching
		$now = gmdate("D, d M Y H:i:s");
		header( 'Expires: 0');
		header( 'Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0' );
		header( "Last-Modified: {$now} GMT" );
		
		// Disposition/Encoding on the File Body; Set download filename
		header( 'Content-Type: text/csv' );
		header( "Content-Disposition: attachment; filename={$filename}" );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Pragma: no-cache' );
	}

	/**
	 * Process the Program Listing and turn into a CSV output buffer
	 * @return string Full CSV output buffer
	 */
	public function process_program_csv_export( $brochure ) {
		global $dns_taxonomies;
		
		$results = $this->retrieve_program_data( $brochure );
				
		ob_start();
		$fh = @fopen( 'php://output', 'w' );
		$header_array = array( 'postid', 'status', 'title', 'description', 'author', 'programnumber', 'categories', 'brochureeditions', 'ages', 
				'teachers', 'locations', 'series', 'frequency', 'startdate', 'enddate', 'starttime', 'endtime', 'daysofweek', 
				'dayofmonth', 'nextdate', 'price', 'childprice', 'memberprice', 'memberchildprice', 'pricedetails', 'maxparticipants'
		 );
		fputcsv( $fh, $header_array );
		
		if ( $results->have_posts()) {
			while ( $results->have_posts() ) {
				$results->the_post();
				fputcsv( $fh, $this->process_program_row( get_the_ID() ) );
			}
		}
		
		// Return the complete output buffer
		fclose($fh);
		return ob_get_clean();
	}
	
	public function retrieve_program_data( $brochure ) {
		$array = array();
		$args = array(
			'post_type' 		=> 'post',
			'post_status' 		=> array( 'draft', 'pending', 'publish' ),
			'posts_per_page'	=> -1
		);

		if ( !empty( $brochure ) ) {
			$args[ 'tax_query' ] = array(
				array(
					'taxonomy' => 'brochure-editions',
					'field' => 'slug',
					'terms' => $brochure
				)
			);
		}
		
		return new WP_Query( $args );
	}
	
	/**
	 * Formats each column of the exported CSV document, by addiing indexed array items matching
	 * the column names by index
	 * @param int $pid Program ID (from post table)
	 * @return array Array of Program Data, indexed with CSV column names
	 */
	public function process_program_row( $pid ) {
		global $post;
		$row[ 'postid' ] 			= $pid;
		$row[ 'status' ]			= $post->post_status;
		$row[ 'title' ] 			= get_the_title();
		$row[ 'description' ] 		= get_the_content();
		$row[ 'author' ] 			= get_the_author();
		$row[ 'programnumber' ] 	= get_post_meta( $pid, 'dns_program_number', true );
		$row[ 'categories' ] 		= $this->check_terms( 'category' );
		$row[ 'brochureeditions' ] 	= $this->check_terms( 'brochure-editions' );
		$row[ 'ages' ] 				= $this->check_terms( 'age-ranges' );
		$row[ 'teachers' ] 			= $this->check_terms( 'teachers' );
		$row[ 'locations' ] 		= $this->check_terms( 'program-locations' );
		$row[ 'series' ] 			= $this->check_terms( 'series' );
		$row[ 'frequency' ] 		= $this->enumerate_frequency( get_post_meta( $pid, 'dns_frequency', true ) );
		$row[ 'startdate' ] 		= get_post_meta( $pid, 'dns_start_date', true );
		$row[ 'enddate' ] 			= get_post_meta( $pid, 'dns_end_date', true );
		$row[ 'starttime' ] 		= get_post_meta( $pid, 'dns_start_time', true );
		$row[ 'endtime' ] 			= get_post_meta( $pid, 'dns_end_time', true );
		$row[ 'daysofweek' ] 		= $this->enumerate_days( get_post_meta( $pid, 'dns_days_week', true ) ); 
		$row[ 'dayofmonth' ] 		= get_post_meta( $pid, 'dns_day_month', true );
		$row[ 'nextdate' ] 			= $this->get_next_date( $row );
		$row[ 'price' ] 			= get_post_meta( $pid, 'dns_price_adult', true );
		$row[ 'childprice' ] 		= get_post_meta( $pid, 'dns_price_child', true );
		$row[ 'memberprice' ] 		= get_post_meta( $pid, 'dns_mem_price_adult', true );
		$row[ 'memberchildprice' ] 	= get_post_meta( $pid, 'dns_mem_price_child', true );
		$row[ 'pricedetails' ] 		= get_post_meta( $pid, 'dns_price_details', true );
		$row[ 'maxparticipants' ] 	= get_post_meta( $pid, 'dns_max_participants', true );
		return $row;
	}
	
	/**
	 * Transform a set of meta terms into a string
	 * @param string $slug Slug index of the taxonomy
	 */
	public function check_terms( $slug ) {
		$terms = get_the_terms( $pid, $slug );
		$output = array();
		if ( !empty( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $slug == 'age-ranges' ) {
					$output[] = 'Age ' . $term->name;		
				}
				else {
					$output[] = $term->name;
				}
			}
			return join( ' | ', $output );
		}
		return '';
	}
	
	/**
	 * Format the days of week string, enumeration based on Monday == 1
	 * @param string $slug Meta index for day array
	 */
	public function enumerate_days( $days ) {
		$day_map = array( 1 => 'M', 'Tu', 'W', 'Th', 'F', 'Sa', 'Su' );
		$output = array();
		if ( !empty( $days ) && is_array( $days ) ) {
			if ( count( $days ) == 0 ) {
				return '';
			}
			
			foreach( $days as $day ) {
				$output[] = $day_map[ $day ];	
			}
			return join( ' | ', $days );
		}
		return '';
	}
	
	/**
	 * Put Schedule Frequency in human readable format
	 * Once = 0
	 * Weekly = 1
	 * Monthly = 2
	 * 
	 * @param string $freq Text number specifying schedule frequency
	 * @return string 
	 */
	function enumerate_frequency( $freq ) {
		switch( $freq ) {
			case '0':
				$freq = 'Once';
				break;
			case '1':
				$freq = 'Weekly';
				break;
			case '2':
				$freq = 'Monthly';
				break;
			default:
				$freq = '';
				break;
		}
		return $freq;
	}
	
	/**
	 * Find the next date for the program
	 * @param array $row Other row data, must include all schedule data
	 */
	public function get_next_date( $row ) {
		$frequency = $row[ 'frequency' ];
		$now = gmdate( 'm/d/Y' );
		
		switch( $frequency ) {
			// Once
			case '0':
				return $row[ 'startdate' ];
				break;
			// Weekly
			case '1':
				// TODO - Write date comparsion
				return $row[ 'startdate' ];
				break;
			// Monthly
			case '2':
				// TODO - Write date comparsion
				return $row[ 'startdate' ];
				break;
			default: 
				break;
		}
	}
}