<?php

// Standard length of input fields for username and password:
// size=28 is max for small screens without restyling.
define('FDV_USER_FIELD_SIZE', 28);


/**
 * Convenience function for use with drupal_match_path()
 *
 * @see fdvegan_Util::isCurrentPage()
 *
 * @param mixed $path    A string of one path, or array of path strings.
 * @throws FDVegan_InvalidArgumentException
 * @return bool    TRUE if current page is in given path list.
 */
function fdvegan_is_current_page($path) {
    if (is_string($path)) {
        $pattern_string = $path;
    } else if (is_array($path)) {
        $pattern_string = implode(PHP_EOL, $path);
    } else {
        throw new FDVegan_InvalidArgumentException("template.php::fdvegan_is_current_page() invalid type");
    }
    return drupal_match_path(current_path(), $pattern_string);
}


/**
 * Implements hook_html_head_alter().
 * This will overwrite the default meta character type tag with HTML5 version.
 */
function best_responsive_sub_html_head_alter(&$head_elements) {
  $head_elements['system_meta_content_type']['#attributes'] = array(
    'charset' => 'utf-8'
  );
}

/**
 * Insert themed breadcrumb page navigation at top of the node content.
 */
function best_responsive_sub_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];
  if (!empty($breadcrumb)) {
    // Use CSS to hide title .element-invisible.
    $output = '<h2 class="element-invisible">' . t('You are here') . '</h2>';
    // comment below line to hide current page to breadcrumb
$breadcrumb[] = drupal_get_title();
    $output .= '<nav class="breadcrumb">' . implode(' » ', $breadcrumb) . '</nav>';
    return $output;
  }
}

/**
 * Override or insert variables into the page template.
 */
function best_responsive_sub_preprocess_page(&$vars, $hook) {
  if ((isset($_GET['a']) && $_GET['a'] == 1) ||
      (isset($_GET['ajax']) && $_GET['ajax'] == 1)) {
    $vars['theme_hook_suggestions'][] = 'page__ajax';
  }

  // Setup "Page Not Found" handling:
  // Note - this doesn't really work.  Instead use:  Admin->Configuration->Site information : Default 404 (not found) page
  $header = drupal_get_http_header('status'); 
  if ($header == '404 Not Found') {
    $vars['theme_hook_suggestions'][] = 'page-not-found';  // created in fdvegan.module::fdvegan_page_not_found()
  }
  if ($header == '403 Forbidden') {
    $vars['theme_hook_suggestions'][] = 'page-forbidden';  // created in fdvegan.module::fdvegan_page_forbidden()
  }

  if (isset($vars['main_menu'])) {
    $vars['main_menu'] = theme('links__system_main_menu', array(
      'links' => $vars['main_menu'],
      'attributes' => array(
        'class' => array('links', 'main-menu', 'clearfix'),
      ),
      'heading' => array(
        'text' => t('Main menu'),
        'level' => 'h2',
        'class' => array('element-invisible'),
      )
    ));
  }
  else {
    $vars['main_menu'] = FALSE;
  }
  if (isset($vars['secondary_menu'])) {
    $vars['secondary_menu'] = theme('links__system_secondary_menu', array(
      'links' => $vars['secondary_menu'],
      'attributes' => array(
        'class' => array('links', 'secondary-menu', 'clearfix'),
      ),
      'heading' => array(
        'text' => t('Secondary menu'),
        'level' => 'h2',
        'class' => array('element-invisible'),
      )
    ));
  }
  else {
    $vars['secondary_menu'] = FALSE;
  }

    // Add javascript files for front-page jquery slideshow.
    if (drupal_is_front_page()) {
        drupal_add_js(drupal_get_path('theme', 'best_responsive_sub') . '/js/flexslider-min.js');
        drupal_add_js(drupal_get_path('theme', 'best_responsive_sub') . '/js/slide.js');
    }

    // Add javascript file for Pinterest.
    // See:  https://developers.pinterest.com/docs/widgets/save/?
    //drupal_add_js('//assets.pinterest.com/js/pinit.js');
}


/**
 * Preprocesses the wrapping HTML.
 *
 * @param array &$vars    Template variables.
 */
