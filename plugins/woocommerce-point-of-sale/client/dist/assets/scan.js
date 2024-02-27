import{ab as D,g as R,aH as I,W as O,aI as T,a9 as M,aJ as L,aK as k,Y as w,ac as C,ad as H,b as N}from"../index.js";var P={exports:{}};(function(o,e){Object.defineProperty(e,"__esModule",{value:!0});var c=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol?"symbol":typeof t};e.default={onScan:function(){var u=arguments.length<=0||arguments[0]===void 0?{}:arguments[0],s=u.barcodePrefix,i=u.barcodeValueTest,n=u.finishScanOnMatch,r=u.scanDuration,a=arguments[1];if(typeof s!="string")throw new TypeError("barcodePrefix must be a string");if((typeof i>"u"?"undefined":c(i))!=="object"||typeof i.test!="function")throw new TypeError("barcodeValueTest must be a regular expression");if(n!=null&&typeof n!="boolean")throw new TypeError("finishScanOnMatch must be a boolean");if(r&&typeof r!="number")throw new TypeError("scanDuration must be a number");if(typeof a!="function")throw new TypeError("scanHandler must be a function");r=r||50;var l=null,v="",m="",S=!1,y=function(){S&&i.test(m)&&a(m),E()},E=function(){l=null,v="",m="",S=!1},b=function(g){var d=String.fromCharCode(g.which),p=s.indexOf(d),x=s.slice(0,p);l||(l=setTimeout(y,r)),v===x&&d===s.charAt(p)?v+=d:(S||s==="")&&(m+=d),v===s&&(S=!0,n&&i.test(m)&&(clearTimeout(l),y()))},f=function(){document.removeEventListener("keypress",b)};return document.addEventListener("keypress",b),f}},o.exports=e.default})(P,P.exports);var V=D(P.exports);function _(o,e){if(o.length>1||!R("pos","keyboard_shortcuts"))return;const c=I(),t=O(),u=T(),s=M(),i=L(),n=k(),r=H(),a=c.operatingMode,l=!u.cart_contents.size,v=()=>{!n.enableScanProduct||e.currentRoute.value.name!=="products"?(n.enableScanProduct=!0,e.push({name:"products"})):n.enableScanProduct=!1},m=()=>{if(a==="cart"){if(l){r(w(104),"error");return}t.initCheckout({amount:C(u.total),mode:"hold"})}},S=()=>{if(a==="cart"){if(l){r(w(104),"error");return}t.initCheckout({amount:C(u.total),mode:"default"})}if(a==="order"&&t.initCheckout({amount:C(s.order.total),mode:"pay"}),a==="refund"){if(!i.amount){r(w(385),"error");return}t.initCheckout({amount:i.amount,mode:"refund"})}};function y(){c.productAddModal=!0}function E(){T().clearCart(!0)}function b(d){var x;const p=document==null?void 0:document.getElementById(d);p&&((x=p==null?void 0:p.querySelector("input"))==null||x.focus())}function f(d){e.push({name:d})}const h=o.toUpperCase(),g={B:v,C:()=>f("coupon"),D:()=>f("discount"),F:()=>f("fee"),H:m,L:()=>b("productSearch"),N:()=>f("note"),O:()=>f("orders"),P:y,R:()=>f("products"),S:()=>f("shipping"),T:S,U:()=>b("customerSearch"),V:E};h in g&&g[h]()}function A(o){var e;!!(navigator!=null&&navigator.mediaDevices)&&!!((e=navigator.mediaDevices)!=null&&e.enumerateDevices)&&navigator.mediaDevices.enumerateDevices().then(c=>{c.length&&(o.videoInputDevices=c.filter(t=>t.kind==="videoinput").map(t=>({label:t.label,id:t.deviceId})))}).catch(c=>{window.console.error(c)})}function B(o,e){const c=()=>{var a;const n=((a=document.activeElement)==null?void 0:a.tagName)||"";return!!["INPUT","TEXTAREA"].includes(n)},t=()=>{const n=e.currentRoute.value.name||"";return!!["login","403","404"].includes(n)},u=n=>n.length===1?(_(n,e),!0):!1,s=()=>{if(o.enableScanCustomer)return"customer-scan";if(o.enableScanCustomerCard)return"customer-card-scan";if(o.enableScanNewProduct)return"new-product-scan";if(e.currentRoute.value.name==="products"&&o.enableScanProduct)return"product-scan";if(e.currentRoute.value.name==="products"&&!o.enableScanProduct)return"product-scan-ignored";if(e.currentRoute.value.name==="orders")return"order-scan";if(e.currentRoute.value.name==="coupon")return"coupon-scan"},i={barcodePrefix:"",barcodeValueTest:/.*/,finishScanOnMatch:!1,scanDuration:500};V.onScan(i,async n=>{const r=n.trim();if(!r||c()||t()||u(r))return;const a=s();a&&window.emitter.emit(a,r)})}var U=N(({router:o})=>{const e=k();A(e),B(e,o)});export{U as default};
