<?php
/**
 * ITERAS
 *
 * @package   Iteras_Admin
 * @author    ITERAS Team <team@iteras.dk>
 * @license   GPL-2.0+
 * @link      http://www.iteras.dk
 * @copyright 2024 ITERAS ApS
 */

/**
 * @package Iteras_Admin
 * @author  ITERAS Team <team@iteras.dk>
 */
class Iteras_Admin {

  protected static $instance = null;

  protected $plugin_screen_hook_suffix = null;

  protected $plugin = null;
  protected $plugin_slug = null;

  protected $access_levels = null;
  protected $paywall_display_types = null;
  protected $paywall_integration_methods = null;

  private function __construct() {
    $this->plugin = Iteras::get_instance();
    $this->plugin_slug = $this->plugin->get_plugin_slug();

    add_action( 'init', array( $this, 'load_settings' ) );
    add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

    // Load admin style sheet and JavaScript.
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

    // Add the options page and menu item.
    add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

    // Add an action link pointing to the options page.
    $plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
    add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

    // Add column on post list
    add_filter( 'manage_post_posts_columns', array( $this, 'add_paywall_post_columns' ) );
    add_action( 'manage_post_posts_custom_column', array( $this, 'populate_paywall_post_columns' ), 10, 2 );
    
    add_action( 'load-post.php', array( $this, 'paywall_post_meta_boxes_setup' ) );
    add_action( 'load-post-new.php', array( $this, 'paywall_post_meta_boxes_setup' ) );

    // Add bulk update paywall state
    add_filter( 'bulk_actions-edit-post', array( $this, 'add_bulk_actions' ) );
    add_filter( 'handle_bulk_actions-edit-post', array( $this, 'execute_bulk_actions' ), 10, 3 );
  }

  public function load_settings() {
    $this->access_levels = array(
      "" => __('Everybody', $this->plugin_slug),
      //"user" => __('Registered accounts', $this->plugin_slug),
      //"sub" => __('Paying subscribers', $this->plugin_slug),
    );

    $this->paywall_display_types = array(
      "redirect" => __('Redirect to subscribe landing page', $this->plugin_slug),
      "samepage" => __('Cut text and add call-to-action box', $this->plugin_slug),
    );

    $this->paywall_integration_methods = array(
      "auto" => __('Automatic', $this->plugin_slug),
      "custom" => __('Custom', $this->plugin_slug),
    );
  }

  public function load_plugin_textdomain() {
    // Load the plugin text domain for translation.
    load_plugin_textdomain( $this->plugin_slug, false, plugin_basename(ITERAS_PLUGIN_PATH) . '/languages/' );
  }


  function paywall_post_meta_boxes_setup() {
    add_action( 'add_meta_boxes', array( $this, 'paywall_add_post_meta_boxes') );
    add_action( 'save_post', array( $this, 'paywall_save_post' ), 10, 2 );
  }


  function paywall_add_post_meta_boxes() {
    add_meta_box( "iteras-paywall-box", __("ITERAS Paywall"), array( $this, "paywall_post_meta_box" ), array("post", "page"), "side", "high" );
  }


