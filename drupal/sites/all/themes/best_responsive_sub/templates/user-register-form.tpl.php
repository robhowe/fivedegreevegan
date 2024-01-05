<?php
/**
 * See: ../template.php::best_responsive_sub_preprocess_user_register_form()
 */
?>

<div class="fdv-user-register-form-wrapper">
<?php if (!empty($intro_text)): ?>
    <div id="fdv-intro-text"><?php print render($intro_text); ?></div>
<?php endif; ?>
<?php
    print drupal_render_children($form);
?>

<?php
    if (module_exists('simple_fb_connect')):
        // See: https://developers.facebook.com/apps/
?>
    <div class="fdv-separator"><hr /> or <hr /></div>
    <a href="/user/simple-fb-connect" class="fdv-btn fdv-user-login-facebook">Create Account using Facebook</a>
<?php endif; ?>

<?php if (!empty($bottom_text)): ?>
    <div id="fdv-bottom-text"><?php print render($bottom_text); ?></div>
<?php endif; ?>

</div>

