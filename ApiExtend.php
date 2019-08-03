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

        $this->version = "1.1.3";
        $this->requires = array(
            "MantisCore" => "2.0.0",
        );

        $this->author = "Scott Meesseman";
        $this->contact = "spmeesseman@gmail.com";
        $this->url = "https://github.com/mantisbt-plugins/ApiExtend";
    }

    function init() 
    {
        $t_core = config_get_global('core_path');
        $t_path = config_get_global('plugin_path'). plugin_get_current() . DIRECTORY_SEPARATOR . 'core'. DIRECTORY_SEPARATOR;
        set_include_path(get_include_path() . PATH_SEPARATOR . $t_core . PATH_SEPARATOR . $t_path);
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

}
