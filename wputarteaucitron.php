<?php
defined('ABSPATH') || die;
/*
Plugin Name: WPU Tarte Au Citron
Plugin URI: https://github.com/WordPressUtilities/wputarteaucitron
Update URI: https://github.com/WordPressUtilities/wputarteaucitron
Description: Simple implementation for Tarteaucitron.js
Version: 0.17.0
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
    private $plugin_version = '0.17.0';
    private $tarteaucitron_version = '1.19.0';
    private $settings_obj;
    private $prefix_stat = 'wputarteaucitron_stat_';
    private $plugin_settings = array(
        'id' => 'wputarteaucitron',
        'name' => 'WPU Tarte Au Citron'
    );

    private $services = array(
        'googletagmanager' => array(
            'label' => 'Google Tag Manager',
            'field_label' => 'GTM ID',
            'setting_key' => 'gtm_id',
            'user_key' => 'googletagmanagerId',
            'example' => 'GTM-1234'
        ),
        'gtag' => array(
            'label' => 'GA 4',
            'field_label' => 'GA 4 ID',
            'setting_key' => 'ga4_id',
            'user_key' => 'gtagUa',
            'example' => 'GTM-1234'
        ),
        'facebookpixel' => array(
            'label' => 'Facebook Pixel',
            'field_label' => 'Facebook Pixel ID',
            'setting_key' => 'fbpix_id',
            'user_key' => 'facebookpixelId',
            'example' => '123487593'
        ),
        'hubspot' => array(
            'label' => 'Hubspot API',
            'field_label' => 'Hubspot API key',
            'setting_key' => 'hubspot_api_key',
            'user_key' => 'hubspotId'
        ),
        'plausible' => array(
            'label' => 'Plausible',
            'field_label' => 'Plausible Domain',
            'setting_key' => 'plausible_domain',
            'user_key' => 'plausibleDomain'
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
        add_action('wpubasesettings_after_content_settings_page_wputarteaucitron', array(&$this, 'info_display'));
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
            'disable_banner_loggedin' => array(
                'section' => 'settings',
                'label' => __('Disable banner for logged in users', 'wputarteaucitron'),
                'required' => true,
                'help' => __('Banner will be visible only for non-logged in users', 'wputarteaucitron'),
                'default_value' => '0',
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
            )
        );

        $this->services = apply_filters('wputarteaucitron__services', $this->services);

        foreach ($this->services as $key => $service) {
            $service_setting = array(
                'lang' => true,
                'wputarteaucitron_value' => true,
                'section' => 'trackers',
                'label' => $service['field_label']
            );
            if (isset($service['example'])) {
                $service_setting['help'] = sprintf(__('Example: %s', 'wputarteaucitron'), $service['example']);
            }
            $this->settings[$service['setting_key']] = $service_setting;
        }

        $this->settings = apply_filters('wputarteaucitron__settings', $this->settings);
        require_once __DIR__ . '/inc/WPUBaseSettings/WPUBaseSettings.php';
        $this->settings_obj = new \wputarteaucitron\WPUBaseSettings($this->settings_details, $this->settings);

        require_once __DIR__ . '/inc/WPUBaseUpdate/WPUBaseUpdate.php';
        $this->settings_update = new \wputarteaucitron\WPUBaseUpdate(
            'WordPressUtilities',
            'wputarteaucitron',
            $this->plugin_version);

        /* Admin widget */
        add_action('wp_dashboard_setup', array(&$this, 'wputarteaucitron_add_dashboard_widget'));
    }

    public function wp_enqueue_scripts() {
        $settings = $this->settings_obj->get_settings();
        $current_lang = $this->settings_obj->get_current_language();

        /* Check default settings */
        if (empty($settings) || !is_array($settings) || isset($settings['enable_banner']) && $settings['enable_banner'] == '0') {
            return;
        }

        if(isset($settings['disable_banner_loggedin']) && $settings['disable_banner_loggedin'] == '1' && is_user_logged_in()) {
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

        /* Build settings for services */
        $script_settings['services'] = array();
        foreach ($this->services as $k => $service) {
            if (!isset($script_settings[$service['setting_key']])) {
                continue;
            }
            $script_settings['services'][$k] = array(
                'setting_key' => $service['setting_key'],
                'user_key' => $service['user_key']
            );
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
        if (!isset($_POST['status'], $_POST['service']) || !$_POST['service']) {
            return;
        }

        if (!isset($this->services[$_POST['service']])) {
            return;
        }

        $service_key = esc_sql($_POST['service']);

        /* Bypassing option API to avoid cache problems */
        global $wpdb;
        $option_id = $this->prefix_stat . 'service_' . $service_key . '_' . ($_POST['status'] ? 'allowed' : 'disallowed');
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

    public function stats_display($mode = 'default') {
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
            $table_html .= '<td>' . $total . '</td>';
            $table_html .= '<td>' . $allowed . ' <small>(' . $stat_allowed . '%)</small></td>';
            $table_html .= '<td>' . $refused . ' <small>(' . $stat_refused . '%)</small></td>';
            $table_html .= '</tr>';
        }

        if (!$table_html) {
            return;
        }
        if ($mode != 'widget') {
            echo '<hr />';
            echo '<div style="max-width:600px">';
            echo '<h2>' . __('Stats', 'wputarteaucitron') . '</h2>';
        }
        echo '<table contenteditable class="widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th></th>';
        echo '<th>' . __('Total', 'wputarteaucitron') . '</th>';
        echo '<th>' . __('Accepted', 'wputarteaucitron') . '</th>';
        echo '<th>' . __('Refused', 'wputarteaucitron') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>' . $table_html . '</tbody>';
        echo '</table>';

        $since = $this->stats_get_since();
        $date_format = get_option('date_format') . ', ' . get_option('time_format');
        echo '<p>' . sprintf(__('Since %s', 'wputarteaucitron'), wp_date($date_format, $since)) . '.</p>';
        if ($mode != 'widget') {
            echo '<form action="" method="post">';
            submit_button(__('Reset stats', 'wputarteaucitron'));
            echo wp_nonce_field($this->prefix_stat, $this->prefix_stat . 'nonce');
            echo '</form>';
            echo '</div>';
        }
    }

    public function info_display() {
        echo '<hr />';
        echo '<p><a href="https://github.com/AmauriC/tarteaucitron.js" target="_blank">tarteaucitron.js</a> v' . $this->tarteaucitron_version . '</p>';
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
        $this->stats_display('widget');
    }

}

$WPUTarteAuCitron = new WPUTarteAuCitron();
