<?php
/**
 * Run by WordPress when the plugin is deleted (Plugins → Delete, or
 * wp plugin uninstall), not when it is only deactivated.
 *
 * The token is a credential: left in the database, a plugin installed again
 * later (by a new agency, from a backup, by composer install) accepted the
 * old one without anybody pasting it, and whoever held the Spottlab account
 * it came from had the site's diagnostics again. So everything the plugin
 * kept goes with it, on every site of a network: the token, when it last
 * sent a test email, the sizes it measured, what it last heard of the update
 * lists, and the release it last saw. Nothing else is touched.
 *
 * The names are WP_Health_Check_Plugin's constants (OPTION_TOKEN,
 * MAIL_CHECK_LAST, SIZES_CACHE, SIZES_LAST, UPDATE_CHECKS, UPDATE_CACHE),
 * written out: the plugin's own file is not loaded to remove it.
 */
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Only WordPress, removing the plugin.
}

if (is_multisite()) {
    // Straight from each site's own options table, not through
    // switch_to_blog(): switching loads every site's autoloaded options to
    // read its roles, and without a persistent object cache it keeps them
    // all, so on a large network memory or time ran out partway, WordPress
    // never deleted the files, and every retry started again from the first
    // site. Exact names only, the transients by the names WordPress stores
    // them under; sites a few hundred at a time.
    global $wpdb;

    $spottlab_connector_options = array('health_check_api_token', 'spottlab_connector_mail_check');
    $spottlab_connector_transients = array('spottlab_connector_sizes', 'spottlab_connector_sizes_last');
    $spottlab_connector_names = $spottlab_connector_options;
    foreach ($spottlab_connector_transients as $spottlab_connector_transient) {
        $spottlab_connector_names[] = '_transient_' . $spottlab_connector_transient;
        $spottlab_connector_names[] = '_transient_timeout_' . $spottlab_connector_transient;
    }
    $spottlab_connector_in = implode(', ', array_fill(0, count($spottlab_connector_names), '%s'));

    // A persistent object cache keeps each site's options (and, with it,
    // transients) across requests, so a row deleted underneath it would
    // still be read, the token too, by the plugin installed again. Only its
    // keys are dropped, for one site at a time: no options are loaded.
    $spottlab_connector_cached = wp_using_ext_object_cache();
    $spottlab_connector_batch = 500;
    $spottlab_connector_offset = 0;

    do {
        $spottlab_connector_sites = get_sites(array(
            'fields' => 'ids',
            'number' => $spottlab_connector_batch,
            'offset' => $spottlab_connector_offset,
            'orderby' => 'id',
            'order' => 'ASC',
            'update_site_meta_cache' => false,
        ));

        foreach ($spottlab_connector_sites as $spottlab_connector_site) {
            $spottlab_connector_table = $wpdb->get_blog_prefix($spottlab_connector_site) . 'options';
            $wpdb->query($wpdb->prepare(
                "DELETE FROM `{$spottlab_connector_table}` WHERE option_name IN ({$spottlab_connector_in})",
                $spottlab_connector_names
            ));

            if ($spottlab_connector_cached) {
                // Every drop-in should have it; one that does not is left
                // the slow way, for its own sites only.
                $spottlab_connector_light = function_exists('wp_cache_switch_to_blog');
                $spottlab_connector_light ? wp_cache_switch_to_blog($spottlab_connector_site) : switch_to_blog($spottlab_connector_site);

                foreach ($spottlab_connector_names as $spottlab_connector_name) {
                    wp_cache_delete($spottlab_connector_name, 'options');
                }
                foreach ($spottlab_connector_transients as $spottlab_connector_transient) {
                    wp_cache_delete($spottlab_connector_transient, 'transient');
                }
                wp_cache_delete('alloptions', 'options');
                wp_cache_delete('notoptions', 'options');

                if (! $spottlab_connector_light) {
                    restore_current_blog();
                }
            }
        }

        $spottlab_connector_offset += count($spottlab_connector_sites);
    } while (count($spottlab_connector_sites) === $spottlab_connector_batch);

    if ($spottlab_connector_cached && function_exists('wp_cache_switch_to_blog')) {
        wp_cache_switch_to_blog(get_current_blog_id());
    }
} else {
    delete_option('health_check_api_token');
    delete_option('spottlab_connector_mail_check');
    delete_transient('spottlab_connector_sizes');
    delete_transient('spottlab_connector_sizes_last');
}

// Kept once for the whole network.
delete_site_option('spottlab_connector_update_checks');
delete_site_transient('spottlab_connector_release');
