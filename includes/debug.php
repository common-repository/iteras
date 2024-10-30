<?php
if (!function_exists('_log')) {
  function _log( ...$messages ) {
    if( WP_DEBUG === true ) {
      foreach ( $messages as $message ) {
        if( !isset( $message ) ){
          error_log("*undefined*");
        }
        elseif( is_array( $message ) || is_object( $message ) ){
          error_log( print_r( $message, true ) );
        }
        elseif( is_bool( $message ) ){
          if ( $message === true )
            error_log("*true*");
          else
            error_log("*false*");
        }
        else {
          error_log( $message );
        }
      }
    }
  }
}
?>
