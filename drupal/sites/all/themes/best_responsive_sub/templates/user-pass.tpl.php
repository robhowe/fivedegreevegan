<?php
/**
 * See: ../template.php::best_responsive_sub_preprocess_user_pass()
 */
?>

<div class="fdv-user-pass-form-wrapper">
<?php if (!empty($intro_text)): ?>
    <div id="fdv-intro-text"><?php print render($intro_text); ?></div>
<?php endif; ?>
<?php
    print drupal_render_children($form);
?>

<?php if (!empty($bottom_text)): ?>
    <div id="fdv-bottom-text"><?php print render($bottom_text); ?></div>
<?php endif; ?>

</div>

