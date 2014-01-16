
$(document).ready(function() {
    $('#slideshow-div').rsfSlideshow({
        controls: {
            playPause: {auto: true},
            previousSlide: {auto: true},
            nextSlide: {auto: true},
            index: {auto: true}
        },
        bounding_box: [600,600],
        effect: 'slideLeft'
    });
});

