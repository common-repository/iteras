<?php

wp_nonce_field( "post".$post->ID, 'iteras_paywall_post_nonce' );

if (!$settings['api_key'] || !$settings['signing_key']) {
?>
  <div class="attention-box">
    <?=strtr(__('The ITERAS plugin has not been configured properly. Please go to the <a href="%url%">settings page</a> and complete the configuration.', $domain), array('%url%' => $this->settings_url())); ?>
  </div>
<?php
}
else {
  echo '<b>'.__('Only available for', $domain).":</b><br>";

  $i = 1;
  foreach ($settings['paywalls'] as $paywall) {
    echo '<input id="iteras-paywall-checkbox'.$i.'" type="checkbox" name="iteras-paywall[]" value="'.$paywall['paywall_id'].'" '. (in_array($paywall['paywall_id'], $enabled_paywalls) ? 'checked="checked"' : "").'>';
    echo '<label for="iteras-paywall-checkbox'.$i.'">'.$paywall['name'].'</label><br>';
    $i += 1;
  }

  if (!$settings['profile_name'] or empty($settings['paywalls']))
    echo '<br><p class="description error">'.strtr(__("The ITERAS settings haven't been filled in properly. Go to the <a href='%url%'>settings page</a> to correct them.", $domain), array('%url%' => $this->settings_url())).'</p>';
}
?>
