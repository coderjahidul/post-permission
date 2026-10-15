<?php
/**
 * Post Permission
 *
 * @package       POSTPERMIS
 * @author        Jahidul islam Sabuz
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   Post Permission
 * Plugin URI:    https://github.com/coderjahidul/Post-Permission.git
 * Description:   This is some demo short description...
 * Version:       1.0.0
 * Author:        Jahidul islam Sabuz
 * Author URI:    https://grocoder.com
 * Text Domain:   post-permission
 * Domain Path:   /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Add custom column to post list
function nq_add_noindex_column($columns) {
    $columns['noindex'] = 'Index Allow';
    return $columns;
}
add_filter('manage_posts_columns', 'nq_add_noindex_column');

// Show custom column value. No meta means indexing is allowed.
function nq_show_noindex_column($column, $post_id) {
    if ($column !== 'noindex') {
        return;
    }
    $allowed = nq_is_index_allowed($post_id);
    echo '<span class="nq-index-allow" data-index-allow="' . esc_attr($allowed ? '1' : '0') . '">' . esc_html($allowed ? 'Yes' : 'No') . '</span>';
}
add_action('manage_posts_custom_column', 'nq_show_noindex_column', 10, 2);

// Add Quick Edit field. The row is cloned per post, so the checkbox state comes from JS.
function nq_add_noindex_quick_edit($column_name, $post_type) {
    if ($column_name !== 'noindex') {
        return;
    }
    wp_nonce_field('nq_index_allow_quick_edit', 'nq_index_allow_nonce');
    ?>
    <fieldset class="inline-edit-col-right inline-edit-noindex">
        <div class="inline-edit-col">
            <label class="alignleft">
                <input type="hidden" name="nq_index_allow_present" value="1">
                <span class="title">Index Allow</span>
                <span class="input-text-wrap">
                    <input type="checkbox" name="index_allow" value="1">
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action('quick_edit_custom_box', 'nq_add_noindex_quick_edit', 10, 2);

// Enqueue JavaScript for Quick Edit
function nq_enqueue_quick_edit_noindex_script($hook) {
    if ($hook !== 'edit.php') {
        return;
    }
    $script_path = plugin_dir_path(__FILE__) . 'js/quick-edit-noindex.js';
    wp_enqueue_script(
        'quick-edit-noindex',
        plugin_dir_url(__FILE__) . 'js/quick-edit-noindex.js',
        array('jquery', 'inline-edit-post'),
        file_exists($script_path) ? filemtime($script_path) : '1.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'nq_enqueue_quick_edit_noindex_script');

// Save only when Quick Edit submitted this field. Other saves must leave the meta alone.
function nq_save_noindex_quick_edit($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (empty($_POST['nq_index_allow_present'])) {
        return;
    }
    if (
        ! isset($_POST['nq_index_allow_nonce']) ||
        ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nq_index_allow_nonce'])), 'nq_index_allow_quick_edit')
    ) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $allow = isset($_POST['index_allow']) && '1' === sanitize_text_field(wp_unslash($_POST['index_allow']));
    if ($allow) {
        delete_post_meta($post_id, '_noindex');
    } else {
        update_post_meta($post_id, '_noindex', '1');
    }
}
add_action('save_post', 'nq_save_noindex_quick_edit');

function nq_is_index_allowed($post_id) {
    return '1' !== (string) get_post_meta($post_id, '_noindex', true);
}

// One robots tag via core, so an explicit noindex is what crawlers see.
function nq_wp_robots($robots) {
    if (! is_singular()) {
        return $robots;
    }
    $post_id = get_queried_object_id();
    if ($post_id && ! nq_is_index_allowed($post_id)) {
        $robots['noindex'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'nq_wp_robots');
