(function ($) {
"use strict";

// nav
$('.mobo-bar, .setting').click(function(event) {
    $(".content-wrapper").toggleClass('active');
});

// nav
$('#search-box').click(function(event) {
    $('.search-popup, .overlay').addClass('active');
});

$('.overlay').click(function(event) {
    $('.search-popup, .overlay').removeClass('active');
});

$(window).bind("resize", function () {
    var widths = $(this).width();
    if(widths <= 991) {
        $(".content-wrapper").addClass('active');
    }else{
        $(".content-wrapper").removeClass('active');
    }
}).trigger('resize');


// owlCarousel
$('.owl-carousel').owlCarousel({
    loop:true,
    margin:0,
	items:1,
	navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
    nav:true,
	dots:false,
    responsive:{
        0:{
            items:1
        },
        767:{
            items:3
        },
        992:{
            items:5
        }
    }
})







})(jQuery);