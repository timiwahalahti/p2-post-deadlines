<?php
/**
 * Plugin name: Post Deadlines
 * Plugin URI: https://wordpress.org/plugins/post-deadlines
 * Description: Simple plugin to add deadlines for posts and list posts with upcoming deadlines. Can be used as standalone or with P2 or o2.
 * Version: 1.0.0
 * Requires at least: 4.6
 * Tested up to: 4.9.8
 * Stable tag: 1.0.1
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.html
 * Text Domain: postdeadlines
 */

if ( ! defined( 'ABSPATH' )  ) {
 	exit();
}

class Post_Deadlines {
	const VERSION = '1.0.1';

	/**
	 *  Throw some hooks to the water and add shortcodes.
	 *  @since 1.0.0
	 */
  public function __construct() {
    add_action( 'init',                                 array( $this, 'load_textdomain' ) );
  	add_action( 'wp_enqueue_scripts',										array( $this, 'register_scripts' ) );
  	add_action( 'p2_post_form',                         array( $this, 'p2_post_form_add_datefield' ) );
    add_action( 'o2_post_form_extras',                  array( $this, 'o2_post_form_add_datefield' ) );
  	add_action( 'wp_ajax_post_save_deadline',           array( $this, 'save_post_deadline' ) );
		add_action( 'wp_ajax_nopriv_post_save_deadline', 	  array( $this, 'save_post_deadline' ) );
    add_action( 'admin_enqueue_scripts',                array( $this, 'register_admin_scripts' ) );
    add_action( 'add_meta_boxes',                       array( $this, 'register_meta_box' ) );
    add_action( 'save_post',                            array( $this, 'save_meta_box' ) );
    add_filter( 'the_content',                          array( $this, 'show_post_deadline_in_content' ) );
    add_shortcode( 'upcoming_post_deadlines',           array( $this, 'shortcode_list_upcomig_deadlines' ) );
  } // end function __construct

