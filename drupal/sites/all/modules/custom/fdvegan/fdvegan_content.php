<?php
/**
 * fdvegan_content.php
 *
 * Implementation of all output "View" for module fdvegan.
 *
 * PHP version 5.6
 *
 * @category   Content
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 */


class fdvegan_Content
{
    /**
     * Called from fdvegan.module::fdvegan_actor_form()
     *
     * @return string    Content
     */
    public static function getActorContent($options = NULL)
    {
        if (!isset($options['PersonId']) && !isset($options['FullName'])) {
            fdvegan_Content::syslog('LOG_ERR', 'getActorContent() invalid options provided: ' . print_r($options,1));
            return self::getSystemErrorContent($options);
            //throw new FDVegan_InvalidArgumentException('No actor name provided for content');
        }
        try {
            $person = new fdvegan_Person($options);
        }
        catch (FDVegan_NotFoundException $e) {  // No person found
            return self::getActorNotFoundContent($options);
        }

        drupal_add_js(path_to_theme() . '/js/fdvegan_movie.js');
        drupal_add_js(path_to_theme() . '/js/fdvegan_person.js');

        $veg_status = $person->getVegTagName();  // Retrieve only one tag string, not all.

        $tmdb_tag = '';
        if (!empty($person->getTmdbInfoUrl())) {
            $tmdb_link = l('More info on TMDb',
                           $person->getTmdbInfoUrl(),
                           array('attributes' => array('target' => '_blank')));
            $tmdb_tag = '<span class="small">'. $tmdb_link .'.</span><br />';
        }
        $imdb_tag = '';
        if (!empty($person->getImdbInfoUrl())) {
            $imdb_link = l('See info on IMDb',
                           $person->getImdbInfoUrl(),
                           array('attributes' => array('target' => '_blank')));
            $imdb_tag = '<span class="small">'. $imdb_link .'.</span><br />';
        }
        $img_tag = theme('image', array(
                            'path' => $person->getImagePath('medium'),
                            'alt'  => htmlspecialchars($person->fullname),
                            'attributes' => array('class' => array('fdvegan-person','fdvegan-person-expand-m-to-l'),
                                                  'data-hover-src' => $person->getImagePath('large'),
                                ),
                    ));

        $tags_content = '';
        $tags = $person->getTags();  // Get all tags.
        if (!empty($tags) && $tags->count()) {
            $tags_content = '<ul class="fdv-person-tags">';
            foreach($tags->getItems() as $tag) {
                $person_tag_img_tag = theme('image', array(
                                            'path'  => path_to_theme() . "/images/tags/{$tag->tagName}.png",
                                            'alt'   => $tag->tagName,
                                            'title' => $tag->tagName,
                                            'attributes' => array('class' => "fdv-person-tag-{$tag->tagName}",
                                                ),
                                           ));
                $tags_content .= "<li>{$person_tag_img_tag}</li>";
            }
            $tags_content .= '</ul>';
        }

        $quote_text = '';
        if (!empty($person->getQuoteText())) {
            $quote_text = '"'. $person->getQuoteText() .'" - '. substr($person->firstName,0,1) .'. '. $person->lastName;
        }
        $homepage_tag = '-';
        if (!empty($person->getHomepageUrl())) {
            $homepage_tag = l($person->getHomepageUrl(),
                              $person->getHomepageUrl(),
                              array(
                                    'external' => TRUE,
                                    'attributes' => array('target'=> '_blank',
                                                          'rel'   => 'external',
                                                          'title' => t("Website dedicated to {$person->fullname}"),
                                                         ),
                             ));
        }
        $birthday_text   = empty($person->getBirthday()) ? '-' : $person->getBirthday();
        $birthplace_text = empty($person->getBirthplace()) ? '-' : $person->getBirthplace();
        $biography_text  = empty($person->getBiography()) ? 'No biography info.' : $person->getBiography();
        $content = <<<EOT
            <div class="row">
                <div class="left">
                    <span class="label">Actor:</span> <span class="value">{$person->fullname}</span><br />
                    <span class="label">Status:</span> <span class="value">{$veg_status}</span><br />
                    <span class="label">{$tags_content}</span><br />
                    {$tmdb_tag}
                    {$imdb_tag}
                    <span class="fdvegan-person-quote">{$quote_text}</span><br />
                </div>
                <div class="right">{$img_tag}</div>
            </div>
            <div class="row">
                <div class="left">
                    <span class="label">Official Homepage:</span> <span class="value">{$homepage_tag}</span><br />
                    <span class="label">Birthday:</span> <span class="value">{$birthday_text}</span><br />
                    <span class="label">Birthplace:</span> <span class="value">{$birthplace_text}</span><br />
                </div>
            </div>
            <div class="row fdvegan-person-biography-row">
                <h4 class="fdvegan-person-biography-header">Biography</h4>
                <div class="fdvegan-person-biography-text">{$biography_text}</div>
            </div>
EOT;

        $credits = $person->getCredits();

        $header = array('',
                        array('data' => t('Release Date')/*, 'field' => 'release_date'*/, 'sort' => 'desc'),
                        array('data' => t('Movie'),/* 'field' => 'movie_title'*/),
                        array('data' => t('Character'),/* 'field' => 'character'*/),
                       );
        $sticky = TRUE;
        $empty_msg = t('No movies found.');
        $rows = array();
        foreach ($credits as $credit) {
            $image_tag = theme('image', array(
//                                   'style_name' => 'thumbnail',
                                   'path'   => $credit->movie->getImagePath('movie', 'small'),
                                   'alt'    => htmlspecialchars($credit->movie->title),
//                                   'title' => htmlspecialchars($credit->movie->title),
                                   'attributes' => array('class' => array('fdvegan-movie-icon','fdvegan-movie-expand-s-to-m'),
                                                         'data-hover-src' => $credit->movie->getImagePath('movie', 'medium'),
                                       ),
                              ));
            $image_link = l($image_tag,
                            'movie',
                            array('html' => TRUE,
                                  'query' => array('movie_id' => $credit->movie->movieId)
                                 )
                           );
            $movie_text = $credit->movie->title;
            // Get the fdvegan-level-micro-icon fdvegan_level_micro_icon_*.gif if any.
            $movie_text .= ' &nbsp;'. fdvegan_Content::getFdvCountImageTag($credit->movie);
            $movie_link = l($movie_text,
                            'movie',
                            array('html' => TRUE,
                                  'query' => array('movie_id' => $credit->movie->movieId)
                                 )
                           );
            $character_link = $credit->Character;
            $rows[] = array('data' => array($image_link,
                                            $credit->movie->releasedate,
                                            $movie_link,
                                            $character_link
                                      )
            );
        }
        $credits_table_cache = array('keys' => array('getActorContent', $person->getPersonId()),
                                     'granularity' => DRUPAL_CACHE_PER_USER,
                                     'expire' => CACHE_TEMPORARY
                                     );
        $credits_table = array('#theme'      => 'table',
                               '#attributes' => array('class' => array('tablesorter')),  // see module "tablesorter"
                               '#header'     => $header,
                               '#rows'       => $rows,
                               '#empty'      => $empty_msg,
                               '#caption'    => 'Associated with movies:',
                               '#jsorted'    => TRUE,  // see module "tablesorter"
//                                   '#sticky'     => $sticky,  // does not work well with module "tablesorter"
//                                   '#cache' => $credits_table_cache,  // seems to mess up the #sticky after reload
                              );

        $content .= drupal_render($credits_table);

        //
        // Add the Actor Tree visualization, if desired:
        //
        if (module_exists('fdvegan_rest_api')) {
            if (user_access('use fdvegan') || fdvegan_Util::isEnvLTE('DEV')) {  // only particular users can see this new functionality

                $depth = 5;
                drupal_add_css(path_to_theme() . '/css/style_graph.css');
                drupal_add_js(array('fdvegan' => array('person_id' => $person->getPersonId())), array('type' => 'setting'));
                drupal_add_js(array('fdvegan' => array('depth' => $depth)), array('type' => 'setting'));
                /* On mobile devices only show the "View Actor Tree full-window" button,
                 * otherwise, show both the dialog-popup & full-window buttons.
                 */
                $detect = mobile_detect_get_object();
                if ($detect->isMobile()) {
                    $content .= <<<EOT
                                <input type="button" id="fdvegan-actor-tree-modal-btn"
                                 value="View {$depth}&deg;V Actor Tree"
                                 onclick="window.location='actor-tree?person_id={$person->getPersonId()}&depth={$depth}';" />
EOT;
                } else {
                    drupal_add_library('system', 'ui.dialog');
                    $content .= <<<EOT
                                <div id="fdvegan-actor-tree-modal"></div>
                                <input type="button" id="fdvegan-actor-tree-modal-btn"
                                 value="View {$depth}&deg;V Actor Tree" />
EOT;
                    $tree_image_tag = theme('image', array(
                                           'path'   => '/sites/all/themes/best_responsive_sub/images/popout-icon.png',
                                           'alt'    => 'View Actor Tree full-window',
                                           //'title' => 'View Actor Tree full-window',
                                           //'attributes' => array('class' => 'fdv-popout-icon'),
                                      ));
                    $tree_image_link = l($tree_image_tag,
                                         'actor-tree',
                                         array('html' => TRUE,
                                               'attributes' => array('class' => 'fdv-popout-icon'),
                                               'query' => array('person_id' => $person->getPersonId(),
                                                                'depth'     => $depth)
                                              )
                                        );
                    $content .= $tree_image_link;
                }
            }
        }

        return $content;
    }


