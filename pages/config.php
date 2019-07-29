<?php

require_once('constant_api.php');
require_once('releases_api.php');

auth_reauthenticate();
access_ensure_global_level(config_get('manage_plugin_threshold'));

$t_project_id = helper_get_current_project();
$t_title = str_replace( '%%project%%', project_get_name( $t_project_id ), plugin_lang_get( 'config_page_title' ) );
layout_page_header($t_title);

layout_page_begin('manage_overview_page.php');
print_manage_menu('manage_plugin_page.php');

?>

<br />
<!-- div align="center" -->
<?php
$t_block_id = 'ApiExtend_config';
$t_collapse_block = is_collapsed($t_block_id);
$t_block_css = $t_collapse_block ? 'collapsed' : '';
$t_block_icon = $t_collapse_block ? 'fa-chevron-down' : 'fa-chevron-up';
$t_fa_icon = 'fa-cogs';
$next_version_type = plugin_config_get('next_version_type');

echo '<div id="' . $t_block_id . '" class="widget-box widget-color-blue2  no-border ' . $t_block_css . '">';
echo '  <div class="widget-header widget-header-small">';
echo '    <h4 class="widget-title lighter">';
echo '    <i class="ace-icon fa ' . $t_fa_icon . '"></i>';
echo $t_title, lang_get('word_separator');
echo '    </h4>';
echo '    <div class="widget-toolbar">';
echo '      <a data-action="collapse" href="#">';
echo '        <i class="1 ace-icon fa ' . $t_block_icon . ' bigger-125"></i>';
echo '      </a>';
echo '    </div>';
echo '  </div>';
echo '  <div class="widget-body">';
echo '    <div class="widget-main">';
?>

<br />
<form name="plugins_releases" method="post" action="<?php echo plugin_page('config_update') ?>">
    <?php echo form_security_field('plugin_ApiExtend_config_update') ?>
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="project_id" value="<?php echo $t_project_id ?>" />
    <input type="hidden" name="plugin" value="releases" />
    <!--    <table class="width75" cellspacing="1"> -->

    <div class="widget-box widget-color-blue2  no-border">
    <div class="widget-header widget-header-small">
    <h4 class="widget-title lighter"><?php echo plugin_lang_get('config_section_general') ?></h4>
    </div>
    </div>

    <table class="width100 table table-striped table-bordered table-condensed" cellspacing="1">

        <tr <?php echo helper_alternate_class() ?>>
            <td class="category" width="150">
                <?php echo plugin_lang_get('api_user'); ?>
            </td>
            <td>
                <input name="api_user" size="20" value="<?php echo plugin_config_get('api_user', '') ?>" />
            </td>
        </tr>

        <tr <?php echo helper_alternate_class() ?>>
            <td class="category" width="150">
                <?php echo plugin_lang_get('api_token'); ?>
            </td>
            <td>
                <input name="api_token" size="50" value="<?php echo plugin_config_get('api_token', '') ?>" />
            </td>
        </tr>
                                        
    </table>

    <div class="widget-box widget-color-blue2  no-border">
    <div class="widget-header widget-header-small">
    <h4 class="widget-title lighter"><?php echo plugin_lang_get('config_section_issues') ?></h4>
    </div>
    </div>

    <table class="width100 table table-striped table-bordered table-condensed" cellspacing="1">

        <!-- issues/count enable -->
        <tr <?php echo helper_alternate_class() ?>>
            <td class="category" width="150">
                <?php echo plugin_lang_get('config_issues_count'); ?>
            </td>
            <td>
                <input type="checkbox" name="issues_count" <?php if (plugin_config_get('issues_count', ON) == ON) echo ' checked="checked"' ?> />
            </td>
        </tr>

        <tr <?php echo helper_alternate_class() ?>>
            <td class="category" width="150">
                <?php echo plugin_lang_get('config_issues_countbadge'); ?>
            </td>
            <td>
                <input type="checkbox" name="issues_countbadge" <?php if (plugin_config_get('issues_countbadge', ON) == ON) echo ' checked="checked"' ?> />
            </td>
        </tr>
                                        
    </table>

    <div class="widget-box widget-color-blue2  no-border">
    <div class="widget-header widget-header-small">
    <h4 class="widget-title lighter"><?php echo plugin_lang_get('config_section_version') ?></h4>
    </div>
    </div>

    <table class="width100 table table-striped table-bordered table-condensed" cellspacing="1">

        <!-- version enable -->
        <tr <?php echo helper_alternate_class() ?>>
            <td class="category" width="150">
                <?php echo plugin_lang_get('config_version'); ?>
            </td>
            <td>
                <input type="checkbox" name="version" <?php if (plugin_config_get('version', ON) == ON) echo ' checked="checked"' ?> />
            </td>
        </tr>

        <tr <?php echo helper_alternate_class() ?>>
            <td class="category" width="150">
                <?php echo plugin_lang_get('config_versionbadge'); ?>
            </td>
            <td>
                <input type="checkbox" name="versionbadge" <?php if (plugin_config_get('versionbadge', ON) == ON) echo ' checked="checked"' ?> />
            </td>
        </tr>

        <tr>
            <td class="category">
                <?php echo plugin_lang_get('config_next_version_type') ?>
            </td>
            <td>
                <select name="next_version_type">
                    <option value="0" <?php echo check_selected($next_version_type, 0); ?>><?php echo plugin_lang_get('config_next_unreleased') ?></option>
                    <option value="1" <?php echo check_selected($next_version_type, 1); ?>><?php echo plugin_lang_get('config_next_minor_unreleased') ?></option>
                </select>
            </td>
        </tr>
                                        
    </table>

    <input tabindex="4" type="submit" class="button" value="<?php echo lang_get('submit_button') ?>" /> &nbsp;&nbsp;
    
    <?php if ($t_project_id != ALL_PROJECTS) { ?><input type="button" class="button" value="<?php echo lang_get('revert_to_all_project') ?>" onclick="document.forms.plugins_releases.action.value='delete';document.forms.plugins_releases.submit();" /><?php } ?>

</form>

</div>
</div>
</div>

<?php
layout_page_end();
