!function(e){function r(a){if(t[a])return t[a].exports;var n=t[a]={i:a,l:!1,exports:{}};return e[a].call(n.exports,n,n.exports,r),n.l=!0,n.exports}var t={};r.m=e,r.c=t,r.d=function(e,t,a){r.o(e,t)||Object.defineProperty(e,t,{configurable:!1,enumerable:!0,get:a})},r.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(t,"a",t),t},r.o=function(e,r){return Object.prototype.hasOwnProperty.call(e,r)},r.p="",r(r.s=1)}([,function(e,r,t){"use strict";t(2),function(){function e(e,r){r||(r=window.location.href),e=e.replace(/[\[\]]/g,"\\$&");var t=new RegExp("[?&]"+e+"(=([^&#]*)|&|#|$)"),a=t.exec(r);return a?a[2]?decodeURIComponent(a[2].replace(/\+/g," ")):"":null}function r(e){return document.getElementsByClassName(e)}function t(e){var r=e;4===r.length&&(r="#"+r[1]+r[1]+r[2]+r[2]+r[3]+r[3]);var t=/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(r);return t?{r:parseInt(t[1],16),g:parseInt(t[2],16),b:parseInt(t[3],16)}:null}function a(e,r,t){var a=[e,r,t].map(function(e){return e/=255,e<=.03928?e/12.92:Math.pow((e+.055)/1.055,2.4)});return.2126*a[0]+.7152*a[1]+.0722*a[2]}function n(e,r){var t=(a(e.r,e.g,e.b)+.05)/(a(r.r,r.g,r.b)+.05);return t<1&&(t=1/t),t}function c(){var e=m.play();void 0!==e&&e.then(function(){}).catch(function(e){})}var o=/^((?!chrome|android).)*safari/i.test(navigator.userAgent),s=function(e){return document.getElementById(e)}("visa-branding"),i=e("constrained"),l=!!i&&"true"===i,u=Math.max(document.documentElement.clientWidth,window.innerWidth||0),g=Math.max(document.documentElement.clientHeight,window.innerHeight||0),d=u/g;l?(s.classList.add("constrained"),d>1.5&&document.getElementById("visa-branding").classList.add("greater-than-ar")):d>4.6&&document.getElementById("visa-branding").classList.add("greater-than-ar");var f=e("sound");if(!f||"true"===f){var m=new Audio("assets/visa_branding_sound.mp3");o?c():setTimeout(function(){c()},220)}var h=e("checkmark");!h||"true"===h||s.classList.add("no-checkmark");var v=e("color")||"blue",k=v.toLowerCase();if("blue"!==k.toLowerCase()&&"white"!==k.toLowerCase())if(/(^[0-9A-F]{6}$)|(^[0-9A-F]{3}$)/i.test(v)){var b="#"+v,_=function(e){var r=t(e),a=n(t("#ffffff"),r),c=n(t("#1a1f71"),r),o={theme:"",error:""};return a<3&&c<3?(o.error="Your custom color doesn't provide enough contrast. Please enter another color.",o):a===c?(o.theme="custom_dark",o):(o.theme=a>c?"custom_dark":"custom_light",o)}(b);_.error?(console.error(_.error),v="blue"):v=_.theme,function(e){var a=["flag-container","visa-container","wiper-left","wiper-right","wiper-middle","flag-mask-top","flag-mask-bottom","checkmark-mask","constrained-bottom-flag-mask","constrained-top-flag-mask"];s.style.backgroundColor=e;for(var n=0;n<a.length;n++)r(a[n])[0].style.backgroundColor=e;var c=t(e),o="linear-gradient(to $DIRECTION, $TRANSPARENT_COLOR 0%, $COLOR 95%)".replace("$COLOR",e);o=o.replace("$TRANSPARENT_COLOR","rgba("+c.r+","+c.g+","+c.b+",0)"),r("top-flag-fade-mask")[0].style.background=o.replace("$DIRECTION","left"),r("bottom-flag-fade-mask")[0].style.background=o.replace("$DIRECTION","right")}(b)}else console.error("An invalid hex color was passed:",v),v="blue";var p="assets",w=r("top-flag")[0],C=r("checkmark-circle")[0],L=r("checkmark")[0],O=r("visa-logo")[0],y=r("bottom-flag")[0];switch(v.toLowerCase()){case"white":p+="/white_theme",w.src=p+"/flag_blue.svg",C.src=p+"/checkmark_circle_blue.svg",L.src=p+"/checkmark_check_blue.svg",O.src=p+"/logo_blue.svg",y.src=p+"/flag_gold.svg",s.classList.add("background-light");break;case"custom_light":p+="/custom_light",w.src=p+"/flag_blue.svg",C.src=p+"/checkmark_circle_blue.svg",L.src=p+"/checkmark_check_blue.svg",O.src=p+"/logo_blue.svg",y.src=p+"/flag_blue.svg";break;case"custom_dark":p+="/custom_dark",w.src=p+"/flag_white.svg",C.src=p+"/checkmark_circle_white.svg",L.src=p+"/checkmark_check_white.svg",O.src=p+"/logo_white.svg",y.src=p+"/flag_white.svg";break;case"blue":default:p+="/blue_theme",w.src=p+"/flag_bluegradient.svg",C.src=p+"/checkmark_circle_white.svg",L.src=p+"/checkmark_check_white.svg",O.src=p+"/logo_white.svg",y.src=p+"/flag_goldgradient.svg",s.classList.add("background-dark")}s.addEventListener("animationend",function(e){"scaleXY"===e.animationName&&window.parent.postMessage("visa-sensory-branding-end","*")},!1)}()},function(e,r){}]);