    /**
     * Called from fdvegan.module::fdvegan_movie_form()
     *
     * @return string    Content
     */
    public static function getMovieContent($options = NULL)
    {
        if (!isset($options['MovieId']) && !isset($options['Title'])) {
            fdvegan_Content::syslog('LOG_ERR', 'getMovieContent() invalid options provided: ' . print_r($options,1));
            return self::getSystemErrorContent($options);
            //throw new FDVegan_InvalidArgumentException('No movie title provided for content');
        }
        try {
            $movie = new fdvegan_Movie($options);
        }
        catch (FDVegan_NotFoundException $e) {  // No person found
            return self::getMovieNotFoundContent($options);
        }

        drupal_add_js(path_to_theme() . '/js/fdvegan_movie.js');

        $tmdb_link = '';
        if (!empty($movie->getTmdbInfoUrl())) {
            $tmdb_link = l('More info on TMDb',
                           $movie->getTmdbInfoUrl(),
                           array('attributes' => array('target'=>'_blank')));
        }
        $imdb_link = '';
        if (!empty($movie->getImdbInfoUrl())) {
            $imdb_link = l('View info on IMDb',
                           $movie->getImdbInfoUrl(),
                           array('attributes' => array('target'=>'_blank')));
        }
        $trailer_link = '';
        if (!empty($movie->getTmdbInfoUrl()) && user_access('use fdvegan')) {
            drupal_add_js(path_to_theme() . '/js/fdvegan_movie_trailer.js');
            $trailer_link = l('View the trailer on YouTube',
                              '#',  // This link will be updated by javascript in .../js/fdvegan_movie_trailer.js
                              array('html' => TRUE,
                                    'attributes' => array('target' => '_blank',
                                                          'onClick' => 'return false;',
                                                         )
                             ));
        }
        $homepage_tag = '-';
        if (!empty($movie->getHomepageUrl())) {
            $homepage_tag = l($movie->getHomepageUrl(),
                              $movie->getHomepageUrl(),
                              array(
                                    'external' => TRUE,
                                    'attributes' => array('target'=> '_blank',
                                                          'title' => t("Website dedicated to {$movie->title}"),
                                                         ),
                             ));
        }
        $adult_rated_tag = '';
        if ($movie->getAdultRated()) {
            $adult_img_tag = theme('image', array(
                                'path' => fdvegan_Util::getStandardImageUrl('movie_rating_R.gif'),
                                'alt'  => 'Adult-rated movie',
                                'attributes' => array('class' => 'fdvegan-movie-adult-rated',
                                    ),
                        ));
            $adult_rated_tag = "<span class=\"label\">Rating:</span> <span class=\"value\">{$adult_img_tag}</span><br />";
        }
        $tagline_text = empty($movie->getTagline()) ? '' : $movie->getTagline();
        $status_tag = '';
        if (!empty($movie->getStatus()) && ($movie->getStatus() != 'Released')) {
            $status_tag = "<span class=\"label\">Status:</span> <span class=\"value\">{$movie->status}</span><br />";
        }
        $runtime_tag = '';
        $runtime_mins = $movie->getRuntime();
        if (!empty($runtime_mins)) {
            $runtime_text = fdvegan_Util::convertMinutesToHumanReadable($runtime_mins);
            $runtime_tag = "<span class=\"label\">Runtime:</span> <span class=\"value\">{$runtime_text}</span><br />";
        }
        $budget_text   = empty($movie->getBudget()) ? '-' : '$' . number_format($movie->getBudget());
        $revenue_text  = empty($movie->getRevenue()) ? '-' : '$' . number_format($movie->getRevenue());
        $overview_text = empty($movie->getOverview()) ? 'No overview text.' : $movie->getOverview();
        $img_tag = theme('image', array(
                            'path' => $movie->getImagePath('movie', 'medium'),
                            'alt'  => htmlspecialchars($movie->title),
                            'attributes' => array('class' => array('fdvegan-movie','fdvegan-movie-expand-m-to-l'),
                                                  'data-hover-src' => $movie->getImagePath('movie', 'large'),
                                ),
                    ));
        $content = "\n<div id=\"fdvegan-movie-data\" data-movie_id=\"{$movie->getMovieId()}\"" .
                   " data-title=\"" . urlencode($movie->Title) . "\"></div>";
        $content .= <<<EOT

            <div class="row">
                <div>
                    <span class="label">Movie:</span> <span class="value">{$movie->title}</span>
                </div>
            </div>
            <div class="row">
                <div class="left">
                    <span class="label">Release Date:</span> <span class="value">{$movie->releasedate}</span><br />
                    {$adult_rated_tag}
                    {$status_tag}
                    {$runtime_tag}
                    <span class="small">{$tmdb_link}.</span><br />
                    <span class="small">{$imdb_link}.</span><br />
                    <div id="fdvegan-movie-trailer"><span class="small">{$trailer_link}.</span><br /></div>
                    <span class="fdvegan-movie-tagline">{$tagline_text}</span><br />
                </div>
                <div class="right">
                    {$img_tag}
                </div>
            </div>
            <div class="row">
                <div class="left">
                    <span class="label">Official Homepage:</span> <span class="value">{$homepage_tag}</span><br />
                    <span class="label">Budget:</span> <span class="value">{$budget_text}</span><br />
                    <span class="label">Revenue:</span> <span class="value">{$revenue_text}</span><br />
                </div>
            </div>
            <div class="row fdvegan-movie-overview-row">
                <h4 class="fdvegan-movie-overview-header">Overview</h4>
                <div class="fdvegan-movie-overview-text">{$overview_text}</div>
            </div>
EOT;

        // Build the entire Credits table:
        $content .= self::getPersonCollectionContent($movie->getCredits(), 'Veg*n actors:', TRUE);

        return $content;
    }


