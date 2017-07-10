<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Linked In Login
Plugin URI: http://www.envisioning.me
Description: a plugin to create awesomeness and spread joy
Version: 1.2
Author: Andy
Author URI: http://mrtotallyawesome.com
License: GPL2
*/

define( 'LIP_PATH', plugin_dir_path( __FILE__ ) );
define( 'LIP_URL', plugin_dir_url(__FILE__));

require_once (LIP_PATH.'/LinkedInLogin.php');

$x = new LinkedInLogin();