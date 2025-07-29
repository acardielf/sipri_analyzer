/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/chartjs-plugin-autocolors@0.3.1/dist/chartjs-plugin-autocolors.esm.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import{hsv2rgb as o,rgbString as t}from"@kurkle/color";
/*!
 * chartjs-plugin-autocolors v0.3.1
 * https://github.com/kurkle/chartjs-plugin-autocolors#readme
 * (c) 2024 Jukka Kurkela <jukka.kurkela@gmail.com>
 * Released under the MIT license
 */function e(o,t,e,r){return"data"===r?(o.backgroundColor=t,o.border=e):(o.backgroundColor=o.backgroundColor||t,o.borderColor=o.borderColor||e),o.backgroundColor===t&&o.borderColor===e}function r(o,t,e){const r=o.next().value;return"function"==typeof t?t(Object.assign({colors:r},e)):r}const a={id:"autocolors",beforeUpdate(a,n,d){const{mode:c="dataset",enabled:l=!0,customize:s,repeat:u}=d;if(!l)return;const f=function*(e=1){const r=function*(){for(yield 0;;)for(let o=1;o<10;o++){const t=1<<o;for(let o=1;o<=t;o+=2)yield o/t}}();let a=r.next();for(;!a.done;){let n=o(Math.round(360*a.value),.6,.8);for(let o=0;o<e;o++)yield{background:t({r:n[0],g:n[1],b:n[2],a:192}),border:t({r:n[0],g:n[1],b:n[2],a:144})};n=o(Math.round(360*a.value),.6,.5);for(let o=0;o<e;o++)yield{background:t({r:n[0],g:n[1],b:n[2],a:192}),border:t({r:n[0],g:n[1],b:n[2],a:144})};a=r.next()}}(u);if(d.offset)for(let o=0;o<d.offset;o++)f.next();return"label"===c?function(o,t,a,n){const d={};for(const c of o.data.datasets){const l=c.label??"";d[l]||(d[l]=r(t,a,{chart:o,datasetIndex:0,dataIndex:void 0,label:l}));const s=d[l];e(c,s.background,s.border,n)}}(a,f,s,c):function(o,t,a,n){const d="dataset"===n;let c=r(t,a,{chart:o,datasetIndex:0,dataIndex:d?void 0:0});for(const l of o.data.datasets)if(d)e(l,c.background,c.border,n)&&(c=r(t,a,{chart:o,datasetIndex:l.index}));else{const d=[],s=[];for(let e=0;e<l.data.length;e++)d.push(c.background),s.push(c.border),c=r(t,a,{chart:o,datasetIndex:l.index,dataIndex:e});e(l,d,s,n)}}(a,f,s,c)}};export{a as default};