    /**
     * Called from fdvegan.module::fdvegan_actor_list() and via fdvegan_actor_form(), and self::getMovieContent()
     *
     * @return string    Content
     */
    public static function getPersonCollectionContent($person_collection, $caption=NULL, $credit_flag=FALSE)
    {
        drupal_add_js(path_to_theme() . '/js/fdvegan_person.js');
        $header = array('');
        if ($credit_flag) {
            $header[] = array('data' => t('Character')/*, 'field' => 'character'*/);
        }
        $header[] = array('data' => t('Actor')/*, 'field' => 'full_name'*/, 'sort' => 'desc');
        $header[] = array('data' => t('Veg*n?'));
//        $header[] = array('data' => t('More Info'));

        $sticky = TRUE;
        $empty_msg = t('No actors found.');
        $rows = array();
        foreach ($person_collection as $elem) {
            if ($credit_flag) {
                $person = $elem->Person;
            } else {
                $person = $elem;
            }
            $row_class = '';
            $name_link = l($person->fullName,
                           'actor',
                           array('query' => array('person_id' => $person->personId)));
            // Get the fdvegan-level-micro-icon fdvegan_level_micro_icon_*.gif if any.
            //$name_link .= ' &nbsp;'. fdvegan_Content::getFdvCountImageTag($person);
            $image_tag = theme('image', array(
//                                   'style_name' => 'thumbnail',
                                   'path'   => $person->getImagePath('small'),
                                   'alt'    => htmlspecialchars($person->fullName),
//                                   'title' => htmlspecialchars($person->fullName),
                                   'attributes' => array('class' => array('fdvegan-person-icon','fdvegan-person-expand-s-to-m'),
                                                         'data-hover-src' => $person->getImagePath('medium'),
                                       ),
                                  ));
            $image_link = l($image_tag,
                            'actor',
                            array('html' => TRUE,
                                  'query' => array('person_id' => $person->personId)
                                 )
                           );
            $tmdb_link = '';
            if (!empty($person->getTmdbInfoUrl())) {
                $tmdb_link = l('View on TMDb',
                               $person->getTmdbInfoUrl(),
                               array(
                                     'external' => TRUE,
                                     'attributes' => array('target' => '_blank',
                                                           'rel'    => 'external',
                                                           'title'  => t('More info on TMDb'),
                                                          ),
                                 ));
            }
            $imdb_link = '';
            if (!empty($person->getImdbInfoUrl())) {
                $imdb_link = l('View on IMDb',
                               $person->getImdbInfoUrl(),
                               array(
                                     'external' => TRUE,
                                     'attributes' => array('target' => '_blank',
                                                           'rel'    => 'external',
                                                           'title'  => t('More info on IMDb'),
                                                          ),
                                 ));
            }
            $tags_content = '-';
/*
            $tags = $person->getTags();  // Get all tags.
            if (!empty($tags) && $tags->count()) {
                $tags_content = '<ul class="fdv-person-tags">';
                foreach($tags->getItems() as $tag) {
                    $person_tag_img_tag = theme('image', array(
                                                'path'  => path_to_theme() . "/images/tags/{$tag->tagName}.png",
                                                'alt'   => $tag->tagName,
                                                'title' => $tag->tagName,
                                                'attributes' => array('class' => "fdv-person-tag-{$tag->tagName}",
                                                    ),
                                               ));
                    $tags_content .= "<li>{$person_tag_img_tag}</li>";
                }
                $tags_content .= '</ul>';
            }
*/
            $tag_name = $person->getVegTagName();  // Retrieve only one tag name, not all.
            if (!empty($tag_name)) {
                $person_tag_img_tag = theme('image', array(
                                            'path'  => path_to_theme() . "/images/tags/{$tag_name}.png",
                                            'alt'   => $tag_name,
                                            'title' => $tag_name,
                                            'attributes' => array('class' => "fdv-person-tag-{$tag_name}",
                                                ),
                                           ));
                $tags_content = "<span class=\"display-none\">{$tag_name}</span><ul class=\"fdv-person-tags\"><li>{$person_tag_img_tag}</li></ul>";
            }
            if ($person->getNumCredits() > 1) {
                $row_class = 'fdvegan-strong';
            }
            $row = array($image_link);
            if ($credit_flag) {
                $row[] = $elem->Character;
            }
            $row[] = $name_link;
            $row[] = $tags_content;
//            $row[] = empty($tmdb_link) ? $imdb_link : $tmdb_link;
            $rows[] = array('data'  => $row,
                            'class' => array($row_class)
            );
        }

        if ($credit_flag) {
            $keys = array('getMovieContent', $person_collection->getMovieId());
        } else {
            // For the "All Actors" page, we can cache it:
            $keys = array('getPersonContent', 'all');
            // @TODO - but this currently fails and messes up the cache if
            //         it was called for an actor search-by-name!
        }
        $actors_table_cache = array('keys'        => $keys,
                                    'granularity' => DRUPAL_CACHE_PER_USER,
                                    'expire'      => CACHE_TEMPORARY,
                                   );
        $actors_table = array('#theme' => 'table',
                              '#attributes' => array('class' => array('tablesorter'),  // see module "tablesorter"
                                                    ),
                              '#header'     => $header,
                              '#rows'       => $rows,
                              '#empty'      => $empty_msg,
                              '#caption'    => $caption,
                              '#jsorted'    => TRUE,  // see module "tablesorter"
//                              '#sticky'     => $sticky,  // does not work well with module "tablesorter"
//                            '#cache' => $actors_table_cache,  // seems to mess up the #sticky after reload
                             );

        return drupal_render($actors_table);
    }


