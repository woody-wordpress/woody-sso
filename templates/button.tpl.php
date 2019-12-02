<style>
    body.login form {
        padding: 30px;
    }

    body.login #nav,
    body.login .user-pass-wrap,
    body.login form p {
        display: none;
    }
</style>

<div class="idp-login">
    <a style="color:#FFF; width:100%; text-align:center; float:none;" class="button button-primary button-large" href="<?php echo site_url('?auth=sso'); ?>">Se connecter avec LE STUDIO</a>
</div>

<script type="text/javascript">
    var scr = document.createElement("script");
    scr.src = "https://connect.studio.raccourci.fr/oauth/v2/logout";
    document.body.appendChild(scr);
</script>

<?php
if (!empty($_GET['error'])) {
    switch ($_GET['error']) {
        case 'restricted-access':
            $message = 'Vous ne disposez pas des droits suffisants pour accéder à ce site';
            break;
        default:
            $message = 'Une erreur inconnue est survenue';
            break;
    }

    echo '<div id="login_error" style="clear:both;margin-top:15px;">' . $message . '</div>';
}
?>
