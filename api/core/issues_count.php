<?php

require_once( __DIR__ . '/../../../../vendor/erusev/parsedown/Parsedown.php');
require_once('bug_api.php' );
require_once('constant_api.php');
require_once('releases_api.php');
require_once('releases_email_api.php');

$g_app->group('/issues/count', function() use ($g_app) 
{
	$g_app->get('', 'bugcount_get');
	$g_app->get('/', 'bugcount_get');
	$g_app->get('/{project}', 'bugcount_get');
	$g_app->get('/{project}/', 'bugcount_get');
	$g_app->get('/{project}/{type}', 'bugcount_get');
	$g_app->get('/{project}/{type}/', 'bugcount_get');
});

$g_app->group('/issues/countbadge', function() use ($g_app) 
{
	$g_app->get('', 'bugcount_svg_get');
	$g_app->get('/', 'bugcount_svg_get');
	$g_app->get('/{project}', 'bugcount_svg_get');
	$g_app->get('/{project}/', 'bugcount_svg_get');
	$g_app->get('/{project}/{type}', 'bugcount_svg_get');
	$g_app->get('/{project}/{type}/', 'bugcount_svg_get');
});

function bugcount_svg_get(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	$rsp = bugcount_base($p_request, $p_response, $p_args);
	return $p_response->withRedirect($rsp['url']);
}

function bugcount_get(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	$rsp = bugcount_base($p_request, $p_response, $p_args);
	
	#$p_response->write(''.$rsp['count']);
	#return $p_response->withHeader(HTTP_STATUS_SUCCESS, "Success");

	$response = array(
		'count' => $rsp['count']
	);

	return $p_response->withStatus(HTTP_STATUS_CREATED, "Success")->withJson($response);
}

function bugcount_base(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	$t_project_id = 0;

	#
	# Ensure valid project was provided by client
	#
	$p_project = isset($p_args['project']) ? $p_args['project'] : $p_request->getParam('project');
	if (is_blank($p_project)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project' is missing.");
	} 
	else {
		$t_project_id = project_get_id_by_name($p_project, false);
		if ($t_project_id == null) {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid.");
		}
	}

	#
	# Ensure valid type was provided by client
	#
	$p_type = isset($p_args['type']) ? $p_args['type'] : $p_request->getParam('type');
	if (is_blank($p_type)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'type' is missing.");
	} 
	else {
		if ($p_type != 'closed' && $p_type != 'open') {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'type' is invalid, must be one of 'closed' or 'open'.");
		}
	}

	$t_user_id = null;

	#
	# Get current user and check if upload access is granted
	#
	$t_user_id = auth_get_current_user_id();
	$t_current_user = user_get_username($t_user_id);
	log_event(LOG_PLUGIN, "ApiExtend: User is %s", $t_current_user);

	#
	# Parse payload
	#
	$p_payload = $p_request->getParsedBody();
	if ($p_payload !== null) {
		#return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Unable to parse body, specify content type");
		#
		# User
		#
		#$t_current_user_id = auth_get_current_user_id();
		#$t_current_user = user_get_username($t_current_user_id);
		#$t_user_id = isset($p_payload['user']) ? $p_payload['user'] : null;
	}

	$t_badge_color = "C23023";
	if ($p_type == 'closed') {
		$t_badge_color = "0BB367";
	}
	
	$t_bug_count = 0;
	$t_page_number = 1;
	$t_per_page = -1;
	$t_page_count = 1;

	$t_rows = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count, null, $t_project_id, $t_user_id, true );
	if ($t_rows != null) 
	{
		$t_bug_count = 0;
		$t_bug_count_filtered = 0;
		$status_closed_level = config_get("bug_resolved_status_threshold");

		log_event(LOG_PLUGIN, "ApiExtend: Examine bugs");

		foreach ($t_rows as $bug)
		{
			log_event(LOG_PLUGIN, "ApiExtend: ID: %d  Status: %s Res: %s", $bug->id, $bug->status, $bug->resolution);

			$t_bug_count++;

			if ($p_type == 'closed') {
				if ($bug->status >= $status_closed_level) {
					$t_bug_count_filtered++;
				}
			}
			else {
				if ($bug->status < $status_closed_level) {
					$t_bug_count_filtered++;
				}
			}
		}

		log_event(LOG_PLUGIN, "ApiExtend: Total bug count is %d", $t_bug_count);
		log_event(LOG_PLUGIN, "ApiExtend: Filtered bug count is %d", $t_bug_count_filtered);
	}
	
	$t_badge_text = plugin_lang_get("api_badge_text_issues_$p_type") . "%20" . plugin_lang_get("api_badge_text_issues");
	$t_img_url = "https://img.shields.io/badge/$t_badge_text-$t_bug_count_filtered-$t_badge_color.svg?logo=codeigniter&logoColor=f5f5f5";

	return array ( 'url' => $t_img_url, 'count' => $t_bug_count_filtered);
}

