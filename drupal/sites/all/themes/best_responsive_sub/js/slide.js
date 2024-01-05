// See:  https://github.com/woocommerce/FlexSlider/wiki/FlexSlider-Properties
jQuery(document).ready(function($){
  $(window).load(function() {
    $("#single-post-slider").flexslider({
      animation: 'slide',  // 'slide' or 'fade'
      slideshow: true,
      randomize: false,
      pauseOnAction: true,
      pauseOnHover: true,
      itemMargin: 0,
      initDelay: 1000,  // 1000 == 1 second
      slideshowSpeed: 12000,  // 1000 == 1 second
      animationSpeed: 700,  // 1000 == 1 second
      direction: 'horizontal',  // 'horizontal' or 'vertical'
      directionNav: true,  // left & right navigation buttons
      controlNav: false,
      keyboard: false,  // Boolean.  Allow slider navigating via keyboard left/right keys.
      mousewheel: false,  // Boolean.  (Dependency) Allows slider navigating via mousewheel.
      pausePlay: false,  // Boolean.  Create pause/play element to control slider slideshow.
      prevText: '<',
      nextText: '>',
      smoothHeight: false,  // Boolean.  Animate the height of the slider smoothly for slides of varying height.
      start: function(slider) {
        slider.container.click(function(e) {
          if( !slider.animating ) {
            slider.flexAnimate( slider.getTarget('next') );
          }
        });
      }
    });
  });
});