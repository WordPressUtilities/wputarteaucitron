<?php
defined('ABSPATH') || die;
/*
Plugin Name: WPU Tarte Au Citron
Plugin URI: https://github.com/WordPressUtilities/wputarteaucitron
Update URI: https://github.com/WordPressUtilities/wputarteaucitron
Description: Simple implementation for Tarteaucitron.js
Version: 1.5.0
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wputarteaucitron
Domain Path: /lang
Requires at least: 6.2
Requires PHP: 8.0
Network: Optional
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUTarteAuCitron {
    public $settings_update;
    public $plugin_description;
    public $settings_details;
    public $settings;
    private $plugin_version = '1.5.0';
    private $tarteaucitron_version = '1.34.0';
    private $settings_obj;
    private $stats_obj = false;
    private $prefix_stat = 'wputarteaucitron_stat_';
    private $stats_table_name = 'wputarteaucitron_stats';
    private $plugin_settings = array(
        'id' => 'wputarteaucitron',
        'name' => 'WPU Tarte Au Citron'
    );

    private $services = array(
        'googleads' => array(
            'section' => 'trackers_google',
            'label' => 'Google Ads',
            'setting_key' => 'googleads_id',
            'user_key' => 'googleadsId',
            'example' => 'AW-123456789'
        ),
        'googletagmanager' => array(
            'section' => 'trackers_google',
            'label' => 'Google Tag Manager',
            'setting_key' => 'gtm_id',
            'user_key' => 'googletagmanagerId',
            'example' => 'GTM-1234'
        ),
        'gtag' => array(
            'section' => 'trackers_google',
            'label' => 'GA 4',
            'setting_key' => 'ga4_id',
            'user_key' => 'gtagUa',
            'example' => 'G-12345678'
        ),
        'matomocloud' => array(
            'section' => 'trackers_matomo',
            'label' => 'Matomo ID',
            'setting_key' => 'matomocloud_id',
            'user_key' => 'matomoId',
            'extra_settings' => array(
                'matomo_host' => array(
                    'label' => 'Matomo Host',
                    'section' => 'trackers_matomo',
                    'setting_key' => 'matomocloud_host',
                    'user_key' => 'matomoHost'
                ),
                'matomo_jspath' => array(
                    'label' => 'Matomo JS Path',
                    'section' => 'trackers_matomo',
                    'setting_key' => 'matomocloud_jspath',
                    'user_key' => 'matomoCustomJSPath'
                )
            )
        ),
        'facebookpixel' => array(
            'label' => 'Facebook Pixel',
            'setting_key' => 'fbpix_id',
            'user_key' => 'facebookpixelId',
            'example' => '123487593'
        ),
        'hubspot' => array(
            'label' => 'Hubspot API',
            'setting_key' => 'hubspot_api_key',
            'user_key' => 'hubspotId'
        ),
        'hotjar' => array(
            'label' => 'Hotjar',
            'setting_key' => 'hotjar_id',
            'user_key' => 'hotjarId',
            'example' => '1234567',
            'extra_settings' => array(
                'hotjar_sv' => array(
                    'label' => 'Hotjar SV',
                    'setting_key' => 'hotjar_sv',
                    'user_key' => 'HotjarSv',
                    'example' => '5'
                )
            )
        ),
        'linkedininsighttag' => array(
            'label' => 'LinkedIn Insight',
            'setting_key' => 'linkedin_partner_id',
            'user_key' => 'linkedininsighttag',
            'example' => '123456'
        ),
        'plausible' => array(
            'label' => 'Plausible',
            'setting_key' => 'plausible_domain',
            'user_key' => 'plausibleDomain'
        ),
        'twitteruwt' => array(
            'label' => 'Twitter UWT',
            'setting_key' => 'twitteruwt_id',
            'user_key' => 'twitteruwtId',
            'example' => 'o2f123'
        )
    );

    public function __construct() {
        add_action('init', array(&$this, 'load_translation'));
        add_action('init', array(&$this, 'init'));

        # Front Assets
        add_action('wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts'));

        # AJAX
        add_action('wp_ajax_wputarteaucitron_status', array(&$this, 'callback_ajax'));
        add_action('wp_ajax_nopriv_wputarteaucitron_status', array(&$this, 'callback_ajax'));

        # Admin
        add_action('wpubasesettings_after_content_settings_page_wputarteaucitron', array(&$this, 'stats_display'));
        add_action('wpubasesettings_after_content_settings_page_wputarteaucitron', array(&$this, 'info_display'));
        add_action('load-settings_page_wputarteaucitron', array(&$this, 'stats_reset_action'));
        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));

        # Reset legacy counters when logs are enabled
        add_action('update_option_' . $this->plugin_settings['id'] . '_options', array(&$this, 'stats_logs_activation'), 10, 2);
    }

    public function load_translation() {
        $lang_dir = dirname(plugin_basename(__FILE__)) . '/lang/';
        if (strpos(__DIR__, 'mu-plugins') !== false) {
            load_muplugin_textdomain('wputarteaucitron', $lang_dir);
        } else {
            load_plugin_textdomain('wputarteaucitron', false, $lang_dir);
        }
        $this->plugin_description = __('Simple implementation for Tarteaucitron.js', 'wputarteaucitron');
    }

    public function init() {

        # SETTINGS
        $this->settings_details = array(
            # Admin page
            'create_page' => true,
            'plugin_basename' => plugin_basename(__FILE__),
            'user_cap' => apply_filters('wputarteaucitron__admin__user_cap', 'manage_options'),
            # Default
            'plugin_name' => $this->plugin_settings['name'],
            'plugin_id' => $this->plugin_settings['id'],
            'option_id' => $this->plugin_settings['id'] . '_options',
            'sections' => array(
                'settings' => array(
                    'name' => __('Settings', 'wputarteaucitron')
                ),
                'settings_icon' => array(
                    'name' => __('Icon', 'wputarteaucitron'),
                    'is_open' => false
                ),
                'settings_banner' => array(
                    'name' => __('Banner', 'wputarteaucitron'),
                    'is_open' => false
                ),
                'trackers_google' => array(
                    'name' => __('Trackers - Google', 'wputarteaucitron'),
                    'is_open' => false
                ),
                'trackers_matomo' => array(
                    'name' => __('Trackers - Matomo', 'wputarteaucitron'),
                    'is_open' => false
                ),
                'trackers' => array(
                    'name' => __('Trackers', 'wputarteaucitron'),
                    'is_open' => false
                )
            )
        );

        $yes_no = array(__('No', 'wputarteaucitron'), __('Yes', 'wputarteaucitron'));

        $privacy_page_id = get_option('wp_page_for_privacy_policy');
        $this->settings = array(
            'enable_banner' => array(
                'section' => 'settings',
                'label' => __('Activate banner', 'wputarteaucitron'),
                'required' => true,
                'help' => __('Banner will be visible and scripts will be loaded', 'wputarteaucitron'),
                'default_value' => '1',
                'type' => 'select',
                'datas' => $yes_no
            ),
            'disable_banner_loggedin' => array(
                'section' => 'settings',
                'label' => __('Disable banner for logged in users', 'wputarteaucitron'),
                'required' => true,
                'help' => __('Banner will be visible only for non-logged in users', 'wputarteaucitron'),
                'default_value' => '0',
                'type' => 'select',
                'datas' => $yes_no
            ),
            'enable_logs' => array(
                'section' => 'settings',
                'label' => __('Log consent history', 'wputarteaucitron'),
                'help' => __('Stores a daily count of choices and displays an acceptance rate chart instead of the current totals. Enabling this resets the current counters.', 'wputarteaucitron'),
                'default_value' => '0',
                'type' => 'select',
                'datas' => $yes_no
            ),
            'privacy_page_id' => array(
                'section' => 'settings',
                'label' => __('Privacy URL', 'wputarteaucitron'),
                'lang' => 1,
                'type' => 'post',
                'default_value' => $privacy_page_id ? (int) $privacy_page_id : '',
                'post_type' => 'page'
            ),
            'custom_icon_id' => array(
                'section' => 'settings_icon',
                'label' => __('Custom Icon', 'wputarteaucitron'),
                'type' => 'media'
            ),
            'show_icon' => array(
                'section' => 'settings_icon',
                'label' => __('Show icon', 'wputarteaucitron'),
                'required' => true,
                'help' => sprintf(__('Or create a link to reopen the popup : %s', 'wputarteaucitron'), htmlentities('<a data-wputarteaucitron-open-panel="1" href="#">Cookies</a>')),
                'default_value' => '1',
                'type' => 'select',
                'datas' => $yes_no
            ),
            'icon_position' => array(
                'section' => 'settings_icon',
                'label' => __('Icon position', 'wputarteaucitron'),
                'type' => 'select',
                'datas' => array(
                    'BottomRight' => __('Bottom Right', 'wputarteaucitron'),
                    'BottomLeft' => __('Bottom Left', 'wputarteaucitron'),
                    'TopRight' => __('Top Right', 'wputarteaucitron'),
                    'TopLeft' => __('Top Left', 'wputarteaucitron')
                )
            ),
            'banner_orientation' => array(
                'section' => 'settings_banner',
                'label' => __('Banner position', 'wputarteaucitron'),
                'type' => 'select',
                'datas' => array(
                    'bottom' => __('Bottom', 'wputarteaucitron'),
                    'middle' => __('Middle', 'wputarteaucitron'),
                    'top' => __('Top', 'wputarteaucitron'),
                    'popup' => __('Popup', 'wputarteaucitron')
                )
            ),
            'banner_message' => array(
                'section' => 'settings_banner',
                'label' => __('Banner message', 'wputarteaucitron'),
                'lang' => 1,
                'type' => 'textarea'
            ),
            'display_accept_all_cta' => array(
                'section' => 'settings_banner',
                'label' => __('Display the “Accept All” CTA', 'wputarteaucitron'),
                'type' => 'select',
                'datas' => $yes_no
            ),
            'display_deny_all_cta' => array(
                'section' => 'settings_banner',
                'label' => __('Display the “Deny All” CTA', 'wputarteaucitron'),
                'type' => 'select',
                'datas' => $yes_no
            ),
            'blocking_overlay' => array(
                'section' => 'settings_banner',
                'label' => __('Blocking overlay', 'wputarteaucitron'),
                'help' => __('Blocks the page until a choice is made. The “Deny All” CTA is forced on for legal compliance.', 'wputarteaucitron'),
                'default_value' => '0',
                'type' => 'select',
                'datas' => $yes_no
            ),
            'disable_google_consent_mode' => array(
                'section' => 'trackers_google',
                'label' => __('Disable Google Consent Mode', 'wputarteaucitron'),
                'help' => __('If you use Google Consent Mode elsewhere, you can disable it here to avoid conflicts', 'wputarteaucitron'),
                'default_value' => '0',
                'type' => 'select',
                'datas' => $yes_no
            )
        );

        $this->services = apply_filters('wputarteaucitron__services', $this->services);

        foreach ($this->services as $service) {
            $service_setting = $this->get_field_setting($service);
            $this->settings[$service['setting_key']] = $service_setting;

            if (isset($service['extra_settings'])) {
                foreach ($service['extra_settings'] as $extra_settings) {
                    $this->settings[$extra_settings['setting_key']] = $this->get_field_setting($extra_settings);
                }
            }
        }

        $this->settings = apply_filters('wputarteaucitron__settings', $this->settings);
        require_once __DIR__ . '/inc/WPUBaseSettings/WPUBaseSettings.php';
        $this->settings_obj = new \wputarteaucitron\WPUBaseSettings($this->settings_details, $this->settings);

        $this->stats_load_table();

        require_once __DIR__ . '/inc/WPUBaseUpdate/WPUBaseUpdate.php';
        $this->settings_update = new \wputarteaucitron\WPUBaseUpdate(
            'WordPressUtilities',
            'wputarteaucitron',
            $this->plugin_version);

        /* Admin widget */
        add_action('wp_dashboard_setup', array(&$this, 'wputarteaucitron_add_dashboard_widget'));
    }

    public function get_field_setting($args = array()) {
        $base_setting = array(
            'lang' => true,
            'wputarteaucitron_value' => true,
            'section' => 'trackers'
        );
        $item = array_merge($base_setting, array(
            'label' => $args['label']
        ));
        if (isset($args['section'])) {
            $item['section'] = $args['section'];
        }
        if (isset($args['help'])) {
            $item['help'] = $args['help'];
        }
        if (isset($args['example'])) {
            $item['help'] = sprintf(__('Example: %s', 'wputarteaucitron'), $args['example']);
        }
        return $item;
    }

    public function wp_enqueue_scripts() {
        $settings = $this->settings_obj->get_settings();
        $current_lang = $this->settings_obj->get_current_language();

        /* Check default settings */
        if (empty($settings) || !is_array($settings) || (isset($settings['enable_banner']) && $settings['enable_banner'] == '0')) {
            return;
        }

        if (isset($settings['disable_banner_loggedin']) && $settings['disable_banner_loggedin'] == '1' && is_user_logged_in()) {
            return;
        }

        /* Front Style */
        wp_register_style('wputarteaucitron_front_style', plugins_url('assets/front.css', __FILE__), array(), $this->plugin_version);
        wp_enqueue_style('wputarteaucitron_front_style');

        /* Front Script with localization / variables */
        wp_register_script('wputarteaucitron_main', plugins_url('assets/tarteaucitron/tarteaucitron.min.js', __FILE__), array(), $this->tarteaucitron_version, true);
        wp_register_script('wputarteaucitron_front_script', plugins_url('assets/front.js', __FILE__), array('wputarteaucitron_main'), $this->plugin_version, true);

        /* Privacy page */
        $privacy_page = false;
        $privacy_page_id = false;
        if (isset($settings['privacy_page_id']) && $settings['privacy_page_id']) {
            $privacy_page_id = $settings['privacy_page_id'];
        }
        $privacy_page_id_lang = $this->settings_obj->get_setting('privacy_page_id', !!$current_lang);
        if ($privacy_page_id_lang) {
            $privacy_page_id = $privacy_page_id_lang;
        }
        if ($privacy_page_id) {
            $privacy_page = get_page_link($privacy_page_id);
        }

        $script_settings = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wputarteaucitron_nonce'),
            'accept_all_cta' => !isset($settings['display_accept_all_cta']) || $settings['display_accept_all_cta'],
            'deny_all_cta' => isset($settings['display_deny_all_cta']) && $settings['display_deny_all_cta'],
            /* Never block the privacy page: users must be able to read it before choosing */
            'blocking_overlay' => !empty($settings['blocking_overlay']) && !($privacy_page_id && is_page($privacy_page_id)),
            'disable_google_consent_mode' => isset($settings['disable_google_consent_mode']) && $settings['disable_google_consent_mode'],
            'show_icon' => !isset($settings['show_icon']) || $settings['show_icon'],
            'cookie_name' => 'tarteaucitron',
            'hashtag' => '#tarteaucitron',
            'banner_message' => $this->settings_obj->get_setting('banner_message', !!$current_lang),
            'banner_orientation' => isset($settings['banner_orientation']) ? $settings['banner_orientation'] : 'bottom',
            'icon_position' => isset($settings['icon_position']) ? $settings['icon_position'] : 'BottomRight',
            'privacy_page' => $privacy_page,
            'custom_icon' => isset($settings['custom_icon_id']) && is_numeric($settings['custom_icon_id']) ? wp_get_attachment_image_url($settings['custom_icon_id'], 'thumbnail') : false
        );

        foreach ($this->settings as $key => $details) {
            if (!isset($details['wputarteaucitron_value']) || !$details['wputarteaucitron_value']) {
                continue;
            }
            if (!isset($settings[$key]) || !$settings[$key]) {
                continue;
            }
            $script_settings[$key] = $settings[$key];
        }

        $forced_scripts = apply_filters('wputarteaucitron__forced_scripts', array());

        /* Build settings for services */
        $script_settings['services'] = array();
        foreach ($this->services as $k => $service) {
            if (!isset($script_settings[$service['setting_key']]) && !in_array($k, $forced_scripts)) {
                continue;
            }
            $script_settings['services'][$k] = array(
                'setting_key' => $service['setting_key'],
                'user_key' => $service['user_key']
            );
            if (isset($service['extra_settings'])) {
                foreach ($service['extra_settings'] as $extra_key => $extra_settings) {
                    if (!isset($script_settings[$extra_settings['setting_key']])) {
                        continue;
                    }
                    if (!isset($script_settings['services'][$k]['extra'])) {
                        $script_settings['services'][$k]['extra'] = array();
                    }
                    $script_settings['services'][$k]['extra'][$extra_key] = array(
                        'setting_key' => $extra_settings['setting_key'],
                        'user_key' => $extra_settings['user_key']
                    );
                }
            }
        }
        $script_settings = apply_filters('wputarteaucitron__script_settings', $script_settings);
        wp_localize_script('wputarteaucitron_front_script', 'wputarteaucitron_settings', $script_settings);
        wp_enqueue_script('wputarteaucitron_front_script');
    }

    /* ----------------------------------------------------------
      AJAX
    ---------------------------------------------------------- */

    public function callback_ajax() {
        check_ajax_referer('wputarteaucitron_nonce');
        if (!isset($_POST['services']) || !is_array($_POST['services'])) {
            return;
        }

        /* Only keep known services : keys come from the browser */
        $statuses = array();
        foreach ($_POST['services'] as $service_key => $status) {
            if (!isset($this->services[$service_key])) {
                continue;
            }
            $statuses[$service_key] = $status ? 1 : 0;
        }

        if (!$statuses) {
            return;
        }

        if ($this->stats_logs_enabled()) {
            $this->stats_log_decision($statuses);
        } else {
            $this->stats_increment_counters($statuses);
        }

        wp_send_json_success();
    }

    /* ----------------------------------------------------------
      Stats : storage
    ---------------------------------------------------------- */

    public function stats_logs_enabled() {
        return $this->settings_obj && $this->settings_obj->get_setting('enable_logs') == '1';
    }

    /**
     * Create / update the stats table when logs are enabled
     */
    public function stats_load_table() {
        if (!$this->stats_logs_enabled()) {
            return;
        }
        require_once __DIR__ . '/inc/WPUBaseAdminDatas/WPUBaseAdminDatas.php';
        $this->stats_obj = new \wputarteaucitron\WPUBaseAdminDatas();
        $this->stats_obj->init(array(
            'handle_database' => false,
            'plugin_id' => $this->plugin_settings['id'],
            'table_name' => $this->stats_table_name,
            'table_fields' => array(
                'day' => array(
                    'public_name' => __('Day', 'wputarteaucitron'),
                    'type' => 'date'
                ),
                'service' => array(
                    'public_name' => __('Service', 'wputarteaucitron'),
                    'type' => 'varchar'
                ),
                'nb_ok' => array(
                    'public_name' => __('Accepted', 'wputarteaucitron'),
                    'type' => 'sql',
                    'sql' => 'INT UNSIGNED NOT NULL DEFAULT 0'
                ),
                'nb_ko' => array(
                    'public_name' => __('Refused', 'wputarteaucitron'),
                    'type' => 'sql',
                    'sql' => 'INT UNSIGNED NOT NULL DEFAULT 0'
                ),
                'nb_partial' => array(
                    'public_name' => __('Partial', 'wputarteaucitron'),
                    'type' => 'sql',
                    'sql' => 'INT UNSIGNED NOT NULL DEFAULT 0'
                )
            )
        ));

        /* WPUBaseAdminDatas does not handle indexes : the unique key is required by the upsert */
        $opt_index = $this->prefix_stat . 'table_index';
        if (get_option($opt_index) != '1') {
            global $wpdb;
            $wpdb->query("ALTER TABLE " . $this->stats_obj->tablename . " ADD UNIQUE KEY day_service (`day`, `service`)");
            update_option($opt_index, '1', false);
        }
    }

    /**
     * Legacy counters, used when logs are disabled
     * @param array $statuses  [service_key => 0|1]
     */
    public function stats_increment_counters($statuses) {
        /* Bypassing option API to avoid cache problems */
        global $wpdb;
        foreach ($statuses as $service_key => $status) {
            $option_id = $this->prefix_stat . 'service_' . $service_key . '_' . ($status ? 'allowed' : 'disallowed');
            $option_value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", $option_id));
            if (!is_numeric($option_value)) {
                $wpdb->insert($wpdb->options, array(
                    'option_name' => $option_id,
                    'option_value' => 1,
                    'autoload' => 'no'
                ));
            } else {
                $wpdb->update($wpdb->options, array(
                    'option_value' => intval($option_value) + 1,
                    'autoload' => 'no'
                ), array(
                    'option_name' => $option_id
                ));
            }
        }

        /* Update since */
        $this->stats_get_since();
    }

    /**
     * Store a full modal decision : one row per service, plus a global row
     * @param array $statuses  [service_key => 0|1]
     */
    public function stats_log_decision($statuses) {
        if (!$this->stats_obj) {
            return;
        }

        /* Day is computed in the site timezone, not the MySQL server one */
        $day = wp_date('Y-m-d');

        foreach ($statuses as $service_key => $status) {
            $this->stats_increment_row($day, $service_key, $status ? 'nb_ok' : 'nb_ko');
        }

        $values = array_values($statuses);
        $global_column = 'nb_partial';
        if (!in_array(0, $values, true)) {
            $global_column = 'nb_ok';
        } elseif (!in_array(1, $values, true)) {
            $global_column = 'nb_ko';
        }
        $this->stats_increment_row($day, '_global', $global_column);
    }

    /**
     * Atomic increment of one counter, relies on the UNIQUE (day, service) key
     */
    private function stats_increment_row($day, $service, $column) {
        /* $column is never user input : it is one of the three hardcoded names above */
        global $wpdb;
        $table = $this->stats_obj->tablename;
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (`day`, `service`, `{$column}`) VALUES (%s, %s, 1)
             ON DUPLICATE KEY UPDATE `{$column}` = `{$column}` + 1",
            $day, $service
        ));
    }

    /* ----------------------------------------------------------
      Stats
    ---------------------------------------------------------- */

    /**
     * Get stats start date
     * @param  boolean $reset   Reset start date
     * @return int              Start date
     */
    public function stats_get_since($reset = false) {
        $opt_time = $this->prefix_stat . 'since';
        $opt_time_val = get_option($opt_time);
        if (!$opt_time_val || $reset) {
            $opt_time_val = time();
            update_option($opt_time, $opt_time_val);
        }
        return $opt_time_val;
    }

    /**
     * Reset all stats
     */
    public function stats_reset() {
        delete_option($this->prefix_stat . 'since');
        foreach ($this->services as $key => $infos) {
            $base_id = $this->prefix_stat . 'service_' . $key;
            delete_option($base_id . '_allowed');
            delete_option($base_id . '_disallowed');
        }
    }

    public function stats_reset_action() {
        if (isset($_POST[$this->prefix_stat . 'nonce']) && wp_verify_nonce($_POST[$this->prefix_stat . 'nonce'], $this->prefix_stat)) {
            $this->stats_reset();
        }
    }

    /**
     * Freeze and reset legacy counters when logs are switched on
     */
    public function stats_logs_activation($old_value, $new_value) {
        $was_enabled = is_array($old_value) && isset($old_value['enable_logs']) && $old_value['enable_logs'] == '1';
        $is_enabled = is_array($new_value) && isset($new_value['enable_logs']) && $new_value['enable_logs'] == '1';
        if ($is_enabled && !$was_enabled) {
            $this->stats_reset();
        }
    }

    /* ----------------------------------------------------------
      Stats : totals
    ---------------------------------------------------------- */

    /**
     * Per service totals
     * @return array [service_key => ['allowed' => int, 'refused' => int]]
     */
    public function stats_get_totals() {
        if ($this->stats_logs_enabled()) {
            return $this->stats_get_totals_from_table();
        }
        return $this->stats_get_totals_from_counters();
    }

    public function stats_get_totals_from_counters() {
        $totals = array();
        foreach ($this->services as $key => $infos) {
            $base_id = $this->prefix_stat . 'service_' . $key;
            $allowed = get_option($base_id . '_allowed');
            $refused = get_option($base_id . '_disallowed');
            if (!is_numeric($allowed) && !is_numeric($refused)) {
                continue;
            }
            $totals[$key] = array(
                'allowed' => is_numeric($allowed) ? intval($allowed) : 0,
                'refused' => is_numeric($refused) ? intval($refused) : 0
            );
        }
        return $totals;
    }

    public function stats_get_totals_from_table($days = 30) {
        if (!$this->stats_obj) {
            return array();
        }
        global $wpdb;
        $table = $this->stats_obj->tablename;
        $since = wp_date('Y-m-d', time() - $days * DAY_IN_SECONDS);
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT `service`, SUM(`nb_ok`) AS nb_ok, SUM(`nb_ko`) AS nb_ko
             FROM {$table} WHERE `day` >= %s AND `service` != '_global' GROUP BY `service`",
            $since
        ));

        $totals = array();
        foreach ($results as $result) {
            if (!isset($this->services[$result->service])) {
                continue;
            }
            $totals[$result->service] = array(
                'allowed' => intval($result->nb_ok),
                'refused' => intval($result->nb_ko)
            );
        }
        return $totals;
    }

    /* ----------------------------------------------------------
      Stats : display
    ---------------------------------------------------------- */

    public function stats_display($mode = 'default') {
        if ($this->stats_logs_enabled()) {
            $this->stats_display_logs($mode);
            return;
        }
        $this->stats_display_counters($mode);
    }

    /**
     * Build the per service totals table
     */
    public function stats_display_table($totals) {
        $table_html = '';
        foreach ($totals as $key => $values) {
            if (!isset($this->services[$key])) {
                continue;
            }
            $allowed = $values['allowed'];
            $refused = $values['refused'];
            $total = $allowed + $refused;

            $stat_allowed = '0';
            $stat_refused = '0';
            if ($total) {
                $stat_allowed = number_format($allowed / $total * 100, 2);
                $stat_refused = number_format($refused / $total * 100, 2);
            }

            $table_html .= '<tr>';
            $table_html .= '<th scope="row">' . esc_html($this->services[$key]['label']) . '</th>';
            $table_html .= '<td>' . $total . '</td>';
            $table_html .= '<td>' . $allowed . ' <small>(' . $stat_allowed . '%)</small></td>';
            $table_html .= '<td>' . $refused . ' <small>(' . $stat_refused . '%)</small></td>';
            $table_html .= '</tr>';
        }

        if (!$table_html) {
            return '';
        }

        $html = '<table class="widefat fixed striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th></th>';
        $html .= '<th>' . __('Total', 'wputarteaucitron') . '</th>';
        $html .= '<th>' . __('Accepted', 'wputarteaucitron') . '</th>';
        $html .= '<th>' . __('Refused', 'wputarteaucitron') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>' . $table_html . '</tbody>';
        $html .= '</table>';
        return $html;
    }

    public function stats_display_counters($mode = 'default') {
        $table = $this->stats_display_table($this->stats_get_totals_from_counters());
        if (!$table) {
            return;
        }
        if ($mode != 'widget') {
            echo '<hr />';
            echo '<div style="max-width:600px">';
            echo '<h2>' . __('Stats', 'wputarteaucitron') . '</h2>';
        }
        echo $table;

        $since = $this->stats_get_since();
        $date_format = get_option('date_format') . ', ' . get_option('time_format');
        echo '<p>' . sprintf(esc_html__('Since %s', 'wputarteaucitron'), wp_date($date_format, $since)) . '.</p>';
        if ($mode != 'widget') {
            echo '<form action="" method="post">';
            submit_button(__('Reset stats', 'wputarteaucitron'));
            wp_nonce_field($this->prefix_stat, $this->prefix_stat . 'nonce');
            echo '</form>';
            echo '</div>';
        }
    }

    public function stats_display_logs($mode = 'default') {
        if ($mode == 'widget') {
            $table = $this->stats_display_table($this->stats_get_totals_from_table(30));
            if (!$table) {
                return;
            }
            echo $table;
            echo '<p>' . esc_html__('Last 30 days.', 'wputarteaucitron') . '</p>';
            return;
        }

        $period = $this->stats_get_current_period();
        $service = $this->stats_get_current_service();
        $chart = $this->stats_get_chart_datas($service, $period);

        echo '<hr />';
        echo '<div id="' . esc_attr($this->plugin_settings['id']) . '-stats" style="max-width:800px">';
        echo '<h2>' . __('Stats', 'wputarteaucitron') . '</h2>';

        echo $this->stats_get_filters_html($period, $service);

        if (!$chart['labels']) {
            echo '<p>' . esc_html__('No stats yet.', 'wputarteaucitron') . '</p>';
            echo '</div>';
            return;
        }

        echo '<div style="margin:1em 0"><canvas id="wputarteaucitron-chart" height="250"></canvas></div>';
        echo '<p>' . esc_html(sprintf(
            _n('%1$s decision, %2$s%% accepted over the period.', '%1$s decisions, %2$s%% accepted over the period.', $chart['total'], 'wputarteaucitron'),
            number_format_i18n($chart['total']),
            number_format($chart['rate'], 2)
        )) . '</p>';

        $json = wp_json_encode($chart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        echo '<script id="wputarteaucitron-chart-datas" type="application/json">' . $json . '</script>';
        /* Chart.js is loaded in the footer : wait for it before drawing */
        echo '<script>(function(){';
        echo 'function init(){if(typeof Chart==="undefined"){return setTimeout(init,50);}';
        echo 'var d=JSON.parse(document.getElementById("wputarteaucitron-chart-datas").textContent);';
        echo 'new Chart(document.getElementById("wputarteaucitron-chart"),{type:"line",';
        echo 'data:{labels:d.labels,datasets:[{label:d.label,data:d.rates,borderColor:"#2271b1",backgroundColor:"rgba(34,113,177,.1)",fill:true,tension:.2}]},';
        echo 'options:{responsive:true,maintainAspectRatio:false,scales:{y:{min:0,max:100,ticks:{callback:function(v){return v+"%";}}}},';
        echo 'plugins:{tooltip:{callbacks:{label:function(c){return c.parsed.y.toFixed(2)+"% ("+d.totals[c.dataIndex]+")";}}}}}});';
        echo '}init();}());</script>';

        echo '</div>';
    }

    /**
     * Available chart periods, in days
     * @return array [days => label]
     */
    private function stats_get_periods() {
        return array(
            30 => __('30 days', 'wputarteaucitron'),
            90 => __('90 days', 'wputarteaucitron'),
            365 => __('12 months', 'wputarteaucitron')
        );
    }

    private function stats_get_current_period() {
        $periods = $this->stats_get_periods();
        $period = isset($_GET['wputac_period']) ? intval($_GET['wputac_period']) : 0;
        if (!isset($periods[$period])) {
            $period = array_key_first($periods);
        }
        return $period;
    }

    private function stats_get_current_service() {
        $service = isset($_GET['wputac_service']) ? sanitize_text_field(wp_unslash($_GET['wputac_service'])) : '_global';
        if ($service != '_global' && !isset($this->services[$service])) {
            $service = '_global';
        }
        return $service;
    }

    private function stats_get_filters_html($period, $service) {
        /* Resolves the real parent page, which is filterable in WPUBaseSettings */
        $base_url = menu_page_url($this->plugin_settings['id'], false);
        if (!$base_url) {
            $base_url = admin_url('options-general.php?page=' . $this->plugin_settings['id']);
        }

        $periods = $this->stats_get_periods();

        $services = array('_global' => __('Global', 'wputarteaucitron'));
        foreach ($this->stats_get_logged_services() as $key) {
            $services[$key] = $this->services[$key]['label'];
        }

        /* Anchor : the stats block sits at the bottom of a long settings page */
        $anchor = '#' . $this->plugin_settings['id'] . '-stats';

        $html = '<p>';
        foreach ($services as $key => $label) {
            $url = add_query_arg(array('wputac_period' => $period, 'wputac_service' => $key), $base_url) . $anchor;
            $html .= $key == $service
            ? '<strong style="margin-right:1em">' . esc_html($label) . '</strong>'
            : '<a style="margin-right:1em" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        $html .= '</p><p>';
        foreach ($periods as $key => $label) {
            $url = add_query_arg(array('wputac_period' => $key, 'wputac_service' => $service), $base_url) . $anchor;
            $html .= $key == $period
            ? '<strong style="margin-right:1em">' . esc_html($label) . '</strong>'
            : '<a style="margin-right:1em" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        $html .= '</p>';
        return $html;
    }

    /**
     * Services having at least one logged row
     */
    private function stats_get_logged_services() {
        if (!$this->stats_obj) {
            return array();
        }
        global $wpdb;
        $table = $this->stats_obj->tablename;
        $keys = $wpdb->get_col("SELECT DISTINCT `service` FROM {$table} WHERE `service` != '_global'");
        return array_values(array_filter($keys, function ($key) {
            return isset($this->services[$key]);
        }));
    }

    /**
     * Acceptance rate over time for one service
     */
    public function stats_get_chart_datas($service, $period) {
        $empty = array('labels' => array(), 'rates' => array(), 'totals' => array(), 'label' => '', 'total' => 0, 'rate' => 0);
        if (!$this->stats_obj) {
            return $empty;
        }

        /* Daily points up to 90 days, monthly beyond */
        $is_monthly = $period > 90;
        /* Percent signs are doubled : the query goes through $wpdb->prepare() */
        $bucket = $is_monthly ? "DATE_FORMAT(`day`, '%%Y-%%m')" : "`day`";

        global $wpdb;
        $table = $this->stats_obj->tablename;
        $since = wp_date('Y-m-d', time() - $period * DAY_IN_SECONDS);
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT {$bucket} AS bucket, SUM(`nb_ok`) AS nb_ok, SUM(`nb_ko`) AS nb_ko, SUM(`nb_partial`) AS nb_partial
             FROM {$table} WHERE `service` = %s AND `day` >= %s GROUP BY bucket ORDER BY bucket ASC",
            $service, $since
        ));

        if (!$results) {
            return $empty;
        }

        $rows = array();
        foreach ($results as $result) {
            $rows[$result->bucket] = intval($result->nb_ok) + intval($result->nb_ko) + intval($result->nb_partial)
            ? array(intval($result->nb_ok), intval($result->nb_ko) + intval($result->nb_partial))
            : false;
        }

        $datas = $empty;
        $datas['label'] = $service == '_global'
        ? __('Global acceptance rate', 'wputarteaucitron')
        : sprintf(__('Acceptance rate : %s', 'wputarteaucitron'), $this->services[$service]['label']);

        $sum_ok = 0;
        $sum_all = 0;
        $date_format = $is_monthly ? 'M Y' : get_option('date_format');

        /* Every bucket of the period is plotted : days without any decision stay visible as gaps */
        foreach ($this->stats_get_period_buckets($period, $is_monthly) as $key => $timestamp) {
            $datas['labels'][] = wp_date($date_format, $timestamp);
            if (!isset($rows[$key]) || !$rows[$key]) {
                $datas['rates'][] = null;
                $datas['totals'][] = 0;
                continue;
            }
            list($ok, $ko) = $rows[$key];
            $total = $ok + $ko;
            $datas['rates'][] = round($ok / $total * 100, 2);
            $datas['totals'][] = $total;
            $sum_ok += $ok;
            $sum_all += $total;
        }

        $datas['total'] = $sum_all;
        $datas['rate'] = $sum_all ? $sum_ok / $sum_all * 100 : 0;
        return $datas;
    }

    /**
     * Every bucket key of the period, mapped to a timestamp usable for display
     * @return array [bucket_key => timestamp]
     */
    private function stats_get_period_buckets($period, $is_monthly) {
        $buckets = array();

        if (!$is_monthly) {
            for ($i = $period; $i >= 0; $i--) {
                $timestamp = time() - $i * DAY_IN_SECONDS;
                $buckets[wp_date('Y-m-d', $timestamp)] = $timestamp;
            }
            return $buckets;
        }

        /* Months are walked as integers : building them from a string would reintroduce a timezone shift */
        $start = time() - $period * DAY_IN_SECONDS;
        $year = intval(wp_date('Y', $start));
        $month = intval(wp_date('n', $start));
        $last_key = wp_date('Y-m');
        while (count($buckets) < 200) {
            $key = sprintf('%04d-%02d', $year, $month);
            /* Mid month : keeps the label on the right month whatever the timezone */
            $buckets[$key] = strtotime($key . '-15 12:00:00');
            if ($key === $last_key) {
                break;
            }
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }
        return $buckets;
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook != 'settings_page_' . $this->plugin_settings['id'] || !$this->stats_logs_enabled()) {
            return;
        }
        wp_enqueue_script('wputarteaucitron-chartjs', plugins_url('assets/chart.umd.min.js', __FILE__), array(), '4.4.4', true);
    }

    public function info_display() {
        echo '<hr />';
        echo '<p><a href="https://github.com/AmauriC/tarteaucitron.js" rel="noopener noreferrer" target="_blank">tarteaucitron.js</a> v' . esc_html($this->tarteaucitron_version) . '</p>';
    }

    public function wputarteaucitron_add_dashboard_widget() {
        if (!current_user_can('edit_users')) {
            return;
        }
        wp_add_dashboard_widget(
            'wputarteaucitron_dashboard_widget',
            $this->plugin_settings['name'],
            array(&$this, 'wputarteaucitron_dashboard_widget__content')
        );
    }

    public function wputarteaucitron_dashboard_widget__content() {
        ob_start();
        $this->stats_display('widget');
        $out = ob_get_clean();

        if (!$out) {
            echo '<p>' . esc_html__('No stats yet.', 'wputarteaucitron') . '</p>';
        } else {
            echo $out;
        }
    }

}

$WPUTarteAuCitron = new WPUTarteAuCitron();