    /**
     * Called from fdvegan.module::fdvegan_actor_network()
     *
     * @return string    Content
     */
    public static function getActorNetworkContent($options)
    {
        drupal_add_css(path_to_theme() . '/css/style_graph.css');
        drupal_add_js(array('fdvegan' => array('degrees' => $options['Degrees'])), array('type' => 'setting'));
        drupal_add_js(array('fdvegan' => array('max_degrees' => fdvegan_Util::getMaxAllowedDegrees())), array('type' => 'setting'));
        drupal_add_js('https://d3js.org/d3.v3.min.js', 'file');  // if want to use a CDN server
//        drupal_add_js('sites/default/files/graph/d3/d3.v3.min.js', 'file');  // if don't want to use a CDN
        drupal_add_js('sites/default/files/graph/fdvegan_rest_api_actor_network.js', 'file');

        drupal_add_library('system', 'ui.dialog');

        $help_modal_content = <<<EOT
            <div style="margin-bottom: 12px;">
                Use the "&deg;V" selector input at the top left of this page to view all of the different degree connections, from 1 to 5, for all the actors.
            </div>
            <div>This graph displays only actors that have at least one connection.</div>
EOT;
        $warning_popup_content = 'You can only view from 1 to 5 degree connections.';
        if (!user_is_logged_in()) {
            $link = l('login',
                      'user/login',
                      array('attributes' => array('title'  => t('Login or Signup for free!')),
                     ));
            $help_modal_content .= <<<EOT
                <div style="margin-top: 12px;">To view 3&deg; connections or higher you must {$link} (with a free account).</div>
EOT;
            $warning_popup_content = "To view 3&deg; connections or higher you must {$link} (with a free account).";
        } else if (!user_access('pro fdvegan')) {
            $link = l('Pro version',
                      'user/login',
                      array('attributes' => array('title' => t('Upgrade to a Pro account')),
                            'fragment' => 'fdv-bottom-text',
                           )
                     );
            $help_modal_content .= <<<EOT
                <div style="margin-top: 12px;">To view 4&deg; connections (and see other advanced functionality) you need to upgrade your account to the {$link}.</div>
EOT;
            $warning_popup_content = "To view 4&deg; connections (and see other advanced functionality) you need to upgrade your account to the {$link}.";
        }
        $content = '<div id="fdvegan-actor-network-help-modal">' . $help_modal_content . '</div>';
        $content .= '<div id="fdvegan-actor-network-warning-modal">' . $warning_popup_content . '</div>';
        // See: https://www.tjvantoll.com/2012/07/15/native-html5-number-picker-vs-jquery-uis-spinner-which-to-use/
        $deg_selector_widget = <<<EOT
            <input type="number" min="1" max="5" step="1" size="1" maxlength="1" value="{$options['Degrees']}" id="fdv-deg-selector" />
EOT;
        $content .= <<<EOT
<div class="fdv-actor-network-header">
  <div class="col1of3"><h1 class="fdv-actor-network-title">{$deg_selector_widget}&deg;V Actor Network</h1></div>
  <div class="col2of3"><div id="fdvegan-actor-network-top-text"></div></div>
  <div class="col3of3"><input type="button" id="fdvegan-actor-network-help-modal-btn" class="fdv-btn-short" value="Help" /></div>
</div>
<div id="fig" class="loading"></div>
EOT;
        return $content;
    }


    /**
     * Called from fdvegan.module::fdvegan_actor_tree()
     *
     * @return string    Content
     */
    public static function getActorTreeContent($options)
    {
        try {
            $person = new fdvegan_Person($options);
        }
        catch (FDVegan_NotFoundException $e) {  // No matching person found
            return fdvegan_Content::getActorNotFoundContent($options);
        }
        catch (Exception $e) {
            fdvegan_Content::syslog('LOG_ERR', 'error in getActorTreeContent() with options provided: ' . print_r($options,1));
            return fdvegan_Content::getSystemErrorContent($options);
        }

        drupal_add_css(path_to_theme() . '/css/style_graph.css');
        // URL param "a=1" means data-only ajax.
        $content = <<<EOT
            <div class="fdv-actor-tree-header">
                <a href="/actor?person_id={$person->personId}" class="fdv-actor-tree-actor-name">
                    $person->fullName
                </a>
                has {$person->getFdvCount()} connections within 1&deg;
            </div>
            <div class="fdv-actor-tree-wrapper">
                <iframe id="fdv-actor-tree-iframe" class="loading" src="/actor-tree-only?person_id={$options['PersonId']}&depth={$options['Depth']}&a=1"></iframe>
            </div>
EOT;
        return $content;
    }

