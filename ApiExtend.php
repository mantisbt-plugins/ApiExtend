<?php

# Copyright (c) 2019 Scott Meesseman
# Licensed under GPL3 

class ApiExtendPlugin extends MantisPlugin
{
    public function register()
    {
        $this->name = plugin_lang_get("title");
        $this->description = plugin_lang_get("description");
        $this->page = 'config';

        $this->version = "1.1.5";
        $this->requires = array(
            "MantisCore" => "2.0.0",
        );

        $this->author = "Scott Meesseman";
        $this->contact = "spmeesseman@gmail.com";
        $this->url = "https://github.com/mantisbt-plugins/ApiExtend";
    }

    function init() 
    {
        $t_inc = get_include_path();
        $t_core = config_get_global('core_path');
        $t_path = config_get_global('plugin_path'). plugin_get_current() . DIRECTORY_SEPARATOR . 'core'. DIRECTORY_SEPARATOR;
        if (strstr($t_inc, $t_core) == false) {
            set_include_path($t_inc . PATH_SEPARATOR . $t_core . PATH_SEPARATOR . $t_path);
        }
        else {
            set_include_path($t_inc .  PATH_SEPARATOR . $t_path);
        }
    }

    function config() {
        return array(
            'issues_count'  => ON,
            'issues_countbadge'  => ON,
            'version'  => ON,
            'versionbadge'  => ON,
            'next_version_type' => 1,
            'api_user' => '',
            'api_token' => ''
        );
    }

    /*
    public function hooks() 
    {
		return parent::hooks() + array(
			'EVENT_REST_API_ROUTES' => 'routes',
		);
    }
    
    public function routes( $p_event_name, $p_event_args ) 
    {
		$t_app = $p_event_args['app'];
		$t_plugin = $this;
		$t_app->group(
			plugin_route_group(),
			function() use ( $t_app, $t_plugin ) {
				$t_app->delete( '/{id}/token', [$t_plugin, 'route_token_revoke'] );
				$t_app->post( '/{id}/webhook', [$t_plugin, 'route_webhook'] );
			}
		);
	}

    public function route_token_revoke( $p_request, $p_response, $p_args ) 
    {

		return $p_response->withStatus( HTTP_STATUS_NO_CONTENT );
    }
    */

}
