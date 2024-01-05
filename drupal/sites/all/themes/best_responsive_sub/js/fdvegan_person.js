/*
 * Functions to support the pages with Person images.
 */


var actor_tree_dialog = null;


function displayActorTreePopup() {
    if (actor_tree_dialog === null) {
        actor_tree_dialog = true;
        var tree_url = "actor-tree-only?person_id=" + Drupal.settings.fdvegan.person_id +
                       "&depth=" + Drupal.settings.fdvegan.depth +
                       "&a=1";  // URL param "a=1" means data-only ajax.
        jQuery("#fdvegan-actor-tree-modal").append(jQuery("<iframe />")
            .attr("src", tree_url))
            .dialog({
                autoOpen: false,
                title: Drupal.settings.fdvegan.depth + "&deg;V Actor Tree",
                modal: true,
                position: 'center',
                height: 400,
                width: 350,
                closeOnEscape: true,
                //show: 'fade',
                hide: 'fade',
                buttons: [{
                        text: "Close",
                        "class": 'fdv-btn',
                        click: function() {
                            jQuery(this).dialog("close");
                        }
                    }],
                open: function(event, ui) { 
                    jQuery('.ui-widget-overlay').bind('click', function() {
                        jQuery("#fdvegan-actor-tree-modal").dialog('close');
                    });
                }
            });
    }
    jQuery('#fdvegan-actor-tree-modal').dialog('open');
}


jQuery(document).ready(function($) {

    $("#fdvegan-actor-tree-modal-btn").click(displayActorTreePopup);


    var personHeightS = 67, personWidthS = 45;
    var personHeightM = 275, personWidthM = 185;
    var personHeightL = 632, personWidthL = 421;

    /*
     * Make medium person image popup a large image on hover.
     */
    var popuppl = $('<img id="fdvegan-popup-person-l">').appendTo('#main-content');
    var popupplHideFunction = function() {
        popuppl.stop(1,1);
        var animateTop  = '+=' + parseInt((personHeightL - personHeightM) / 2);
        var animateLeft = '+=' + parseInt((personWidthL - personWidthM) / 2);
        popuppl.css({height: personHeightL, width: personWidthL}).
                animate({top: animateTop, left: animateLeft,
                         height: personHeightM, width: personWidthM
                        },
                        200,
                        function() {popuppl.hide();}
        );
    };
    popuppl.mouseleave(popupplHideFunction).click(popupplHideFunction);
    $('.fdvegan-person-expand-m-to-l').mouseenter(function() {
            popuppl.stop(1,1).hide();
            popuppl.attr('src', $(this).data('hover-src'));
            popuppl.attr('alt', $(this).attr('alt'));
            var offset = $(this).offset();
            var top  = parseInt(offset.top);
            var left = parseInt(offset.left);
            var animateTop  = '-=' + parseInt((personHeightL - personHeightM) / 2);
            var animateLeft = '-=' + parseInt((personWidthL - personWidthM) / 2);
            popuppl.css({top: top, left: left, height: personHeightM, width: personWidthM}).
                  show().animate({top: animateTop, left: animateLeft,
                                  height: personHeightL, width: personWidthL
                                 },
                                 300);
    });


    /*
     * Make small person images popup a medium image on hover.
     */
    var popuppm = $('<img id="fdvegan-popup-person-m">').appendTo('#main-content');
    var popuppmHideFunction = function() {
        popuppm.stop(1,1);
        var animateTop  = '+=' + parseInt((personHeightM - personHeightS) / 2);
        var animateLeft = '+=' + parseInt((personWidthM - personWidthS) / 2);
        popuppm.css({height: personHeightM, width: personWidthM}).
                animate({top: animateTop, left: animateLeft,
                         height: personHeightS, width: personWidthS
                        },
                        200,
                        function() {popuppm.hide();}
        );
    };
    popuppm.mouseleave(popuppmHideFunction).click(popuppmHideFunction);
    $('.fdvegan-person-expand-s-to-m').mouseenter(function() {
            popuppm.stop(1,1).hide();
            popuppm.attr('src', $(this).data('hover-src'));
            popuppm.attr('alt', $(this).attr('alt'));
            var offset = $(this).offset();
            var top  = parseInt(offset.top);
            var left = parseInt(offset.left);
            var animateTop  = '-=' + parseInt((personHeightM - personHeightS) / 2);
            var animateLeft = '-=' + parseInt((personWidthM - personWidthS) / 2);
            popuppm.css({top: top, left: left, height: personHeightS, width: personWidthS}).
                  show().animate({top: animateTop, left: animateLeft,
                                  height: personHeightM, width: personWidthM
                                 },
                                 300);
    });

});