    /**
     * Called from fdvegan.module::fdvegan_actor_tree_only()
     *
     * @return string    Content
     */
    public static function getActorTreeContentOnly($options)
    {
        try {
            $person = new fdvegan_Person($options);
            $options['PersonId'] = $person->personId;
        }
        catch (FDVegan_NotFoundException $e) {  // No person found
            return self::getActorNotFoundContent($options);
        }

        drupal_add_css(path_to_theme() . '/css/style_graph.css');
        drupal_add_js(array('fdvegan' => array('person_id' => $options['PersonId'])), array('type' => 'setting'));
        drupal_add_js(array('fdvegan' => array('depth' => $options['Depth'])), array('type' => 'setting'));
        drupal_add_js('https://cdnjs.cloudflare.com/ajax/libs/protovis/3.3.1/protovis.min.js', 'file');  // if want to use a CDN server
//        drupal_add_js('sites/default/files/graph/protovis/protovis.min.js', 'file');  // if don't want to use a CDN
        drupal_add_js('sites/default/files/graph/fdvegan_rest_api_actor_tree.js', 'file');

        /* No output is actually needed, we simply give this <div> tag as a grep-able placeholder
         * since a D3.js coder might search for it.
         */
        //$content = '<div id="fig" class="loading"></div>';
        $content = '';
        return $content;
    }


    /**
     * Called from fdvegan.module::fdvegan_movie_list() and fdvegan_movie_form()
     *
     * @return string    Content
     */
    public static function getMovieCollectionContent($movie_collection)
    {
        drupal_add_js(path_to_theme() . '/js/fdvegan_movie.js');
        $header = array('',
                        array('data' => t('Release Date')/*, 'field' => 'release_date'*/, 'sort' => 'desc'),
                        array('data' => t('Movie'),/* 'field' => 'movie_title'*/),
//                        array('data' => t('# of Vegans'),/* 'field' => 'fdv_count'*/),
                       );
        $sticky = TRUE;
        $empty_msg = t('No movies found.');
        $rows = array();
        foreach ($movie_collection as $movie) {
            $row_class = '';
            $image_tag = theme('image', array(
//                                   'style_name' => 'thumbnail',
                                   'path'   => $movie->getImagePath('movie', 'small'),
                                   'alt'    => htmlspecialchars($movie->title),
//                                   'title' => htmlspecialchars($movie->title),
                                   'attributes' => array('class' => array('fdvegan-movie-icon','fdvegan-movie-expand-s-to-m'),
                                                         'data-hover-src' => $movie->getImagePath('movie', 'medium'),
                                       ),
                              ));
            $image_link = l($image_tag,
                            'movie',
                            array('html' => TRUE,
                                  'query' => array('movie_id' => $movie->movieId)
                                 )
                           );
            $movie_text = $movie->title;
            // Get the fdvegan-level-micro-icon fdvegan_level_micro_icon_*.gif if any.
            $movie_text .= ' &nbsp;'. fdvegan_Content::getFdvCountImageTag($movie);
            $movie_link = l($movie_text,
                            'movie',
                            array('html' => TRUE,
                                  'query' => array('movie_id' => $movie->movieId)
                                 )
                           );
            $rows[] = array('data' => array($image_link,
                                            $movie->releasedate,
                                            $movie_link,
//                                            $movie->getNumCredits()
                                      ),
                            'class' => array($row_class)
            );
        }
        $movies_table = array('#theme'      => 'table',
                              '#attributes' => array('class' => array('tablesorter'),  // see module "tablesorter"
                                                    ),
                              '#header'     => $header,
                              '#rows'       => $rows,
                              '#empty'      => $empty_msg,
//                              '#caption'    => 'All Movies:',
                              '#jsorted'    => TRUE,  // see module "tablesorter"
//                                  '#sticky'     => $sticky,  // does not work well with module "tablesorter"
                             );

        $content = drupal_render($movies_table);

        if ($movie_collection->count() > 5) {
// @TODO            if ($movie_collection->count() <= $movie_collection->getTotalNumRows()) {
            if ($movie_collection->count() < $movie_collection->getLimit()) {
                $content .= <<<EOT
    <br />
    <div class="center small">Showing all {$movie_collection->count()} movies.</div>
EOT;
            } else {
                $content .= <<<EOT
    <br />
    <div class="center small">Showing first {$movie_collection->count()} movies.</div>
EOT;
            }
        }

        return $content;
    }


