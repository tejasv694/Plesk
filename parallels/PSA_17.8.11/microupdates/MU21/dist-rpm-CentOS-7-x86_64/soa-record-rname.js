!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t():"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?exports.Jsw=t():e.Jsw=t()}(this,function(){return function(e){function t(n){if(r[n])return r[n].exports;var o=r[n]={i:n,l:!1,exports:{}};return e[n].call(o.exports,o,o.exports,t),o.l=!0,o.exports}var r={};return t.m=e,t.c=r,t.i=function(e){return e},t.d=function(e,r,n){t.o(e,r)||Object.defineProperty(e,r,{configurable:!1,enumerable:!0,get:n})},t.n=function(e){var r=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(r,"a",r),r},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=155)}({155:/*!******************************************!*\
  !*** ./app/dns-zone/soa-record-rname.js ***!
  \******************************************/
function(e,t,r){"use strict";function n(e){if(Array.isArray(e)){for(var t=0,r=Array(e.length);t<e.length;t++)r[t]=e[t];return r}return Array.from(e)}Object.defineProperty(t,"__esModule",{value:!0});var o=function(){var e=[].concat(n(document.querySelectorAll("input[name*=rname_type]"))),t=function(){e.forEach(function(e){var t=document.getElementById("soaRecord-rname_"+e.value);if(t){var r=t.parentElement.classList;e.checked?(r.remove("hidden"),t.removeAttribute("disabled")):(r.add("hidden"),t.setAttribute("disabled","true"))}})};e.forEach(function(e){return e.addEventListener("change",t)}),t()};t.default=function(){o()}}})});
//# sourceMappingURL=soa-record-rname.js.map