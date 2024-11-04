import{s as m}from"./index.CKPiWbrn.js";import{B as _,o as v,c as b,k as y,m as h,n as g,u,a as s,j as e,w as p}from"./app.nOLxvhWu.js";import{s as w}from"./index.DcXiv5Mf.js";import"./index.5cZuQ5Px.js";import"./index.Cwe1jGmX.js";import"./index.pxpWFvq0.js";import"./index.B50m-utj.js";import"./index.CYijPz_l.js";var S=function(o){return o.dt,`
.p-buttongroup .p-button {
    margin: 0;
}

.p-buttongroup .p-button:not(:last-child),
.p-buttongroup .p-button:not(:last-child):hover {
    border-right: 0 none;
}

.p-buttongroup .p-button:not(:first-of-type):not(:last-of-type) {
    border-radius: 0;
}

.p-buttongroup .p-button:first-of-type:not(:only-of-type) {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.p-buttongroup .p-button:last-of-type:not(:only-of-type) {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.p-buttongroup .p-button:focus {
    position: relative;
    z-index: 1;
}
`},k={root:"p-buttongroup p-component"},B=_.extend({name:"buttongroup",theme:S,classes:k}),$={name:"BaseButtonGroup",extends:g,style:B,provide:function(){return{$pcButtonGroup:this,$parentInstance:this}}},f={name:"ButtonGroup",extends:$,inheritAttrs:!1};function D(a,o,n,r,d,l){return v(),b("span",h({class:a.cx("root"),role:"group"},a.ptmi("root")),[y(a.$slots,"default")],16)}f.render=D;const C="/build/assets/logo-white.2XEe4oVr.svg",P="/build/assets/logo.DbEabZU5.svg",I={class:"flex flex-col md:flex-row gap-8"},G={class:"md:w-1/2"},H={class:"card flex flex-col gap-4"},R=s("div",{class:"font-semibold text-xl"},"Default",-1),T={class:"flex flex-wrap gap-2"},V={class:"card flex flex-col gap-4"},W=s("div",{class:"font-semibold text-xl"},"Severities",-1),E={class:"flex flex-wrap gap-2"},L={class:"card flex flex-col gap-4"},N=s("div",{class:"font-semibold text-xl"},"Text",-1),O={class:"flex flex-wrap gap-2"},U={class:"card flex flex-col gap-4"},j=s("div",{class:"font-semibold text-xl"},"Outlined",-1),z={class:"flex flex-wrap gap-2"},A={class:"card flex flex-col gap-4"},X=s("div",{class:"font-semibold text-xl"},"Group",-1),Z={class:"flex flex-wrap gap-2"},q={class:"card flex flex-col gap-4"},F=s("div",{class:"font-semibold text-xl"},"SplitButton",-1),J={class:"flex flex-wrap gap-2"},K={class:"card flex flex-col gap-4"},M=s("div",{class:"font-semibold text-xl"},"Templating",-1),Q={class:"flex flex-wrap gap-2"},Y=s("img",{alt:"logo",src:C,style:{width:"1.5rem"}},null,-1),ee=s("img",{alt:"logo",src:P,style:{width:"1.5rem"}},null,-1),te=s("span",{class:"ml-2 text-bold"},"PrimeVue",-1),se={class:"md:w-1/2"},oe={class:"card flex flex-col gap-4"},le=s("div",{class:"font-semibold text-xl"},"Icons",-1),ie={class:"flex flex-wrap gap-2"},ne={class:"card flex flex-col gap-4"},ae=s("div",{class:"font-semibold text-xl"},"Raised",-1),re={class:"flex flex-wrap gap-2"},de={class:"card flex flex-col gap-4"},ce=s("div",{class:"font-semibold text-xl"},"Rounded",-1),pe={class:"flex flex-wrap gap-2"},ue={class:"card flex flex-col gap-4"},ve=s("div",{class:"font-semibold text-xl"},"Rounded Icons",-1),be={class:"flex flex-wrap gap-2"},fe={class:"card flex flex-col gap-4"},xe=s("div",{class:"font-semibold text-xl"},"Rounded Text",-1),me={class:"flex flex-wrap gap-2"},_e={class:"card flex flex-col gap-4"},ye=s("div",{class:"font-semibold text-xl"},"Rounded Outlined",-1),he={class:"flex flex-wrap gap-2"},ge={class:"card flex flex-col gap-4"},we=s("div",{class:"font-semibold text-xl"},"Loading",-1),Se={class:"flex flex-wrap gap-2"},He={__name:"ButtonDoc",setup(a){const o=u([{label:"Update",icon:"pi pi-refresh"},{label:"Delete",icon:"pi pi-times"},{separator:!0},{label:"Home",icon:"pi pi-home"}]),n=u([!1,!1,!1]);function r(d){n.value[d]=!0,setTimeout(()=>n.value[d]=!1,1e3)}return(d,l)=>{const t=w,x=f,i=m;return v(),b("div",I,[s("div",G,[s("div",H,[R,s("div",T,[e(t,{label:"Submit"}),e(t,{label:"Disabled",disabled:!0}),e(t,{label:"Link",class:"p-button-link"})])]),s("div",V,[W,s("div",E,[e(t,{label:"Primary"}),e(t,{label:"Secondary",severity:"secondary"}),e(t,{label:"Success",severity:"success"}),e(t,{label:"Info",severity:"info"}),e(t,{label:"Warn",severity:"warn"}),e(t,{label:"Help",severity:"help"}),e(t,{label:"Danger",severity:"danger"}),e(t,{label:"Contrast",severity:"contrast"})])]),s("div",L,[N,s("div",O,[e(t,{label:"Primary",text:""}),e(t,{label:"Secondary",severity:"secondary",text:""}),e(t,{label:"Success",severity:"success",text:""}),e(t,{label:"Info",severity:"info",text:""}),e(t,{label:"Warn",severity:"warn",text:""}),e(t,{label:"Help",severity:"help",text:""}),e(t,{label:"Danger",severity:"danger",text:""}),e(t,{label:"Plain",plain:"",text:""})])]),s("div",U,[j,s("div",z,[e(t,{label:"Primary",outlined:""}),e(t,{label:"Secondary",severity:"secondary",outlined:""}),e(t,{label:"Success",severity:"success",outlined:""}),e(t,{label:"Info",severity:"info",outlined:""}),e(t,{label:"warn",severity:"warn",outlined:""}),e(t,{label:"Help",severity:"help",outlined:""}),e(t,{label:"Danger",severity:"danger",outlined:""}),e(t,{label:"Contrast",severity:"contrast",outlined:""})])]),s("div",A,[X,s("div",Z,[e(x,null,{default:p(()=>[e(t,{label:"Save",icon:"pi pi-check"}),e(t,{label:"Delete",icon:"pi pi-trash"}),e(t,{label:"Cancel",icon:"pi pi-times"})]),_:1})])]),s("div",q,[F,s("div",J,[e(i,{label:"Save",model:o.value},null,8,["model"]),e(i,{label:"Save",model:o.value,severity:"secondary"},null,8,["model"]),e(i,{label:"Save",model:o.value,severity:"success"},null,8,["model"]),e(i,{label:"Save",model:o.value,severity:"info"},null,8,["model"]),e(i,{label:"Save",model:o.value,severity:"warn"},null,8,["model"]),e(i,{label:"Save",model:o.value,severity:"help"},null,8,["model"]),e(i,{label:"Save",model:o.value,severity:"danger"},null,8,["model"]),e(i,{label:"Save",model:o.value,severity:"contrast"},null,8,["model"])])]),s("div",K,[M,s("div",Q,[e(t,{type:"button"},{default:p(()=>[Y]),_:1}),e(t,{type:"button",outlined:"",severity:"success"},{default:p(()=>[ee,te]),_:1})])])]),s("div",se,[s("div",oe,[le,s("div",ie,[e(t,{icon:"pi pi-star-fill",class:"mr-2 mb-2"}),e(t,{label:"Bookmark",icon:"pi pi-bookmark",class:"mr-2 mb-2"}),e(t,{label:"Bookmark",icon:"pi pi-bookmark",iconPos:"right",class:"mr-2 mb-2"})])]),s("div",ne,[ae,s("div",re,[e(t,{label:"Primary",raised:""}),e(t,{label:"Secondary",severity:"secondary",raised:""}),e(t,{label:"Success",severity:"success",raised:""}),e(t,{label:"Info",severity:"info",raised:""}),e(t,{label:"Warn",severity:"warn",raised:""}),e(t,{label:"Help",severity:"help",raised:""}),e(t,{label:"Danger",severity:"danger",raised:""}),e(t,{label:"Contrast",severity:"contrast",raised:""})])]),s("div",de,[ce,s("div",pe,[e(t,{label:"Primary",rounded:""}),e(t,{label:"Secondary",severity:"secondary",rounded:""}),e(t,{label:"Success",severity:"success",rounded:""}),e(t,{label:"Info",severity:"info",rounded:""}),e(t,{label:"Warn",severity:"warn",rounded:""}),e(t,{label:"Help",severity:"help",rounded:""}),e(t,{label:"Danger",severity:"danger",rounded:""}),e(t,{label:"Contrast",severity:"contrast",rounded:""})])]),s("div",ue,[ve,s("div",be,[e(t,{icon:"pi pi-check",rounded:""}),e(t,{icon:"pi pi-bookmark",severity:"secondary",rounded:""}),e(t,{icon:"pi pi-search",severity:"success",rounded:""}),e(t,{icon:"pi pi-user",severity:"info",rounded:""}),e(t,{icon:"pi pi-bell",severity:"warn",rounded:""}),e(t,{icon:"pi pi-heart",severity:"help",rounded:""}),e(t,{icon:"pi pi-times",severity:"danger",rounded:""})])]),s("div",fe,[xe,s("div",me,[e(t,{icon:"pi pi-check",text:"",raised:"",rounded:""}),e(t,{icon:"pi pi-bookmark",severity:"secondary",text:"",raised:"",rounded:""}),e(t,{icon:"pi pi-search",severity:"success",text:"",raised:"",rounded:""}),e(t,{icon:"pi pi-user",severity:"info",text:"",raised:"",rounded:""}),e(t,{icon:"pi pi-bell",severity:"warn",text:"",raised:"",rounded:""}),e(t,{icon:"pi pi-heart",severity:"help",text:"",raised:"",rounded:""}),e(t,{icon:"pi pi-times",severity:"danger",text:"",raised:"",rounded:""})])]),s("div",_e,[ye,s("div",he,[e(t,{icon:"pi pi-check",rounded:"",outlined:""}),e(t,{icon:"pi pi-bookmark",severity:"secondary",rounded:"",outlined:""}),e(t,{icon:"pi pi-search",severity:"success",rounded:"",outlined:""}),e(t,{icon:"pi pi-user",severity:"info",rounded:"",outlined:""}),e(t,{icon:"pi pi-bell",severity:"warn",rounded:"",outlined:""}),e(t,{icon:"pi pi-heart",severity:"help",rounded:"",outlined:""}),e(t,{icon:"pi pi-times",severity:"danger",rounded:"",outlined:""})])]),s("div",ge,[we,s("div",Se,[e(t,{type:"button",class:"mr-2 mb-2",label:"Search",icon:"pi pi-search",loading:n.value[0],onClick:l[0]||(l[0]=c=>r(0))},null,8,["loading"]),e(t,{type:"button",class:"mr-2 mb-2",label:"Search",icon:"pi pi-search",iconPos:"right",loading:n.value[1],onClick:l[1]||(l[1]=c=>r(1))},null,8,["loading"]),e(t,{type:"button",class:"mr-2 mb-2",icon:"pi pi-search",loading:n.value[2],onClick:l[2]||(l[2]=c=>r(2))},null,8,["loading"]),e(t,{type:"button",class:"mr-2 mb-2",label:"Search",loading:n.value[3],onClick:l[3]||(l[3]=c=>r(3))},null,8,["loading"])])])])])}}};export{He as default};