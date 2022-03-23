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
        add_action('init', array($this, 'refreshToken'));
        add_action('login_footer', array($this, 'loginFooter'));
        add_action('login_header', array($this, 'loginHeader'));
        add_filter('timber_locations', [$this, 'injectTimberLocation']);

        add_action('wp_logout', array($this, 'logout'));
        add_filter('woody_theme_siteconfig', [$this, 'woodyThemeSiteconfig']);
        add_shortcode('sso_button', array($this, 'shortcode'));

        \WP_CLI::add_command('woody_add_sso_domains', [$this, 'woodyAddSsoDomains']);
    }

    /**
     * Register site domain to SSO server
     */
    public static function woodyAddSsoDomains()
    {
        //Retrieve site domains
        $domains = [];
        $polylang = get_option('polylang');
        if ($polylang['force_lang'] == 3 && !empty($polylang['domains'])) {
            foreach ($polylang['domains'] as $lang => $domain) {
                $domains[$lang] = parse_url($domain, PHP_URL_HOST);
            }
        } else {
            $domains['all'] = parse_url(WP_HOME, PHP_URL_HOST);
        }

        foreach ($domains as $domain) {
            //Call idp to activate domain
            $response = wp_remote_post(
                'https://connect.studio.raccourci.fr/admin/wordpress',
                array(
                    'method' => 'POST',
                    'body' => json_encode(array(
                        "token" => WOODY_SSO_ADD_URL_TOKEN,
                        "clientname" => "api-ts",
                        "productname" => "wordpress",
                        "instancename" => $domain
                    )),
                    'timeout' => 15,
                    'headers' => array(
                        'Content-Type' => 'application/json',
                    ),
                )
            );

            if (is_wp_error($response) || empty($response['body'])) {
                $error_message = $response->get_error_message();
                echo sprintf('Failed: %s not authorized to connect to the SSO (%s)', $domain, $error_message) . PHP_EOL;
            } else {
                $body = json_decode($response['body'], true);
                if (empty($body) || empty($body['message'])) {
                    echo sprintf('Failed: %s not authorized to connect to the SSO (%s)', $domain) . PHP_EOL;
                } elseif ($body['message'] != 'OK') {
                    echo sprintf('Failed: %s not authorized to connect to the SSO (%s)', $domain, $body['message']) . PHP_EOL;
                } else {
                    echo sprintf('Success: "%s" authorized to connect to the SSO', $domain) . PHP_EOL;
                }
            }
        }
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

    public function injectTimberLocation($locations)
    {
        $locations[] = WOODY_ADDON_DIR_ROOT . '/templates';
        return $locations;
    }

    /**
     * Add login button for SSO on the login form.
     * @link https://codex.wordpress.org/Plugin_API/Action_Reference/login_form
     */
    public static function loginFooter()
    {
        print \Timber::compile('woody_login_footer.twig');
    }

    /**
     * Add login button for SSO on the login form.
     * @link https://codex.wordpress.org/Plugin_API/Action_Reference/login_form
     */
    public static function loginHeader()
    {
        $logo_website_path = get_stylesheet_directory() . '/logo.svg';
        if (file_exists($logo_website_path)) {
            $logo_website = str_replace('style.css', 'logo.svg', get_stylesheet_uri());
        } else {
            $logo_website = '../img/wp-admin/loader_woody.png';
        }

        $error_message = null;
        if (!empty($_GET['error'])) {
            switch ($_GET['error']) {
                case 'restricted-access':
                    $error_message = 'Vous ne disposez pas des droits suffisants pour accéder à ce site';
                    break;
                default:
                    $error_message = 'Une erreur inconnue est survenue';
                    break;
            }
        }

        $params = [
            'home_url' => home_url(),
            'logo_website' => $logo_website,
            'error_message' => $error_message,
            'auth_sso_url' => site_url('?auth=sso'),
            'hide_default_login' => true
        ];
        $params = apply_filters('woody_sso_login_header', $params);

        print \Timber::compile('woody_login_header.twig', $params);
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
        setcookie(WOODY_SSO_REFRESH_TOKEN, '', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
        setcookie(WOODY_SSO_EXPIRATION_TOKEN, '', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
    }

    /**
     * Refresh user session access token if time is almost exceeded
     */
    public function refreshToken()
    {
        if (is_user_logged_in() && !empty($_COOKIE[WOODY_SSO_EXPIRATION_TOKEN]) && !empty($_COOKIE[WOODY_SSO_REFRESH_TOKEN])) {
            $options = get_option('woody_sso_options');
            $access_token_expiration = (int) $_COOKIE[WOODY_SSO_EXPIRATION_TOKEN];
            $refresh_token = $_COOKIE[WOODY_SSO_REFRESH_TOKEN];

            // If current token is going to expire, refresh token
            if (time() > $access_token_expiration - 300 && time() < $access_token_expiration) {
                // REFRESH TOKEN
                $params = array(
                    'grant_type' => 'refresh_token',
                    'client_id' => $options['client_id'],
                    'client_secret' => $options['client_secret'],
                    'refresh_token' => $refresh_token,
                    'idp_application' => 'woody',
                    'site_key'      => WP_SITE_KEY
                );

                $curl = curl_init();
                $args = array(
                    CURLOPT_URL => $options['server_url'] . '/oauth/v2/token',
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $params,
                    CURLOPT_RETURNTRANSFER => true
                );
                curl_setopt_array($curl, $args);

                $tokens = json_decode(curl_exec($curl));

                if ($tokens) {
                    setcookie(WOODY_SSO_ACCESS_TOKEN, $tokens->access_token, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
                    setcookie(WOODY_SSO_REFRESH_TOKEN, $tokens->refresh_token, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
                    setcookie(WOODY_SSO_EXPIRATION_TOKEN, time() + $tokens->expires_in, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
                }

                curl_close($curl);
            }
        }
    }

    public function woodyThemeSiteconfig($siteConfig)
    {
        if (is_admin() && !empty($_COOKIE[WOODY_SSO_ACCESS_TOKEN])) {
            $siteConfig['woody_sso_access_token'] = $_COOKIE[WOODY_SSO_ACCESS_TOKEN];
        }

        return $siteConfig;
    }
}
