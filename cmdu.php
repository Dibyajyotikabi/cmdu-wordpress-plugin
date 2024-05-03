<?php
/*
Plugin Name: Conditional Modified Date Update (CMDU)
Description: Allows enabling or disabling modified date updates on a per-post basis, controlled by a global setting.
Version: 1.5
Author: Dibyajyoti Kabi
Author URI: https://dibyajyotikabi.com/
*/

// Add the settings page
add_action('admin_menu', 'cmdu_add_settings_page');
function cmdu_add_settings_page() {
    add_options_page(
        'CMDU Settings',
        'CMDU',
        'manage_options',
        'cmdu-settings',
        'cmdu_render_settings_page'
    );
}

function cmdu_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>CMDU Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cmdu_settings');
            do_settings_sections('cmdu_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'cmdu_register_settings');
function cmdu_register_settings() {
    register_setting('cmdu_settings', 'cmdu_enabled');
    add_settings_section(
        'cmdu_main_settings',
        'Settings',
        null,
        'cmdu_settings'
    );
    add_settings_field(
        'cmdu_enable_field',
        'Enable Per-Post Control',
        'cmdu_enable_checkbox',
        'cmdu_settings',
        'cmdu_main_settings'
    );
}

function cmdu_enable_checkbox() {
    $option = get_option('cmdu_enabled');
    echo '<input type="checkbox" id="cmdu_enabled" name="cmdu_enabled" value="1" ' . checked(1, $option, false) . '/>';
}

// Add meta box if global setting is enabled
if (get_option('cmdu_enabled') == '1') {
    add_action('add_meta_boxes', 'cmdu_add_meta_box');
}

function cmdu_add_meta_box() {
    add_meta_box(
        'cmdu_meta_box',
        'Allow Modified Date Update',
        'cmdu_meta_box_callback',
        'post',
        'side',
        'high'
    );
}

function cmdu_meta_box_callback($post) {
    wp_nonce_field('cmdu_nonce_action', 'cmdu_nonce');
    $value = get_post_meta($post->ID, '_cmdu_allow', true);
    echo '<input type="checkbox" id="_cmdu_allow" name="_cmdu_allow" value="1" ' . checked(1, $value, false) . '/>';
    echo '<label for="_cmdu_allow">Allow modified date to update</label>';
}

// Save the meta box content
add_action('save_post', 'cmdu_save_meta_box');
function cmdu_save_meta_box($post_id) {
    if (!isset($_POST['cmdu_nonce']) || !wp_verify_nonce($_POST['cmdu_nonce'], 'cmdu_nonce_action')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $new_value = isset($_POST['_cmdu_allow']) ? '1' : '0';
    update_post_meta($post_id, '_cmdu_allow', $new_value);
}

// Filter to prevent modified date update based on per-post setting
add_filter('wp_insert_post_data', 'cmdu_prevent_modified_date_update', 99, 2);
function cmdu_prevent_modified_date_update($data, $postarr) {
    if (get_post_meta($postarr['ID'], '_cmdu_allow', true) != '1') {
        $current_post = get_post($postarr['ID']);
        if ($current_post) {
            $data['post_modified'] = $current_post->post_modified;
            $data['post_modified_gmt'] = $current_post->post_modified_gmt;
        }
    }
    return $data;
}