    /**
     * Called from fdvegan.module::fdvegan_play_view()
     *
     * @return string    Content
     */
    public static function getPlayGameContent($options)
    {
        global $user;
        $username = !empty($user->name) ? $user->name : t('Anonymous');
        drupal_add_css(path_to_theme() . '/css/style_play_game.css');
        drupal_add_js(array('fdvegan' => array('username' => $username)), array('type' => 'setting'));
        drupal_add_js(array('fdvegan' => array('degrees' => $options['Degrees'])), array('type' => 'setting'));
        drupal_add_js(array('fdvegan' => array('max_degrees' => fdvegan_Util::getMaxAllowedDegrees())), array('type' => 'setting'));
        drupal_add_js(array('fdvegan' => array('difficulty' => $options['Difficulty'])), array('type' => 'setting'));
        drupal_add_js(array('fdvegan' => array('round' => $options['Round'])), array('type' => 'setting'));
        drupal_add_js(array('fdvegan' => array('seconds_per_degree' => $options['SecondsPerDegree'])), array('type' => 'setting'));
        drupal_add_js(array('fdvegan' => array('seconds_remaining_in_round' => $options['SecondsRemainingInRound'])), array('type' => 'setting'));
        drupal_add_js(path_to_theme() . '/js/fdvegan_play_game.js', 'file');

        drupal_add_library('system', 'ui.dialog');

        $rules_modal_content = <<<EOT
            <div style="margin-bottom: 12px;">
                First, familiarize yourself with the <a href="/game" target="_blank">general rules of play</a>.<br />
                <br />
                A game consists of multiple rounds of guessing actors and movies.  First, choose the difficulty level of the game, then click the "Start the Game!" button.  The settings you initially choose will apply to all subsequent rounds of play.  This way, each round can be played by a different person at the same difficulty level.<br />
                <br />
                Finding more than 2&deg; connections can be extremely challenging, so don't be surprised if you get stumped.<br />
                On each round, you will be given the same number of hints to use if you need.  But remember, you'll only get a certain number of hints per day depending on what type of account you have.<br />
                <br />
                Good luck!
            </div>
EOT;
        $warning_popup_content = 'You can only play with 1 to 5 degree connections.';
        if (!user_is_logged_in()) {
            $link = l('login',
                      'user/login',
                      array('attributes' => array('title'  => t('Login or Signup for free!')),
                     ));
            $rules_modal_content .= <<<EOT
                <div style="margin-top: 12px;">To play with 3&deg; connections or higher you must {$link} (with a free account).</div>
EOT;
            $warning_popup_content = "To play with 3&deg; connections or higher you must {$link} (with a free account).";
        } else if (!user_access('pro fdvegan')) {
            $link = l('Pro version',
                      'user/login',
                      array('attributes' => array('title' => t('Upgrade to a Pro account')),
                            'fragment' => 'fdv-bottom-text',
                           )
                     );
            $rules_modal_content .= <<<EOT
                <div style="margin-top: 12px;">To play with 4&deg; connections (and see other advanced functionality) you need to upgrade your account to the {$link}.</div>
EOT;
            $warning_popup_content = "To play with 4&deg; connections (and see other advanced functionality) you need to upgrade your account to the {$link}.";
        }
        $content = '<div id="fdv-play-game-rules-modal">' . $rules_modal_content . '</div>';
        $content .= '<div id="fdv-play-game-warning-modal">' . $warning_popup_content . '</div>';
        // See: https://www.tjvantoll.com/2012/07/15/native-html5-number-picker-vs-jquery-uis-spinner-which-to-use/
//            <input type="number" min="1" max="5" step="1" size="1" maxlength="1" value="{$options['Degrees']}" id="fdv-deg-selector" />
        $deg_selector_widget = <<<EOT
<select id="fdv-deg-selector">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
</select>
EOT;
//            <input type="number" min="1" max="5" step="1" size="1" maxlength="1" value="{$options['Difficulty']}" id="fdv-difficulty-selector" />
        $difficulty_selector_widget = <<<EOT
<select id="fdv-difficulty-selector">
                <option value="1">Easy</option>
                <option value="2">Medium</option>
                <option value="3">Hard</option>
                <option value="4">5&deg; Vegan Master</option>
</select>
EOT;
        $hints_selector_widget = <<<EOT
<select id="fdv-hints-selector">
                <option value="1" selected="selected">Yes</option>
                <option value="0">No</option>
</select>
EOT;
        $content .= <<<EOT
<div id="fdv-play-game-header">
    <input type="button" id="fdvegan-play-game-rules-modal-btn" class="fdv-btn-short" value="Rules" />
    <div id="fdv-play-game-top-text"><span class="bold">{$username}</span>, ready to play a game?</div>
</div>
<div id="fdv-play-game-setup-content">
    <span class="label">Max # of degrees to use:</span> <span class="value">{$deg_selector_widget} &deg;</span> <span class="small fdv-deg-selector-description">(higher degrees are much tougher)</span><br />
    <span class="label">Difficulty:</span> <span class="value">{$difficulty_selector_widget}</span><br />
    <span class="label">Allow Hints?</span> <span class="value">{$hints_selector_widget}</span><br />
    <input type="button" id="fdv-play-game-start-btn" class="full-width" value="Start the Game!" />
</div>
<div id="fdv-play-game-content" class="loading"></div>
EOT;
        return $content;
    }



    //////////////////////////////



    /**
     * Note - this function is deprecated, see: fdvegan_script_init_load.php
     */
    public static function getInitLoadContent($start_num = 1, $process_num = 5)
    {
        $options = array();
        $options['Start'] = $start_num;  // $start_num - 1
        $options['Limit'] = $process_num;
        // Input validations
        if (($start_num < 1) || ($process_num < 1)) {
            fdvegan_Content::syslog('LOG_ERR', "getInitLoadContent({$start_num},{$process_num}) invalid arguments.");
            return self::getSystemErrorContent($options);
            //throw new FDVegan_InvalidArgumentException("Invalid start_num={$start_num} or process_num={$process_num}.");
            //return array(-1, $start_num, 'Error');
        }

        $initial_processed_num = $start_num;

        $persons_collection = new fdvegan_PersonCollection($options);
        $persons_collection->loadPersonsArray();
        $persons_collection->removeNullTMDbPersons();

        if ($start_num < count($persons_collection)) {
            fdvegan_Content::syslog('LOG_DEBUG', 'Loading (' . count($persons_collection) . ') persons.');
            $loop = 0;
            foreach ($persons_collection as $person) {
                $loop += 1;
                if ($loop >= $start_num) {
                    if ($start_num >= $initial_processed_num + $process_num) {
                        $content = <<<EOT
                            <div class="row">
                                Initial database loading.  Processed record # ($start_num)...
                            </div>
EOT;
                        return array(0, $start_num, $content);
                        break;
                    }
                    fdvegan_Content::syslog('LOG_INFO', 'Loading data for person "' . $person->FullName . '".');

                    // Get latest data from TMDb
                    try {
                        $person->loadPersonFromTMDbById();
                        $start_num += 1;
                    }
                    catch (TMDbException $e) {
                        throw new FDVegan_TmdbException("Caught TMDbException: {$e->getMessage()}", $e->getCode(), $e, 'LOG_NOTICE');
/*
                        $content = <<<EOT
                            <div class="row">
                                We are currently experiencing an error when connecting to the online TMDb system.<br />
                                <br />
                                Please try again later.<br />
                                <br />
                            </div>
EOT;
*/
                    }
                    catch (Exception $e) {
                        throw new FDVegan_Exception("Caught Exception: {$e->getMessage()} while loadPersonFromTMDbById()", $e->getCode(), $e, 'LOG_ERR');
/*
                        $content = <<<EOT
                            <div class="row">
                                A system error has occurred.<br />
                                <br />
                                Please try again later, and feel free to report this issue to the Webmaster if the problem persists.<br />
                                <br />
                            </div>
EOT;
*/
                    }
                }
            }
        }
        fdvegan_Content::syslog('LOG_DEBUG', 'Finished loading (' . count($persons_collection) . ') persons.');

        $content = <<<EOT
            <div class="row">
                Initial database load complete.
            </div>
EOT;

        return array(1, $start_num, $content);
    }


