<?php

    require_once( 'releases_api.php' );

    form_security_validate( 'plugin_ApiExtend_config_update' );

    auth_reauthenticate();
    access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

    $t_project_id = helper_get_current_project();

    $t_action = gpc_get_string( 'action', 'none' );
    if ( $t_action == 'update' ) {
        $api_user = gpc_get_string( 'api_user' );
        $api_token = gpc_get_string( 'api_token' );
        $issues_count = gpc_get_bool( 'issues_count' );
        $issues_countbadge = gpc_get_bool( 'issues_countbadge' );
        plugin_config_set( 'api_user', $api_user, NO_USER, $t_project_id );
        plugin_config_set( 'api_token', $api_token, NO_USER, $t_project_id );
        plugin_config_set( 'issues_count', $issues_count, NO_USER, $t_project_id );
        plugin_config_set( 'issues_countbadge', $issues_countbadge, NO_USER, $t_project_id );
    }

    if ( $t_action == 'delete' && $t_project_id != ALL_PROJECTS ) {
        plugin_config_delete( 'api_user', NO_USER, $t_project_id );
        plugin_config_delete( 'api_token', NO_USER, $t_project_id );
        plugin_config_delete( 'issues_count', NO_USER, $t_project_id );
        plugin_config_delete( 'issues_countbadge', NO_USER, $t_project_id );
    }

    form_security_purge( 'plugin_ApiExtend_config_update' );

    print_successful_redirect(plugin_page("config", true));
