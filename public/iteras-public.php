<?php
/**
 * ITERAS
 *
 * @package   Iteras
 * @author    ITERAS Team <team@iteras.dk>
 * @license   GPL-2.0+
 * @link      http://www.iteras.dk
 * @copyright 2024 ITERAS ApS
 */

/**
 * @package Iteras
 * @author  ITERAS Team <team@iteras.dk>
 */
class Iteras {

  const VERSION = '1.7.0';

  const SETTINGS_KEY = "iteras_settings";
  const POST_META_KEY = "iteras_paywall";
  const DEFAULT_ARTICLE_SNIPPET_SIZE = 300;

  protected $plugin_slug = 'iteras';

  protected static $instance = null;

  public $settings = null;


  private function __construct() {
    // run migrations if needed
    self::migrate();

    // Load plugin text domain
    add_action( 'init', array( $this, 'load_settings' ) );
    add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

    // Activate plugin when new blog is added
    add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

    // Load public-facing style sheet and JavaScript.
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    add_filter( 'the_content', array( $this, 'potentially_paywall_content_filter' ), 99 );
    add_filter( 'the_content_feed', array( $this, 'potentially_paywall_content_filter' ), 99 );

    add_shortcode( 'iteras-ordering', array( $this, 'ordering_shortcode') );
    add_shortcode( 'iteras-paywall-login', array( $this, 'paywall_shortcode') );
    add_shortcode( 'iteras-selfservice', array( $this, 'selfservice_shortcode') );
    add_shortcode( 'iteras-if-logged-in-link', array( $this, 'if_logged_in_link_shortcode') );
    add_shortcode( 'iteras-if-logged-in', array( $this, 'if_logged_in_shortcode') );
    add_shortcode( 'iteras-if-not-logged-in', array( $this, 'if_not_logged_in_shortcode') );

    add_shortcode( 'iteras-paywall-content', array( $this, 'paywall_content_shortcode') );

    add_shortcode( 'iteras-return-to-page', array( $this, 'return_to_page_shortcode') );

    add_shortcode( 'iteras-signup', array( $this, 'signup_shortcode') ); // deprecated
  }


  public function get_plugin_slug() {
    return $this->plugin_slug;
  }


  public static function get_instance() {
    if ( null == self::$instance ) {
      self::$instance = new self;
    }

    return self::$instance;
  }


  public static function activate( $network_wide ) {
    if ( function_exists( 'is_multisite' ) && is_multisite() ) {

      if ( $network_wide  ) {

	// Get all blog ids
	$blog_ids = self::get_blog_ids();

	foreach ( $blog_ids as $blog_id ) {

	  switch_to_blog( $blog_id );
	  self::single_activate();

	  restore_current_blog();
	}

      } else {
	self::single_activate();
      }

    } else {
      self::single_activate();
    }

  }


  public static function deactivate( $network_wide ) {

    if ( function_exists( 'is_multisite' ) && is_multisite() ) {

      if ( $network_wide ) {

	// Get all blog ids
	$blog_ids = self::get_blog_ids();

	foreach ( $blog_ids as $blog_id ) {

	  switch_to_blog( $blog_id );
	  self::single_deactivate();

	  restore_current_blog();

	}

      } else {
	self::single_deactivate();
      }

    } else {
      self::single_deactivate();
    }

  }


  public function activate_new_site( $blog_id ) {

    if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
      return;
    }

