<?php
/*
Plugin Name: WPU Tarte Au Citron
Plugin URI: https://github.com/WordPressUtilities/wputarteaucitron
Update URI: https://github.com/WordPressUtilities/wputarteaucitron
Description: Simple implementation for Tarteaucitron.js
Version: 0.11.0
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wputarteaucitron
Domain Path: /lang
Requires at least: 6.0
Requires PHP: 8.0
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUTarteAuCitron {
    public $plugin_description;
    public $settings_details;
    public $settings;
    private $plugin_version = '0.11.0';
    private $tarteaucitron_version = '1.15.0';
    private $settings_obj;
    private $prefix_stat = 'wputarteaucitron_stat_';
    private $plugin_settings = array(
        'id' => 'wputarteaucitron',
        'name' => 'WPU Tarte Au Citron'
    );

    private $services = array(
        'googletagmanager' => array(
            'label' => 'Google Tag Manager',
            'setting_key' => 'gtm_id',
            'user_key' => 'googletagmanagerId'
        ),
        'gtag' => array(
            'label' => 'GA 4',
            'setting_key' => 'ga4_id',
            'user_key' => 'gtagUa'
        ),
        'facebookpixel' => array(
            'label' => 'Facebook Pixel',
            'setting_key' => 'fbpix_id',
            'user_key' => 'facebookpixelId'
        ),
        'hubspot' => array(
            'label' => 'Hubspot API',
            'setting_key' => 'hubspot_api_key',
            'user_key' => 'hubspotId'
        )
    );

    public function __construct() {
        add_filter('plugins_loaded', array(&$this, 'plugins_loaded'));

        # Front Assets
        add_action('wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts'));

        # AJAX
        add_action('wp_ajax_wputarteaucitron_status', array(&$this, 'callback_ajax'));
        add_action('wp_ajax_nopriv_wputarteaucitron_status', array(&$this, 'callback_ajax'));

        # Admin
        add_action('wpubasesettings_after_content_settings_page_wputarteaucitron', array(&$this, 'stats_display'));
        add_action('load-settings_page_wputarteaucitron', array(&$this, 'stats_reset_action'));
    }

    public function plugins_loaded() {
        # TRANSLATION
        if (!load_plugin_textdomain('wputarteaucitron', false, dirname(plugin_basename(__FILE__)) . '/lang/')) {
            load_muplugin_textdomain('wputarteaucitron', dirname(plugin_basename(__FILE__)) . '/lang/');
        }
        $this->plugin_description = __('Simple implementation for Tarteaucitron.js', 'wputarteaucitron');
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
                'trackers' => array(
                    'name' => __('Trackers', 'wputarteaucitron')
                )
            )
        );

        $yes_no = array(__('No', 'wputarteaucitron'), __('Yes', 'wputarteaucitron'));

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
            'privacy_page_id' => array(
                'section' => 'settings',
                'label' => __('Privacy URL', 'wputarteaucitron'),
                'lang' => 1,
                'type' => 'post',
                'post_type' => 'page'
            ),
            'custom_icon_id' => array(
                'section' => 'settings',
                'label' => __('Custom Icon', 'wputarteaucitron'),
                'type' => 'media'
            ),
            'show_icon' => array(
                'section' => 'settings',
                'label' => __('Show icon', 'wputarteaucitron'),
                'required' => true,
                'help' => sprintf(__('Or create a link to reopen the popup : %s', 'wputarteaucitron'), htmlentities('<a data-wputarteaucitron-open-panel="1" href="#">Cookies</a>')),
                'default_value' => '1',
                'type' => 'select',
                'datas' => $yes_no
            ),
            'icon_position' => array(
                'section' => 'settings',
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
                'section' => 'settings',
                'label' => __('Banner position', 'wputarteaucitron'),
                'type' => 'select',
                'datas' => array(
                    'bottom' => __('Bottom', 'wputarteaucitron'),
                    'middle' => __('Middle', 'wputarteaucitron'),
                    'top' => __('Top', 'wputarteaucitron')
                )
            ),
            'banner_message' => array(
                'section' => 'settings',
                'label' => __('Banner message', 'wputarteaucitron'),
                'lang' => 1,
                'type' => 'textarea'
            ),
            'display_accept_all_cta' => array(
                'section' => 'settings',
                'label' => __('Display the “Accept All” CTA', 'wputarteaucitron'),
                'type' => 'select',
                'datas' => $yes_no
            ),
            'display_deny_all_cta' => array(
                'section' => 'settings',
                'label' => __('Display the “Deny All” CTA', 'wputarteaucitron'),
                'type' => 'select',
                'datas' => $yes_no
            ),
            'gtm_id' => array(
                'wputarteaucitron_value' => true,
                'section' => 'trackers',
                'help' => sprintf(__('Example: %s', 'wputarteaucitron'), 'GTM-1234'),
                'label' => __('GTM ID', 'wputarteaucitron')
            ),
            'ga4_id' => array(
                'wputarteaucitron_value' => true,
                'section' => 'trackers',
                'help' => sprintf(__('Example: %s', 'wputarteaucitron'), 'G-XXXXXXXXX'),
                'label' => __('GA 4 ID', 'wputarteaucitron')
            ),
            'fbpix_id' => array(
                'wputarteaucitron_value' => true,
                'section' => 'trackers',
                'help' => sprintf(__('Example: %s', 'wputarteaucitron'), '123487593'),
                'label' => __('Facebook Pixel ID', 'wputarteaucitron')
            ),
            'hubspot_api_key' => array(
                'wputarteaucitron_value' => true,
                'section' => 'trackers',
                'label' => __('Hubspot API Key', 'wputarteaucitron')
            )
        );

        $this->settings = apply_filters('wputarteaucitron__settings', $this->settings);
        require_once dirname(__FILE__) . '/inc/WPUBaseSettings/WPUBaseSettings.php';
        $this->settings_obj = new \wputarteaucitron\WPUBaseSettings($this->settings_details, $this->settings);
    }

    public function wp_enqueue_scripts() {
        $settings = $this->settings_obj->get_settings();
        $current_lang = $this->settings_obj->get_current_language();

        /* Check default settings */
        if (empty($settings) || !is_array($settings) || isset($settings['enable_banner']) && $settings['enable_banner'] == '0') {
            return;
        }

        /* Front Style */
        wp_register_style('wputarteaucitron_front_style', plugins_url('assets/front.css', __FILE__), array(), $this->plugin_version);
        wp_enqueue_style('wputarteaucitron_front_style');

        /* Front Script with localization / variables */
        wp_register_script('wputarteaucitron_main', plugins_url('assets/tarteaucitron/tarteaucitron.js', __FILE__), array(), $this->tarteaucitron_version, true);
        wp_register_script('wputarteaucitron_front_script', plugins_url('assets/front.js', __FILE__), array('wputarteaucitron_main'), $this->plugin_version, true);

        /* Privacy page */
        $privacy_page = false;
        $privacy_page_id = false;
        if (isset($setting['privacy_page_id']) && $setting['privacy_page_id']) {
            $privacy_page_id = $setting['privacy_page_id'];
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

        $script_settings['services'] = $this->services;

        $script_settings = apply_filters('wputarteaucitron__script_settings', $script_settings);

        wp_localize_script('wputarteaucitron_front_script', 'wputarteaucitron_settings', $script_settings);
        wp_enqueue_script('wputarteaucitron_front_script');
    }

    /* ----------------------------------------------------------
      AJAX
    ---------------------------------------------------------- */

    function callback_ajax() {
        check_ajax_referer('wputarteaucitron_nonce');
        if (!isset($_POST['status'], $_POST['service']) || !$_POST['service']) {
            return;
        }

        if (!isset($this->services[$_POST['service']])) {
            return;
        }

        $option_id = $this->prefix_stat . 'service_' . $_POST['service'] . '_' . ($_POST['status'] ? 'allowed' : 'disallowed');
        $option_value = get_option($option_id, 0);
        if (!$option_value) {
            $option_value = 0;
        }
        update_option($option_id, ++$option_value, false);

        /* Update since */
        $this->stats_get_since();

        wp_send_json_success();
    }

    /* ----------------------------------------------------------
      Stats
    ---------------------------------------------------------- */

    /**
     * Get stats start date
     * @param  boolean $reset   Reset start date
     * @return int              Start date
     */
    function stats_get_since($reset = false) {
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
    function stats_reset() {
        delete_option($this->prefix_stat . 'since');
        foreach ($this->services as $key => $infos) {
            $base_id = $this->prefix_stat . 'service_' . $key;
            delete_option($base_id . '_allowed');
            delete_option($base_id . '_disallowed');
        }
    }

    function stats_reset_action() {
        if (isset($_POST[$this->prefix_stat . 'nonce']) && wp_verify_nonce($_POST[$this->prefix_stat . 'nonce'], $this->prefix_stat)) {
            $this->stats_reset();
        }
    }

    function stats_display() {

        $table_html = '';

        foreach ($this->services as $key => $infos) {
            $base_id = $this->prefix_stat . 'service_' . $key;
            $allowed = get_option($base_id . '_allowed');
            $refused = get_option($base_id . '_disallowed');

            if (!is_numeric($allowed) && !is_numeric($refused)) {
                continue;
            }

            $stat_allowed = '0';
            $stat_refused = '0';
            $total = $allowed + $refused;
            if ($total) {
                $stat_allowed = number_format($allowed / $total * 100, 2);
                $stat_refused = number_format($refused / $total * 100, 2);
            }

            $table_html .= '<tr>';
            $table_html .= '<th scope="row">' . $infos['label'] . '</th>';
            $table_html .= '<td>' . $allowed . ' <small>(' . $stat_allowed . '%)</small></td>';
            $table_html .= '<td>' . $refused . ' <small>(' . $stat_refused . '%)</small></td>';
            $table_html .= '</tr>';
        }

        if (!$table_html) {
            return;
        }

        echo '<hr />';
        echo '<div style="max-width:600px">';
        echo '<h2>' . __('Stats', 'wputarteaucitron') . '</h2>';
        echo '<table contenteditable class="widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th></th>';
        echo '<th>' . __('Accepted', 'wputarteaucitron') . '</th>';
        echo '<th>' . __('Refused', 'wputarteaucitron') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>' . $table_html . '</tbody>';
        echo '</table>';

        $since = $this->stats_get_since();
        $date_format = get_option('date_format') . ', ' . get_option('time_format');
        echo '<p>' . sprintf(__('Since %s', 'wputarteaucitron'), wp_date($date_format, $since)) . '.</p>';
        echo '<form action="" method="post">';
        submit_button(__('Reset stats', 'wputarteaucitron'));
        echo wp_nonce_field($this->prefix_stat, $this->prefix_stat . 'nonce');
        echo '</form>';
    }

}

$WPUTarteAuCitron = new WPUTarteAuCitron();
