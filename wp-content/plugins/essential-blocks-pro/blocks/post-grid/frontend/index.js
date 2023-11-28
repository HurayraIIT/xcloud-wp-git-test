!function(){"use strict";var e={n:function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,{a:n}),n},d:function(t,n){for(var r in n)e.o(n,r)&&!e.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:n[r]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)}},t=window.wp.apiFetch,n=e.n(t);function r(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}var a=!!EssentialBlocksLocalize&&EssentialBlocksLocalize.rest_rootURL;function o(e,t){return new RegExp("(\\s|^)"+t+"(\\s|$)").test(e.className)}function i(e){var t=e.querySelector(".ebpg-pagination-item.active");if(t){var n=parseInt(t.dataset.pagenumber),r=e.querySelectorAll(".ebpg-pagination-item"),a=r.length,o=1;r.forEach((function(e){o=parseInt(e.dataset.pagenumber),function(e){e.classList.remove("show"),e.classList.add("hide")}(e),(1===n&&o<=3||o>=n&&o<=n+2||o===a||1===o&&(n>=a-2||n>=4))&&function(e){e.classList.remove("hide"),e.classList.add("show")}(e)}));var i=e.querySelectorAll(".ebpg-pagination-item-separator");i.length>0&&i.forEach((function(e){e.remove()}));var s='<button class="ebpg-pagination-item-separator">...</button>';n<r.length-2&&r[r.length-1].insertAdjacentHTML("beforebegin",s),(n>=a-2||a>4&&n>=4)&&r[1].insertAdjacentHTML("afterend",s),1===n?(e.querySelector(".ebpg-pagination-item-previous").disabled=!0,e.querySelector(".ebpg-pagination-item-next").disabled=!1):n===a?(e.querySelector(".ebpg-pagination-item-previous").disabled=!1,e.querySelector(".ebpg-pagination-item-next").disabled=!0):(e.querySelector(".ebpg-pagination-item-previous").disabled=!1,e.querySelector(".ebpg-pagination-item-next").disabled=!1)}}function s(e){var t=1,n=e.closest(".ebpg-pagination").querySelector(".ebpg-pagination-item.active");if(n){if(t=n.dataset?n.dataset.pagenumber:1,o(e,"ebpg-pagination-item-next")){for(var r=n.nextElementSibling;r&&!r.classList.contains("ebpg-pagination-item");)r=r.nextElementSibling;r.classList.add("active"),n.classList.remove("active")}else if(o(e,"ebpg-pagination-item-previous")){for(var a=n.previousElementSibling;a&&!a.classList.contains("ebpg-pagination-item");)a=a.previousElementSibling;a.classList.add("active"),n.classList.remove("active")}i(e.closest(".ebpg-pagination"))}return t}n().use(n().createRootURLMiddleware(a)),window.addEventListener("DOMContentLoaded",(function(){document.querySelectorAll(".eb-post-grid-wrapper").forEach((function(e,t){var a=e.querySelector(".eb-post-grid-search");if(a){var c=a.getAttribute("data-ajax-search"),l=a.querySelector("form");l.addEventListener("submit",(function(e){e.preventDefault()}));var p=l.querySelector(".eb-post-grid-search-field"),u=a.querySelector(".eb-post-grid-search-result"),d=a.querySelector(".eb-post-grid-search-content"),g=a.querySelector(".eb-post-grid-search-not-found"),b=a.querySelector(".eb-post-grid-search-loader"),f="";p.addEventListener("keyup",(function(e){var t=this;setTimeout((function(){var a=e.target.value.trim();if(a!==f){var l;f=a,l="&query_type=search&s=".concat(a);var p="",y=t.closest(".eb-post-grid-category-filter");if(y){var v,h=y.dataset.ebpgtaxonomy,m="",q=function(e,t){var n="undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(!n){if(Array.isArray(e)||(n=function(e,t){if(e){if("string"==typeof e)return r(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);return"Object"===n&&e.constructor&&(n=e.constructor.name),"Map"===n||"Set"===n?Array.from(e):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?r(e,t):void 0}}(e))||t&&e&&"number"==typeof e.length){n&&(e=n);var a=0,o=function(){};return{s:o,n:function(){return a>=e.length?{done:!0}:{done:!1,value:e[a++]}},e:function(e){throw e},f:o}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var i,s=!0,c=!1;return{s:function(){n=n.call(e)},n:function(){var e=n.next();return s=e.done,e},e:function(e){c=!0,i=e},f:function(){try{s||null==n.return||n.return()}finally{if(c)throw i}}}}(y.querySelectorAll(".ebpg-category-filter-list-item"));try{for(q.s();!(v=q.n()).done;){var S=v.value;if(S.classList.contains("active")){m=S.getAttribute("data-ebpgCategory"),p="all"===m?"&query_type=filter":"&taxonomy=".concat(h,"&category=").concat(m);break}}}catch(e){q.e(e)}finally{q.f()}}var L=t.closest(".eb-post-grid-wrapper").dataset;if("1"==c){b.style.display="block";var w=new FormData;w.append("action","post_grid_search_result_content"),w.append("post_grid_search_nonce",EssentialBlocksProLocalize.post_grid_search_nonce),w.append("searchKey",a),w.append("query_data",L.querydata),w.append("attributes",L.attributes),w.append("queryParamString",p),fetch(EssentialBlocksProLocalize.ajax_url,{method:"POST",body:w}).then((function(e){return e.text()})).then((function(e){var t=JSON.parse(e);t.success&&(b.style.display="none",u.style.display="none",g.style.display="none",d.innerHTML="",a.length>0&&(u.style.display="block",(null==t?void 0:t.data.length)>0?(d.style.display="block",d.insertAdjacentHTML("beforeend",t.data)):(d.style.display="none",g.style.display="block")))})).catch((function(e){return console.log(e)}))}n()({path:"essential-blocks/v1/queries?query_data=".concat(L.querydata,"&attributes=").concat(L.attributes).concat(p).concat(l),parse:!1}).then((function(e){var r=e.headers.get("X-WP-Total");if(r){var a=new FormData;a.append("action","post_grid_block_pagination"),a.append("post_grid_pagination_nonce",EssentialBlocksLocalize.post_grid_pagination_nonce),a.append("querydata",null==L?void 0:L.querydata),a.append("attributes",null==L?void 0:L.attributes),a.append("totalPosts",r),fetch(EssentialBlocksLocalize.ajax_url,{method:"POST",body:a}).then((function(e){return e.text()})).then((function(e){t.closest(".eb-post-grid-wrapper").querySelector(".ebpg-pagination")&&(t.closest(".eb-post-grid-wrapper").querySelector(".ebpg-pagination").innerHTML=e),function(e,t){if(document.getElementsByClassName("ebpg-pagination").length>0){var r=document.querySelectorAll(".ebpg-pagination button");r.length>0&&(document.querySelectorAll(".ebpg-pagination").forEach((function(e){i(e)})),r.forEach((function(r){var a=1;r.addEventListener("click",(function(){var r=this,c=o(this,"ebpg-pagination-button"),l=o(this,"ebpg-pagination-item-previous"),p=o(this,"ebpg-pagination-item-next");a=c?parseInt(a)+1:l?parseInt(s(this))-1:p?parseInt(s(this))+1:parseInt(this.dataset.pagenumber);var u=this.closest(".eb-post-grid-wrapper");if(u){var d=u.dataset,g=e||"",b=t||"";n()({path:"essential-blocks/v1/queries?query_data=".concat(d.querydata,"&attributes=").concat(d.attributes).concat(g).concat(b,"&pageNumber=").concat(a)}).then((function(e){c?e?r.closest(".ebpg-pagination").insertAdjacentHTML("beforebegin",e):(r.closest(".ebpg-pagination").insertAdjacentHTML("beforebegin",'<p class="eb-no-posts">No more Posts</p>'),r.closest(".ebpg-pagination").innerHTML=""):(r.closest(".eb-post-grid-wrapper").querySelectorAll(".ebpg-grid-post").forEach((function(e){e.remove()})),r.closest(".ebpg-pagination").insertAdjacentHTML("beforebegin",e),o(r,"ebpg-pagination-item")&&(r.closest(".ebpg-pagination").querySelectorAll(".ebpg-pagination-item").forEach((function(e){e.classList.remove("active")})),r.classList.add("active")),i(r.closest(".ebpg-pagination")))}))}}))})))}}(p,l)})).catch((function(e){return console.log(e)})),n()({path:"essential-blocks/v1/queries?query_data=".concat(L.querydata,"&attributes=").concat(L.attributes).concat(p).concat(l)}).then((function(e){t.closest(".eb-post-grid-wrapper").querySelectorAll(".ebpg-grid-post").forEach((function(e){e.remove()})),t.closest(".eb-post-grid-wrapper").querySelector("p")&&t.closest(".eb-post-grid-wrapper").querySelector("p").remove(),y?t.closest(".eb-post-grid-category-filter").insertAdjacentHTML("afterend",e):t.closest(".eb-post-grid-search").insertAdjacentHTML("afterend",e)}))}else t.closest(".eb-post-grid-wrapper").querySelectorAll(".ebpg-grid-post").forEach((function(e){e.remove()})),t.closest(".eb-post-grid-wrapper").querySelector(".ebpg-pagination")&&(t.closest(".eb-post-grid-wrapper").querySelector(".ebpg-pagination").innerHTML=""),t.closest(".eb-post-grid-wrapper").querySelector("p")&&t.closest(".eb-post-grid-wrapper").querySelectorAll("p").forEach((function(e){e.remove()})),t.closest(".eb-post-grid-wrapper").insertAdjacentHTML("beforeend","<p>No Posts Found</p>")}),(function(e){console.log("error",e)}))}}),1500)}))}}))}))}();