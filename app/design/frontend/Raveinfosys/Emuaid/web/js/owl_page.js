   jQuery(document).ready(function() {
        jQuery("#owl-demo").owlCarousel({

		  navigation : true,
		  slideSpeed : 300,
		  paginationSpeed : 400,
		  singleItem : true,
		  autoPlay : true

		  // "singleItem:true" is a shortcut for:
		  // items : 1, 
		  // itemsDesktop : false,
		  // itemsDesktopSmall : false,
		  // itemsTablet: false,
		  // itemsMobile : false

      });
	  
	  var owl = jQuery("#owl-demo-testimonial");
     
		  owl.owlCarousel({
			  items : 3, //10 items above 1000px browser width
			  itemsDesktop : [1000,3], //5 items between 1000px and 901px
			  itemsDesktopSmall : [900,3], // betweem 900px and 601px
			  itemsTablet: [600,2], //2 items between 600 and 0
			  itemsMobile : [400,1] // itemsMobile disabled - inherit from itemsTablet option
		  });
		  
		  // Custom Navigation Events
		  jQuery(".next").click(function(){
			owl.trigger('owl.next');
		  })
		  jQuery(".prev").click(function(){
			owl.trigger('owl.prev');
		  })
	  
    });

	
//Top_menu colore change
	jQuery(function() {
		 var path = window.location.href; // because the 'href' property of the DOM element is the absolute path
		 //alert(path);
			 jQuery('.nav li a').each(function() {
			  if (this.href === path) {
				jQuery('.nav > li > a[href="'+path+'"]').parent().addClass('active');
			  }
		 });
	});

	
//Sidebar_testimonial
	jQuery(document).ready(function() {
						var conf = {
								fx: 'fade',
								timeout: 10000,
								next: '#simplicity-testimonials-2 .prev',
								prev: '#simplicity-testimonials-2 .next',
								before: animateHeight,
								slideResize: true,
								containerResize: false,
								width: '100%',
								fit: 1	
							};
													
						if(jQuery('#simplicity-testimonials-2 .jcycle').length){
							jQuery('#simplicity-testimonials-2 .jcycle').cycle(conf);
						}
						
						function animateHeight(currElement, nextElement, opts, isForward) { 							

								jQuery(nextElement).closest('.jcycle').animate({height:jQuery(nextElement).innerHeight()});	
						}
						
					});

	
//FAQ listing Mobile view
	jQuery(document).ready(function() {
        jQuery('.faq_mob ul li').click(function() {
			var i = jQuery(this).index();
			jQuery('.faq_mob ul #faq_ans' + (i+1) +'>h3').toggleClass('active expand');	
			jQuery('.faq_mob ul #faq_ans' + (i+1) +'>div').toggle("slide");
					
        });
    });
    
 
