document.addEventListener("DOMContentLoaded",(function(){var e=document.querySelectorAll(".eb-recaptcha-field-wrapper");if(e)for(var t=function(){var t=e[a],c=t.getAttribute("data-site-key");"v3"===(t.getAttribute("data-field-id"),EssentialBlocksProLocalize.recaptcha_type)&&grecaptcha.ready((function(){grecaptcha.execute(c,{action:"submit"}).then((function(e){var a=t.querySelector("input");a&&(a.value=e)}))}))},a=0;a<e.length;a++)t()}));