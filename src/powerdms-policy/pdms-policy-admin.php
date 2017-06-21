<?php
/*
Plugin Name: PowerDMS Policy
Plugin URI: http://powerdms.com
Description: PowerDMS, Document Management, Document Management System, Policy Management, Compliance, Governance, Content Management, ECM
Version: 0.0.0
Author: PowerDMS Inc.
Author URI: http://powerdms.com

Copyright 2017 PowerDMS Inc.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// General
require_once('pdms-admin-settings.php');

// Policy
require_once('pdms-policy-shortcodes.php');
require_once('pdms-policy-admin-settings.php');

// Administration Menus
function pdms_menu() {
    add_menu_page(
        'PowerDMS Settings',
        'PowerDMS',
        'manage_options',
        'pdms',
        'pdms_settings_page'
    );
    add_submenu_page(
        'pdms',
        'PowerDMS Policy Settings',
        'Policy Settings',
        'manage_options',
        'pdms-policy-settings',
        'pdms_policy_settings_page'
    );
}

add_action('admin_menu', 'pdms_menu');

?>