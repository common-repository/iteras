=== ITERAS ===
Tags: paywall, subscribe, subscriptions, subscription, subscribers, access-control, paid content, premium, premium content, monetize, magazine, media pass, registration, billing, membership, member, earn money
Requires at least: 3.5.1
Tested up to: 6.4.2
Stable tag: 1.7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integration with ITERAS, a cloud-based state-of-the-art system for managing subscriptions and payments for magazines.

== Description ==

ITERAS is a complete system for managing subscriptions for print and digital magazines. We automate as many of the hassles of billing as possible and offer you a choice of a wide range of billing methods.

If you publish a printed publication in Denmark and Norway you will likely need to be able to send "indbetalingskort"/"girokort" which we have fully automated. We also integrate with the Danish NemHandel (EAN). Therefore, Iteras is a one-stop solution for Danish and Norwegian magazines and others with similar needs (foreninger, software providers, etc.).

For more information, check out [iteras.dk](https://www.iteras.dk/).

This plugin allows you to integrate the ordering and self-service parts of ITERAS easily, and set up a paywall in WordPress to only allow access to certain posts to your ITERAS subscribers.

The plugin uses the ITERAS Javascript API, allowing further customizations for those who need it.

== Installation ==

1. Navigate to the 'Add New' in the plugins dashboard and either upload the plugin (if you have already downloaded it) or search for "ITERAS" in WordPress plugins, then click "Install Now".
2. Activate the plugin in the plugins dashboard.
3. Go to the plugin settings (either from the dashboard or from Settings->ITERAS) and fill in the requested details. You'll need to do some work on either a call to action box or a landing page for the paywall.

== Frequently Asked Questions ==

= How does the plugin work? =

Each page/post gets a little box where you can choose whether to paywall it or not. The plugin then associates a little bit of extra metadata with the post (stored with the key "iteras_paywall").

Then when that post is served, the plugin first checks if the visitor has a valid access pass from the ITERAS server.

If not you can setup the plugin to cut off the content and instead insert call to action content (sign up to get access or login). Or you can also setup the plugin to redirect to another landing page.

= How do I get started? =

Make a test subscriber inside Iteras, fill in the plugin settings, add a test post that you set to be behind the paywall, then experiment with the layout. Once you're happy, you can add the paywall to real posts.

= What shortcodes are available? =

You can insert a login iframe in the content by using the WordPress shortcode `[iteras-paywall-login paywallid="abc123,def456"]`.

You can insert an ordering iframe for signing up for a subscription or ordering a product with the shortcode `[iteras-ordering orderingid="subscribenow"]`.

You can insert a selfservice iframe to allow subscribers to manage their subscriptions with the shortcode `[iteras-selfservice]`.

The `[iteras-return-to-page url='/some/url/?p=123']` is useful if you need to link to another page in the call to action content. It will append the current page to the given URL so that the visitor is redirected back to the original page after having completed an ordering flow or having logged in. NOTE: due to limitations in the WordPress parser, when you put [iteras-return-to-page] in an HTML tag attribute, you need to be aware of single quotes and double quotes. `<a href="[iteras-return-to-page url='/some/url/?p=123']">sign up!</a>` works while `<a href="[iteras-return-to-page url="/some/url/?p=123"]">sign up!</a>` doesn't!

These shortcodes, except [iteras-return-to-page], are internally converted to calls to the ITERAS Javascript API. You can pass more parameters if you need to, e.g. `next`, see the [API documentation](https://app.iteras.dk/api/) for more details. The profile parameter is implicit and comes from your plugin settings.

There's a few shortcodes for controlling content based on the users logged in status. For showing content when the user has access use `[iteras-if-logged-in paywallid="abc123,def456"]Content only shown if the user is logged in and has access[/iteras-if-logged-in]`. `[iteras-if-not-logged-in]` does the opposite. `[iteras-if-logged-in-link paywallid="abc123,def456"]Content[/iteras-if-logged-in-link]` will automatically insert a link to the subscriber landing page that is configured with the plugin, alternatively a `url` attribute can be supplied, while `login_text` can we used to customize the link text. These shortcodes only work with server validation enabled.

= Is it possible to force the selection of a specific product on an ordering form? =

Yes! Add the GET-parameter `orderproduct` to a link to your ordering page, e.g. `/buysubscription/?orderproduct=12MPro`. This will select the "12MPro" product when the user gets to the form.

= Is it possble to programatically control if a page should be paywalled? =

It is possible to override the decision of which content should be paywalled. Add a hook to the `iteras_override_content_paywall` filter to define a function that should have the last say in if the content should be paywalled or not. This can for example be used for handling share links the gives the user access eventhough they don't have a login. This could look something like this:

`
/**
 * Override the decision to paywall the particular content
 *
 * @param integer $post_paywall_ids The paywalls that give access to the post, if empty the post would not be paywalled be default
 * @param object $post Reference to the post being potentially paywalled
 * @param boolean $user_authorized If the user has a valid iteraspass that has the appropriate access
 * @param array $paywall_config Contains the Iteras plugin configuration
 */
function make_paywall_decision($post_paywall_ids, $post, $user_authorized, $paywall_config) {
  // example of generated share token
  error_log(openssl_encrypt(json_encode(array('shared_by'=>"John Doe", 'post_id' => $post->ID)), "aes-128-cbc", "somesecret"));

  $token = $_GET['token'];
  if (isset($token)) {
    $share = json_decode(openssl_decrypt($token, "aes-128-cbc", "somesecret"));
    if (isset($share) and $share->post_id == $post->ID) {
      echo "Shared by " . $share->shared_by;
      // signal that the paywall should not be applied
      return false;
    }
  }

  // return the paywall ids to apply the paywall as normal
  return $post_paywall_ids;
}

add_filter('iteras_override_content_paywall', 'make_paywall_decision', 10, 4);
`

= My visitors don't return to the page they came from after having signed up? =

When you link to other pages, you may be losing the return information needed to redirect the visitors. Send the URL through [iteras-return-to-page] as described above to forward the return information.

When an iframe is embedded directly, the return information is inserted automatically, but when you put the iframe on another page and link to that, the iframe needs to know where the visitors come from to be able to return them correctly.

= Can I do something with this plugin without an ITERAS account? =

No. But it’s not hard to get one - if you are interested in learning more about ITERAS, please visit [iteras.dk](https://www.iteras.dk/) and contact us.

= What should I do if the plugin ends up being included multiple times on the same page? =

You will need to do a custom integration. The plugin attaches to the `the_content` hook in Wordpress which in some situations is called mutiple times by 3rd party plugins.

In this case set the "Paywall integration method" to "Custom" and add the paywall code manually to the theme or plugin you are using. This can be done either by wrapping the post body with the shortcode `[iteras-paywall-content]...[/iteras-paywall-content]` or by calling `Iteras::get_instance()->potentially_paywall_content(...)` which returns the content wrapped with the paywall.


== Changelog ==
= 1.7.0 =
* Paywall signing key is now handled separately

= 1.6.0 =
* Added bulk action for updating paywall state on posts

= 1.5.0 =
* Added possibility to force a product selection on ordering forms with the `orderproduct` GET parameter
* Fixed problem with fade bar styling of the call-to-action box

= 1.4.1 =
* Content in feeds (such as RSS) is now truncated if the post has been marked as paywalled

= 1.4.0 =
* Added `iteras_override_content_paywall` filter for overriding the decision of paywalling the content

= 1.3.6 =
* Added `[iteras-if-logged-in]`, `[iteras-if-not-logged-in]` and `[iteras-if-logged-in-link]` shortcodes for controlling content based on logged in status

= 1.3.5 =
* Added filter (`after_paywall_script_prepared_except_redirect`) of the prepared paywall script before adding to the end of content

= 1.3.4 =
* Render paywall box during document render to make sure Iteras shortcodes are inserted at the proper position

= 1.3.2 =
* Fixed problem related to older Wordpress versions

= 1.3.1 =
* Moved down paywall priority so other shortcodes are run before truncating content
* Only apply paywall to detail pages (singles)

= 1.3.0 =
* Show paywalled content for editors
* Added indication if content is paywalled on post list view
* Fixed problem with default paywall configuration

= 1.2.1 =
* Tested with WordPress 5.1

= 1.2 =
* Output paywall pass cookie check result to be able to detect missing cookies
* New setting to disable server-side validation of paywall pass cookies

= 1.1.2 =
* Be more defensive when checking the pass to prevent possible compatibility problems
* Remove incorrect warning when using the call to action content

= 1.1.1 =
* Fix server-side check in case a setting is missing
* Add [iteras-ordering] shortcode to replace deprecated [iteras-signup] (still accessible for the time being)
* Add [iteras-return-to-page url='...'] to make it easier to avoid breaking the return path when using links in call to action content
* Fix some issues in the settings page
* Update translation
* Expand documentation

= 1.1 =
* Server-side authorization check and content truncation
* Paywalling of pages

= 1.0 =
* Support for new paywall setup in ITERAS with multiple paywalls
* Changed how posts are paywalled
* Update to this version requires reconfiguration of the plugin
* Paywall login shortcode now accepts paywallid [iteras-paywall-login paywallid="abc123,def456"]

= 0.5 =
* Support for manual inclusion of paywall
* Direct access to paywall functions for custom integration

= 0.4.5 =
* Corrected references to the ITERAS backend

= 0.4.4 =
* Support older PHP versions

= 0.4.3 =
* Fixed CSS gradient for call-to-action box

= 0.4.1 =
* Fixed shortcode handling

= 0.4 =
* Added support for call-to-action box and cut text on restricted posts

= 0.3 =
* Added shortcodes for embedding ITERAS content (iteras-signup, iteras-selfservice, iteras-paywall-login)
* Added danish translation

= 0.2 =
* First public release

= 0.1 =
* First release - paywall integration.

== Upgrade Notice ==

= 0.1 =
You should upgrade to this first version.
