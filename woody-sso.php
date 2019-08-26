<?php

/**
 * Plugin Name: Woody SSO
 * Plugin URI: https://github.com/woody-wordpress/woody-sso
 * Version: 1.3.9
 * Description: Replaces the Wordpress connection system with the SSO of Raccourci Agency: THE STUDIO
 * Author: Raccourci Agency
 * Author URI: https://www.raccourci.fr
 * License: GPL2
 *
 * This program is GLP but; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of.
 */

defined('ABSPATH') or die('No script kiddies please!');

if (!defined('WOODY_SSO_FILE')) {
    define('WOODY_SSO_FILE', plugin_dir_path(__FILE__));
}

if (!defined('WOODY_SSO_ACCESS_TOKEN')) {
    define('WOODY_SSO_ACCESS_TOKEN', 'woody_sso_access_token');
}

// Require the main plugin clas
require_once(WOODY_SSO_FILE . '/library/class-client.php');
$GLOBAL['WOODY_SSO'] = WOODY_SSO_Client::instance();
