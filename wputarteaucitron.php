<?php
/*
Plugin Name: WPU Tarte Au Citron
Plugin URI: https://github.com/WordPressUtilities/wputarteaucitron
Update URI: https://github.com/WordPressUtilities/wputarteaucitron
Description: Simple implementation for Tarteaucitron.js
Version: 0.6.0
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wputarteaucitron
Domain Path: /lang
Requires at least: 6.2
Requires PHP: 8.0
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUTarteAuCitron {
    public $plugin_description;
    public $settings_details;
    public $settings;
    private $plugin_version = '0.6.0';
    private $tarteaucitron_version = '1.14.0';
    private $settings_obj;
    private $plugin_settings = array(
        'id' => 'wputarteaucitron',
        'name' => 'WPU Tarte Au Citron'
    );

    public function __construct() {
        add_filter('plugins_loaded', array(&$this, 'plugins_loaded'));
        # Front Assets
        add_action('wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts'));
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
        $this->settings = array(
            'enable_banner' => array(
                'section' => 'settings',
                'label' => __('Activate banner', 'wputarteaucitron'),
                'required' => true,
                'help' => __('Banner will be visible and scripts will be loaded', 'wputarteaucitron'),
                'default_value' => '1',
                'type' => 'select',
                'datas' => array(__('No', 'wputarteaucitron'), __('Yes', 'wputarteaucitron'))
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
            'display_deny_all_cta' => array(
                'section' => 'settings',
                'label' => __('Display the “Deny All” CTA', 'wputarteaucitron'),
                'type' => 'select',
                'datas' => array(__('No', 'wputarteaucitron'), __('Yes', 'wputarteaucitron'))
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
            'accept_all_cta' => true,
            'deny_all_cta' => isset($settings['display_deny_all_cta']) && $settings['display_deny_all_cta'],
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

        $script_settings = apply_filters('wputarteaucitron__script_settings', $script_settings);

        wp_localize_script('wputarteaucitron_front_script', 'wputarteaucitron_settings', $script_settings);
        wp_enqueue_script('wputarteaucitron_front_script');
    }
}

$WPUTarteAuCitron = new WPUTarteAuCitron();
