//define some shortcodes scripts

//courses collection pagination
jQuery(function ($) {
    $.fn.LPCourseCollectionCarousel = function () {
        if(typeof  $.fn.owlCarousel !== 'function') {
            console.error('owlcarousel not found!');
            return;
        }

        var carouselElement = $(this).find('.owl-carousel');

        var defaultOptions = {
            items: 4,
            itemsDesktop: [1199, 4],
            itemsDesktopSmall: [979, 3],
            itemsTablet: [768, 2],
            itemsTabletSmall : [600, 2],
            itemsMobile: [481, 1],
            singleItem: false,
            itemsScaleUp: true,
            slideSpeed: 200,
            paginationSpeed: 800,
            rewindSpeed: 1000,
            autoPlay: false,
            stopOnHover: true,
            navigation: true,
            navigationText: ['&larr;',"&rarr;"],
            scrollPerPage: false,
            pagination: false,
            autoHeight: false
        };


        var options = getDataOptions();

        function getDataOptions() {
            var dataOptions = carouselElement.data();
            for(var op in defaultOptions){
                var prop = op.toLowerCase();
                if(defaultOptions.hasOwnProperty(op) && dataOptions.hasOwnProperty((prop)) && dataOptions[prop] != ""){
                    switch(typeof defaultOptions[op]){
                        case 'number':{
                            dataOptions[op] = Number(dataOptions[prop]);
                            break;
                        }
                        case 'object': {
                            switch(typeof defaultOptions[op][0]) {
                                case 'number':{
                                    var value = parseInt(dataOptions[prop]);
                                    dataOptions[op] = [defaultOptions[op][0], value];
                                    break;
                                }
                                default:{
                                    try {
                                        dataOptions[op] = JSON.parse(dataOptions[prop]);
                                    } catch(e){
                                        console.error('JSON parse error! can not set input data for property ' + prop);
                                    }
                                }
                            }
                            break;
                        }
                        case 'boolean': {
                            dataOptions[op] = Boolean(dataOptions[prop]);
                            break;
                        }
                        default:{
                            dataOptions[op] = dataOptions[prop];
                        }
                    }
                }

                if(prop == 'navigationtextnext'){
                    options['navigationtext'][1] = options['navigationtextnext'];
                }

                if(prop == 'navigationtextprev' ){
                    options['navigationtext'][0] = options['navigationtextprev'];
                }
            };


            return $.extend({}, defaultOptions, dataOptions);
        };

        carouselElement.owlCarousel(options);

        //handle events
        $(this).find('.owl.next').click(function (e) {
           trigger('owl.next');
        });

        $(this).find('.owl.prev').click(function (e) {
            trigger('owl.prev');
        });

        return carouselElement;
    };
});

jQuery(function ($) {
   $('.archive-course-collection-outer').each(function(){
       $(this).LPCourseCollectionCarousel();
    });
});