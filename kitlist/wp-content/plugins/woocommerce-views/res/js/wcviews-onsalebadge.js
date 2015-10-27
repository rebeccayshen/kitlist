jQuery( document ).ready( function( $ ) {
	
	/** EMERSON: (New in WooCommerce Views 2.4+)
	 * WooCommerce on-sale badge handler
	 * During page load, wrap on sale badge so the badge will append to product image;
	 * During AJAX response, wrap it as well so the on-sale badge will append without needing page reload.
	 */
	
	/** Page load wrapping*/	
	$('.woocommerce span.onsale').wrap('<span class="wcviews_onsale_wrap" />');
	    
	/** Views AJAX response Wrapping */
	$.each(['html'], function (i, ev) {
	        var el = $.fn[ev];
	        $.fn[ev] = function () {
	            
	            if ($(this).is('div.js-wpv-view-layout') && $(arguments[i]).length) {
	                
	            	var $form_content = $('<div></div>').append($(arguments[i]));	               
	                $form_content.find('span.onsale').wrap('<span class="wcviews_onsale_wrap" />');

	                arguments = new Array($form_content.html());
	            }
	            this.trigger(ev, arguments);
	            var result = el.apply(this, arguments);

	            return result;
	        };
	  });
	
	/** AJAX response wrapping */
	jQuery(document).ajaxSuccess(function(event,xhr,options){	
		
        var responseText_string= xhr.responseText;        
        if (responseText_string.indexOf("js-wpv-view-layout") > -1) {        	
        	//Let's check if its wrapped already
        	if (!($('span.wcviews_onsale_wrap').length)) {        		
        		$('.woocommerce span.onsale').wrap('<span class="wcviews_onsale_wrap" />');
        	}       	
		}         
	});
	
	//Restore add to cart quantity when queried with AJAX	
	$.each(['html'], function (i, ev) {
        var el = $.fn[ev];
        $.fn[ev] = function () {
            
            if ($(this).is('div.js-wpv-view-layout') && $(arguments[i]).length) {
                
            	var $form_content = $('<div></div>').append($(arguments[i]));	               
                $form_content.find('form.cart div.quantity').addClass( 'buttons_added' ).append( '<input type="button" value="+" class="plus" />' ).prepend( '<input type="button" value="-" class="minus" />' );

                arguments = new Array($form_content.html());
            }
            this.trigger(ev, arguments);
            var result = el.apply(this, arguments);

            return result;
        };
  });	
	
	//Star rating adjustment
	$('.woocommerce .star-rating').addClass('wc_views_star_rating');

});

function wcviews_onsale_pagination_callback() {
	    
    	//Let's check if its wrapped already
    	if (!(jQuery('span.wcviews_onsale_wrap').length)) {    		
    		jQuery('.woocommerce span.onsale').wrap('<span class="wcviews_onsale_wrap" />');
    	}        	
	 
}