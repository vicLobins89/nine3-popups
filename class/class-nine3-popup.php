<?php
/**
 * This class initialisises the plugin and does the setup legwor
 *
 * @package nine3popup
 */

namespace nine3popup;

/**
 * Class init
 */
final class Nine3_Popup {
	/**
	 * The array of popus and where they belong
	 *
	 * @var $popups
	 */
	private $popups;

	/**
	 * Energise!
	 */
	public function __construct() {
		// Styles and scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'nine3_popup_scripts' ] );

		// Import ACF fields.
		include NINE3_POPUPS_PATH . '/acf/acf-fields.php';

		// Setup cpt.
		add_action( 'init', [ $this, 'popup_post_type' ] );

		// Populate popups array.
		add_action( 'init', [ $this, 'populate_popups_array' ] );

		// Render popup.
		add_action( 'wp_footer', [ $this, 'render_popup' ] );

		// Empty option on post save.
		add_action( 'save_post_popup', [ $this, 'empty_popup_option' ] );

		// Ajax callback to drop expire cookie.
		add_action( 'wp_ajax_nine3-popup', [ $this, 'popup_close_callback' ] );
		add_action( 'wp_ajax_nopriv_nine3-popup', [ $this, 'popup_close_callback' ] );

		// Add custom columns.
		add_filter( 'manage_popup_posts_columns', [ $this, 'popup_custom_columns' ] );
		add_action( 'manage_popup_posts_custom_column', [ $this, 'popup_custom_column' ], 10, 2 );
	}

