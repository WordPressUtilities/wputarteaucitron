<?php
defined('ABSPATH') || die;
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

/* Delete options */
$options = array(
    'wputarteaucitron_options'
);
foreach ($options as $opt) {
    delete_option($opt);
    delete_site_option($opt);
}

global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wputarteaucitron_stat_%'");
