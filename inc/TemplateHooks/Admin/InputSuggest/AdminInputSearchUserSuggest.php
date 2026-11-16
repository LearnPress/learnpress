<?php

namespace LearnPress\TemplateHooks\Admin\InputSuggest;

use Exception;
use LearnPress\Databases\UserDB;
use LearnPress\Filters\UserFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use stdClass;
use Throwable;

/**
 * Admin search-suggest input for users.
 *
 * Usage: echo AdminInputSearchUserSuggest::instance()->layout( [ 'name' => 'user_id' ] );
 *
 * @since 4.4.15
 * @version 1.0.0
 */
class AdminInputSearchUserSuggest extends AdminInputSearchSuggest {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Allow callback for AJAX.
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		/** @use self::render_users */
		$callbacks[] = get_class( $this ) . ':render_users';

		return $callbacks;
	}

	/**
	 * HTML of the search user input.
	 *
	 * @param array $data {@see AdminInputSearchSuggest::layout()}
	 *
	 * @return string
	 */
	public function layout( array $data = [] ): string {
		$args = [
			'id_url'                  => 'lp-search-user-suggest',
			'search'                  => '',
			// Empty string: nothing is loaded until the user types.
			'html_no_load_ajax_first' => '',
			'enableScrollToView'      => false,
			'enableUpdateParamsUrl'   => false,
		];

		/** @use self::render_users */
		$callback = [
			'class'  => self::class,
			'method' => 'render_users',
		];

		$data['html_result'] = TemplateAJAX::load_content_via_ajax( $args, $callback );
		if ( ! isset( $data['placeholder'] ) ) {
			$data['placeholder'] = __( 'Search user...', 'learnpress' );
		}

		return parent::layout( $data );
	}

	/**
	 * Render list users suggest, called via AJAX.
	 *
	 * Permission: administrator only.
	 *
	 * @param array $data [ 'search' => keyword, 'type_select' => 'multiple' to render the items with checkbox ]
	 *
	 * @return stdClass
	 */
	public static function render_users( array $data = [] ): stdClass {
		$content          = new stdClass();
		$content->content = '';

		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( esc_html__( 'You do not have permission to search users.', 'learnpress' ) );
			}

			$search = self::get_search( $data );
			if ( $search === '' ) {
				return $content;
			}

			$filter                  = new UserFilter();
			$filter->only_fields     = [ 'ID', 'display_name', 'user_email' ];
			$filter->search          = $search;
			$filter->order_by        = 'display_name';
			$filter->order           = 'ASC';
			$filter->limit           = self::LIMIT;
			$filter->page            = 1;
			$filter->run_query_count = false;

			$users = UserDB::getInstance()->get_users( $filter );
			$items = [];
			if ( is_array( $users ) ) {
				foreach ( $users as $user ) {
					$display_name = (string) ( $user->display_name ?? '' );
					$items[]      = [
						'id'    => (int) ( $user->ID ?? 0 ),
						'text'  => $display_name,
						'label' => sprintf( '%s (%s)', $display_name, $user->user_email ?? '' ),
					];
				}
			}

			$content->content = self::html_list_result( $items );
		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
		}

		return $content;
	}
}