	/**
	 * Scripts and styles.
	 */
	public function nine3_popup_scripts() {
		// Load stylesheet.
		wp_enqueue_style( 'nine3popup-style', NINE3_POPUPS_URI . '/css/style.css', [], '1.0' );

		// Register script.
		wp_register_script( 'nine3popup-script', NINE3_POPUPS_URI . '/dist/build.js', [], '1.0', true );

		// Localize.
		$data = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'nine3_popup' ),
		];

		wp_localize_script( 'nine3popup-script', 'nine3popup', $data );
		wp_enqueue_script( 'nine3popup-script' );
	}

	/**
	 * Callback for cpt register
	 */
	public function popup_post_type() {
		$args = [
			'public'       => true,
			'label'        => __( 'Popups', 'nine3popup' ),
			'menu_icon'    => 'dashicons-editor-expand',
			'show_in_rest' => true,
			'has_archive'  => false,
			'supports'     => [ 'title', 'editor', 'custom-fields' ],
		];
		register_post_type( 'popup', $args );
	}

	/**
	 * Populates our class property $this->popups array
	 * Use get_posts to get all popup posts, then check ACF to determine where they are needed.
	 * Then create associative array keyed by where they belong, ie:
	 * - $this->popups['all_pages'][ ARRAY OF POPUP IDS ];
	 * - $this->popups['selected_pages'][ ARRAY OF SELECTED PAGE IDS ][ ARRAY OF POPUP IDS ];
	 * - $this->popups['custom_urls'][ ARRAY OF SELECTED PAGE URLS ][ ARRAY OF POPUP IDS ];
	 * Then save array to WP option.
	 */
	public function populate_popups_array() {
		// Check if option is set.
		$popups = get_option( 'nine3_popups' );

		if ( $popups ) {
			$this->popups = $popups;
		} else {
			$args = [
				'numberposts' => -1,
				'post_type'   => 'popup',
				'post_status' => 'publish',
			];

			$all_pages_popups = get_posts( $args );
			if ( ! empty( $all_pages_popups ) ) {
				foreach ( $all_pages_popups as $popup ) {
					$popup_location = get_field( 'nine3_popup_select_where', $popup->ID );

					switch ( $popup_location ) {
						case 'All Pages':
							$this->popups['all_pages'][] = $popup->ID;
							break;
						case 'Select Page':
							$page_ids = get_field( 'nine3_popup_page_selector', $popup->ID );
							if ( ! empty( $page_ids ) ) {
								foreach ( $page_ids as $page_id ) {
									$this->popups['selected_pages'][ $page_id ][] = $popup->ID;
								}
							}
							break;
						case 'Custom URL':
							$urls = get_field( 'nine3_popup_custom_url', $popup->ID );
							if ( ! empty( $urls ) ) {
								$urls = preg_split( '/\r\n|[\r\n]/', $urls );
								foreach ( $urls as $url ) {
									$this->popups['custom_urls'][ sanitize_title( wp_unslash( $url ) ) ][] = $popup->ID;
								}
							}
							break;
					}
				}
			}

			// Reset.
			wp_reset_postdata();

			// Save to option.
			update_option( 'nine3_popups', $this->popups );
		}
	}

	/**
	 * Checks if page has popup and renders it
	 */
	public function render_popup() {
		if ( is_admin() ) {
			return;
		}

		// First check if there are any All Pages popups and render them.
		if ( isset( $this->popups['all_pages'] ) ) {
			foreach ( $this->popups['all_pages'] as $popup_id ) {
				if ( ! isset( $_COOKIE[ 'nine3_popup_closed_' . $popup_id ] ) ) {
					include NINE3_POPUPS_PATH . '/template-parts/single-popup-item.php';
				}
			}
		}

		// Then check if there are popups set to the current page ID.
		if ( isset( $this->popups['selected_pages'] ) ) {
			global $post;
			$post_id = $post->ID;

			if ( isset( $this->popups['selected_pages'][ $post_id ] ) ) {
				foreach ( $this->popups['selected_pages'][ $post_id ] as $popup_id ) {
					if ( ! isset( $_COOKIE[ 'nine3_popup_closed_' . $popup_id ] ) ) {
						include NINE3_POPUPS_PATH . '/template-parts/single-popup-item.php';
					}
				}
			}
		}

		// Finally check if there are popups set to the current URL.
		if ( isset( $this->popups['custom_urls'] ) ) {
			global $wp;
			$page_url = sanitize_title( wp_unslash( home_url( $wp->request ) ) );

			foreach ( $this->popups['custom_urls'] as $url => $popups ) {
				if ( $url === $page_url ) {
					foreach ( $popups as $popup_id ) {
						if ( ! isset( $_COOKIE[ 'nine3_popup_closed_' . $popup_id ] ) ) {
							include NINE3_POPUPS_PATH . '/template-parts/single-popup-item.php';
						}
					}
				}
			}
		}
	}

	/**
	 * Once a popup post is saved we need to empty the WP option so everything is up to date
	 */
	public function empty_popup_option() {
		update_option( 'nine3_popups', '' );
	}

	/**
	 * Once a user clicks close we need to drop a cookie and make sure the popup doesn't
	 * reopen on other pages/visists for as long as is required in the popup settings.
	 */
	public function popup_close_callback() {
		// Verify nonce.
		if ( ! isset( $_REQUEST['nonce'] ) && ! wp_verify_nonce( $_REQUEST['nonce'], 'nine3-popup' ) ) { // phpcs:ignore
			echo wp_json_encode( 'Invalid nonce.', 'nine3popup' );
			wp_die();
		}

		// Make sure ID is sent.
		if ( ! isset( $_REQUEST['popup_id'] ) ) {
			echo wp_json_encode( 'No Popup ID provided', 'nine3popup' );
			wp_die();
		}

		// Get popup ID.
		$popup_id = intval( sanitize_title( wp_unslash( $_REQUEST['popup_id'] ) ) );
		$this->set_closed_cookie( $popup_id );
		echo wp_json_encode( 'Popup Closed: ' . $popup_id );
		wp_die();
	}

	/**
	 * Set cookie for popup
	 *
	 * @param int $popup_id self explanatory.
	 */
	private function set_closed_cookie( $popup_id ) {
		$path        = '/';
		$host        = str_replace( [ 'http://', 'https://' ], '', get_option( 'siteurl' ) );
		$cookie_time = get_field( 'nine3_popup_cookie_expiry', $popup_id );
		$expire      = strtotime( '+1 day' );
		if ( ! empty( $cookie_time ) ) {
			$expire = strtotime( '+' . $cookie_time );
		}
		setcookie( 'nine3_popup_closed_' . $popup_id, true, $expire, $path, $host );
	}

	/**
	 * Popup custom columns callback
	 *
	 * @param array $columns array of columns.
	 */
	public function popup_custom_columns( $columns ) {
		$new_columns = [
			'priority' => __( 'Priority', 'nine3popup' ),
		];
		$offset      = 2;
		$columns     = array_slice( $columns, 0, $offset, true ) + $new_columns + array_slice( $columns, $offset, null, true );
		return $columns;
	}

	/**
	 * Populate custom columns
	 *
	 * @param string $column name of column.
	 * @param int    $post_id the post ID.
	 */
	public function popup_custom_column( $column, $post_id ) {
		if ( $column === 'priority' ) {
			$priority = get_field( 'nine3_popup_priority', $post_id );
			echo ! empty( $priority ) ? esc_html( $priority ) : esc_html__( 'Not set', 'nine3popup' );
		}
	}
}