  function add_paywall_post_columns( $columns ) {
    $columns["iteras-paywalled"] = __("Paywall");
    return $columns;
  }

  
  function populate_paywall_post_columns( $column, $post_id ) {
    if ($column == "iteras-paywalled") {
      $paywalled = !!get_post_meta($post_id, Iteras::POST_META_KEY, true);
      echo '<div class="dashicons dashicons-'. ($paywalled ? "yes" : "no") .'"></div>';      
    }
  }

  
  function paywall_post_meta_box( $post, $box ) {
    $settings = $this->plugin->settings;
    $domain = $this->plugin_slug;

    $default_paywall = array();
    $default_access = $this->plugin->settings['default_access'];
    if ($default_access != "")
      $default_paywall = array($default_access);

    // check if the post has ITERAS metadata
    $post_custom_keys = get_post_custom_keys($post->ID);
    if ($post_custom_keys && in_array(Iteras::POST_META_KEY, $post_custom_keys)) {
      $enabled_paywalls = get_post_meta($post->ID, Iteras::POST_META_KEY, true);
    }
    else {
      $enabled_paywalls = $default_paywall;
    }

    // backwards compatibility
    if (!is_array($enabled_paywalls)) {
      if (in_array($enabled_paywalls, array('user', 'sub')))
        $enabled_paywalls = $this->plugin->get_paywall_ids();
      elseif ($enabled_paywalls === "")
        $enabled_paywalls = array();
      else
        $enabled_paywalls = $default_paywall;
    }
    elseif (!isset($enabled_paywalls)) {
      $enabled_paywalls = $default_paywall;
    }

    $level_descriptions = array(
      "" => __('Does not restrict visitors, everyone can see the content', $this->plugin_slug),
      "user" => __('Content restricted to visitors who are in the subscriber database (but they are not required to have an active subscription)', $this->plugin_slug),
      "sub" => __('Content restricted to visitors with an active subscription', $this->plugin_slug),
    );

    include_once( 'views/post-meta-box.php' );
  }

  function update_paywall_on_post( $post_id, $paywall_value, $add_to_existing = false ) {
    $existing_value = get_post_meta( $post_id, Iteras::POST_META_KEY, true );
    if (!isset( $existing_value ))
      $existing_value = null;

    if ( $paywall_value !== null && $existing_value === null )
      add_post_meta( $post_id, Iteras::POST_META_KEY, $paywall_value, true );

    elseif ( $paywall_value !== null && $paywall_value != $existing_value ) {
      if ( $add_to_existing && !empty( $existing_value ) )
        $paywall_value = array_unique( array_merge( $existing_value, $paywall_value ) );

      update_post_meta( $post_id, Iteras::POST_META_KEY, $paywall_value );
    }

    elseif ( $paywall_value === null && $existing_value )
      delete_post_meta( $post_id, Iteras::POST_META_KEY, $existing_value );
  }

  function paywall_save_post( $post_id, $post ) {
    if ( !isset( $_POST['iteras_paywall_post_nonce'] ) || !wp_verify_nonce( $_POST['iteras_paywall_post_nonce'], "post".$post_id ) )
      return $post_id;

    $post_type = get_post_type_object( $post->post_type );

    if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
      return $post_id;

    $paywall_value = isset( $_POST['iteras-paywall'] ) ? $_POST['iteras-paywall'] : array();

    $this->update_paywall_on_post( $post_id, $paywall_value );
  }

  function add_bulk_actions( $bulk_actions ) {
    $settings = $this->plugin->settings;
    foreach ( $settings['paywalls'] as $p ) {
      $bulk_actions['iteras-add-paywall-' . $p['paywall_id']] = __( 'Add paywall', $this->plugin_slug ) . ': ' . $p['name'];
    }

    $bulk_actions['iteras-remove-paywalls'] = __( 'Remove paywalls', $this->plugin_slug );
    return $bulk_actions;
  }

  function execute_bulk_actions( $redirect_url, $action, $post_ids ) {
    $settings = $this->plugin->settings;

    $add_prefix = 'iteras-add-paywall-';
    if ( substr($action, 0, strlen($add_prefix)) === $add_prefix ) {
      $paywall_id = substr($action, strlen($add_prefix));
      foreach ($post_ids as $post_id) {
        $this->update_paywall_on_post( $post_id, array( $paywall_id ), true );
      }
    }

    elseif ($action === 'iteras-remove-paywalls') {
      foreach ($post_ids as $post_id) {
        $this->update_paywall_on_post( $post_id, null );
      }
    }

    return $redirect_url;
  }

  public static function get_instance() {
    if ( null == self::$instance ) {
      self::$instance = new self;
    }

    return self::$instance;
  }


  public function enqueue_admin_styles() {
    if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
      return;
    }

