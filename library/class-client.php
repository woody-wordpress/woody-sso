<?php

/**
 * @package Woody SSO
 * @author Léo POIROUX <leo@raccourci.fr>
 * @author Jeremy LEGENDRE <jeremy.legendre@raccourci.fr>
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * WOODY_SSO_Client
 */
class WOODY_SSO_Client
{
    /** Server Instance */
    public static $_instance = null;

    /** Default Settings */
    protected $default_settings = array(
        'client_id'            => '',
        'client_secret'        => '',
        'server_url'           => '',
        'server_oauth_trigger' => 'oauth',
        'server_auth_endpoint' => 'authorize',
        'server_token_endpont' => 'token',
        'server_user_endpoint' => 'me'
    );

    public function __construct()
    {
        add_action("init", array($this, "includes"));
        add_action('login_form', array($this, 'form_button'));
        add_action('wp_logout', array($this, 'logout'));
        add_shortcode('sso_button', array($this, 'shortcode'));
    }

    /**
     * populate the instance if the plugin for extendability
     * @return object plugin instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * plugin includes called during load of plugin
     * @return void
     */
    public static function includes()
    {
        require_once(WOODY_SSO_FILE . '/includes/rewrites.php');
        new WOODY_SSO_Rewrites();
    }

    /**
     * Add login button for SSO on the login form.
     * @link https://codex.wordpress.org/Plugin_API/Action_Reference/login_form
     */
    public static function form_button()
    {
        require_once(WOODY_SSO_FILE . '/templates/button.tpl.php');
    }

    /**
     * Login Button Shortcode
     */
    public static function shortcode($atts)
    {
        $a = shortcode_atts(array(
            'type'   => 'primary',
            'title'  => 'Login using Single Sign On',
            'class'  => 'sso-button',
            'target' => '_blank',
            'text'   => 'Single Sign On'
        ), $atts);

        return '<a class="' . $a['class'] . '" href="' . site_url('?auth=sso') . '" title="' . $a['title'] . '" target="' . $a['target'] . '">' . $a['text'] . '</a>';
    }

    /**
     * Get user login redirect. Just in case the user wants to redirect the user to a new url.
     */
    public static function logout()
    {
        setcookie(WOODY_SSO_ACCESS_TOKEN, '', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
    }
}
