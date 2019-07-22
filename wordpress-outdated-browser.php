<?php

/*
Plugin Name: WordPress Outdated Browser
Plugin URI: https://github.com/orainteractive/wordpress-outdated-browser
Description: Detect your browser version, if it is outdated advises users to upgrade their browser.
Version: 1.0
Author: Julian Maison-Guillard (Julian Miller)
Author URI: https://github.com/jmillerOI
License: MIT
*/

// Per WP Standard check for ABSPATH, if not available exit because this is not
// a "WordPress Installation"

if (!defined('ABSPATH')) {
  exit;
}

class WordPressOutdatedBrowser
{
  public function __construct()
  {
    add_action('init', array($this, 'outdated_browser_textdomain'));
    add_action('admin_menu', array($this, 'option_settings_page'));
    add_action('admin_init', array($this, 'settings_init'));
    add_action('admin_enqueue_scripts', array($this, 'color_picker_init'));
    add_action('wp_enqueue_scripts', 'scripts_init');
    add_action('admin_notices', array($this, 'plugin_messages'));
    add_action('wp_footer', array($this, 'trigger_outdated_browser_check'));
  }

  function option_settings_page()
  {
    global $param;
    $param = add_menu_page(
      __('Outdated Browser Options', 'wp_outdated_bwsr'),
      'Outdated Browser Options',
      'activate_plugins',
      'wordpress-outdated-browser-options',
      array($this, 'print_page_html'),
      'dashicons-admin-site'
    );
  }

  function settings_init()
  {
    add_settings_section(
      'outdated_browser_general_section',
      __('Outdated Browser Options', 'wp_outdated_bwsr'),
      array($this, 'header_section_cb'),
      'wordpress-outdated-browser-options'
    );

    add_settings_field(
      'background-color',
      __('Background color', 'wp_outdated_bwsr'),
      array($this, 'bg_color_setting_cb'),
      'wordpress-outdated-browser-options',
      'outdated_browser_general_section'
    );

    add_settings_field(
      'text-color',
      __('Text color', 'wp_outdated_bwsr'),
      array($this, 'text_color_setting_cb'),
      'wordpress-outdated-browser-options',
      'outdated_browser_general_section'
    );

    add_settings_field(
      'browser',
      __('Browser lower than', 'wp_outdated_bwsr'),
      array($this, 'browser_choice_cb'),
      'wordpress-outdated-browser-options',
      'outdated_browser_general_section',
      $browsers = array(
        'js:Promise' => 'Edge',
        'borderImage' => 'IE11',
        'transform' => 'IE10',
        'boxShadow' => 'IE9',
        'borderSpacing' => 'IE8',
      )
    );

    register_setting('wordpress-outdated-browser-options', 'background-color');
    register_setting('wordpress-outdated-browser-options', 'text-color');
    register_setting('wordpress-outdated-browser-options', 'browser');
  }

  function header_section_cb()
  {
    echo __('Customize the outdated browser plugin view.', 'wp_outdated_bwsr');
  }

  function bg_color_setting_cb()
  {
    echo "<input type='text' name='background-color' id='background-color' class='code' value='" . get_option('background-color') . "'/>";
  }

  function text_color_setting_cb()
  {
    echo "<input type='text' name='text-color' id='text-color' class='code' value='" . get_option('text-color') . "'/>";
  }

  function browser_choice_cb($browsers)
  {
    echo "<select name=\"browser\" id=\"browser\">";
    foreach ($browsers as $key => $browser) {
      echo "<option value='" . $browser . "' " . selected(get_option('browser'), $browser, true) . ">" . $browser . "</option>";
    }
    echo "</select>";
  }

  function print_page_html()
  {
    ?>
      <form method="post" action="options.php">
        <?php
        settings_fields('wordpress-outdated-browser-options');
        do_settings_sections('wordpress-outdated-browser-options');
        submit_button();
        $result = $this->fetch_settings();
        ?>
      </form>
    <?php
  }

  function color_picker_init()
  {
    if (is_admin()) {
      wp_enqueue_style('wp-color-picker');
      wp_enqueue_script('wordpress-outdated-browser-cp-handler', plugins_url('assets/wpobrsw-admin.js', __FILE__), array('wp-color-picker'), false, true);
    }
  }

  function fetch_settings()
  {
    return array(
      'bgColor' => get_option('background-color'),
      'textColor' => get_option('text-color'),
      'language' => get_option('language'),
      'browser' => get_option('browser')
    );
  }

  function plugin_messages()
  {
    settings_errors();
  }

  function trigger_outdated_browser_check()
  {
    echo('<div id="outdated"></div>');
    $html_url = plugin_dir_url(__FILE__);
    $opts = $this->fetch_settings();
    $language = $opts['language'] ? $opts['language'] : 'en';
    $opts['language'] = $html_url . 'assets/outdatedbrowser/lang/' . $language . '.html';
    $this->scripts_init($opts);
  }

  function scripts_init($opts)
  {
    $script_url = plugin_dir_url(__FILE__);
    $css = $script_url . 'assets/outdatedbrowser/outdatedbrowser.min.css';
    $js = $script_url . 'assets/outdatedbrowser/outdatedbrowser.min.js';
    $core = $script_url . 'assets/outdated-browser-core.js';

    wp_enqueue_style('outdated-browser-css', $css);
    wp_enqueue_script('outdated-browser-js', $js);
    wp_enqueue_script('outdated-browser-core', $core, array(), '1.0.0', true);
    wp_localize_script('outdated-browser-core', 'options', $opts);
  }
}

// Start
add_action('init', "OutdatedBrowserInit", 1);

function OutdatedBrowserInit()
{
  global $outdatedBrowser;
  $outdatedBrowser = new WordPressOutdatedBrowser();
}
