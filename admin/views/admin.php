<div class="wrap">
  <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

  <?php foreach ($messages as $message) { ?>
    <div class="notice notice-<?=$message['type']?> is-dismissible">
      <p><?=$message['text']?></p>
    </div>
  <?php } ?>

  <form method="post" action="">
    <input name="paywall" type="hidden" value="<?=$settings['paywall_id']; ?>">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="profile"><?php _e('ITERAS URL-id', $domain); ?></label></th>
        <td>
          <input class="regular-text" name="profile" placeholder="<?php _e('e.g. sportsmanden', $domain); ?>" type="text" value="<?=$settings['profile_name']; ?>">
          <p class="description"><?php _e('You can find your URL-id on the customer service settings page under the general settings section in the top right menu in ITERAS.', $domain); ?></p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="signingkey"><?php _e('ITERAS signing key', $domain); ?></label></th>
        <td>
          <input class="regular-text" id="signingkey" name="signing_key" placeholder="<?php _e('e.g.', $domain); ?> j8kau2v3lzp0n12ilw1d0hurr6059gyo" type="text" value="<?=$settings['signing_key']; ?>">
          <p class="description"><?php _e('You can find your signing key in the general settings section in the top right menu in ITERAS.', $domain); ?></p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="apikey"><?php _e('ITERAS API key', $domain); ?></label></th>
        <td>
          <input class="regular-text" id="apikey" name="api_key" placeholder="<?php _e('e.g.', $domain); ?> drurhphapaikr5fcywk158n93ghat0vz" type="text" value="<?=$settings['api_key']; ?>">
          <p class="description"><?php _e('You can create an API key under integrations in the top right menu in ITERAS.', $domain); ?></p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="apikey"><?php _e('Available paywalls', $domain); ?></label></th>
        <td>
          <?php
          if (empty($settings['paywalls'])) {
            _e('No paywalls available', $domain);
          }
          else {
          ?>
          <ul class="paywall-list">
            <?php foreach ($settings['paywalls'] as $paywall) { ?>
              <li><?=$paywall['name']?> <span class="muted">(ID: <?=$paywall['paywall_id']?>)</span></li>
            <?php } ?>
          </ul>
          <?php } ?>
          <button class="button lightgreen" name="sync"><span class="dashicons dashicons-update" style="line-height: 1.3;"></span> <?php _e('Synchronize', $domain); ?></button>
          <p class="description"><?php _e('You can configure paywalls in ITERAS in the paywalls section under integrations in the top right menu in ITERAS. Afterwards click the synchronize button here.', $domain); ?></p>
        </td>
      </tr>
      
      <tr>
        <th scope="row"><label for="defaultaccess"><?php _e('Default paywall access', $domain); ?></label></th>
        <td>
          <select id="defaultaccess" name="default_access">
            <?php foreach ($access_levels as $level => $label) { ?>
              <option value="<?=$level?>" <?php if ($settings['default_access'] == $level) echo 'selected="selected"' ?> ><?=$label?></option>
            <?php } ?>
          </select>

          <p class="description"><?php _e('Default paywall access for new posts.', $domain); ?></p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="paywall_display_type"><?php _e('Access restriction', $domain); ?></label></th>
        <td>
          <select id="paywall_display_type" name="paywall_display_type">
            <?php foreach ($this->paywall_display_types as $type => $label) { ?>
              <option value="<?=$type?>" <?php if ($settings['paywall_display_type'] == $type) echo 'selected="selected"' ?> ><?=$label?></option>
            <?php } ?>
          </select>

          <p class="description"><?php _e("How users will be greeted on an article they don't have access to.", $domain); ?></p>
        </td>
      </tr>

      <tr class="landing-page-type">
        <th scope="row"><label for="subscribeurl"><?php _e('Subscribe landing page', $domain); ?></label></th>
        <td>
          <input class="regular-text" id="subscribeurl" name="subscribe_url" placeholder="<?php _e('e.g. /?page_id=1', $domain); ?>" type="text" value="<?=$settings['subscribe_url']; ?>">
          <p class="description"><?php _e('URL to the landing page for logging in or becoming a <b>paying subscriber</b>.', $domain); ?></p>
        </td>
      </tr>

      <tr style="display: none">
        <th scope="row"><label for="userurl"><?php _e('User landing page', $domain); ?></label></th>
        <td>
          <input class="regular-text" id="userurl" name="user_url" placeholder="<?php _e('e.g. /?page_id=2', $domain); ?>" type="text" value="<?=$settings['user_url']; ?>">
          <p class="description"><?php _e('URL to the landing page for logging in or registering as a <b>user</b>. The subscribe and user landing page can point to the same Wordpress page.', $domain); ?></p>
        </td>
      </tr>

      <tr class="box-type">
        <th scope="row"><label for="paywall_snippet_size"><?php _e('Cut text at', $domain); ?></label></th>
        <td>
          <input class="regular-text" name="paywall_snippet_size" style="width:6em;" placeholder="<?php _e('e.g. 30', $domain); ?>" type="text" value="<?=$settings['paywall_snippet_size']; ?>"> <?php _e('characters', $domain); ?>
        </td>
      </tr>

      <tr class="box-type">
        <th scope="row"><label for="paywall_box"><?php _e('Call-to-action content', $domain); ?></label></th>
        <td>
          <?php wp_editor($settings['paywall_box'], "paywall_box"); ?>
          <p class="description"><?php _e('Present ordering offers and a login option. If you link to separate ordering and login pages, run the URL through the shortcode [iteras-return-to-page], e.g. &lta href="[iteras-return-to-page url=\'/?page_id=2\']&gt;sign up here!&lt;/a&gt;" to let the visitor return to the same page after ordering or logging in.', $domain); ?></p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="paywall_server_side_validation"><?php _e('Validation method', $domain); ?></label></th>
        <td>
          <label><input type="checkbox" name="paywall_server_side_validation" id="paywall_server_side_validation" <?php if ($settings['paywall_server_side_validation']) print("checked"); ?>><?php _e('Enable server-side validation of access pass cookie', $domain); ?></label>
          </select>

          <p class="description"><?php _e("With server-side validation, the ITERAS API key will be used to check the signature of access pass cookies. This effectively prevents leaking paywalled content even for visitors trying to circumvent the paywall. However, in some cases a caching front-end service may strip the cookie before it reaches the WordPress server or only allow it through for logged-in WordPress users. In case it's not possible to reconfigure the service, you can disable server-side validation.", $domain); ?></p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="paywall_integration_method"><?php _e('Paywall integration method', $domain); ?></label></th>
        <td>
          <select id="paywall_integration_method" name="paywall_integration_method">
            <?php foreach ($this->paywall_integration_methods as $method => $label) { ?>
              <option value="<?=$method?>" <?php if ($settings['paywall_integration_method'] == $method) echo 'selected="selected"' ?> ><?=$label?></option>
            <?php } ?>
          </select>

          <p class="description"><?php _e("For custom integration use either <code>[iteras-paywall-content]...[/iteras-paywall-content]</code> shortcode or call the API function <code>Iteras::get_instance().potentially_paywall_content(...)</code>.", $domain); ?></p>
        </td>
      </tr>
    </table>

    <?php if (ITERAS_DEBUG) { ?>
      <button class="button" name="reset">Reset</button>
    <?php } ?>

    <?php submit_button(); ?>
  </form>

  <p><?php _e('For more information about the ITERAS API check out the <a target="_blank" href="https://app.iteras.dk/api/">API documentation</a>.', $domain); ?>

</div>
