<?php
namespace BetterLinksPro\API;;

use BetterLinksPro\Traits\ArgumentSchema;
use BetterLinksPro\Traits\UTM as UTMTrait;

class UTM extends \WP_REST_Controller
{
    use ArgumentSchema, UTMTrait;
    /**
	 * Initialize hooks and option name
	 */
	private $templates;
	public function __construct()
	{
        $this->namespace = BETTERLINKS_PRO_PLUGIN_SLUG . '/v1';
		$this->rest_base = 'utm';

		$this->templates = get_option(BETTERLINKS_PRO_UTM_OPTION_NAME, []);
		
		// Previously saved templates were serialized, so we need to re-check if templates are serialized or not.
		if( is_serialized( $this->templates ) ) {
			$templates = unserialize($this->templates);
			for ($i=0; $i < count($templates); $i++) { 
				$templates[$i]['template_index'] = $i;
			}
			$this->templates = $templates;
		}
    }
    
    /**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes()
	{
		register_rest_route($this->namespace,
        '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [$this, 'get_items'],
				'permission_callback' => [$this, 'permissions_check'],
				'args' => $this->get_utm_schema(),
			],
		]);

		register_rest_route($this->namespace,
        '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => [$this, 'create_item'],
				'permission_callback' => [$this, 'permissions_check'],
				'args' => $this->get_utm_schema(),
			],
		]);

		register_rest_route($this->namespace,
        '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [$this, 'update_item'],
				'permission_callback' => [$this, 'permissions_check'],
				'args' => $this->get_utm_schema(),
			],
		]);

		register_rest_route($this->namespace,
        '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::DELETABLE,
				'callback' => [$this, 'delete_items'],
				'permission_callback' => [$this, 'permissions_check'],
				'args' => $this->get_utm_schema(),
			],
		]);

		register_rest_route(
			$this->namespace,
        '/' . $this->rest_base . '(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => [$this, 'permissions_check'],
					'args'                => $this->get_utm_schema(),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => [$this, 'permissions_check'],
					'args'                => $this->get_utm_schema(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => [$this, 'permissions_check'],
					'args'                => array(
						'template_name' => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Whether to bypass Trash and force deletion.' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
    }

    /**
	 * Get UTM
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function get_items($request)
	{
		$templates = $this->templates;
		
		return new \WP_REST_Response(
			$templates,
			200
		);
	}

	public function get_item($request) 
	{
		return new \WP_REST_Response(
			[],
			200
		);
	}

	/**
	 * Create OR Update betterlinks
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item($request)
	{
		$request = $request->get_params();
		$templates = $this->templates;

		// 👇 checking if utm_campaign or template_name is already exists
		$existing_field = $this->check_fields_exists($templates, $request['utm_campaign'], $request['template_name']);
		if( is_array( $existing_field ) ) {
			return new \WP_REST_Response(
				$existing_field,
				406
			);
		}

		$template_count = count( $templates );
		$request['template_index'] = $template_count > 0 ? $templates[count($templates)-1]['template_index']+1 : 0;
		array_push($templates, $request);
		update_option(BETTERLINKS_PRO_UTM_OPTION_NAME, $templates);
		return new \WP_REST_Response(
			$templates,
			200
		);
	}

	/**
	 * Create OR Update betterlinks
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item($request)
	{
		$params = $request->get_params();
		$templates = $this->templates;

		$new_templates = array_filter( $templates, function( $value ) use ($params) {
			return $value['template_index'] != $params['template_index'];
		} );

		$existing_field = $this->check_fields_exists($new_templates, $request['utm_campaign'], $request['template_name']);
		if( is_array( $existing_field ) ) {
			return new \WP_REST_Response(
				$existing_field,
				406
			);
		}

		if( '' !== $params['template_index'] ) {
			// $templates[$params['template_index']] = $params;
			for ($i=0; $i < count($templates); $i++) { 
				if( $templates[$i]['template_index'] == $params['template_index'] ) {
					$templates[$i] = $params; 
				}
			}
			update_option(BETTERLINKS_PRO_UTM_OPTION_NAME,  $templates );
			return new \WP_REST_Response(
				[
					'success' => true,
					'message' => esc_html__('Updated successfully', 'betterlinks-pro'),
					'templates' => $templates,
				],
				200
			);
		}

		return new \WP_REST_Response(
			[
				'success' => false,
				'message' => esc_html__('Update failed', 'betterlinks-pro'),
			],
			406
		);
	}

	/**
	 * Delete betterlinks
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function delete_items($request)
	{
		$params = $request->get_params();

		$templates = $this->templates;
		$template_index = $this->get_template(	$templates, $params['template_name']);
		if( $template_index !== false ) {
			unset($templates[$template_index]);
			$templates = array_values($templates);
			update_option(BETTERLINKS_PRO_UTM_OPTION_NAME,  $templates );
		}

		return new \WP_REST_Response(
			[
				'message' => 'deleted template',
				'templates' => $templates,
			],
			200
		);
	}
    
    /**
	 * Check if a given request has access to update a setting
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function permissions_check($request)
	{
		return current_user_can('manage_options');
	}
}