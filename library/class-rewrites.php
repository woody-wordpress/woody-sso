<?php

/**
 * @package Woody SSO
 * @author Léo POIROUX <leo@raccourci.fr>
 * @author Jeremy LEGENDRE <jeremy.legendre@raccourci.fr>
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * WOODY_SSO_Rewrites
 */
class WOODY_SSO_Rewrites
{
    public function __construct()
    {
        add_filter('rewrite_rules_array', array($this, 'create_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('woody_update', array($this, 'flush_rewrite_rules'));
        add_action('template_redirect', array($this, 'template_redirect_intercept'));
    }

    public function create_rewrite_rules($rules)
    {
        global $wp_rewrite;
        $newRule  = array('auth/(.+)' => 'index.php?auth=' . $wp_rewrite->preg_index(1));
        $newRules = $newRule + $rules;

        return $newRules;
    }

    public function add_query_vars($qvars)
    {
        $qvars[] = 'auth';

        return $qvars;
    }

    public function flush_rewrite_rules()
    {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

    public function template_redirect_intercept()
    {
        global $wp_query;
        if ($wp_query->get('auth') && $wp_query->get('auth') == 'sso') {
            require_once(dirname(dirname(__FILE__)) . '/includes/callback.php');
            exit;
        }
    }
}
