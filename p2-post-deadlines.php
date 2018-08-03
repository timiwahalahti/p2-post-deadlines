<?php
/**
 * Plugin name: P2 Post Deadlines
 * Plugin URI: https://wordpress.org/plugins/p2-post-deadlines
 * Description: Simple plugin to add deadlines for P2 posts and list upcoming deadlines
 * Version: 0.1.0
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.html
 * Text Domain: p2postdeadlines
 */

if ( ! defined( 'ABSPATH' )  ) {
 	exit();
}

class P2_Post_Deadlines {
	const VERSION = '0.1.0';

	/**
	 *  Throw some hooks to the water and add shortcodes.
	 *  @since 0.1.0
	 */
  public function __construct() {
  	add_action( 'wp_enqueue_scripts',										array( $this, 'register_scripts' ) );
  	add_action( 'p2_post_form', 												array( $this, 'p2_post_form_add_datefield' ) );
  	add_action( 'wp_ajax_p2post_save_deadline', 				array( $this, 'save_post_deadline' ) );
		add_action( 'wp_ajax_nopriv_p2post_save_deadline', 	array( $this, 'save_post_deadline' ) );
		add_filter( 'the_content', 													array( $this, 'show_post_deadline_in_content' ) );
  	add_shortcode( 'upcoming_post_deadlines',						array( $this, 'shortcode_list_upcomig_deadlines' ) );
  } // end function __construct

