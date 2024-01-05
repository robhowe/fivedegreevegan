<?php
/**
 * See: ../template.php::best_responsive_sub_preprocess_user_login()
 */
?>

<div class="fdv-user-login-form-wrapper">
<?php if (!empty($intro_text)): ?>
    <div id="fdv-intro-text"><?php print render($intro_text); ?></div>
<?php endif; ?>
<?php
    print drupal_render($form['name']);
    print drupal_render($form['pass']);
?>
    <a href="/user/password" class="fdv-user-login-forget-password">Forget your username or password?</a>

<?php
    // Render login button
    print drupal_render($form['form_build_id']);
    print drupal_render($form['form_id']);
    print drupal_render($form['actions']);
?>

<?php
    if (module_exists('simple_fb_connect')):
        // See: https://developers.facebook.com/apps/
?>
    <div class="fdv-separator"><hr /> or <hr /></div>
    <a href="/user/simple-fb-connect" class="fdv-btn fdv-user-login-facebook">Login with Facebook</a>
<?php endif; ?>

<?php if (module_exists('openid')): ?>
<?php if (FALSE === '@TODO not visually implemented correctly yet'): ?>
    <div class="fdv-separator"><hr /> or <hr /></div>
    <div class="fdv-user-login-openid-links">
<?php
        print drupal_render($form['openid_identifier']);
        print drupal_render($form['openid_links']);
?>
    </div>
<?php endif; ?>
<?php endif; ?>

<?php if (!empty($bottom_text)): ?>
    <div id="fdv-bottom-text"><?php print render($bottom_text); ?></div>
<?php endif; ?>

</div>

