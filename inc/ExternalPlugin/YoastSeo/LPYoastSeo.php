<?php
/**
 * Class LPYoastSeo
 *
 * Compatible with wordpress.org/plugins/wordpress-seo/
 * @since 4.2.6.4
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\YoastSeo;

use LearnPress\Helpers\Singleton;
use LP_Helper;
use LP_Page_Controller;

class LPYoastSeo {
	use Singleton;

	public function init() {
		add_filter( 'wpseo_opengraph_url', [ $this, 'opengraph_url' ] );
	}

	public function opengraph_url( $open_graph_url ) {
		if ( LP_Page_Controller::is_page_instructor() ) {
			$open_graph_url = LP_Helper::getUrlCurrent();
		}

		return $open_graph_url;
	}
}