    /**
     * Called from fdvegan.module::fdvegan_actor_load_form()
     *
     * @return string    Content
     */
    public static function loadActorContent($options = NULL)
    {
        $content = '';
        if (!isset($options['PersonId']) && !isset($options['FullName'])) {
            fdvegan_Content::syslog('LOG_ERR', 'loadActorContent() invalid options provided: ' . print_r($options,1));
            return self::getSystemErrorContent($options);
            //throw new FDVegan_InvalidArgumentException('No actor name provided to load');
        }
        try {
            $person = new fdvegan_Person($options);
        }
        catch (FDVegan_NotFoundException $e) {  // No person found
            return self::getActorNotFoundContent($options);
        }

        if (!empty($person->getTmdbId())) {
            // Is already loaded in our DB
            $content .= <<<EOT
                <div class="row">
                    {$person->fullName} was initially loaded in our database on {$person->created}.
                <br />
                </div>
EOT;
            if ($person->created < $person->updated) {
                $content .= <<<EOT
                    <div class="row">
                        {$person->fullName} was last updated on {$person->updated}.
                    <br />
                    </div>
EOT;
            }

            // Go ahead and get latest data from TMDb anyway
            try {
                $person->loadPersonFromTMDbById();
            }
            catch (TMDbException $e) {
                throw new FDVegan_TmdbException("Caught TMDbException: {$e->getMessage()}", $e->getCode(), $e, 'LOG_NOTICE');
/*
                $content .= <<<EOT
                    <div class="row">
                        We are currently experiencing an error when connecting to the online TMDb system.<br />
                        <br />
                        Please try again later.<br />
                        <br />
                    </div>
EOT;
*/
            }
            catch (Exception $e) {
                throw new FDVegan_Exception("Caught Exception: {$e->getMessage()} while loadPersonFromTMDbById()", $e->getCode(), $e, 'LOG_ERR');
/*
                $content .= <<<EOT
                    <div class="row">
                        A system error has occurred.<br />
                        <br />
                        Please try again later, and feel free to report this issue to the Webmaster if the problem persists.<br />
                        <br />
                    </div>
EOT;
*/
            }

            // Next, load the person's credits from TMDb
            try {
                $person->getCredits()->loadEachMovie($options);
            }
            catch (TMDbException $e) {
                throw new FDVegan_TmdbException("Caught TMDbException: {$e->getMessage()}", $e->getCode(), $e, 'LOG_NOTICE');
            }
            catch (Exception $e) {
                throw new FDVegan_Exception("Caught Exception: {$e->getMessage()} while loadPersonFromTMDbById()", $e->getCode(), $e, 'LOG_ERR');
            }

            $view_opts = array('PersonId' => $person->personId);
            $content .= fdvegan_Content::getActorContent($view_opts);

        } else {
            // Ready to query data from TMDb's API and load into our DB
// @TODO
throw new FDVegan_NotImplementedException('loadActorContent() load from TMDb by Full Name not implemented yet.');

            $person_image_url = $person->getImagePath('medium');
            $content .= <<<EOT
Loaded actor data from TMDb.
                <div class="row">
                    <div class="left">
                        <span class="label">Actor:</span> <span class="value">{$person->fullName}</span>
                    </div>
                    <div class="right">
                        <img class="person" src="{$person_image_url}" />
                    </div>
                </div>
                <div class="row">
                    <a href="https://www.themoviedb.org/person/{$person->tmdbid}" rel="external" target="_blank">More info on TMDb</a>.
                </div>
EOT;
        }

        return $content;
    }


    /**
     * Implementation of view for getActorNotFoundContent().
     */
    public static function getActorNotFoundContent($options = NULL)
    {
        $person_img_tag = theme('image', array(
                                'path' => fdvegan_Util::getStandardImageUrl('fdv_person_silhouette.png'),
                                'alt'  => 'Actor Not Found',
                                'attributes' => array('class' => 'fdv-person-not-found',
                                    ),
                               ));

        $content = '';
        if (!empty($options['FullName'])) {
            $content .= <<<EOT
            <div class="row">
                Sorry, no actor by the name “{$options['FullName']}” can be found in our vegan database.
            </div>
EOT;
        } else {
            $content .= <<<EOT
            <div class="row">
                Sorry, that actor was not found in our vegan database.
            </div>
EOT;
        }
        $content .= <<<EOT
            <div class="row">
                <br />
                Please go back and <a href="actor">try searching again</a>.
            </div>
            <br /><br />
            <div class="row fdv-person-not-found">
                {$person_img_tag}
            </div>
EOT;

        return $content;
    }


    /**
     * Implementation of view for getMovieNotFoundContent().
     */
    public static function getMovieNotFoundContent($options = NULL)
    {
        $movie_img_tag = theme('image', array(
                               'path' => fdvegan_Util::getStandardImageUrl('fdv_movie_reel_spill.png'),
                               'alt'  => 'Movie Not Found',
                               'attributes' => array('class' => 'fdv-movie-not-found',
                                   ),
                              ));

        $content = '';
        if (!empty($options['Title'])) {
            $content .= <<<EOT
            <div class="row">
                Sorry, no movie by the name “{$options['Title']}” can be found in our vegan database.<br />
                Chances are, there are no veg*ns starring in that movie.
            </div>
EOT;
        } else {
            $content .= <<<EOT
            <div class="row">
                Sorry, that movie was not found in our vegan database.<br />
                Chances are, there are no veg*ns starring in that movie.
            </div>
EOT;
        }
        $content .= <<<EOT
            <div class="row">
                <br />
                Please go back and <a href="movie">try searching again</a>.
            </div>
            <br /><br />
            <div class="row fdv-movie-not-found">
                {$movie_img_tag}
            </div>
EOT;

        return $content;
    }


