/*
 * Functions to support the pages with Movie images.
 */

jQuery(document).ready(function($) {

    /*
     * Make medium movie images popup a large image on hover.
     */
    var movieHeightM = 275, movieWidthM = 185;
    var movieHeightL = 750, movieWidthL = 500;
    var popupml = $('<img id="fdvegan-popup-movie-l">').appendTo('#main-content');
    var popupmlHideFunction = function() {
        popupml.stop(1,1);
        var animateTop  = '+=' + parseInt((movieHeightL - movieHeightM) / 2);
        var animateLeft = '+=' + parseInt((movieWidthL - movieWidthM) / 2);
        popupml.css({height: movieHeightL, width: movieWidthL}).
                animate({top: animateTop, left: animateLeft,
                         height: movieHeightM, width: movieWidthM
                        },
                        200,
                        function() {popupml.hide();}
        );
    };
    popupml.mouseleave(popupmlHideFunction).click(popupmlHideFunction);
    $('.fdvegan-movie-expand-m-to-l').mouseenter(function() {
            popupml.stop(1,1).hide();
            popupml.attr('src', $(this).data('hover-src'));
            popupml.attr('alt', $(this).attr('alt'));
            var offset = $(this).offset();
            var top  = parseInt(offset.top);
            var left = parseInt(offset.left);
            var animateTop  = '-=' + parseInt((movieHeightL - movieHeightM) / 2);
            var animateLeft = '-=' + parseInt((movieWidthL - movieWidthM) / 2);
            popupml.css({top: top, left: left, height: movieHeightM, width: movieWidthM}).
                  show().animate({top: animateTop, left: animateLeft,
                                  height: movieHeightL, width: movieWidthL
                                 },
                                 300);
    });


    /*
     * Make small movie images popup a medium image on hover.
     */
    var movieHeightS = 72, movieWidthS = 48;
//    var movieHeightM = 275, movieWidthM = 185;
    var popupmm = $('<img id="fdvegan-popup-movie-m">').appendTo('#main-content');
    var popupmmHideFunction = function() {
        popupmm.stop(1,1);
        var animateTop  = '+=' + parseInt((movieHeightM - movieHeightS) / 2);
        var animateLeft = '+=' + parseInt((movieWidthM - movieWidthS) / 2);
        popupmm.css({height: movieHeightM, width: movieWidthM}).
                animate({top: animateTop, left: animateLeft,
                         height: movieHeightS, width: movieWidthS
                        },
                        200,
                        function() {popupmm.hide();}
        );
    };
    popupmm.mouseleave(popupmmHideFunction).click(popupmmHideFunction);
    $('.fdvegan-movie-expand-s-to-m').mouseenter(function() {
            popupmm.stop(1,1).hide();
            popupmm.attr('src', $(this).data('hover-src'));
            popupmm.attr('alt', $(this).attr('alt'));
            var offset = $(this).offset();
            var top  = parseInt(offset.top);
            var left = parseInt(offset.left);
            var animateTop  = '-=' + parseInt((movieHeightM - movieHeightS) / 2);
            var animateLeft = '-=' + parseInt((movieWidthM - movieWidthS) / 2);
            popupmm.css({top: top, left: left, height: movieHeightS, width: movieWidthS}).
                  show().animate({top: animateTop, left: animateLeft,
                                  height: movieHeightM, width: movieWidthM
                                 },
                                 300);
    });

});
