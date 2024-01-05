<?php
/**
 * fdvegan_blocks.php
 *
 * Implementation of all fdvegan Drupal Blocks' content.
 *
 * PHP version 5.6
 *
 * @category   Install
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.5
 */


    /**
     * Implements hook_block_info().
     *
     * @see https://api.drupal.org/api/drupal/modules%21block%21block.api.php/function/hook_block_info/7.x
     */
    function fdvegan_block_info() {
        $blocks = array();
        $blocks['fdvegan_slider_block'] = array(
            'info'       => t('FDV Slider Block'),
            'cache'      => DRUPAL_CACHE_GLOBAL,
            'status'     => TRUE,  // enabled
            'region'     => 'content',  // see {theme}.info file
            'visibility' => BLOCK_VISIBILITY_LISTED,
            'pages'      => '<front>',
            'weight'     => 20,
        );
        $blocks['fdvegan_sidebar_block_1'] = array(
            'info'       => t('FDV Sidebar Block 1'),
            'cache'      => DRUPAL_NO_CACHE,
            'status'     => TRUE,  // enabled
            'region'     => 'sidebar_first',
            'visibility' => BLOCK_VISIBILITY_NOTLISTED,
            'pages'      => '',
            'weight'     => 50,
        );
        $blocks['fdvegan_sidebar_block_2'] = array(
            'info'       => t('FDV Sidebar Block 2'),
            'cache'      => DRUPAL_CACHE_PER_ROLE,
            'status'     => TRUE,  // enabled
            'region'     => 'sidebar_first',
            'visibility' => BLOCK_VISIBILITY_NOTLISTED,
            'pages'      => '',
            'weight'     => 60,
        );
        $blocks['fdvegan_sidebar_block_3'] = array(
            'info'       => t('FDV Sidebar Block 3'),
            'cache'      => DRUPAL_CACHE_PER_ROLE,
            'status'     => TRUE,  // enabled
            'region'     => 'sidebar_first',
            'visibility' => BLOCK_VISIBILITY_NOTLISTED,
            'pages'      => 'actor-network',  // Don't show on the /actor-network page.
            'weight'     => 70,
        );
        $blocks['fdvegan_social_block'] = array(
            'info'       => t('FDV Social Block'),
            'cache'      => DRUPAL_CACHE_PER_ROLE,
            'status'     => TRUE,  // enabled
            'region'     => 'sidebar_first',
            'visibility' => BLOCK_VISIBILITY_NOTLISTED,
            'pages'      => '',
            'weight'     => 80,
        );
        $blocks['fdvegan_better_than_block'] = array(
            'info'       => t('FDV Better Than Bacon Block'),
            'cache'      => DRUPAL_CACHE_PER_ROLE,
            'status'     => FALSE,  // disabled
            'region'     => 'sidebar_first',
            'visibility' => BLOCK_VISIBILITY_NOTLISTED,
            'pages'      => '',
            'weight'     => 90,
        );
        $blocks['fdvegan_sense_block'] = array(  // Google AdSense
            'info'       => t('FDV AdSense Block'),
            'cache'      => DRUPAL_NO_CACHE,
            'status'     => FALSE,  // disabled
            'region'     => 'sidebar_first',
            'visibility' => BLOCK_VISIBILITY_NOTLISTED,
            'pages'      => '',
            'weight'     => 100,
        );
        $blocks['fdvegan_footer_block'] = array(
            'info'       => t('FDV Footer Block'),
            'cache'      => DRUPAL_CACHE_GLOBAL,
            'status'     => TRUE,  // enabled
            'region'     => 'footer',
            'visibility' => BLOCK_VISIBILITY_NOTLISTED,
            'pages'      => '',
            'weight'     => 20,
        );
        return $blocks;
    }


    /**
     * Implements hook_block_view().
     */
    function fdvegan_block_view($delta = '') {
        $block = array();

        switch ($delta) {
            case 'fdvegan_slider_block':
                $block['subject'] = '';
                $block['content'] = _fdvegan_slider_block_content();
                break;
            case 'fdvegan_sidebar_block_1':
                $block['subject'] = '';
                $block['content'] = _fdvegan_sidebar_block_1_content();
                break;
            case 'fdvegan_sidebar_block_2':
                $block['subject'] = '';
                $block['content'] = _fdvegan_sidebar_block_2_content();
                break;
            case 'fdvegan_sidebar_block_3':
                if (user_access('use fdvegan')) {
                    $block['subject'] = '';
                    $block['content'] = _fdvegan_sidebar_block_3_content();
                }
                break;
            case 'fdvegan_social_block':
                $block['subject'] = '';
                $block['content'] = _fdvegan_social_block_content();
                break;
            case 'fdvegan_better_than_block':
                $block['subject'] = '';
                $block['content'] = _fdvegan_better_than_block_content();
                break;
            case 'fdvegan_sense_block':
                $block['subject'] = '';
                $block['content'] = _fdvegan_sense_block_content();
                break;
            case 'fdvegan_footer_block':
                $block['subject'] = '';
                $block['content'] = _fdvegan_footer_block_content();
                break;
        }
        return $block;
    }


     /**
      * Note - Slideshow files are automatically read from the /sites/default/files/front_slider_images dir.
      *        Any image files named "front-slide-*.png" will be used.
      */
    function _fdvegan_slider_block_content() {

        $output = '';
        // Do not show the slider on small mobile device screens.
        $detect = mobile_detect_get_object();
        //fdvegan_Content::syslog('LOG_DEBUG', "mobile_detect_get_object(): is_mobile={$detect->isMobile()}, is_tablet={$detect->isTablet()}.");
        if (!$detect->isMobile() && user_access('pro fdvegan')) {
            $slider_dir = variable_get('file_public_path', conf_path() . '/files') . '/front_slider_images';
            $files = file_scan_directory($slider_dir, '/front-slide-.+\.(png|jpg|gif)$/');
            ksort($files);  // Sort by filename, just to be sure they are in order.
            $output .= <<<EOT
    <div id="home-slider" class="mobile-hide">
        <div class="flexslider-container">
            <div id="single-post-slider" class="flexslider">
              <ul class="slides">
EOT;
            $loop = 0;
            foreach ($files as $absolute => $file_obj) {
                $output .= '                <li class="slide"><img src="' . $absolute . '" alt="Slide ' . ++$loop . '"/></li>' . "\n";
            }

            $output .= <<<EOT
                </ul><!-- /slides -->
            </div><!-- /flexslider -->
        </div>
    </div>
EOT;
        }
        return $output;
    }


    /**
     * Ad content for "Go Vegan" or "Veganuary".
     */
    function _fdvegan_sidebar_block_1_content() {

        $show_ad_num = 1;  // Default to showing "Go Vegan" ad.
        if (mt_rand(1,4) > 3) {  // 20% of time show "Veganuary" ad.
            if (mt_rand(1,5) > 4) {  // 5% of time show no ad.
                $show_ad_num = 0;
            } else {
                $show_ad_num = 2;
            }
        }

        switch ($show_ad_num) {
            case 1:  // Show "Go Vegan" ad.
                // The original img URL from https://www.chooseveg.com/ was https://mfa.cachefly.net/chooseveg/images/uploads/2016/03/vsg-button-en.png
                $img_tag = theme('image', array(
                                 'path' => 'public://pictures/chooseveg-com_order.png',
                                 'alt' => t('Order your FREE Vegetarian Starter Guide today!'),
                                 'attributes' => array('class' => 'fdvegan-go-veg-img',
                                                      ),
                                ));
                $output  = l($img_tag,
                             'https://www.chooseveg.com/vsg',
                             array('html' => TRUE,
                                   'external' => TRUE,
                                   'attributes' => array('target'=> '_blank',
                                                         'rel'   => 'external',
                                                         'title' => t('Choose Veg'),
                                                         'class' => 'fdvegan-go-veg-block',
                                                        ),
                                  )
                            );
            break;

            case 2:  // Show "Veganuary" ad.
                // The original img URL from https://veganuary.com/ was https://ieatgrass.com/wp-content/uploads/2014/12/1526273_196910863848359_1670673407_n.jpg
                // or https://veganuary.com/wp-content/uploads/2016/07/veganuary_logo_pantone_814-Converted1.jpg
                // or https://www.healthhampers.com/wp-content/uploads/2017/01/1066.jpg
                $use_img_num = mt_rand(1,3);
                $img_tag = theme('image', array(
                                 'path' => 'public://pictures/veganuary-com_'.$use_img_num.'.png',
                                 'alt' => t('Everyone should try vegan this January!'),
                                 'attributes' => array('class' => 'fdvegan-veganuary-img',
                                                      ),
                                ));
                $output  = l($img_tag,
                             'https://veganuary.com/',
                             array('html' => TRUE,
                                   'external' => TRUE,
                                   'attributes' => array('target'=> '_blank',
                                                         'rel'   => 'external',
                                                         'title' => t('Try Veganuary'),
                                                         'class' => 'fdvegan-veganuary-block',
                                                        ),
                                  )
                            );
            break;

            case 0:  // Show no ad.
            default:
                $output = '';
        }

        return $output;
    }


    /**
     * Ad content for "Your Daily Vegan".
     */
    function _fdvegan_sidebar_block_2_content() {
        $img_tag = theme('image', array(
                         'path' => 'public://pictures/dailyvegan-com_picks.png',
                         'alt' => t('Vegan Netflix Streaming Guide'),
                         'attributes' => array('class' => 'fdvegan-your-daily-vegan-img',
                                              ),
                        ));
        $output  = l($img_tag,
                     'https://www.yourdailyvegan.com/vegan-netflix-guide/',
                     array('html' => TRUE,
                           'external' => TRUE,
                           'attributes' => array('target'=> '_blank',
                                                 'title' => t('A current list of vegan-centric titles now streaming'),
                                                 'class' => 'fdvegan-your-daily-vegan-block',
                                                ),
                          )
                    );
        return $output;
    }


    /**
     * Ad content for "vegan_connections_lv1_sidebar".
     */
    function _fdvegan_sidebar_block_3_content() {
        $show_ad_num = 1;  // Default to showing "View All Actors Network" ad.
        if (mt_rand(1,4) > 3) {  // 20% of time show no ad.
            $show_ad_num = 0;
        }

        switch ($show_ad_num) {
            case 1:  // Show "View All Actors Network" ad.
                $img_tag = theme('image', array(
                                 'path' => 'public://pictures/vegan_connections_lv1_sidebar.png',
                                 'alt' => t('View the entire Actor Network'),
                                 'attributes' => array('class' => 'fdvegan-actor_network-img',
                                                      ),
                                ));
                $output  = l($img_tag,
                             '/actor-network',
                             array('html' => TRUE,
                                   'attributes' => array('title' => t('The entire Actor Network visualized'),
                                                         'class' => 'fdvegan-actor-network-block',
                                                        ),
                                  )
                            );
            break;

            case 0:  // Show no ad.
            default:
                $output = '';
        }

        return $output;
    }


    /**
     * Display Social Media buttons.
     * E.g.: AddToAny button, FaceBook, Pinterest, Twitter, etc.
     * Note - For now, this simply adds hidden content to help support the AddToAny block.
     *        This data is also somewhat deprecated due to the use of the Metatag module.
     */
    function _fdvegan_social_block_content() {
        $site_url = 'public://fdvegan_site.png';
        $transp_img_tag = theme('image', array(
                                'path' => 'public://pictures/transp.gif',
                                'alt'  => t('Five Degrees of Vegan'),
                                'attributes' => array('data-pin-url' => 'https://fivedegreevegan.aprojects.org/',
                                                      'data-pin-media' => $site_url,
                                                      'data-pin-description' => 'Five Degrees of Vegan',
                                                     ),
                               ));
        $site_img_tag = theme('image', array(
                              'path' => $site_url,
                              'alt'  => t('Five Degrees of Vegan'),
                             ));

        // Pinterest, FaceBook
        $output = '<div style="display: none">' . $transp_img_tag . $site_img_tag . '</div>';
        return $output;
    }


    function _fdvegan_better_than_block_content() {
        $img_tag = theme('image', array(
                         'path' => 'public://pictures/smiley.png',
                         'alt' => t('Smile'),
                         'attributes' => array('class' => 'fdvegan-smiley-img',
                                              ),
                        ));
        $output = <<<EOT
        <div id="home-blurb">
            <div class="fdv-toggle-btn">
                What's better than bacon?
            </div>
            <div class="fdv-toggle-btn fdv-toggle" style="display:none">
                Every actor and actress on this website! &nbsp;{$img_tag}
            </div>
        </div>
        <div class="clear"></div>
EOT;
        return $output;
    }


    /**
     * Display Google AdSense content.
     * This only displays ads on particular pages, and then only rarely.
     */
    function _fdvegan_sense_block_content() {

        $output = '<div class="fdv-sense fdv-no-print">';
        if (user_access('pro fdvegan')) {
            $output .= 'Thank you for being a Pro member.';
        } elseif (user_is_logged_in()) {
            //$output .= 'Thank you for being a registered member.';
        } elseif (!drupal_is_front_page()) {
            $chances_are = 60;  // Default to showing ads less than 40% of the time.
            if (drupal_lookup_path('alias', current_path()) == 'how-help') {
                $chances_are -= 10;  // Show ads 10% more often on the "How Can I Help?" page.
            }
            $detect = mobile_detect_get_object();
            if ($detect->isMobile() || $detect->isTablet()) {
                $chances_are += 5;  // Show ads 5% less often for mobile or tablet users.
            }
            // Use $_SERVER['HTTP_ACCEPT_LANGUAGE'] to quickly guess user's country location.
            if (locale_language_from_browser(language_list()) !== 'en') {
                $chances_are -= 10;  // Show ads 10% more often for non-US users.
            }
            if (mt_rand(1,100) > $chances_are) {
                // <!-- fivedegreevegan_sidebar-right_AdSense_250x300_as -->
                $output .= '
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- fivedegreevegan_sidebar-right_AdSense_250x300_as -->
<ins class="adsbygoogle"
     style="display:inline-block;width:250px;height:300px"
     data-ad-client="ca-pub-7197934182497223"
     data-ad-slot="3234592692"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
';
            }
        }
        $output .= '</div>
';
        return $output;
    }


    function _fdvegan_footer_block_content() {
        $front_page_link = l('5&deg;V',
                             '<front>',
                             array('html' => TRUE,
                                   'attributes' => array('title' => t('Five Degree Vegan'),
                                                        ),
                                  )
                            );
        $copyright_content = '<span class="fdv-copyright">' . t('Copyright') . ' &copy; ' . date("Y") . ' ' . $front_page_link . '</span>';
        $tmdb_link = l(t('TMDb'),
                       'https://www.themoviedb.org/',
                       array('html' => TRUE,
                             'external' => TRUE,
                             'attributes' => array('target'=> '_blank',
                                                   'title' => t('The Movie Database'),
                                                  ),
                            )
                      );
        $tmdb_content = '<span class="fdv-tmdb">' . t('Initial data provided by ') . $tmdb_link . '</span>';
        $contact_link = l(t('Contact Us'),
                          'contact',
                          array('html' => TRUE,
                                'external' => FALSE,
                                'attributes' => array('title' => t('Send us your feedback'),
                                                     ),
                               )
                         );
        $contact_content = '<span class="fdv-contact">' . $contact_link . '</span>';

        $output = '
    <div id="fdv-footer">
    ' . $copyright_content . $tmdb_content . $contact_content . '
    </div>
                  ';

        return $output;
    }