    /**
     * Implementation of view for getSystemErrorContent().
     */
    public static function getSystemErrorContent($options = NULL)
    {
        $movie_img_tag = theme('image', array(
                               'path' => fdvegan_Util::getStandardImageUrl('fdv_movie_reel_spill.png'),
                               'alt'  => 'Oops',
                               'attributes' => array('class' => 'fdv-not-found',
                                   ),
                              ));

        $content = <<<EOT
            <div class="row">
                Sorry, a system error has occurred.
                We are working to correct the problem.  Please try again later.
            </div>
            <br /><br />
            <div class="row fdv-not-found">
                {$movie_img_tag}
            </div>
EOT;

        return $content;
    }


    /**
     * Implementation of view for getPageNotFoundContent().
     * @see fdvegan.module::fdvegan_page_not_found()
     */
    public static function getPageNotFoundContent()
    {
        $movie_img_tag = theme('image', array(
                               'path' => fdvegan_Util::getStandardImageUrl('fdv_movie_reel_spill.png'),
                               'alt'  => 'Oops',
                               'attributes' => array('class' => 'fdv-not-found',
                                   ),
                              ));

        $content = '<br /><div class="large bold">Oops!</div><br />';
        $content .= '<div class="large">The requested page: &nbsp; <span class="plain-font">' . check_url(request_uri()) . '</span> &nbsp; could not be found.</div>';
        if (!user_is_logged_in()) {
            $content .= '<br /><br />';
            $content .= 'Try logging in first.';
        }
        $content .= <<<EOT
            <br /><br />
            <div class="row fdv-not-found">
                {$movie_img_tag}
            </div>
EOT;

        return $content;
    }


    /**
     * Implementation of view for getPageForbiddenContent().
     * @see fdvegan.module::fdvegan_page_forbidden()
     */
    public static function getPageForbiddenContent()
    {
        $movie_img_tag = theme('image', array(
                               'path' => fdvegan_Util::getStandardImageUrl('fdv_movie_reel_spill.png'),
                               'alt'  => 'Oops',
                               'attributes' => array('class' => 'fdv-not-found',
                                   ),
                              ));

        $content = '<br /><div class="large bold">Page forbidden.</div><br />';
        $content .= '<div class="large">You do not have permission to access the requested page: &nbsp; <span class="plain-font">' . check_url(request_uri()) . '</span></div>';
        if (!user_is_logged_in()) {
            $content .= '<br /><br />';
            $content .= 'Try logging in first.';
        } elseif (!user_access('pro fdvegan')) {
            $content .= '<br /><br />';
            $content .= 'Perhaps you need to upgrade to a Pro account?';
        }
        $content .= <<<EOT
            <br /><br />
            <div class="row fdv-not-found">
                {$movie_img_tag}
            </div>
EOT;

        return $content;
    }


    /**
     * Get the fdvegan-level-micro-icon fdvegan_level_micro_icon_*.gif if any.
     * 
     * @param object $obj    Either a person or movie object.
     * @return string    A Drupal image tag.
     */
    public static function getFdvCountImageTag($obj)
    {
        $img_tag = '';
        // FDV levels are 1 to 5, then 5+ (represented by "fdv_level_micro_icon_6.gif")
        if ($fdveganLevel = min(6, max(0, $obj->getFdvCount()))) {
            $img_tag = theme('image', array(
                                            'path'       => fdvegan_Util::getStandardImageUrl('fdvegan_level_micro_icon_'. $fdveganLevel .'.gif'),
                                            'alt'        => "FDV level {$obj->getFdvCount()} connection",
                                            'title'      => "FDV level {$obj->getFdvCount()} connection",
                                            'attributes' => array('class' => array('fdvegan-level-micro-icon',
                                                                                   'right'),
                                                                  'data-fdvegan-count' => $obj->getFdvCount(),
                                                                 ),
                                           )
            );
        }
        return $img_tag;
    }


    /**
     * Standard debugging function.  Outputs to logfile and possibly stdout (webpage).
     *
     * The output here is configured from the Admin page:  /admin/settings/fdvegan
     *
     * For all valid $priority values see:  https://www.php.net/manual/en/function.syslog.php
     *
     * Example usage:
     *  fdvegan_Content::syslog('LOG_DEBUG', 'this obj data: '. print_r($this,1));
     */
    public static function syslog($priority = 'LOG_DEBUG', $message = '')
    {
        // Only output when a particular permission is set for a role,
        // or this is run via command-line script (see fdvegan_script_init_load.php).
        // See fdvegan.module::fdvegan_permission()
//        if (user_access('administer debug fdvegan') || fdvegan_Util::isRunningAsScript()) {
        if (user_access('view fdvegan') || user_access('use fdvegan') || fdvegan_Util::isRunningAsScript()) {
            if (!array_key_exists($priority, fdvegan_Util::$syslog_levels)) {
                $message = "Unknown priority \"{$priority}\" given for error: " . $message;
                $priority = 'LOG_ERR';
            }
            $threshold = variable_get('fdvegan_syslog_output_level', 'LOG_ERR');  // set in fdvegan.admin.inc::fdvegan_admin_form()
            if (fdvegan_Util::$syslog_levels[$priority] >= fdvegan_Util::$syslog_levels[$threshold]) {
                $bt = debug_backtrace(FALSE, 2);
                $priority_string = substr($priority, 4, strlen($priority));
                if (isset($bt[1]['function'])) {
                    $str = date('Ymd,H:i:s,T')." {$priority_string}: ". basename($bt[0]['file']) .
                            '::' . $bt[1]['function'] . '()' .
                           " line " . $bt[0]['line'] . ": " . $message . PHP_EOL;
                } else {
                    $str = date('Ymd,H:i:s,T')." {$priority_string}: ". basename($bt[0]['file']) .
                            '::' . 'UNKNOWN_FUNCTION' . '()' .
                           " line " . $bt[0]['line'] . ": " . $message . PHP_EOL;
                    $str .= print_r($bt,1) . PHP_EOL;
                }
                $output_filename = variable_get('fdvegan_syslog_output_file');  // set in fdvegan.admin.inc::fdvegan_admin_form()
                file_put_contents ($output_filename, $str, FILE_APPEND);

                $output_to_screen = variable_get('fdvegan_syslog_output_to_screen', 0);  // set in fdvegan.admin.inc::fdvegan_admin_form()
                if ($output_to_screen) {
                    if (user_access('administer debug fdvegan') && !fdvegan_Util::isRunningAsScript()) {
                        $str = '<pre>' . $str . '</pre>';
                    }
                    echo $str;
                }
            }
        }
        return TRUE;
    }



    //////////////////////////////



}