function best_responsive_sub_preprocess_html(&$vars) {

    // Bump the sidebar from the right, to the bottom on particular pages: 'actor-network'
    if (fdvegan_is_current_page('actor-network')) {
        $vars['classes_array'][] = 'fdv-bump-sidebar';
    }

/*
 // Note - The following is deprecated since using module Metatag

    $meta_array = array();
    $meta_array['description'] = array(
        '#type' => 'html_tag',
        '#tag'  => 'meta',
        '#attributes' => array(
            'name'    => 'description',
            'content' => 'Find the Five Degrees of Vegan between all your favorite actors!',
        )
    );
    $meta_array['abstract'] = array(
        '#type' => 'html_tag',
        '#tag'  => 'meta',
        '#attributes' => array(
            'name'    => 'abstract',
            'content' => 'Find the Five Degrees of Vegan between all your favorite actors!',
        )
    );
    $meta_array['keywords'] = array(
        '#type' => 'html_tag',
        '#tag'  => 'meta',
        '#attributes' => array(
            'name'    => 'keywords',
            'content' => 'vegan actors',
        )
    );

    // Add header meta tags to the page's head section
    foreach ($meta_array as $name => $meta) {
        drupal_add_html_head($meta, $name);
    }
*/
}


/**
 * Duplicate of theme_menu_local_tasks() but adds clearfix to tabs.
 */
