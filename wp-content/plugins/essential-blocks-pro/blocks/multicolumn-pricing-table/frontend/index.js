!function(){function t(t){var e={};t.querySelectorAll(".eb-mcpt-feature-list").forEach((function(t){t.querySelectorAll(".eb-mcpt-cell").forEach((function(t,c){var n=t.offsetHeight;(void 0!==e[c]&&e[c]<n||void 0===e[c])&&(e[c]=n)}))})),t.querySelectorAll(".eb-mcpt-feature-list").forEach((function(t){t.querySelectorAll(".eb-mcpt-cell").forEach((function(t,c){t.style.height=e[c]+"px"}))}))}!function(e){var c=!1,n=!1;function i(){c&&n&&function(){for(var e,c=document.querySelectorAll(".eb-mcpt-wrap"),n=function(n){var i=c[n],o=c[n].querySelector(".eb-mcpt-column-features .eb-mcpt-feature-list"),a=c[n].querySelectorAll(".eb-mcpt-column:not(.eb-mcpt-column-features) .eb-mcpt-feature-list");if(o&&o.querySelectorAll(".eb-mcpt-cell").forEach((function(t,e){t.classList.contains("eb-mcpt-header")||a&&a.forEach((function(c){var n=c.querySelectorAll(".eb-mcpt-cell")[e];n&&n.insertAdjacentHTML("afterbegin",'<div class="eb-mcpt-feature-title">'.concat(t.innerHTML,"</div>"))}))})),t(c[n]),window.addEventListener("resize",(function(){setTimeout((function(){t(c[n])}))})),"true"==i.getAttribute("data-collapse")&&window.innerWidth>1024){for(var l=i.querySelectorAll(".eb-mcpt-feature-list .eb-mcpt-cell"),r=i.getAttribute("data-row-number"),s=i.querySelectorAll(".eb-mcpt-feature-list.eb-mcpt-collapse"),u=i.getAttribute("data-text-one"),f=i.getAttribute("data-text-two"),d=i.getAttribute("data-icon-one"),p=i.getAttribute("data-icon-two"),m=i.getAttribute("data-icon-postion"),b=i.querySelector(".eb-mcpt-collapse-button"),h=0,v=0;v<parseInt(r);v++)l[v]&&(h+=l[v].offsetHeight);s.forEach((function(t){t.style.height=h+"px"})),e=!1,b.addEventListener("click",(function(){e?(s.forEach((function(t){t.classList.remove("slide-in"),t.classList.add("slide-out")})),b.innerHTML="right"===m?u+'<span class="eb-mcpt-icon"><i class="'+d+'"></i></span>':'<span class="eb-mcpt-icon"><i class="'+d+'"></i></span>'+u):(s.forEach((function(t){t.classList.remove("slide-out"),t.classList.add("slide-in")})),b.innerHTML="right"===m?f+'<span class="eb-mcpt-icon"><i class="'+p+'"></i></span>':'<span class="eb-mcpt-icon"><i class="'+p+'"></i></span>'+f),e=!e}))}},i=0;i<c.length;i++)n(i)}()}document.addEventListener("DOMContentLoaded",(function(){c=!0,i()})),window.addEventListener("load",(function(){n=!0,i()})),"interactive"===document.readyState&&(c=!0,i()),"complete"===document.readyState&&(c=!0,n=!0,i())}()}();