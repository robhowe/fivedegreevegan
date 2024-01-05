/*
 * Functions to support the Movie page.
 */

jQuery(document).ready(function($) {

    /*
     * Show YouTube trailer link, if any.
     */
    var movie_id = $('#fdvegan-movie-data').data('movie_id');
    var title = $('#fdvegan-movie-data').data('title');
    var k = 'af7df5acc6dc1bcef9272d6c2c3a5e84';  // API keys are free at TMDb.org so please sign up for your own.
    var tmdbApiSearchUrl = 'https://api.themoviedb.org/3/search/movie?query=' + title + '&api_key=' + k;
    $.getJSON(tmdbApiSearchUrl, function(jsonData){
        if ((typeof(jsonData) !== "undefined") &&
            (typeof(jsonData.results) !== "undefined") &&
            (typeof(jsonData.results[0]) !== "undefined") &&
            (typeof(jsonData.results[0].id) !== "undefined")) {
            var movieId = jsonData.results[0].id;
            var tmdbApiTrailersUrl = 'https://api.themoviedb.org/3/movie/' + movieId + '/trailers?api_key=' + k;
            $.getJSON(tmdbApiTrailersUrl, function(jsonData){
                if ((typeof(jsonData) !== "undefined") &&
                    (typeof(jsonData.youtube) !== "undefined") &&
                    (typeof(jsonData.youtube[0]) !== "undefined")) {
                    var videoKey = '';
                    if (typeof(jsonData.youtube[0].source) !== "undefined") {
                        videoKey = jsonData.youtube[0].source;
                    } else if (typeof(jsonData.youtube[0].key) !== "undefined") {
                        videoKey = jsonData.youtube[0].key;
                    } else {
                        return;
                    }
//                    var youTubeUrl = 'https://www.youtube.com/watch?v=' + videoKey;
                    var youTubeUrl = 'https://www.youtube.com/embed/' + videoKey;
                    $('#fdvegan-movie-trailer span a').attr('href', youTubeUrl);
                    $('#fdvegan-movie-trailer span a').removeAttr('onclick');
                    $('#fdvegan-movie-trailer').fadeIn(1000).css('display', 'inline');  // now show the link
                }
            });
        }
    });

});
