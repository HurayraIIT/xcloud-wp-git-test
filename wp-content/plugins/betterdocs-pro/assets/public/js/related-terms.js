!function(e){"use strict";console.log("betterdocs",betterdocsRelatedTerms),e(".betterdocs-load-more-wrapper .betterdocs-load-more-button").on("click",(function(t){t.preventDefault();var r=e(this),o=e(".betterdocs-load-more-loader"),a=(e(".betterdocs-related-terms-inner-wrapper").children().length,e(this).data("current_term_id")),d=e(this).data("page");e.ajax({url:betterdocsRelatedTerms.ajax_url,type:"GET",data:{_wpnonce:betterdocsRelatedTerms.nonce,action:"load_more_terms",current_term_id:a,page:d,kb_slug:betterdocsRelatedTerms.kb_slug},beforeSend:()=>{e(".betterdocs-load-more-button .load-more-text").text("Loading"),o.css("display","block")},success:t=>{if(""!=t.data){r.data("page",d+1);var a=e(t.data.html);let s=setTimeout((()=>{e(".betterdocs-load-more-button .load-more-text").text("Load More"),o.css("display","none"),e(".betterdocs-related-terms-inner-wrapper").append(a),a.css("opacity",0).slideDown("slow").animate({opacity:3}),t.data?.has_more_term||(e(".betterdocs-load-more-wrapper").remove(),clearTimeout(s))}),100)}}})}))}(jQuery);