<?php
/**
 * Page post type handlers
 *
 * @package WordPress
 * @subpackage ENGAP 
 */

/**
 * Page post type handlers
 *
 * This class serves as a small addition on top of the basic post handlers to
 * add small functionality on top of the existing API.
 *
 * In addition, this class serves as a sample implementation of building on top
 * of the existing APIs for custom post types.
 *
 * @package WordPress
 * @subpackage ENGAP 
 */
class ENGAP_Pages extends ENGAP_CustomPostType {
	/**
	 * Base route
	 *
	 * @var string
	 */
	protected $base = '/pages';

	/**
	 * Post type
	 *
	 * @var string
	 */
	protected $type = 'page';

	/**
	 * Register the page-related routes
	 *
	 * @param array $routes Existing routes
	 * @return array Modified routes
	 */
	public function register_routes( $routes ) {
		$routes = parent::register_routes( $routes );
		$routes = parent::register_revision_routes( $routes );
		$routes = parent::register_comment_routes( $routes );

		// Add post-by-path routes
		$routes[ $this->base . '/(?P<path>.+)'] = array(
			array( array( $this, 'get_post_by_path' ),    ENGAP_Server::READABLE ),
			array( array( $this, 'edit_post_by_path' ),   ENGAP_Server::EDITABLE | ENGAP_Server::ACCEPT_JSON ),
			array( array( $this, 'delete_post_by_path' ), ENGAP_Server::DELETABLE ),
		);
        
       $routes[ $this->base]   =array(
				array( array( $this, 'get_pages' ),      ENGAP_Server::READABLE ),
			);

		return $routes;
	}

public function get_pages( $filter = array(), $context = 'view', $type = 'post', $page = 1, $limit = 3) {



$page_ids = get_all_page_ids();
$page_ids_str ='';
foreach ($page_ids as $page_id){
    $page_ids_str .=" / ".$page_id;

}

        $return = array('status'=>'0','result'=>$page_ids_str);
        $response   = new ENGAP_Response();

		$response->set_data( $return );

		return $response;
}
	/**
	 * Retrieve a page by path name
	 *
	 * @param string $path
	 * @param string $context
	 *
	 * @return array|WP_Error
	 */
	public function get_post_by_path( $path, $context = 'view' ) {
		$post = get_page_by_path( $path, ARRAY_A );

		if ( empty( $post ) ) {
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
		}

		return $this->get_post( $post['ID'], $context );
	}

	/**
	 * Edit a page by path name
	 *
	 * @param $path
	 * @param $data
	 * @param array $_headers
	 *
	 * @return true|WP_Error
	 */
	public function edit_post_by_path( $path, $data, $_headers = array() ) {
		$post = get_page_by_path( $path, ARRAY_A );

		if ( empty( $post ) ) {
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
		}

		return $this->edit_post( $post['ID'], $data, $_headers );
	}

	/**
	 * Delete a page by path name
	 *
	 * @param $path
	 * @param bool $force
	 *
	 * @return true|WP_Error
	 */
	public function delete_post_by_path( $path, $force = false ) {
		$post = get_page_by_path( $path, ARRAY_A );

		if ( empty( $post ) ) {
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
		}

		return $this->delete_post( $post['ID'], $force );
	}

	/**
	 * Prepare post data
	 *
	 * @param array $post The unprepared post data
	 * @param string $context The context for the prepared post. (view|view-revision|edit|embed|single-parent)
	 * @return array The prepared post data
	 */
	protected function prepare_post( $post, $context = 'view' ) {
		$_post = parent::prepare_post( $post, $context );

		// Override entity meta keys with the correct links
		$_post['meta']['links']['self'] = json_url( $this->base . '/' . get_page_uri( $post['ID'] ) );

		if ( ! empty( $post['post_parent'] ) ) {
			$_post['meta']['links']['up'] = json_url( $this->base . '/' . get_page_uri( (int) $post['post_parent'] ) );
		}

		return apply_filters( 'json_prepare_page', $_post, $post, $context );
	}
}
