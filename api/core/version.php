<?php

require_once('version_api.php' );

$g_app->group('/version', function() use ($g_app) 
{
	$g_app->get('', 'apiextend_version_get');
	$g_app->get('/', 'apiextend_version_get');
	$g_app->get('/{project}', 'apiextend_version_get');
	$g_app->get('/{project}/', 'apiextend_version_get');
	$g_app->get('/{project}/{type}', 'apiextend_version_get');
	$g_app->get('/{project}/{type}/', 'apiextend_version_get');
});

$g_app->group('/versionbadge', function() use ($g_app) 
{
	$g_app->get('', 'apiextend_version_svg_get');
	$g_app->get('/', 'apiextend_version_svg_get');
	$g_app->get('/{project}', 'apiextend_version_svg_get');
	$g_app->get('/{project}/', 'apiextend_version_svg_get');
	$g_app->get('/{project}/{type}', 'apiextend_version_svg_get');
	$g_app->get('/{project}/{type}/', 'apiextend_version_svg_get');
});

function apiextend_version_svg_get(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	$rsp = apiextend_version_base($p_request, $p_response, $p_args);
	return $p_response->withRedirect($rsp['url']);
}

function apiextend_version_get(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	$rsp = apiextend_version_base($p_request, $p_response, $p_args);
	
	#$p_response->write(''.$rsp['count']);
	#return $p_response->withHeader(HTTP_STATUS_SUCCESS, "Success");

	$response = array(
		'version' => $rsp['version']
	);

	return $p_response->withStatus(HTTP_STATUS_CREATED, "Success")->withJson($response);
}

function apiextend_version_base(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
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
		if ($p_type != 'current' && $p_type != 'next') {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'type' is invalid, must be one of 'current' or 'next'.");
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
		
	}

	#
	# Badge color
	#
	$t_badge_color = "0E7FBF";
	if (isset($p_args['color']) && strlen($p_args['color'] == 6)) {
		$t_badge_color = $p_args['color'];
	}

	$t_version = null;
	log_event(LOG_PLUGIN, "ApiExtend: Examine versions");

	if ($p_type == "current")
	{
		$t_versions = version_get_all_rows($t_project_id, VERSION_RELEASED);

		foreach ($t_versions as $v) 
		{
			if ($t_version != null && strpos($t_version, '.') != false && version_compare($v['version'], $t_version) > 0) {
				$t_version = $v['version'];
			}
			else if ($t_version != null && strpos($t_version, '.') == false && strcmp($v['version'], $t_version) > 0) {
				$t_version = $v['version'];
			}
			else if ($t_version == null) {
				$t_version = $v['version'];
			}
		}
	}
	else 
	{
		$nextMinor = (plugin_config_get('next_version_type') == 1);
		$versions = version_get_all_rows($t_project_id, VERSION_FUTURE);
		foreach ($versions as $v) 
		{
			if ($t_version != null && strpos($t_version, '.') != false && version_compare($v['version'], $t_version) < 0) 
			{
				if (true) {
					if (substr_compare($v['version'], '.0', -2) != 0) {
						continue;
					}
				}
				$t_version = $v['version'];
			}
			else if ($t_version != null && strpos($t_version, '.') == false && strcmp($v['version'], $t_version) < 0) 
			{
				$t_version = $v['version'];
			}
			else if ($t_version == null) 
			{
				if ($t_version == true) {
					if (strpos($v['version'], '.') != false) {
						if (substr_compare($v['version'], '.0', -2) != 0) {
							continue;
						}
					}
				}
				$t_version = $v['version'];
			}
		}
	}

	if ($t_version != null && $t_version != '') 
	{
		log_event(LOG_PLUGIN, "ApiExtend: $p_type version is %d", $t_version);
	}
	else {
		$t_version = "None";
	}
	
	$t_badge_text = plugin_lang_get("api_badge_text_version_$p_type") . "%20" . plugin_lang_get("api_badge_text_version");
	$t_img_url = "https://img.shields.io/badge/$t_badge_text-$t_version-$t_badge_color.svg?logo=azure%20pipelines&logoColor=f5f5f5&cacheSeconds=3600";

	return array ( 'url' => $t_img_url, 'version' => $t_version);
}