function best_responsive_sub_menu_local_tasks(&$variables) {
  $output = '';

  if (!empty($variables['primary'])) {
    $variables['primary']['#prefix'] = '<h2 class="element-invisible">' . t('Primary tabs') . '</h2>';
    $variables['primary']['#prefix'] .= '<ul class="tabs primary clearfix">';
    $variables['primary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['primary']);
  }
  if (!empty($variables['secondary'])) {
    $variables['secondary']['#prefix'] = '<h2 class="element-invisible">' . t('Secondary tabs') . '</h2>';
    $variables['secondary']['#prefix'] .= '<ul class="tabs secondary clearfix">';
    $variables['secondary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['secondary']);
  }
  return $output;
}

/**
 * Override or insert variables into the node template.
 */
function best_responsive_sub_preprocess_node(&$variables) {
  $node = $variables['node'];
  if ($variables['view_mode'] == 'full' && node_is_page($variables['node'])) {
    $variables['classes_array'][] = 'node-full';
  }
  $variables['date'] = t('!datetime', array('!datetime' =>  date('j F Y', $variables['created'])));
}

function best_responsive_sub_page_alter($page) {
  // <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
  $viewport = array(
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => array(
        'name' =>  'viewport',
        'content' =>  'width=device-width, initial-scale=1, maximum-scale=1'
    )
  );
  drupal_add_html_head($viewport, 'viewport');
}


/**
 * Implements hook_user_view_alter().
 *
 * Re-theme the main user page.
 *   URL:  /user
 */
function best_responsive_sub_user_view_alter(&$build) {
    global $user;
    // If want to simply redirect to the user-profile-edit page instead,
    // uncomment the following:
/*
    $redir_url = "user/{$user->uid}/edit";
    drupal_goto($redir_url);
*/

    $profile_link = l('Edit Your Profile',
                     'user/' . $user->uid . '/edit',
                     array('attributes' => array('class'  => array('fdv-btn fdv-user-profile-edit')),
                    ));
    $actor_network_link = l('Actor Network',
                            'actor-network',
                            array('query' => array('degrees' => 1))
                           );
    $actor_tree_link = l('Actor Tree', 'actor');
    $content = <<<EOT
        <!---<div class="clear"></div>--->
        <!---<p>If you would like, you can update your <span class="fdv-brand-text">5&deg; Vegan</span> profile information.</p>--->
        {$profile_link}
        <div class="fdv-user-view-benefits-text">
            Being a <span class="fdv-brand-text">5&deg; Vegan</span> account-holder has its benefits:
            <ul>
                <li>You can see more connections on the {$actor_network_link} and {$actor_tree_link} pages.</li>
                <li>As we add new functionality, you will see it sooner than other lowly non-members.</li>
            </ul>
        </div>
EOT;
    $build['fdv_above_bottom_text'] = array(
        '#markup' => $content,
        '#weight' => 20,
    );

    $content = <<<EOT
        <p id="fdv-bottom-text" class="center">
            We hope to launch our <span class="fdv-brand-text">5&deg; Vegan Pro</span> membership level soon.<br />
            Stay tuned!
        </p>
EOT;
    $build['fdv_bottom_text'] = array(
        '#markup' => $content,
        '#weight' => 30,
    );

    // Tweak other form fields:
    $build['summary']['#title'] = '';
    $build['summary']['member_for']['#title'] = t('Member since:');
    $content = date('F j, Y', $build['#account']->created) .
                    ' <span class="small fdv-user-view-member-since">(' .
                    format_interval(REQUEST_TIME - $build['#account']->created) .
                    ')</span>';
    $build['summary']['member_for']['#markup'] = $content;
}

/**
 * Re-theme the user-profile-edit page.
 *   URL:  /user/{uid}/edit
 */
function best_responsive_sub_form_user_profile_form_alter(&$form, &$form_state, $form_id) {
    global $user;
    $content = <<<EOT
        <p id="fdv-intro-text">If you would like, you can update your <span class="fdv-brand-text">5&deg; Vegan</span> profile information.</p>
EOT;
        $form['fdv_intro_text'] = array(
            '#markup' => $content,
            '#weight' => -20
        );

    $content = <<<EOT
        <p id="fdv-bottom-text" class="center">
            We hope to launch our <span class="fdv-brand-text">5&deg; Vegan Pro</span> membership level soon.<br />
            Stay tuned!
        </p>
EOT;
    $form['fdv_bottom_text'] = array(
        '#markup' => $content,
        '#weight' => 20,
    );

    // Tweak other form fields:
    $form['account']['current_pass']['#size'] = FDV_USER_FIELD_SIZE;
    $form['account']['mail']['#size'] = FDV_USER_FIELD_SIZE;
    $form['account']['mail']['#description'] = 'You must use a valid e-mail address you have access to. We will not make your e-mail address public, and will only send you emails you specifically request (like password-resets), and (very rarely) notifications of important news and new functionality announcements. You can cancel your account at any time.';
    $form['contact']['#access'] = FALSE;  // Hide the Contact Form fieldset completely
    $form['picture']['#description'] = 'We do not <span="italic">currently</span> display your picture publicly, but may do so in the future. e.g.: if we add forum support or public comments on movies and actors.';

    $cancel_link = l('permanently remove your account',
                     'user/' . $user->uid . '/cancel',
                     array('attributes' => array('class'  => array('fdv-user-edit-delete-account')),
                    ));
    $content = <<<EOT
        <div class="fdv-separator" style="margin-top: 40px;"><hr /> or <hr /></div>
        <p class="small">You can {$cancel_link} if you wish.</p>
EOT;
        $form['fdv_sub_bottom_text'] = array(
            '#markup' => $content,
            '#weight' => 120
        );
    $form['actions']['cancel']['#access'] = FALSE;  // Hide the Cancel Account Button completely
    $form['actions']['cancel']['#value'] = 'Delete My Account Completely';
    $form['actions']['cancel']['#attributes'] = array('class' => array('fdv-user-edit-delete-account'));
}

/**
 * Re-theme the user-cancel-confirm page.
 *   URL:  /user/{uid}/cancel
 */
function best_responsive_sub_form_user_cancel_confirm_form_alter(&$form, &$form_state, $form_id) {
    $content = <<<EOT
        <p id="fdv-intro-text">We are sorry to see you go, but if you are absolutely sure you want to completely delete your <span class="fdv-brand-text">5&deg; Vegan</span> account, just click the button below.</p>
EOT;
        $form['fdv_intro_text'] = array(
            '#markup' => $content,
            '#weight' => -20
        );

    // Tweak other form fields:
    $form['description']['#markup'] = 'Your account will be blocked and you will no longer be able to log in.<br />This action cannot be undone.';
    $form['actions']['submit']['#value'] = 'Delete My Account Completely';
}

/**
 * Re-theme the user-login, user-register-form, and user-pass pages.
 *   URLs:  /user/login, /user/register, user/password
 */
function best_responsive_sub_theme() {
    $items = array();

    $items['user_login'] = array(
        'render element' => 'form',
        'path' => drupal_get_path('theme', 'best_responsive_sub') . '/templates',
        'template' => 'user-login',
        'preprocess functions' => array(
            'best_responsive_sub_preprocess_user_login'
        ),
    );
    $items['user_register_form'] = array(
        'render element' => 'form',
        'path' => drupal_get_path('theme', 'best_responsive_sub') . '/templates',
        'template' => 'user-register-form',
        'preprocess functions' => array(
            'best_responsive_sub_preprocess_user_register_form'
        ),
    );
    $items['user_pass'] = array(
        'render element' => 'form',
        'path' => drupal_get_path('theme', 'best_responsive_sub') . '/templates',
        'template' => 'user-pass',
        'preprocess functions' => array(
            'best_responsive_sub_preprocess_user_pass'
        ),
    );
    return $items;
}

function best_responsive_sub_preprocess_user_login(&$vars) {
    $content = <<<EOT
        <p id="fdv-user-register">If you don't already have a <span class="fdv-brand-text">5&deg; Vegan</span> account, you can create one now, FREE!</p>
        <a class="fdv-btn full-width" href="/user/register">Create an account</a>
EOT;
    $vars['bottom_text'] = t($content);

    // Tweak other form fields:
    $vars['form']['name']['#size'] = FDV_USER_FIELD_SIZE;
    $vars['form']['name']['#description'] = '';
    $vars['form']['pass']['#size'] = FDV_USER_FIELD_SIZE;
    $vars['form']['pass']['#description'] = '';
    // Make Username & Password fields appear on one line each:
    //$vars['form']['name']['#attributes']['class'][] = 'container-inline';
    //$vars['form']['pass']['#attributes']['class'][] = 'container-inline';
}

function best_responsive_sub_preprocess_user_register_form(&$vars) {
    $content = <<<EOT
        <div>Register for a new <span class="fdv-brand-text">5&deg; Vegan</span> account, it's completely FREE!</div>
        <div>Be the envy of all your friends as you show off your masterful knowledge of vegan actors and movie connections.</div>
        <div>Also, as we add new functionality, you will see it sooner than other lowly non-members.</div>

        <div>We will never share your email address with any other company or send you any spam.</div>
EOT;
    $vars['intro_text'] = t($content);

    // Tweak other form fields:
    $vars['form']['account']['name']['#size'] = FDV_USER_FIELD_SIZE;
    $vars['form']['account']['mail']['#size'] = FDV_USER_FIELD_SIZE;
    $vars['form']['account']['mail']['#description'] = 'You must use a valid e-mail address you have access to. After you register we will send you an email with a link for you to set your password. We will not make your e-mail address public, and will only send you emails you specifically request (like password-resets), and (very rarely) notifications of important news and new functionality announcements. You can cancel your account at any time.';
}

function best_responsive_sub_preprocess_user_pass(&$vars) {
    if (user_is_logged_in()) {
        $content = <<<EOT
            <p>No worries, if you have forgotten your <span class="fdv-brand-text">5&deg; Vegan</span> password, just click the button below and we will send you a link to reset it.</p>
EOT;
    } else {
        $content = <<<EOT
            <p>No worries, if you have forgotten your password, just enter your <span class="fdv-brand-text">5&deg; Vegan</span> username or e-mail address and we will send you a link to reset it.</p>
EOT;
    }
    $vars['intro_text'] = t($content);

    // Tweak form field:
    $vars['form']['name']['#size'] = FDV_USER_FIELD_SIZE;
}


/**
 * Allows html special chars to be used in page titles.
 */
function bb2html($text) {
    $bbcode = array(
        "[strong]", "[/strong]",
        "[b]", "[/b]",
        "[u]", "[/u]",
        "[i]", "[/i]",
        "[em]", "[/em]",
        "[amp]", "[theta]", "[degree]", "[prime]", "[doubleprime]", "[squareroot]"
    );
    $htmlcode = array(
        "<strong>", "</strong>",
        "<strong>", "</strong>",
        "<u>", "</u>",
        "<em>", "</em>",
        "<em>", "</em>",
        "&amp;", "&theta;", "&#176;", "&prime;", "&Prime;", "&radic;"
    );
    return str_replace($bbcode, $htmlcode, $text);
}
function bb_strip($text) {
    $bbcode = array(
        "[strong]", "[/strong]",
        "[b]", "[/b]",
        "[u]", "[/u]",
        "[i]", "[/i]",
        "[em]", "[/em]",
        "&amp;", "&theta;", "&#176;", "&prime;", "&Prime;", "&radic;"
    );
    return str_replace($bbcode, '', $text);
}

