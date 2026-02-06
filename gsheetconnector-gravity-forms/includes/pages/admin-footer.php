<!-- plugin promotion footer-->
<?php
function gsheetconnector_gffree_admin_footer_text()
{
  $review_url = 'https://wordpress.org/support/plugin/gsheetconnector-gravity-forms/reviews/';
  $plugin_name = 'GSheetConnector For Gravity Forms';

  $text = sprintf(
    /* translators: %1$s: plugin name, %2$s: link to reviews */
    esc_html__(
      'Enjoy using %1$s? Check out our reviews or leave your own on %2$s.',
      'gsheetconnector-gravity-forms'
    ),
    '<strong>' . esc_html($plugin_name) . '</strong>',
    '<a href="' . esc_url($review_url) . '" target="_blank" rel="noopener">' . esc_html__('WordPress.org', 'gsheetconnector-gravity-forms') . '</a>'
  );

  echo wp_kses_post('<span id="footer-left" class="alignleft">' . $text . '</span>');
}
add_filter('admin_footer_text', 'gsheetconnector_gffree_admin_footer_text');


?>
<div class="gsheetconnect-footer-promotion">
  <p><?php echo esc_html("Made with ♥ by the GSheetConnector Team", "gsheetconnector-gravity-forms"); ?></p>
  <ul class="gsheetconnect-footer-promotion-links">
    <li> <a href="https://www.gsheetconnector.com/support"
        target="_blank"><?php echo esc_html("Support", "gsheetconnector-gravity-forms"); ?></a> </li>
    <li> <a href="https://www.gsheetconnector.com/docs/gravity-forms-to-google-sheet-free"
        target="_blank"><?php echo esc_html("Docs", "gsheetconnector-gravity-forms"); ?></a> </li>
   
    <li> <a
        href="https://profiles.wordpress.org/westerndeal/#content-plugins"><?php echo esc_html("Free Plugins", "gsheetconnector-gravity-forms"); ?></a>
    </li>
  </ul>
  <ul class="gsheetconnect-footer-promotion-social">
    <li> <a href="https://www.facebook.com/gsheetconnectorofficial" target="_blank"> <i class="fa-brands fa-facebook"
          aria-hidden="true"></i> </a> </li>
    <li> <a href="https://www.instagram.com/gsheetconnector/" target="_blank"> <i class="fa-brands fa-instagram"
          aria-hidden="true"></i> </a> </li>
    <li> <a href="https://www.linkedin.com/company/gsheetconnector/" target="_blank"> <i class="fa-brands fa-linkedin"
          aria-hidden="true"></i> </a> </li>
    <li> <a href="https://twitter.com/gsheetconnector?lang=en" target="_blank"> <i class="fa-brands fa-x-twitter"
          aria-hidden="true"></i> </a> </li>
    <li> <a href="https://www.youtube.com/@GSheetConnector" target="_blank"> <i class="fa-brands fa-youtube"
          aria-hidden="true"></i> </a> </li>
  </ul>
</div>