    $screen = get_current_screen();
    if ( in_array($screen->id, array("edit-post", "post", "settings_page_iteras")) ) {
      wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Iteras::VERSION );
    }
  }


  public function enqueue_admin_scripts() {
    if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
      return;
    }

    $screen = get_current_screen();
    if ( $this->plugin_screen_hook_suffix == $screen->id ) {
      wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Iteras::VERSION );
    }
  }


  public function add_plugin_admin_menu() {
    $this->plugin_screen_hook_suffix = add_options_page(
      __( 'ITERAS configuration', $this->plugin_slug ),
      __( 'ITERAS', $this->plugin_slug ),
      'manage_options',
      $this->plugin_slug,
      array( $this, 'display_plugin_admin_page' )
    );
  }

  public function fetch_paywalls( $api_key ) {
    $response = wp_remote_get(ITERAS_BASE_URL . "/api/setup/", array(
      'headers' => array('X-Iteras-Key' => $api_key)
    ));

    if ($response['response']['code'] == 200)
      return json_decode($response['body'], true)['paywalls'];
    else
      return false;
  }

  public function display_plugin_admin_page() {
    $messages = array();

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
      $this->save_settings_form();
    }

    if (array_key_exists("sync", $_POST)) {
      $settings = $this->plugin->settings;
      $paywalls = $this->fetch_paywalls($settings['api_key']);

      if ($paywalls !== false) {
        $needs_migrate = (empty($settings['paywalls']) && !empty($paywalls));

        $settings['paywalls'] = $paywalls;
        $this->plugin->save_settings($settings);

        if ($needs_migrate)
          $this->plugin->migrate_posts_to_multi_paywall();

        array_push($messages, array(
          "text" => __( "Synchronization of paywalls from ITERAS complete", $this->plugin_slug ),
          "type" => "success"
        ));
      }
      else {
        array_push($messages, array(
          "text" => __( "Couldn't synchronize paywalls from ITERAS", $this->plugin_slug ),
          "type" => "error"
        ));
      }
    }

    if (ITERAS_DEBUG && array_key_exists("reset", $_POST)) {
      $this->plugin->reset_plugin();
      _log("RESET");
    }

    // template context
    $settings = $this->plugin->settings;
    $domain = $this->plugin_slug;

    $access_levels = $this->access_levels;
    foreach ($settings['paywalls'] as $p) {
      $access_levels[$p['paywall_id']] = $p['name'];
    }

    include_once( 'views/admin.php' );

  }

  public function settings_url() {
    return admin_url( 'options-general.php?page=' . $this->plugin_slug );
  }

  public function add_action_links( $links ) {

    return array_merge(
      array(
        'settings' => '<a href="' . $this->settings_url() . '">' . __( 'Settings', $this->plugin_slug ) . '</a>',
      ),
      $links
    );

  }

  private function save_settings_form() {
    if (!current_user_can('manage_options')) {
      wp_die('You do not have sufficient permissions to access this page.');
    }
    $prev_settings = $this->plugin->settings;
    $settings = array(
      'api_key' => sanitize_text_field($_POST['api_key']),
      'signing_key' => sanitize_text_field($_POST['signing_key']),
      'paywalls' => $prev_settings['paywalls'],
      'profile_name' => sanitize_text_field($_POST['profile']),
      'paywall_id' => $prev_settings['paywall_id'], //sanitize_text_field($_POST['paywall']),
      'subscribe_url' => sanitize_text_field($_POST['subscribe_url']),
      'user_url' => sanitize_text_field($_POST['user_url']),
      'default_access' => sanitize_text_field($_POST['default_access']),
      'paywall_display_type' => sanitize_text_field($_POST['paywall_display_type']),
      'paywall_box' => stripslashes($_POST['paywall_box']),
      'paywall_snippet_size' => sanitize_text_field($_POST['paywall_snippet_size']),
      'paywall_integration_method' => sanitize_text_field($_POST['paywall_integration_method']),
      'paywall_server_side_validation' => isset($_POST['paywall_server_side_validation']),
    );

    $this->plugin->save_settings($settings);
  }
}