    switch_to_blog( $blog_id );
    self::single_activate();
    restore_current_blog();

  }


  public static function uninstall() {
    delete_option(self::SETTINGS_KEY);
    //delete_metadata( 'post', null, Iteras_Admin::$POST_META_KEY, null, true );
  }


  private static function get_blog_ids() {

    global $wpdb;

    // get an array of blog ids
    $sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

    return $wpdb->get_col( $sql );

  }


  private static function single_activate() {
    self::migrate();
  }

  private static function migrate() {
    $settings = get_option(self::SETTINGS_KEY);
    if (empty($settings))
      return;

    $old_version = $settings['version'];
    $new_version = self::VERSION;

    if (!empty($settings) and version_compare($new_version, $old_version, "gt")) {
      // do version upgrades here
      if (version_compare($old_version, "0.3", "le")) {
        $settings['paywall_display_type'] = 'redirect';
        $settings['paywall_box'] = '';
        $settings['paywall_snippet_size'] = self::DEFAULT_ARTICLE_SNIPPET_SIZE;
      }
      if (version_compare($old_version, "0.4.5", "le")) {
        $settings['paywall_integration_method'] = 'auto';
      }
      if (version_compare($old_version, "1.0", "lt")) {
        $settings['api_key'] = '';
        $settings['paywalls'] = array();
      }
      if (version_compare($old_version, "1.2", "lt")) {
        $settings['paywall_server_side_validation'] = true;
      }
      if (version_compare($old_version, "1.7", "lt")) {
        $settings['signing_key'] = $settings['api_key'];
      }

      wp_cache_delete(self::SETTINGS_KEY);
      $settings['version'] = $new_version;
      update_option(self::SETTINGS_KEY, $settings);
    }
  }

  public static function reset_plugin() {
    $posts = get_posts(array(
      'post_type' => 'post'
    ));

    foreach ($posts as $post) {
      delete_post_meta($post->ID, Iteras::POST_META_KEY);
    }

    update_option(self::SETTINGS_KEY, false);
  }

  public function migrate_posts_to_multi_paywall() {
    $posts = get_posts(array(
      'post_type' => 'post'
    ));

    $all_paywall_ids = $this->get_paywall_ids();
    foreach ($posts as $post) {
      $data = get_post_meta($post->ID, self::POST_META_KEY, true);
      _log($post->post_title);

      if (!isset($data)) {
        // pass
        _log("Post not paywalled");
      }
      elseif (is_array($data)) {
        // pass
        _log("Post paywall up-to-date");
      }
      elseif (in_array($data, array("user", "sub"))) {
        update_post_meta($post->ID, Iteras::POST_META_KEY, $all_paywall_ids);
        _log("Post paywall set to all paywalls");
      }
      elseif ($data === "") {
        update_post_meta($post->ID, Iteras::POST_META_KEY, array());
        _log("Post paywall set to no paywall");
      }
      else {
        delete_post_meta($post->ID, Iteras::POST_META_KEY);
        _log("Post paywall removed");
      }
    }
  }

  private static function single_deactivate() {
  }


  public function load_plugin_textdomain() {
    // Load the plugin text domain for translation.
    load_plugin_textdomain( $this->plugin_slug, false, plugin_basename(ITERAS_PLUGIN_PATH) . '/languages/' );
  }


  public function load_settings() {
    $settings = get_option(self::SETTINGS_KEY);

    if (empty($settings)) {
      $settings = array(
        'signing_key' => "",
        'api_key' => "",
        'profile_name' => "", // outphase
        'paywall_id' => "", // outphase
        'subscribe_url' => "",
        'user_url' => "",
        'default_access' => "",
        'paywalls' => array(),
        'paywall_integration_method' => "auto",
        'paywall_server_side_validation' => true,
        'paywall_display_type' => "redirect",
        'paywall_box' => "",
        'paywall_snippet_size' => self::DEFAULT_ARTICLE_SNIPPET_SIZE,
        'version' => self::VERSION,
      );

      add_option(self::SETTINGS_KEY, $settings);
    }

    $this->settings = $settings;
  }


  public function save_settings($settings) {
    wp_cache_delete(self::SETTINGS_KEY);
    $settings['version'] = self::VERSION;
    update_option(self::SETTINGS_KEY, $settings);
    $this->settings = $settings;
  }


  public function get_paywall_ids() {
    $paywalls = $this->settings['paywalls'];

    $paywall_ids = array();
    // paywall id backwards compatibility
    if (!isset($paywalls) || !$paywalls || empty($paywalls)) {
      $id = array_get($this->settings, 'paywall_id', '');
      if ($id != "")
        $paywall_ids = array($id);
    }
    elseif (!empty($paywalls)) {
      $paywall_ids = array_map(function($i) { return $i['paywall_id']; }, $paywalls);
    }

    return $paywall_ids;
  }


  public function enqueue_styles() {
    global $wp_styles;
    wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
    wp_enqueue_style( $this->plugin_slug . '-plugin-styles-ie', plugins_url( 'assets/css/ie.css', __FILE__ ), array(), self::VERSION );
    $wp_styles->add_data( $this->plugin_slug . '-plugin-styles-ie', 'conditional', 'IE 9' );
  }


  public function enqueue_scripts() {
    $version = self::VERSION;
    // include the iteras javascript api
    $url = ITERAS_BASE_URL ."/static/api/iteras.js";
    if (ITERAS_DEBUG) {
      wp_enqueue_script( $this->plugin_slug . '-api-script-debug',  ITERAS_BASE_URL . "/static/api/debug.js");
      // cache buster
      $version = "".time();
    }

    wp_enqueue_script( $this->plugin_slug . '-api-script', $url, array(), $version );

    wp_enqueue_script( $this->plugin_slug . '-plugin-script-truncate', plugins_url( 'assets/js/truncate.js', __FILE__ ), array( 'jquery' ), $version );
    wp_enqueue_script( $this->plugin_slug . '-plugin-script-box', plugins_url( 'assets/js/box.js', __FILE__ ), array( 'jquery' ), $version );
  }

  public function potentially_paywall_content_filter($content) {
    if ( $this->settings['paywall_integration_method'] == "auto" ) {
      $content = $this->potentially_paywall_content($content);
    }
    return $content;
  }

  private function pass_authorized($pass, $restriction, $signing_key) {
    // check signature
    $pos = strrpos($pass, "/");
    $data = substr($pass, 0, $pos);
    $sig = substr($pass, $pos + 1);
    $exp_sig = explode(":", $sig);
    $algo = $exp_sig[0];
    $hmac = $exp_sig[1];

    $algo = array_get(array(
      'sha1' => 'sha1',
      'sha256' => 'sha256'
    ), $algo);

    if (!$algo)
      return false;

    $computed_hmac = hash_hmac($algo, $data, $signing_key);

    if ($computed_hmac !== false && $signing_key && (function_exists("hash_equals") ? !hash_equals($computed_hmac, $hmac) : $computed_hmac != $hmac))
      return false;

    $parts = explode("|", $data);

    // check expiry
    $expiry = strtotime($parts[2]);
    if ($expiry === false || $expiry < time())
      return false;
    
    // check access
    if (count($parts) >= 2) {
      $access_levels = explode(",", $parts[0]);
      $paywall_ids = explode(",", $parts[1]);

      $access = array_combine($paywall_ids, $access_levels);

      foreach ($restriction as $r) {
        if (array_get($access, $r) == "sub") {
          return true;
        }
      }
    }
    return false;
  }

  public function potentially_paywall_content($content) {
    global $post;

    if (!(is_feed() || is_singular()) || !in_the_loop())
      return $content;

    $paywall_ids = get_post_meta( $post->ID, self::POST_META_KEY, true );

    // backwards compatibility
    if (!is_array($paywall_ids) && in_array($paywall_ids, array("user", "sub"))) {
      $paywall_ids = $this->get_paywall_ids();
    }

    $user_authorized = (
      isset($_COOKIE['iteraspass']) && $this->pass_authorized($_COOKIE['iteraspass'], $paywall_ids, $this->settings['signing_key'])
    );

    $paywall_content = !empty($paywall_ids);

    // apply filter that allows forcing the paywall of the content
    $force_paywall_content = apply_filters('iteras_override_content_paywall', $paywall_ids, $post, $user_authorized, $this->settings);
    if (isset($force_paywall_content)) {
      if ($force_paywall_content == false) {
        $paywall_content = false;
      }
      else if (is_array($force_paywall_content)) {
        $paywall_ids = $force_paywall_content;
        $paywall_content = !empty($paywall_ids);
      }
      else {
        error_log("Illegal return value from iteras_force_content_paywall filter. Must be either false, a list of paywall ids or unset.");
      }
    }

    $extra_before = "";
    $extra_after = "";
    // show message without paywall for editors
    if (current_user_can('edit_pages') && !empty($paywall_ids)) {
      $content = '<div class="iteras-paywall-notice"><b>'.__("This content is paywalled").'</b><br>'.__("You are seeing the content because you are logged into WordPress admin.").'</div>'.$content;
    }
    // paywall the content
    else if ($paywall_content) {
      if ($this->settings['paywall_display_type'] == "samepage") {

        if ($this->settings['paywall_box'])
          $box_content = do_shortcode($this->settings['paywall_box']);
        else
          $box_content = "<p>" + __("ITERAS plugin improperly configured. Paywall box content is missing", $this->plugin_slug) + "</p>";

        $box = sprintf(
          file_get_contents(plugin_dir_path( __FILE__ ) . 'views/box.php'),
          $this->settings['paywall_snippet_size'],
          $box_content
        );

        $extra_after = $box.'<script>Iteras.wall({ unauthorized: iterasPaywallContent, paywallid: '.json_encode($paywall_ids).' });</script>';

        /**
         * Filters the prepared paywall script before adding to the end of content
         *
         * @since 1.3.5
         *
         * @param string $extra The script with script tags included
         */
        $extra_after = apply_filters('after_paywall_script_prepared_except_redirect', $extra_after);
      }
      else {
        $extra_before = '<script>Iteras.wall({ redirect: "'.$this->settings['subscribe_url'].'", paywallid: '.json_encode($paywall_ids).' });</script>';
      }

      $truncate_class = "";
      if ($this->settings['paywall_server_side_validation'] && !$user_authorized) {
        $content = truncate_html($content, array_get($this->settings, 'paywall_snippet_size', self::DEFAULT_ARTICLE_SNIPPET_SIZE));
        $truncate_class = "iteras-content-truncated";
        if (!isset($_COOKIE['iteraspass']))
          $truncate_class .= " iteras-no-pass";
        else
          $truncate_class .= " iteras-invalid-pass";
      }

      if (!is_feed())
        $content = $extra_before.'<div class="iteras-content-wrapper '.$truncate_class.'">'.$content.'</div>'.$extra_after;
    }
    
    return $content;
  }

  function combine_attributes($attrs) {
    if (!$attrs or empty($attrs))
      return "";

    $transformed = array();

    foreach ($attrs as $key => $value) {
      if (is_array($value)) {
        array_push($transformed, '"'.$key.'": '.json_encode($value));
      }
      elseif ($value) {
        array_push($transformed, '"'.$key.'": "'.$value.'"');
      }
    }

    if (!empty($transformed))
      return ", ".join(", ", $transformed);
    else
      return "";
  }

  function parse_paywall_ids($paywall_ids) {
    $paywall_ids = preg_replace('/\s*,\s*/', ',', filter_var($paywall_ids, FILTER_SANITIZE_STRING));
    return explode(',', $paywall_ids);
  }

  // [iteras-paywall-content]...[/iteras-paywall-content]
  function paywall_content_shortcode($attrs, $content = null) {
    return $this->potentially_paywall_content($content);
  }


  // [iteras-ordering orderingid="3for1"]
  function ordering_shortcode($attrs) {
    // automatically product prefill if GET-paramter is supplied
    if (isset($_GET['orderproduct']) && !isset($_GET['prefill'])) {
      $attrs['prefill'] = array("products" => $_GET['orderproduct']);
    }

    return '<script>
      document.write(Iteras.orderingiframe({
        "profile": "'.$this->settings['profile_name'].'"'.$this->combine_attributes($attrs).'
      }));</script>';
  }

  // [iteras-signup signupid="3for1"]
  function signup_shortcode($attrs) {
    return '<script>
      document.write(Iteras.signupiframe({
        "profile": "'.$this->settings['profile_name'].'"'.$this->combine_attributes($attrs).'
      }));</script>';
  }


  // [iteras-paywall-login paywallid="abc123,def456"]
  function paywall_shortcode($attrs) {
    if (!is_array($attrs)) {
      $attrs = array();
    }

    if (!empty($attrs) && in_array('paywallid', $attrs)) {
      $attrs['paywallid'] = $this->parse_paywall_ids($attrs['paywallid']);
    }
    else {
      $attrs['paywallid'] = $this->get_paywall_ids();
    }

    if (empty($attrs['paywallid'])) {
      return '<!-- ITERAS paywall enabled but not configured properly: missing paywalls, sync in settings -->';
    }
    else {
      return '<script>
      document.write(Iteras.paywalliframe({
        "profile": "'.$this->settings['profile_name'].'"'.$this->combine_attributes($attrs).'
      }));</script>';
    }
  }


  // [iteras-selfservice]
  function selfservice_shortcode($attrs) {
    return '<script>
      document.write(Iteras.selfserviceiframe({
        "profile": "'.$this->settings['profile_name'].'"'.$this->combine_attributes($attrs).'
      }));</script>';
  }

  // [iteras-return-to-page url='/?p=1']
  function return_to_page_shortcode($attrs) {
    $iterasnext = "iterasnext=" . urlencode($_SERVER["REQUEST_URI"]);

    $parsed = parse_url($attrs['url']);
    if (isset($parsed['query']))
      $parsed['query'] .= "&";
    $parsed['query'] .= $iterasnext;

    return unparse_url($parsed);
  }

  // [iteras-if-logged-in-link paywallid="abc123,def456" url="/?p=1" login_text="You need an account"][/iteras-if-logged-in-link]
  function if_logged_in_link_shortcode($attrs = array(), $content = null) {
    if (!$this->settings['paywall_server_side_validation']) {
      return '<!-- ITERAS server validation needed to use this shortcode -->' . $content;
    }

    $attrs = shortcode_atts(
      array(
        'login_text' => __('You need to be logged in to see this content'),
        'paywallid' => '',
        'url' => $this->settings['subscribe_url'],
      ),
      $attrs,
      'if_logged_in_link_shortcode'
    );

    $paywall_ids = array();
    if (!empty($attrs['paywallid'])) {
      $paywall_ids = $this->parse_paywall_ids($attrs['paywallid']);
    }
    else {
      $paywall_ids = $this->get_paywall_ids();
    }

    if (current_user_can('edit_pages')) {

      $admin_paywall_notice = '<div class="iteras-paywall-notice">';
      $admin_paywall_notice .= '<strong>' . __("This content is paywalled") . '</strong>';

      if (empty($paywall_ids)) {
        $admin_paywall_notice .= '<br>';
        $admin_paywall_notice .= '<em>' . __("paywallid not declared") . '</em>';
      }

      $admin_paywall_notice .= '<br>' . __("You are seeing the content because you are logged into WordPress admin.");
      $admin_paywall_notice .= '</div>';

      $content = $admin_paywall_notice . $content;
    } else {
      if (isset($_COOKIE['iteraspass']) && $this->pass_authorized($_COOKIE['iteraspass'], $paywall_ids, $this->settings['signing_key'])) {
        // User has access
      } else {
        // No access
        $content = '<a class="iteras-login-link" href="' . $this->return_to_page_shortcode(array( 'url' => $attrs['url'] )) . '">' . $attrs['login_text'] . '</a>';
      }
    }

    return $content;
  }

  // [iteras-if-logged-in paywallid="abc123,def456"][/iteras-if-logged-in]
  function if_logged_in_shortcode($attrs = array(), $content = null) {
    return $this->content_by_login_status( 'if_logged_in_shortcode', true, $attrs, $content );
  }

  // [iteras-if-not-logged-in paywallid="abc123,def456"][/iteras-if-not-logged-in]
  function if_not_logged_in_shortcode($attrs = array(), $content = null) {
    return $this->content_by_login_status( 'if_not_logged_in_shortcode', false, $attrs, $content );
  }

  function content_by_login_status($shortcode_name, $show_if_logged_in, $attrs = array(), $content = null) {
    if (!$this->settings['paywall_server_side_validation']) {
      return '<!-- ITERAS server validation needed to use this shortcode -->' . $content;
    }

    $attrs = shortcode_atts(
      array(
        'paywallid' => '',
      ),
      $attrs,
      $shortcode_name
    );

    $paywall_ids = array();
    if (!empty($attrs['paywallid'])) {
      $paywall_ids = $this->parse_paywall_ids($attrs['paywallid']);
    }
    else {
      $paywall_ids = $this->get_paywall_ids();
    }

    if (current_user_can('edit_pages')) {
      $content = __("You are seeing the content because you are logged into WordPress admin.") . $content;
    } else {
      if ((isset($_COOKIE['iteraspass']) && $this->pass_authorized($_COOKIE['iteraspass'], $paywall_ids, $this->settings['signing_key'])) == $show_if_logged_in) {
        // Returns the content without manipulation
      } else {
        $content = '';
      }
    }

    return $content;
  }
}
