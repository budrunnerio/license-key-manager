import{b as e,I as a}from"../index.js";var n=e(async({router:r})=>{await a.init(),a.instance.on("versionchange",()=>{r.push({name:"error-reload-register"})})});export{n as default};
