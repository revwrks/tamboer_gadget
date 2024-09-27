import{s as re}from"./index.Dmfi-JIL.js";import{s as se}from"./index.BIGMjE8y.js";import{s as ie}from"./index.DNQVPJbx.js";import{s as ue}from"./index.BimSrEtP.js";import{s as de}from"./index.BaYAw5mj.js";import{s as ne}from"./index.D9lkt8EW.js";import{s as ce,a as me}from"./index.BLUoHmHi.js";import{s as ve}from"./index.DFT7lvgp.js";import{s as pe}from"./index.DSQTaGDn.js";import{s as fe}from"./index.zYXIKiL0.js";import{u as n,G as _e,b1 as ge,v as be,D as _,b2 as F,c as p,a as s,k as o,w as u,o as d,O as x,t as I,p as E,n as c,a9 as ke}from"./app.DYb4vtlo.js";import"./index.D2YcutoK.js";import"./index.DU--r9eK.js";import"./index.DBRa2qmt.js";import"./index.oaTq0kd9.js";import"./index.CqPNYfth.js";import"./index.By_UZaj1.js";import"./index.QuCDjsSy.js";import"./index.Dl32Gjrb.js";import"./index.C5jhljM2.js";import"./index.yeH71diU.js";import"./index.DBHWkPF6.js";import"./index.Ddk8Teb6.js";import"./index.IlhIhN8w.js";import"./index.CnjyFZOC.js";import"./index.D4jsbkbY.js";import"./index.DkZQtvKd.js";const ye={class:"card"},he={class:"flex flex-wrap gap-2 items-center justify-between"},we=s("h4",{class:"m-0"},"Manage Products",-1),xe=s("i",{class:"pi pi-search"},null,-1),Ce={class:"flex flex-col gap-6"},De=s("label",{for:"tanggal",class:"block font-bold mb-3"},"Tanggal",-1),Ve={key:0,class:"text-red-500"},Ie=s("label",{for:"imei",class:"block font-bold mb-3"},"IMEI",-1),je={key:0,class:"text-red-500"},Pe=s("label",{for:"brand",class:"block font-bold mb-3"},"Brand",-1),Se={key:0,class:"text-red-500"},$e=s("label",{for:"nama",class:"block font-bold mb-3"},"Nama",-1),Me={key:0,class:"text-red-500"},qe=s("label",{for:"warna",class:"block font-bold mb-3"},"Warna",-1),Ue={key:0,class:"text-red-500"},Le=s("label",{for:"harga_masuk",class:"block font-bold mb-3"},"Harga Masuk",-1),Ne={key:0,class:"text-red-500"},Fe=s("label",{for:"harga_jual",class:"block font-bold mb-3"},"Harga Jual",-1),Ee={key:0,class:"text-red-500"},Te={class:"flex flex-col gap-6"},Be=s("label",{for:"harga_jual",class:"block font-bold mb-3"},"Harga Jual",-1),He={key:0,class:"text-red-500"},Re={class:"flex items-center gap-4"},Je=s("i",{class:"pi pi-exclamation-triangle !text-3xl"},null,-1),Oe={key:0},Ae={class:"flex items-center gap-4"},We=s("i",{class:"pi pi-exclamation-triangle !text-3xl"},null,-1),Ye={key:0},wa={__name:"Stok",setup(Ge){const C=n({global:{value:null,matchMode:ke.CONTAINS}}),m=n(),g=async()=>{try{const l=await _.get("/api/stocks");m.value=l.data}catch(l){console.error("Error fetching products:",l)}},j=n();_e(()=>{T(),g(),j.value=setInterval(g,1e4)}),ge(()=>{j.value&&clearInterval(j.value)});const $=n({}),v=be(),M=n(),b=n(!1),y=n(!1),h=n(!1),e=n({}),k=n(),D=n(!1),T=async()=>{try{const l=localStorage.getItem("token"),a=await _.get(`/api/users/${l}`);$.value=a.data}catch(l){console.error("Error fetching user data:",l)}};F(()=>{var a;const l=((a=C.value.global.value)==null?void 0:a.toLowerCase())||"";return m.value.filter(r=>r.imei.toLowerCase().includes(l)||r.brand.toLowerCase().includes(l)||r.nama.toLowerCase().includes(l)||r.warna.toLowerCase().includes(l)||r.harga_masuk.toLowerCase().includes(l)||r.harga_jual.toLowerCase().includes(l)||r.status.toLowerCase().includes(l))});const q=F(()=>$.value.level==="owner"),i=n(!1);function U(l){return l!=null?new Intl.NumberFormat("id-ID",{style:"currency",currency:"IDR",minimumFractionDigits:0,maximumFractionDigits:0}).format(Math.round(l)):"-"}function B(l){switch(l){case"ada":return"Ready";case"terjual":return"Sold";default:return l}}function H(l){switch(l){case"ada":return"success";case"terjual":return"danger";default:return null}}function R(){e.value={},e.value.tanggal=new Date,i.value=!1,b.value=!0}function L(){b.value=!1,i.value=!1}function J(){var l;i.value=!0,(l=e==null?void 0:e.value.nama)!=null&&l.trim()&&(e.value.id_stock?_.put(`/api/stocks/${e.value.id_stock}`,{...e.value,status:e.value.status||"ada"}).then(()=>{const a=z(e.value.id_stock);m.value[a]=e.value,g(),v.add({severity:"success",summary:"Successful",detail:"Product Updated",life:3e3})}).catch(a=>{v.add({severity:"error",summary:"Error",detail:"Failed to update product",life:3e3})}):_.post("/api/stocks",{...e.value,id:null,status:"ada"}).then(a=>{e.value.id_stock=a.data.stok_hp.id_stock,m.value.push(e.value),g(),v.add({severity:"success",summary:"Successful",detail:"Product Created",life:3e3})}).catch(a=>{v.add({severity:"error",summary:"Error",detail:"Failed to create product",life:3e3})}),b.value=!1,e.value={})}function O(l){const a={ada:1,terjual:2};l.data.sort((r,P)=>a[r.status]-a[P.status])}function A(l){e.value={...l},D.value=!0}function W(){const l=localStorage.getItem("token");_.post("/api/penjualan",{tanggal:new Date,id_stock:e.value.id_stock,harga_jual:e.value.harga_jual,id_sales:l}).then(a=>{_.put(`/api/stocks/${e.value.id_stock}`,{status:"terjual"}).then(()=>{g(),v.add({severity:"success",summary:"Success",detail:"Penjualan created and stock updated",life:3e3})}).catch(r=>{v.add({severity:"error",summary:"Error",detail:"Failed to update stock status",life:3e3})})}).catch(a=>{v.add({severity:"error",summary:"Error",detail:"Failed to create penjualan",life:3e3})}),D.value=!1,e.value={}}function Y(l){e.value={...l},b.value=!0}function G(l){e.value=l,y.value=!0}function K(){_.delete(`/api/stocks/${e.value.id_stock}`).then(()=>{m.value=m.value.filter(l=>l.id_stock!==e.value.id_stock),g(),v.add({severity:"success",summary:"Successful",detail:"Product Deleted",life:3e3})}).catch(l=>{v.add({severity:"error",summary:"Error",detail:"Failed to delete product",life:3e3})}),y.value=!1,e.value={}}function z(l){let a=-1;for(let r=0;r<m.value.length;r++)if(m.value[r].id_stock===l){a=r;break}return a}function Q(){M.value.exportCSV()}function X(){h.value=!0}function Z(){const l=k.value.map(r=>r.id_stock),a=l.map(r=>_.delete(`/api/stocks/${r}`));Promise.all(a).then(()=>{m.value=m.value.filter(r=>!l.includes(r.id_stock)),g(),v.add({severity:"success",summary:"Successful",detail:"Products Deleted",life:3e3})}).catch(r=>{v.add({severity:"error",summary:"Error",detail:"Failed to delete selected products",life:3e3})}),h.value=!1,k.value=null}return(l,a)=>{const r=fe,P=pe,ee=ce,w=ve,ae=me,f=ne,le=de,te=ue,oe=ie,S=se,V=re;return d(),p("div",null,[s("div",ye,[o(P,{class:"mb-6"},{start:u(()=>[o(r,{label:"New",icon:"pi pi-plus",severity:"secondary",class:"mr-2",onClick:R}),o(r,{label:"Delete",icon:"pi pi-trash",severity:"secondary",onClick:X,disabled:!k.value||!k.value.length},null,8,["disabled"])]),end:u(()=>[o(r,{label:"Export",icon:"pi pi-upload",severity:"secondary",onClick:a[0]||(a[0]=t=>Q(t))})]),_:1}),o(te,{ref_key:"dt",ref:M,selection:k.value,"onUpdate:selection":a[2]||(a[2]=t=>k.value=t),value:m.value,dataKey:"id_stock",paginator:!0,rows:10,filters:C.value,paginatorTemplate:"FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",rowsPerPageOptions:[5,10,25],currentPageReportTemplate:"Showing {first} to {last} of {totalRecords} products",sortField:"status",sortOrder:1,customSort:!0,onSort:O},{header:u(()=>[s("div",he,[we,o(ae,null,{default:u(()=>[o(ee,null,{default:u(()=>[xe]),_:1}),o(w,{modelValue:C.value.global.value,"onUpdate:modelValue":a[1]||(a[1]=t=>C.value.global.value=t),placeholder:"Search..."},null,8,["modelValue"])]),_:1})])]),default:u(()=>[o(f,{selectionMode:"multiple",style:{width:"3rem"},exportable:!1}),o(f,{field:"tanggal",header:"Tanggal",sortable:""},{body:u(t=>[x(I(new Date(t.data.tanggal).toLocaleDateString("id-ID")),1)]),_:1}),o(f,{field:"status",header:"Status",sortable:"",style:{"min-width":"5rem"}},{body:u(t=>[o(le,{value:B(t.data.status),severity:H(t.data.status)},null,8,["value","severity"])]),_:1}),o(f,{field:"nama",header:"Nama",sortable:"",style:{"min-width":"12rem"}}),o(f,{field:"warna",header:"Warna",sortable:"",style:{"min-width":"12rem"}}),o(f,{field:"harga_masuk",header:"Harga Masuk",sortable:"",style:{"min-width":"8rem"}},{body:u(t=>[x(I(U(t.data.harga_masuk)),1)]),_:1}),q.value?(d(),E(f,{key:0,field:"harga_jual",header:"Harga Jual",sortable:"",style:{"min-width":"8rem"}},{body:u(t=>[x(I(U(t.data.harga_jual)),1)]),_:1})):c("",!0),o(f,{field:"brand",header:"Brand",sortable:"",style:{"min-width":"12rem"}}),o(f,{field:"imei",header:"IMEI",sortable:"",style:{"min-width":"12rem"}}),o(f,{exportable:!1,style:{"min-width":"12rem"}},{body:u(t=>[o(r,{icon:"pi pi-money-bill",outlined:"",rounded:"",class:"mr-2",onClick:N=>A(t.data),disabled:t.data.status==="terjual"},null,8,["onClick","disabled"]),q.value?(d(),E(r,{key:0,icon:"pi pi-pencil",outlined:"",rounded:"",class:"mr-2",onClick:N=>Y(t.data)},null,8,["onClick"])):c("",!0),o(r,{icon:"pi pi-trash",outlined:"",rounded:"",severity:"danger",onClick:N=>G(t.data)},null,8,["onClick"])]),_:1})]),_:1},8,["selection","value","filters"])]),o(V,{visible:b.value,"onUpdate:visible":a[10]||(a[10]=t=>b.value=t),style:{width:"450px"},header:"Product Details",modal:!0},{footer:u(()=>[o(r,{label:"Cancel",icon:"pi pi-times",text:"",onClick:L}),o(r,{label:"Save",icon:"pi pi-check",onClick:J})]),default:u(()=>[s("div",Ce,[s("div",null,[De,o(oe,{id:"tanggal",modelValue:e.value.tanggal,"onUpdate:modelValue":a[3]||(a[3]=t=>e.value.tanggal=t),modelModifiers:{trim:!0},required:"",dateFormat:"dd/mm/yy",autofocus:"",invalid:i.value&&!e.value.tanggal,fluid:"",showIcon:"",disabled:!!e.value.id_stock},null,8,["modelValue","invalid","disabled"]),i.value&&!e.value.tanggal?(d(),p("small",Ve,"Tanggal is required.")):c("",!0)]),s("div",null,[Ie,o(w,{id:"imei",modelValue:e.value.imei,"onUpdate:modelValue":a[4]||(a[4]=t=>e.value.imei=t),modelModifiers:{trim:!0},required:"",invalid:i.value&&!e.value.imei,fluid:"",disabled:!!e.value.id_stock},null,8,["modelValue","invalid","disabled"]),i.value&&!e.value.imei?(d(),p("small",je,"IMEI is required.")):c("",!0)]),s("div",null,[Pe,o(w,{id:"brand",modelValue:e.value.brand,"onUpdate:modelValue":a[5]||(a[5]=t=>e.value.brand=t),modelModifiers:{trim:!0},required:"",invalid:i.value&&!e.value.brand,fluid:""},null,8,["modelValue","invalid"]),i.value&&!e.value.brand?(d(),p("small",Se,"Brand is required.")):c("",!0)]),s("div",null,[$e,o(w,{id:"nama",modelValue:e.value.nama,"onUpdate:modelValue":a[6]||(a[6]=t=>e.value.nama=t),modelModifiers:{trim:!0},required:"",invalid:i.value&&!e.value.nama,fluid:""},null,8,["modelValue","invalid"]),i.value&&!e.value.nama?(d(),p("small",Me,"Nama is required.")):c("",!0)]),s("div",null,[qe,o(w,{id:"warna",modelValue:e.value.warna,"onUpdate:modelValue":a[7]||(a[7]=t=>e.value.warna=t),modelModifiers:{trim:!0},required:"",invalid:i.value&&!e.value.warna,fluid:""},null,8,["modelValue","invalid"]),i.value&&!e.value.warna?(d(),p("small",Ue,"Warna is required.")):c("",!0)]),s("div",null,[Le,o(S,{id:"harga_masuk",modelValue:e.value.harga_masuk,"onUpdate:modelValue":a[8]||(a[8]=t=>e.value.harga_masuk=t),required:"",mode:"currency",currency:"IDR",locale:"id-ID",fluid:"",invalid:i.value&&!e.value.harga_masuk},null,8,["modelValue","invalid"]),i.value&&!e.value.harga_masuk?(d(),p("small",Ne,"Harga Masuk is required.")):c("",!0)]),s("div",null,[Fe,o(S,{id:"harga_jual",modelValue:e.value.harga_jual,"onUpdate:modelValue":a[9]||(a[9]=t=>e.value.harga_jual=t),required:"",mode:"currency",currency:"IDR",locale:"id-ID",fluid:"",invalid:i.value&&!e.value.harga_jual},null,8,["modelValue","invalid"]),i.value&&!e.value.harga_jual?(d(),p("small",Ee,"Harga Jual is required.")):c("",!0)])])]),_:1},8,["visible"]),o(V,{visible:D.value,"onUpdate:visible":a[12]||(a[12]=t=>D.value=t),style:{width:"450px"},header:"Harga Jual",modal:!0},{footer:u(()=>[o(r,{label:"Cancel",icon:"pi pi-times",text:"",onClick:L}),o(r,{label:"Save",icon:"pi pi-check",onClick:W})]),default:u(()=>[s("div",Te,[s("div",null,[Be,o(S,{id:"harga_jual",modelValue:e.value.harga_jual,"onUpdate:modelValue":a[11]||(a[11]=t=>e.value.harga_jual=t),required:"",mode:"currency",currency:"IDR",locale:"id-ID",fluid:"",invalid:i.value&&!e.value.harga_jual},null,8,["modelValue","invalid"]),i.value&&!e.value.harga_jual?(d(),p("small",He,"Harga Jual is required.")):c("",!0)])])]),_:1},8,["visible"]),o(V,{visible:y.value,"onUpdate:visible":a[14]||(a[14]=t=>y.value=t),style:{width:"450px"},header:"Confirm",modal:!0},{footer:u(()=>[o(r,{label:"No",icon:"pi pi-times",text:"",onClick:a[13]||(a[13]=t=>y.value=!1)}),o(r,{label:"Yes",icon:"pi pi-check",onClick:K})]),default:u(()=>[s("div",Re,[Je,e.value?(d(),p("span",Oe,[x("Are you sure you want to delete "),s("b",null,I(e.value.name),1),x("?")])):c("",!0)])]),_:1},8,["visible"]),o(V,{visible:h.value,"onUpdate:visible":a[16]||(a[16]=t=>h.value=t),style:{width:"450px"},header:"Confirm",modal:!0},{footer:u(()=>[o(r,{label:"No",icon:"pi pi-times",text:"",onClick:a[15]||(a[15]=t=>h.value=!1)}),o(r,{label:"Yes",icon:"pi pi-check",text:"",onClick:Z})]),default:u(()=>[s("div",Ae,[We,e.value?(d(),p("span",Ye,"Are you sure you want to delete the selected products?")):c("",!0)])]),_:1},8,["visible"])])}}};export{wa as default};
