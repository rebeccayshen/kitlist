<?php
/**
 * Adjust shortcodes handler in compatibility to WordPress 4.2.3
 */

/** Views runs a filter similar to this but set at a higher priority. */
/** WooCommerce Views sets at lower priority to catch unprocessed shortcodes. */

add_filter('the_content', 'wcviews_preprocess_shortcodes_for_4_2_3', 11);

function wcviews_preprocess_shortcodes_for_4_2_3($content) {
	
	$inner_expressions = array();
	
	//Support for legacy shortcodes to avoid breaking things in old sites.
	$inner_expressions[] = "/\\[(wpv-wooaddcart|wpv-wooaddcartbox|wpv-wooremovecart|wpv-woo-carturl).*?\\]/i";
	
	//Support for newer version of shortcodes
	$inner_expressions[] = "/\\[(wpv-woo-|wpv-add-).*?\\]/i";
			
	foreach ($inner_expressions as $shortcode) {
		$counts = preg_match_all($shortcode, $content, $matches);		
		if($counts > 0) {
			foreach($matches[0] as &$match) {				
				$replacement = do_shortcode($match);
				$resolved_match = $replacement;
				$content = str_replace($match, $resolved_match, $content);
			}
		}
	}
	
	return $content;
}