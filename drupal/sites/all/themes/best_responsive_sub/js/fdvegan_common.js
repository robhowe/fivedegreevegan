/*
 * Common files that are included on every page.
 */


/**
 * Resize an embeded iframe's height.
 * This should be called after its content is loaded and it's then needed to be
 * resized taller or shorter.
 *
 * @param string selector    A jQuery selector string.
 *                           e.g.: "#fdv-actor-tree-iframe"
 * @param int max_height    (optional) Max height desired, in pixels.
 */
function resizeIframe(selector, max_height) {
    max_height = max_height || 700;  // set default max
    var height = jQuery(selector, top.document).contents().find("html").height();
    height = Math.min(max_height, Math.min(screen.availHeight, height));
    jQuery(selector, top.document).css("height", height + 'px');
}


jQuery(document).ready(function($) {

    /*
     * "home-blurb" used on front page
     */
    $('.fdv-toggle-btn').click(function(){
        $('.fdv-toggle').toggle();
    });


    /*
     * "Accordion" used on FAQ page
     */
    $('#accordion div').hide();
    $('#accordion h4').hover(function(){
        $(this).css({'color':'#033','text-decoration':'underline'});
    },function(){
        $(this).css({'color':'#000','text-decoration':'none'});
    });
    $('#accordion h4').click(function(){
//        $(this).next('div').fadeToggle(400);
//        $(this).next('div').slideToggle();
        $(this).next('div').animate({height: "toggle", opacity: "toggle"});
    });  // .eq($show_num).trigger('click');


    /*
     * "Back to Top" link at bottom of (most) every page.
     * @see  page.tpl.php::fdv-back-to-top
     */
    $(window).scroll(function() {
        if ( $(window).scrollTop() > 300 ) {
            $('a.fdv-back-to-top').fadeIn('slow');
        } else {
            $('a.fdv-back-to-top').fadeOut('slow');
        }
    });

    $('a.fdv-back-to-top').click(function() {
        $('html, body').animate({
            scrollTop: 0
        }, 700);
        return false;
    });


    /*
     * jQuery 1.8 no longer supports HTML entities in dialog "title" text,
     * so we have to use "widget" to fix it.
     */
    if (typeof $.ui !== 'undefined') {  // jQuery 1.8+ doesn't support ui.dialog!
        $.widget("ui.dialog", $.extend({}, $.ui.dialog.prototype, {
            _title: function(title) {
                if (!this.options.title ) {
                    title.html("&#160;");
                } else {
                    title.html(this.options.title);
                }
            }
        }));
    }
});

