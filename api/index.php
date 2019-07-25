<?php

# Bypass default Mantis headers
$g_bypass_headers = true;
$g_bypass_error_handler = true;

//require_once( __DIR__ . '/../../../vendor/autoload.php' );
require_once( __DIR__ . '/../../../core.php' );
require_once( __DIR__ . '/../../../api/soap/mc_core.php' );
require_api( 'plugin_api.php' );

$t_restcore_dir = __DIR__ . '/../../../api/rest/restcore/';
$t_restlocal_dir = __DIR__ . '/core/';

require_once( $t_restcore_dir . 'ApiEnabledMiddleware.php' );
require_once( $t_restlocal_dir . 'AuthMiddleware.php' );
require_once( $t_restcore_dir . 'CacheMiddleware.php' );
require_once( $t_restcore_dir . 'OfflineMiddleware.php' );
require_once( $t_restcore_dir . 'VersionMiddleware.php' );

# Hint to re-used mantisconnect code that it is being executed from REST rather than SOAP.
# For example, this will disable logic like encoding dates with XSD meta-data.
ApiObjectFactory::$soap = false;

# Show SLIM detailed errors according to Mantis settings
$t_config = array();

if( ON == config_get_global( 'show_detailed_errors' ) ) {
	$t_config['settings'] = array( 'displayErrorDetails' => true );
}

$t_container = new \Slim\Container( $t_config );
$t_container['errorHandler'] = function( $p_container ) {
	return function( $p_request, $p_response, $p_exception ) use ( $p_container ) {
		$t_data = array(
			'message' => $p_exception->getMessage(),
		);

		if( is_a( $p_exception, 'Mantis\Exceptions\MantisException' ) ) {
			global $g_error_parameters;
			$g_error_parameters =  $p_exception->getParams();
			$t_data['code'] = $p_exception->getCode();
			$t_data['localized'] = error_string( $p_exception->getCode() );

			$t_result = ApiObjectFactory::faultFromException( $p_exception );
			return $p_response->withStatus( $t_result->status_code, $t_result->fault_string )->withJson( $t_data );
		}

		if( is_a( $p_exception, 'Mantis\Exceptions\LegacyApiFaultException' ) ) {
			return $p_response->withStatus( $p_exception->getCode(), $p_exception->getMessage() )->withJson( $t_data );
		}

		$t_stack_as_string = error_stack_trace_as_string( $p_exception );
		$t_error_to_log =  $p_exception->getMessage() . "\n" . $t_stack_as_string;
		error_log( $t_error_to_log );

		$t_settings = $p_container->get('settings');
		if( $t_settings['displayErrorDetails'] ) {
			$p_response = $p_response->withJson($t_data);
		}

		return $p_response->withStatus( HTTP_STATUS_INTERNAL_SERVER_ERROR );
	};
};

$g_app = new \Slim\App($t_container);

# Add middleware - executed in reverse order of appearing here.
$g_app->add(new ApiEnabledMiddleware());
$g_app->add(new AuthMiddleware());
$g_app->add(new VersionMiddleware());
$g_app->add(new OfflineMiddleware());
$g_app->add(new CacheMiddleware());

$routes_registered = false;

plugin_push_current('ApiExtend');

if (plugin_config_get("issues_count") == ON)
{
	$routes_registered = true;
	require_once($t_restlocal_dir . 'issues_count.php' );
}

if ($routes_registered == true) {
	event_signal('EVENT_REST_API_ROUTES', array(array('app' => $g_app)));
	$g_app->run();
	plugin_pop_current();
}
else {
	plugin_pop_current();
	trigger_error("Disabled endpoint");
}
