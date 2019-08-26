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
	log_event(LOG_PLUGIN, "bugcount_svg_get");
	$rsp = bugcount_base($p_request, $p_response, $p_args);
	return $p_response->withRedirect($rsp['url']);
}

function bugcount_get(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	log_event(LOG_PLUGIN, "bugcount_get");

	$rsp = bugcount_base($p_request, $p_response, $p_args);
	
	#$p_response->write(''.$rsp['count']);
	#return $p_response->withHeader(HTTP_STATUS_SUCCESS, "Success");

	$response = array(
		'count' => $rsp['count']
	);

	return $p_response->withStatus(HTTP_STATUS_SUCCESS, "Success")->withJson($response);
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
		if ($p_type != 'closed' && $p_type != 'open' && $p_type != 'all') {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'type' is invalid, must be one of 'closed' or 'open'.");
		}
	}

	$t_user_id = null;

	#
	# Get current user and check if upload access is granted
	#
	$t_user_id = auth_get_current_user_id();
	$t_current_user = user_get_username($t_user_id);
	log_event(LOG_PLUGIN, "User is %s", $t_current_user);

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
	$t_bug_count_filtered = 0;
	$t_status_closed_level = config_get("bug_resolved_status_threshold");

	if ( $p_request->getParam('filters') ) 
	{
		$t_req_filters = $p_request->getParam('filters');
		if ( is_string( $t_req_filters ) ) {
			$t_req_filters = json_decode( $t_req_filters, true );
		}
		foreach ($t_req_filters as $t_filter_num => $t_filter_value ) {
			log_event(LOG_PLUGIN, "Applying requested filter (%s) %s", $t_filter_num, implode(",",$t_filter_value));
			$_POST[$t_filter_value['property']] = $t_filter_value['value'];
		}
	}

	$_POST['type'] = '1';
	$_POST['view_type'] = 'simple';
	$_POST['per_page'] = '-1';
	$_POST['hide_status'] = '-2';
	if ( $p_type == 'open' ) {
		
	}
	else if ( $p_type == 'closed' ) {
		
	}

	$t_filter = filter_gpc_get();

	/*
	$t_filter = filter_ensure_valid_filter( $t_filter );
	# build a filter query, here for counting results
	$t_filter_query = new BugFilterQuery(
		$t_filter,
		array(
			'query_type' => BugFilterQuery::QUERY_TYPE_LIST,
			'project_id' => $t_project_id,
			'user_id' => $t_user_id,
			'use_sticky' => true
		)
	);
	$t_bug_count_filtered = $t_filter_query->get_bug_count();
	*/

	$t_rows = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count, $t_filter, $t_project_id, $t_user_id, true );
	if ($t_rows != null) 
	{
		log_event(LOG_PLUGIN, "Examine bugs");

		foreach ($t_rows as $bug)
		{
			log_event(LOG_PLUGIN, "ID: %d  Status: %s Res: %s", $bug->id, $bug->status, $bug->resolution);

			if ($p_type == 'closed') {
				if ($bug->status >= $t_status_closed_level) {
					$t_bug_count_filtered++;
				}
			}
			else if ($p_type == 'open') {
				if ($bug->status < $t_status_closed_level) {
					$t_bug_count_filtered++;
				}
			}
			else { // 'all'
				$t_bug_count_filtered++;
			}
		}

		log_event(LOG_PLUGIN, "Total bug count is %d", $t_bug_count);
		log_event(LOG_PLUGIN, "Filtered bug count is %d", $t_bug_count_filtered);
	}

	$t_badge_text = plugin_lang_get("api_badge_text_issues_$p_type") . "%20" . plugin_lang_get("api_badge_text_issues");
	$t_img_url = "https://img.shields.io/badge/" . $t_badge_text . "-" . $t_bug_count_filtered . "-" . $t_badge_color . ".svg?logo=codeigniter&logoColor=f5f5f5&cacheSeconds=3600";

	return array ( 'url' => $t_img_url, 'count' => (int)$t_bug_count_filtered);
}

