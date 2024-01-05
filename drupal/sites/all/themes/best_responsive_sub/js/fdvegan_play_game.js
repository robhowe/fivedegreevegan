/*
 * fdvegan_play_game.js
 *
 * Functions to support the "Play Game" page.
 */

var initial_display = true;
var play_game_rules_dialog = null, actor_network_warning_dialog = null;
var game_data = [];
//var current_deg, cluster, svg, bundle, line, link, node, links, nodes;


// Return a human readable time string in M:SS format.
function getTimeRemainingInRound() {
    var minutes = Math.floor(Drupal.settings.fdvegan.seconds_remaining_in_round / 60);
    var seconds = Drupal.settings.fdvegan.seconds_remaining_in_round - minutes * 60;
    seconds += (seconds.toString().length < 2) ? '0' : '';
    return minutes + ':' + seconds;
}


function displayGameContent() {

    // Remove (hide) the setup content:
    jQuery("#fdv-play-game-setup-content").css({display: "none"});
    // Show the game content:
    //jQuery("#fdv-play-game-content").css({display: "block"});
    jQuery("#fdv-play-game-content").fadeIn(1200);

    var top_text = '<span class="bold">Round ' + Drupal.settings.fdvegan.round + ':</span>  ';
    if (Drupal.settings.fdvegan.degrees === 1) {
        top_text += 'Choose the movie that connects';
    } else if (Drupal.settings.fdvegan.degrees === 2) {
        top_text += 'Choose the movies and actor that connects';
    } else {
        top_text += 'Choose the movies and actors that connect';
    }
    top_text += '<br />';
    top_text += '<span class="bold">' + 'actor-1' + '</span>';
    top_text += ' to <span class="bold">' + 'actor-' + (Drupal.settings.fdvegan.degrees + 1) + '</span>';
    top_text += ' in <span class="bold">' + Drupal.settings.fdvegan.degrees + '&deg;</span>.';
    if (Drupal.settings.fdvegan.seconds_remaining_in_round > 0) {
        top_text += '<br /><span class="label">The clock is counting down...</span> <span class="value">' + getTimeRemainingInRound() + '</span>';
    }

    jQuery("#fdv-play-game-top-text").html(top_text).fadeIn(1200);


    // Now, get the game data from the server:

    loadGameData();

    // @TODO...








return;
//    current_deg = 1;

/*
    if (initial_display === true) {

        jQuery("#fdv-deg-selector").bind("keyup mouseup", function () {
            new_val = parseInt(jQuery(this).val()) || 0;
            prev_val = jQuery(this).data("previousValue");
            if (!prev_val || (prev_val !== new_val)) {
                    if (new_val > Drupal.settings.fdvegan.max_degrees) {
                        // Can't view above your max-allowed degrees
                        current_deg = prev_val || Drupal.settings.fdvegan.degrees;
                        jQuery(this).val(current_deg);
                        displayActorNetworkWarningPopup();
                    } else {
                        jQuery(this).data("previousValue", new_val);
                        loadActorNetworkData(new_val);
                   }
               }
            }
        );
        initial_display = false;
    }
    // Remove (cover up) the loading spinner:
    jQuery("body.page-actor-network #fig").removeClass("loading");
    // Enable the the degree-selector widget:
    jQuery("#fdv-deg-selector").removeAttr("disabled");
*/


}


/*
 * Load data from the fdvegan_rest_api.
 */
function loadGameData() {
    if ((game_data.length == 0) ||
        (typeof game_data[deg] === "undefined")) {
        // Show the loading spinner:
        jQuery("body.page-actor-network #fig").addClass("loading");
        // Disable the degree-selector widget:
        jQuery("#fdv-deg-selector").attr("disabled","disabled");
        var fdvRestApiUrl = "/rest-api/v1.0/actor-network?degrees=" + deg;
        jQuery.getJSON(fdvRestApiUrl, function(json) {
            if ((typeof(json) !== "undefined") &&
                (typeof(json.result) !== "undefined") &&
                (typeof(json.degrees) !== "undefined")) {
                game_data[deg] = json.result;
                displayActorNetwork(deg);
            } else {
                //console.debug("REST API failed.");
            }
        });
    } else {
        displayActorNetwork(deg);
    }
}


function displayPlayGameRulesPopup() {
    if (play_game_rules_dialog === null) {
        play_game_rules_dialog = true;
        jQuery("#fdv-play-game-rules-modal")
            .dialog({
                autoOpen: false,
                modal: true,
                position: "center",
                height: 400,
                width: 400,
                closeOnEscape: true,
                //show: "fade",
                hide: "fade",
                buttons: [{
                        text: "Close",
                        "class": "fdv-btn",
                        click: function() {
                            jQuery(this).dialog("close");
                        }
                    }],
                open: function(event, ui) { 
                    jQuery(".ui-widget-overlay").bind("click", function() {
                        jQuery("#fdv-play-game-rules-modal").dialog("close");
                    });
                }
            });
    }
    jQuery("#fdv-play-game-rules-modal")
           .dialog({title: "5&deg;V Game Rules"})
           .dialog("open");
}


function displayActorNetworkWarningPopup() {
    if (actor_network_warning_dialog === null) {
        actor_network_warning_dialog = true;
        jQuery("#fdvegan-actor-network-warning-modal")
            .dialog({
                autoOpen: false,
                modal: true,
                position: "left top",
                height: 245,
                width: 320,
                closeOnEscape: true,
                //show: "fade",
                hide: "fade",
                buttons: [{
                        text: "Ok",
                        "class": "fdv-btn",
                        click: function() {
                            jQuery(this).dialog("close");
                        }
                    }],
                open: function(event, ui) {
                    jQuery(".ui-widget-overlay").bind("click", function() {
                        jQuery("#fdvegan-actor-network-warning-modal").dialog("close");
                    });
                    // Don't display the titlebar for this "popup":
                    jQuery("#fdvegan-actor-network-warning-modal").parent(".ui-dialog").children(":first").hide();
                }
            });
    }
    jQuery("#fdvegan-actor-network-warning-modal").dialog("open");
}


/*
 * Auto-load the Actor Network data from the REST API
 * and immediately display it.
 */
jQuery(document).ready(function() {
    jQuery("#fdvegan-play-game-rules-modal-btn").click(displayPlayGameRulesPopup);
    jQuery("#fdv-play-game-start-btn").click(displayGameContent);
    //loadActorNetworkData(Drupal.settings.fdvegan.degrees);
});

