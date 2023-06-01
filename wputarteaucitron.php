<?php
/*
Plugin Name: WPU Tarte Au Citron
Plugin URI: https://github.com/WordPressUtilities/wputarteaucitron
Update URI: https://github.com/WordPressUtilities/wputarteaucitron
Description: Simple implementation for Tarteaucitron.js
Version: 0.1.0
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wputarteaucitron
Domain Path: /lang/
Requires at least: 6.0
Requires PHP: 8.0
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUTarteAuCitron {
    public $plugin_description;
    public $settings_details;
    public $settings;
    private $plugin_version = '0.1.0';
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
            'privacy_page_id' => array(
                'section' => 'settings',
                'label' => __('Privacy URL', 'wputarteaucitron'),
                'type' => 'page'
            ),
            'gtm_id' => array(
                'section' => 'trackers',
                'help' => 'Example : GTM-1234',
                'label' => __('GTM ID', 'wputarteaucitron')
            )
        );
        include dirname(__FILE__) . '/inc/WPUBaseSettings/WPUBaseSettings.php';
        $this->settings_obj = new \wputarteaucitron\WPUBaseSettings($this->settings_details, $this->settings);
    }

    public function wp_enqueue_scripts() {
        $settings = $this->settings_obj->get_settings();
        /* Front Style */
        wp_register_style('wputarteaucitron_front_style', plugins_url('assets/front.css', __FILE__), array(), $this->plugin_version);
        wp_enqueue_style('wputarteaucitron_front_style');
        /* Front Script with localization / variables */
        wp_register_script('wputarteaucitron_main', plugins_url('assets/tarteaucitron/tarteaucitron.js', __FILE__), array(), $this->plugin_version, true);
        wp_register_script('wputarteaucitron_front_script', plugins_url('assets/front.js', __FILE__), array('wputarteaucitron_main'), $this->plugin_version, true);
        wp_localize_script('wputarteaucitron_front_script', 'wputarteaucitron_settings', array(
            'gtm_id' => isset($settings['gtm_id']) ? $settings['gtm_id'] : false,
            'privacy_page' => isset($settings['privacy_page_id']) ? get_page_link($settings['privacy_page_id']) : false
        ));
        wp_enqueue_script('wputarteaucitron_front_script');
    }
}

$WPUTarteAuCitron = new WPUTarteAuCitron();