  public function load_textdomain() {
    load_plugin_textdomain( 'postdeadlines', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
  }

  /**
   *  Register our scripts and styles. Enqueue will be done later on,
   *  only when P2 post form is visible.
   *
   *  @since  1.0.0
   */
  public function register_scripts() {
  	wp_register_script( 'post-deadlines', plugins_url( 'assets/script.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );
  	wp_register_style( 'jquery-ui', plugins_url( 'assets/jquery-ui.smoothness.css', __FILE__ ) );

    // Really enqueue our scripts.
    wp_enqueue_script( 'post-deadlines' );
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui' );

  	// Localize in order to achieve filterable datepicker options.
  	wp_localize_script( 'post-deadlines', 'postdeadlines', array(
      'datepicker_settings' => self::get_datepicker_settings(),
      'ajaxurl'             => admin_url( 'admin-ajax.php' ),
    ) );
  } // end function register_scripts

  /**
   *  Add the input field and datepicker for post deadline.
   *
   *  @since  1.0.0
   */
  public function p2_post_form_add_datefield() {
    // Add our field.
    echo '<div class="post-deadline-wrapper"><input id="post-deadline-datepicker" type="text" name="post-deadline" placeholder="' . __( 'Set deadline', 'postdeadlines' ) . '" autocomplete="off" /></div>';

    // Add nonce for security.
		wp_nonce_field( 'post_save_deadline', 'post_save_deadline_nonce' );
  } // end function p2_post_form_add_datefield

  /**
   *  Add datepicker field on o2 installation.
   *  o2 needs string instead of staright output, so give it.
   *
   *  @since  1.0.0
   */
  public function o2_post_form_add_datefield() {
    ob_start();
    self::p2_post_form_add_datefield();
    return ob_get_clean();
  } // end function o2_post_form_add_datefield

  /**
   *  Save deadline for the post from AJAX call.
   *  @since  1.0.0
   */
  public function save_post_deadline() {
		$saved = false; // state for return.
		$post_id = absint( $_REQUEST['post_id'] );
		$post_deadline = sanitize_text_field( $_REQUEST['post_deadline'] );

		// No deadline set for post.
		if ( empty( $post_deadline ) ) {
			wp_die( $saved );
		}

		check_ajax_referer( 'post_save_deadline', 'post_save_deadline_nonce' );

		if ( current_user_can( 'edit_post', $post_id ) && self::validate_date( $post_deadline ) ) {
			update_post_meta( $post_id, '_post_deadline', $post_deadline );
      self::purge_transient_cache();
		}

		wp_die( $saved );
  } // end function save_post_deadline

  /**
   *  Register our scripts and styles for dashboard.
   *  @since  0.2.0
   */
  public function register_admin_scripts() {
    // Get current screen info.
    $screen = get_current_screen();

    // No current screen info, bail.
    if ( ! $screen ) {
      return;
    }

    // Current screen is not editor view or post CPT, bail.
    if ( 'post' !== $screen->base && 'post' !== $screen->post_type ) {
      return;
    }

    // Register our scripts
    wp_register_script( 'post-deadlines-admin', plugins_url( 'assets/admin-script.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );

    // Really enqueue our scripts.
    wp_enqueue_script( 'post-deadlines-admin' );

    // Localize in order to achieve filterable datepicker options.
    wp_localize_script( 'post-deadlines-admin', 'postdeadlines', array(
      'datepicker_settings' => self::get_datepicker_settings(),
      'ajaxurl'             => admin_url( 'admin-ajax.php' ),
    ) );

    // Enqueue jquery ui datepicker js and css.
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_register_style( 'jquery-ui', plugins_url( 'assets/jquery-ui.smoothness.css', __FILE__ ) );
    wp_enqueue_style( 'jquery-ui' );
  } // end function register_admin_scripts

  /**
   *  Register metabox for deadline field.
   *  @since  0.2.0
   */
  public function register_meta_box() {
    add_meta_box( 'post-deadline', 'Post deadline', array( $this, 'meta_box_display_callback' ), 'post', 'side', 'high', array(
      '__back_compat_meta_box' => false, // Support Gutenberg better.
    ) );
  } // end function register_meta_box

  /**
   *  Add content to metabox.
   *  @since  0.2.0
   */
  public function meta_box_display_callback( $post ) {
    $value = ''; // Set default value.
    $post_deadline = get_post_meta( $post->ID, '_post_deadline', true ); // Get saved deadline.

    // If there is deadline set and it is valud date, use it.
    if ( ! empty( $post_deadline ) && self::validate_date( $post_deadline ) ) {
      $value = $post_deadline;
    }

    // Add field and bit of js fo datepicker.
    echo '<input id="post-deadline-datepicker" type="text" name="post-deadline" value="' . $value . '" placeholder="' . __( 'Set deadline', 'postdeadlines' ) . '" autocomplete="off" />';

    // Add nonce for security.
    wp_nonce_field( 'post_save_deadline', 'post_save_deadline_nonce' );
  } // end function meta_box_display_callback

  /**
   *  Save post deadline from metabox.
   *  @since  0.2.0
   */
  public function save_meta_box( $post_id ) {
    $nonce_name   = isset( $_POST['post_save_deadline_nonce'] ) ? $_POST['post_save_deadline_nonce'] : '';
    $nonce_action = 'post_save_deadline';

    // Check if nonce is set.
    if ( ! isset( $nonce_name ) ) {
      return;
    }

    // Check if nonce is valid.
    if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
      return;
    }

    // Check if user has permissions to save data.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return;
    }

    // Check if not an autosave.
    if ( wp_is_post_autosave( $post_id ) ) {
      return;
    }

    // Check if not a revision.
    if ( wp_is_post_revision( $post_id ) ) {
      return;
    }

    // Check if deadline is set, delete existing if not.
    if ( ! isset( $_POST['post-deadline'] ) ) {
      delete_post_meta( $post_id, '_post_deadline' );
      self::purge_transient_cache();
      return;
    }

    // Sanitize submitted date.
    $date = sanitize_text_field( $_POST['post-deadline'] );

    // Validate that date is really date, delete existing deadline if not valid.
    if ( ! self::validate_date( $date ) ) {
      delete_post_meta( $post_id, '_post_deadline' );
      self::purge_transient_cache();
      return;
    }

    // Finally set the deadline.
    update_post_meta( $post_id, '_post_deadline', $date );
    self::purge_transient_cache();
  } // end function save_meta_box

  /**
   *  If post has a deadline, show it in the end of post content.
   *  @since  1.0.0
   */
  public function show_post_deadline_in_content( $content ) {
    if ( apply_filters( 'post_deadlines_show_post_deadline_in_content', true ) ) {
    	global $post;
    	$post_deadline = get_post_meta( $post->ID, '_post_deadline', true );

    	if ( empty( $post_deadline ) ) {
    		return $content;
    	}

    	// Add the deadline to end of content.
      $deadline = self::get_post_deadline_string( $post_deadline );
    	$content .= '<p class="post-deadline">' . $deadline['str'] . '</p> ';
    }

		return $content;
  } // end function show_post_deadline_in_content

  /**
   *  Show listing of posts with upcoming deadlines.
   *  @since  1.0.0
   */
  public function shortcode_list_upcomig_deadlines( $atts = array() ) {
  	$atts = shortcode_atts( array( 'order' => 'ASC' ), $atts, 'upcoming_post_deadlines' );
  	$posts = self::get_posts_with_deadline( $atts['order'] );

  	// There is posts, show listing.
  	if ( $posts ) {
      if ( ! empty( locate_template( array( 'post-deadlines-shortcode-list-upcoming.php' ) ) ) ) {
        include get_theme_file_path( 'post-deadlines-shortcode-list-upcoming.php' );
      } else {
        include plugin_dir_path( __FILE__ ) . 'views/shortcode-list-upcoming-deadlines.php';
      }
  	} else {
  		echo '<p>' . __( 'There is no upcoming deadlines.', 'postdeadlines' ) . '</p>';
  	}
  } // end function shortcode_upcomig_deadlines

  private function get_posts_with_deadline( $order = 'DESC' ) {
  	// For security reasons, allow only ASC and DESC order.
  	if ( 'DESC' === strtoupper( $order ) ) {
  		$order = 'DESC';
  	} else  {
  		$order = 'ASC';
  	}

  	// Try to serve posts from cache if allowed by filter.
    if ( apply_filters( 'post_deadlines_cache_posts_with_deadline', true ) ) {
      $today_key = date( 'Ymd' );
    	$posts = get_transient( "posts_with_deadline_{$today_key}_{$order}" );
    	if ( is_array( $posts ) ) {
    		return $posts;
    	}
    }

    // Init results array.
  	$posts = array();

    // Query args for getting post with upcoming deadlines.
		$args = array(
			'post_type'								=> 'post',
			'post_status'							=> 'publish',
			'order'										=> $order,
			'orderby'									=> 'meta_value',
			'meta_key'								=> '_post_deadline',
			'meta_value'							=> date( 'Y-m-d' ),
			'meta_compare'						=> '>=',
			'posts_per_page'					=> 100,
			'no_found_rows'						=> true,
			'cache_results'						=> true,
			'update_post_term_cache'	=> false,
			'update_post_meta_cache'	=> true,
		);

    // Allow query args filtering.
  	$query = new WP_Query( apply_filters( 'post_deadlines_list_upcomig_query_args', $args ) );

    // Loop posts with upcoming deadlines.
  	if ( $query->have_posts() ) {
  		while ( $query->have_posts() ) {
  			$query->the_post();

        // Get post information add add it to results array.
  			$post_id = get_the_id();
  			$post_deadline = get_post_meta( $post_id, '_post_deadline', true );
  			$posts[ $post_id ] = array(
  				'post_id'		=> $post_id,
  				'title'			=> get_the_title(),
  				'deadline'	=> self::get_post_deadline_string( $post_deadline ),
  			);
  		}
  	}

  	// Cache posts for one day if allowed in hook.
    if ( apply_filters( 'post_deadlines_cache_posts_with_deadline', true ) ) {
      set_transient( "posts_with_deadline_{$today_key}_{$order}", $posts, apply_filters( 'post_deadlines_cache_expiration', DAY_IN_SECONDS ) );
    }

  	return apply_filters( 'post_deadlines_get_posts_with_deadline_result', $posts );
  } // end function get_posts_with_deadline

  /**
   *  Setting for jquery ui datepicker, used in admin metabox and frontend.
   *  @since  0.2.0
   */
  private function get_datepicker_settings() {
    return apply_filters( 'post_deadlines_datepicker_options', array(
      'minDate'     => 7,
      'dateFormat'  => 'yy-mm-dd',
    ) );
  } // end function get_datepicker_settings

  /**
   *  Validate that date is in proper format.
   *  @since  1.0.0
   */
  private function validate_date( $date, $format = 'Y-m-d' ) {
  	$d = DateTime::createFromFormat( $format, $date );
    return $d && $d->format( $format ) === $date;
  } // end function validate_date

  /**
   *  Make beautiful string to tell when the deadline is.
   *
   *  @since  1.0.0
   */
  private function get_post_deadline_string( $post_deadline = null ) {
    $deadline_soon = false;
  	$deadline = date_i18n( get_option( 'date_format' ), strtotime( $post_deadline ) );

		if ( date( 'Y-m-d' ) === $post_deadline ) {
		  $deadline = __( 'today', 'postdeadlines' );
      $deadline_soon = true;
		} else if ( date( 'Y-m-d', strtotime( 'tomorrow' ) ) === $post_deadline ) {
		  $deadline = __( 'tomorrow', 'postdeadlines' );
      $deadline_soon = true;
		}

		return apply_filters( 'post_deadlines_deadline_string', array(
      'raw'     => $post_deadline,
      'str'     => sprintf( esc_html__( 'Deadline is %s', 'postdeadlines' ), $deadline ),
      'is_soon' => $deadline_soon
    ) );
  } // end function get_post_deadline_string

  /**
   *  Purge all transient caches with one function.
   *  @since  1.0.0
   */
  private function purge_transient_cache() {
    // Purge just today, as other transients shouldn't exist.
    $today_key = date( 'Ymd' );
    delete_transient( "posts_with_deadline_{$today_key}_ASC" );
    delete_transient( "posts_with_deadline_{$today_key}_DESC" );

    // Fire action after transients have been purged.
    do_action( 'post_deadlines_purged_transient_cache' );
  } // end function purge_transient_cache
} // end class Post_Deadlines

new Post_Deadlines;
