<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A webservice interface to Mantis Bug Tracker
 *
 * @package MantisBT
 * @copyright Copyright MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

use Mantis\Exceptions\ClientException;

$g_app->group('/issues', function() use ( $g_app ) {
	$g_app->get( '', 'issues_get' );
	$g_app->get( '/', 'issues_get' );
	$g_app->get('/{project}', 'issues_get');
	$g_app->get('/{project}/', 'issues_get');
	$g_app->get('/{project}/{type}', 'issues_get');
	$g_app->get('/{project}/{type}/', 'issues_get');
});

/**
 * A method that does the work to handle getting an issue via REST API.
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function issues_get( \Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args ) 
{
	log_event(LOG_PLUGIN, "rest_issue_get");

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

	$t_result = null;
	$t_user_id = null;

	#
	# Get current user and check if upload access is granted
	#
	$t_user_id = auth_get_current_user_id();
	$t_current_user = user_get_username($t_user_id);
	log_event(LOG_PLUGIN, "User is %s", $t_current_user);

	$t_page_number = $p_request->getParam( 'page', 1 );
	$t_page_size = $p_request->getParam( 'page_size', 50 );

	# Get a set of issues
	$t_project_id = (int)$p_request->getParam( 'project_id', ALL_PROJECTS );
	if( $t_project_id != ALL_PROJECTS && !project_exists( $t_project_id ) ) 
	{
		$t_result = null;
		$t_message = "Project '$t_project_id' doesn't exist";
		$p_response = $p_response->withStatus( HTTP_STATUS_NOT_FOUND, $t_message );
	} 
	else 
	{
		/*
		$t_filter_id = trim( $p_request->getParam( 'filter_id', '' ) );

		if( !empty( $t_filter_id ) ) {
			$t_issues = mc_filter_get_issues(
				'', '', $t_project_id, $t_filter_id, $t_page_number, $t_page_size );
		} else {
			$t_issues = mc_filter_get_issues(
				'', '', $t_project_id, FILTER_STANDARD_ANY, $t_page_number, $t_page_size );
		}
		$t_result = array( 'issues' => $t_issues );
		*/
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

		$t_filter = filter_gpc_get();

		$t_issues = array();
		$t_rows = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count, $t_filter, $t_project_id, $t_user_id, true );
		if ($t_rows != null) 
		{
			log_event(LOG_PLUGIN, "Examine bugs");
			
			$t_lang = mci_get_user_lang( $t_user_id );

			foreach ($t_rows as $t_bug)
			{
				log_event(LOG_PLUGIN, "ID: %d  Status: %s Res: %s", $t_bug->id, $t_bug->status, $t_bug->resolution);

				if ($p_type == 'closed') {
					if ($t_bug->status >= $t_status_closed_level) {
						$t_issues[] = mci_issue_data_as_array( $t_bug, $t_user_id, $t_lang );
					}
				}
				else if ($p_type == 'open') {
					if ($t_bug->status < $t_status_closed_level) {
						$t_issues[] = mci_issue_data_as_array( $t_bug, $t_user_id, $t_lang );
					}
				}
				else { // 'all'
					$t_issues[] = mci_issue_data_as_array( $t_bug, $t_user_id, $t_lang );
				}
			}
		}
		
		$t_result = array( 'issues' => $t_issues );
		
		log_event(LOG_PLUGIN, "Total bug count is %d", count( $t_result ) );
	}

	return $p_response = $p_response->withStatus( HTTP_STATUS_SUCCESS )->withJson( $t_result );
}