  /**
   *  Register our scripts and styles. Enqueue will be done later on,
   *  only when P2 post form is visible.
   *
   *  @since  0.1.0
   */
  public function register_scripts() {
  	wp_register_script( 'p2-post-deadlines', plugins_url( 'script.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );
  	wp_register_style( 'jquery-ui', 'http://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );

  	// Localize jQuery UI datepicker in ordedr to achieve filterable options.
  	wp_localize_script( 'jquery-ui-datepicker', 'p2postdeadlines', apply_filters( 'p2_post_deadlines_datepicker_options', array(
  		'minDate'			=> 7,
  		'dateFormat'	=> 'yy-mm-dd',
  	) ) );
  } // end function register_scripts

  /**
   *  Add the input field and datepicker for post deadline.
   *
   *  @since  0.1.0
   */
  public function p2_post_form_add_datefield() {
  	// Enqueue scripts and styles as they are needed now.
  	wp_enqueue_script( 'p2-post-deadlines' );
  	wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui' );

    // Add our field and nonce.
    echo '<input id="p2-post-deadline" type="text" name="p2-post-deadline" placeholder="Select the deadline for this post" autocomplete="off" />';
		wp_nonce_field( 'p2post_save_deadline', 'p2post_save_deadline_nonce' );
  }

  /**
   *  Save deadline for the post from AJAX call.
   *  @since  0.1.0
   */
  public function save_post_deadline() {
		$saved = false; // state for return.
		$post_id = absint( $_REQUEST['post_id'] );
		$post_deadline = sanitize_text_field( $_REQUEST['post_deadline'] );

		// No deadline set for post.
		if ( empty( $post_deadline ) ) {
			wp_die( $saved );
		}

		check_ajax_referer( 'p2post_save_deadline', 'p2post_save_deadline_nonce' );

		if ( current_user_can( 'edit_post', $post_id ) && self::validate_date( $post_deadline ) ) {
			$saved = update_post_meta( $post_id, '_p2_post_deadline', $post_deadline ); // Try to save the deadline.
		}

		wp_die( $saved );
  } // end function save_post_deadline

  /**
   *  If post has a deadline, show it in the end of post content.
   *  @since  0.1.0
   */
  public function show_post_deadline_in_content( $content ) {
  	global $post;
  	$post_deadline = get_post_meta( $post->ID, '_p2_post_deadline', true );

  	if ( empty( $post_deadline ) ) {
  		return $content;
  	}

  	// Add the deadline to end of content.
  	$content .= '<p class="p2-post-deadline">' . self::get_post_deadline_string( $post_deadline ) . '</p> ';
		return $content;
  } // end function show_post_deadline_in_content

  /**
   *  Show listing of posts with upcoming deadlines.
   *  @since  0.1.0
   */
  public function shortcode_list_upcomig_deadlines( $atts = array() ) {
  	$atts = shortcode_atts( array( 'order' => 'ASC' ), $atts, 'upcoming_post_deadlines' );
  	$posts = self::get_posts_with_deadline( $atts['order'] );

  	// There is posts, show listing.
  	if ( $posts ) {
  		include plugin_dir_path( __FILE__ ) . 'views/shortcode-list-upcoming-deadlines.php';
  	} else {
  		echo '<p>' . __( 'There is no posts with upcoming deadlines.', 'my-text-domain' ) . '</p>';
  	}
  } // end function shortcode_upcomig_deadlines

  private function get_posts_with_deadline( $order = 'DESC' ) {
  	// For security reasons, allow only ASC and DESC order.
  	if ( 'DESC' === strtoupper( $order ) ) {
  		$order = 'DESC';
  	} else  {
  		$order = 'ASC';
  	}

  	// Try to serve posts from cache.
  	$posts = get_transient( "p2_posts_with_deadline_{$order}" );
  	if ( is_array( $posts ) ) {
  		return $posts;
  	}

  	$posts = array();
		$args = array(
			'post_type'								=> 'post',
			'post_status'							=> 'publish',
			'order'										=> $order,
			'orderby'									=> 'meta_value',
			'meta_key'								=> '_p2_post_deadline',
			'meta_value'							=> date( 'Y-m-d' ),
			'meta_compare'						=> '>=',
			'posts_per_page'					=> 100,
			'no_found_rows'						=> true,
			'cache_results'						=> true,
			'update_post_term_cache'	=> false,
			'update_post_meta_cache'	=> true,
		);

  	$query = new WP_Query( apply_filters( 'p2_post_deadlines_shortcode_list_upcomig_query_args', $args ) );

  	if ( $query->have_posts() ) {
  		while ( $query->have_posts() ) {
  			$query->the_post();

  			$post_id = get_the_id();
  			$post_deadline = get_post_meta( $post_id, '_p2_post_deadline', true );
  			$posts[ $post_id ] = array(
  				'post_id'		=> $post_id,
  				'title'			=> get_the_title(),
  				'deadline'	=> $post_deadline,
  				'deadline_str'	=> self::get_post_deadline_string( $post_deadline ),
  			);
  		}
  	}

  	// Cache posts for 12 hours.
  	set_transient( "p2_posts_with_deadline_{$order}", $posts, 12 * HOUR_IN_SECONDS );

  	return $posts;
  } // end function get_posts_with_deadline

  /**
   *  Validate that date is in proper format.
   *  @since  0.1.0
   */
  private function validate_date( $date, $format = 'Y-m-d' ) {
  	$d = DateTime::createFromFormat( $format, $date );
    return $d && $d->format( $format ) == $date;
  } // end function validate_date

  /**
   *  Make beautiful string to tell when the deadline is.
   *
   *  @since  0.1.0
   */
  private function get_post_deadline_string( $post_deadline = null ) {
  	$deadline = date_i18n( get_option( 'date_format' ), strtotime( $post_deadline ) );

		if ( date( 'Y-m-d' ) === $post_deadline ) {
		  $deadline = __( 'today', 'my-text-domain' );
		} else if ( date( 'Y-m-d', strtotime( 'tomorrow' ) ) === $post_deadline ) {
		  $deadline = __( 'tomorrow', 'my-text-domain' );
		}

		return sprintf( esc_html__( 'Deadline is %s', 'my-text-domain' ), $deadline );
  } // end function get_post_deadline_string
} // end class P2_Post_Deadlines

new P2_Post_Deadlines;
