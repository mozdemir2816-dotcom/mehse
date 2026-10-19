@verbatim
<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><title>3B Acil Durum Kroki Editörü</title>
<style>
:root{--bg:#fff;--fg:#0f2742;--mut:#5b6b80;--ln:#d6dde8;--pri:#1d4e89;--red:#d64545;--card:#f3f6fb;box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){--bg:#111a26;--fg:#e6edf6;--mut:#9fb0c5;--ln:#2b3b50;--card:#1a2636}}
:root[data-theme="dark"]{--bg:#111a26;--fg:#e6edf6;--mut:#9fb0c5;--ln:#2b3b50;--card:#1a2636}
html{scroll-padding-top:env(safe-area-inset-top,0px)}*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--fg);font:14px/1.4 system-ui,"Segoe UI",Arial,sans-serif}
header{background:linear-gradient(100deg,#0f2f5c,#1d4e89 55%,#c0392b);color:#fff;padding:12px 16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.logo{background:#d64545;border-radius:8px;padding:6px 10px;font-weight:800;letter-spacing:.5px}
header h1{font-size:17px;margin:0}header small{opacity:.85;display:block}
#bar{display:flex;flex-wrap:wrap;gap:6px;padding:8px 12px;border-bottom:1px solid var(--ln);background:var(--card)}
button,select,input{font:inherit;color:var(--fg)}
button{background:var(--bg);border:1px solid var(--ln);border-radius:7px;padding:6px 10px;cursor:pointer}
button.on{background:var(--pri);color:#fff;border-color:var(--pri)}button.red{background:var(--red);color:#fff;border-color:var(--red)}
button:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid var(--pri);outline-offset:1px}
#main{display:flex;flex-wrap:wrap}
#stage{flex:1 1 560px;min-width:0;height:62vh;min-height:360px;position:relative;background:var(--card)}
#cv{width:100%;height:100%;display:block;touch-action:none}
#hint{position:absolute;left:8px;bottom:6px;font-size:12px;color:var(--mut);pointer-events:none}
aside{flex:0 0 340px;max-width:100%;padding:8px 12px;border-left:1px solid var(--ln)}
@media(max-width:900px){aside{flex:1 1 100%;border-left:0}}
details{border:1px solid var(--ln);border-radius:8px;margin:0 0 8px;background:var(--bg)}summary{padding:8px 10px;font-weight:700;cursor:pointer}
details>div{padding:0 10px 10px}
label{display:block;font-size:12px;color:var(--mut);margin:6px 0 0}
input,select{width:100%;padding:6px;border:1px solid var(--ln);border-radius:6px;background:var(--bg)}
input[type=color]{padding:1px;height:32px}
.row{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}.row>*{flex:1}
#sg,#og{display:grid;grid-template-columns:repeat(auto-fill,minmax(78px,1fr));gap:6px;margin-top:8px;max-height:320px;overflow:auto}
#sg button,#og button{padding:4px;display:flex;flex-direction:column;align-items:center;gap:2px}#sg button span,#og button span{font-size:9.5px;line-height:1.1;text-align:center}textarea{width:100%;padding:6px;border:1px solid var(--ln);border-radius:6px;background:var(--bg);font:inherit;color:var(--fg)}
.ok{color:#0a8a3c}.no{color:#c0392b}#chk li{margin:4px 0;list-style:none}#chk ul{padding:0;margin:0}
table{border-collapse:collapse;width:100%;font-size:12px}td,th{border:1px solid var(--ln);padding:3px 5px;text-align:left}
#st{flex:0 0 210px;padding:8px 10px;border-right:1px solid var(--ln);max-height:calc(62vh + 70px);overflow:auto}#st h2{font-size:14px;margin:2px 0 8px}#st>button{width:100%;margin:2px 0 8px}
.sc{display:block;width:100%;text-align:left;margin:0 0 8px;padding:6px}.sc.on{background:var(--card);border:2px solid var(--pri);color:var(--fg)}.sc b{display:block;font-size:12px;margin-top:4px}.sc small{color:var(--mut);font-size:11px;line-height:1.2;display:block}
#ov{position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:9;display:flex;flex-direction:column;align-items:center;gap:8px;overflow:auto;padding:12px}#ov[hidden]{display:none}#ov img{max-width:100%;background:#fff}
@media(max-width:900px){#st{flex:1 1 100%;border-right:0;border-bottom:1px solid var(--ln);max-height:none}#stl{display:flex;gap:8px;overflow-x:auto}.sc{flex:0 0 160px;margin:0}}
#prn{display:none}@media print{body>*:not(#prn){display:none!important}#prn{display:block}#prn img{width:100%;display:block;page-break-after:always}@page{size:A4 landscape;margin:6mm}}
</style></head><body>

<div id="bar">
<button data-t="select" class="on">Seç / Taşı</button><button data-t="obj">Alan / Nesne</button><button data-t="wall">Duvar</button><button data-t="sign">İşaret</button><button data-t="arrow">Yön oku</button><button data-t="route">Tahliye rotası</button>
<button id="fin">Rotayı bitir</button><button id="un">Geri</button><button id="re">İleri</button><button id="rv">Görünümü çevir</button><button id="zi">+</button><button id="zo">−</button><button id="ft">Sığdır</button>
</div>
<div id="main"><nav id="st"><h2>Görüntü tarzları</h2><button id="pv">Tam sayfa önizleme</button><div id="stl"></div></nav><div id="stage"><canvas id="cv"></canvas><div id="hint"></div></div>
<aside>
<details open><summary>1. Yerleştirme</summary><div>
<label>Alan / nesne grubu<select id="ocat"><option value="">Tümü</option><option value="U">Üretim makineleri</option><option value="O">Ofis</option><option value="S">WC ve sosyal alan</option><option value="D">Depo ve lojistik</option><option value="A">Araç ve iş makinesi</option><option value="Z">Alan ve yol zeminleri</option></select></label><div id="og"></div><select id="pre" hidden></select>
<label>İşaret grubu<select id="cat"><option value="">Tümü</option><option value="P">Yasaklama (kırmızı daire)</option><option value="W">Uyarı (sarı üçgen)</option><option value="M">Emredici (mavi daire)</option><option value="E">Acil çıkış ve ilk yardım (yeşil)</option><option value="F">Yangınla mücadele (kırmızı)</option><option value="D">Yön işaretleri (sağ/sol/yukarı/aşağı)</option><option value="Y">Buradasınız</option></select></label>
<div id="sg"></div><div class="row" id="ar"></div></div></details>
<details open><summary>2. Seçili öğe</summary><div id="pr"></div></details>
<details open><summary>3. Mevzuat ön kontrolü</summary><div id="chk"></div></details>
<details open><summary>Acil durum bilgilendirme yazıları</summary><div id="nt"></div></details>
<details open><summary>Acil durum ekipleri (lejand)</summary><div id="tm"></div></details>
<details><summary>4. İşletme ve plan bilgileri</summary><div id="inf"></div></details>
<details><summary>5. Dosya</summary><div><div class="row"><button id="png">PNG indir</button><button id="prt">Yazdır / PDF</button></div><div class="row"><button id="mk" class="red">Kroki'yi kaydet</button></div><div class="row"><button id="sv">JSON kaydet</button><button id="ld">JSON yükle</button><button id="ex">Örnek kroki</button></div><input type="file" id="fi" accept=".json" hidden></div></details>
<details><summary>İşaret renk ve şekil kuralları</summary><div><table><tr><th>Grup</th><th>Şekil</th><th>Renk</th></tr><tr><td>E</td><td>Kare / dikdörtgen</td><td>Yeşil zemin, beyaz sembol</td></tr><tr><td>F</td><td>Kare</td><td>Kırmızı zemin, beyaz sembol</td></tr><tr><td>W</td><td>Üçgen</td><td>Sarı zemin, siyah kenar ve sembol</td></tr><tr><td>P</td><td>Daire + çapraz çizgi</td><td>Beyaz zemin, kırmızı kenar, siyah sembol</td></tr><tr><td>M</td><td>Daire</td><td>Mavi zemin, beyaz sembol</td></tr></table></div></details>
</aside></div>
<div id="ov" hidden><button id="ovc">Kapat</button><div id="ovs"></div></div><div id="prn"></div>
<script>
const $=s=>document.querySelector(s),C=$('#cv'),X=C.getContext('2d');
const CAT={E:{bg:'#009a44',fg:'#fff',sh:'s'},F:{bg:'#d62718',fg:'#fff',sh:'s'},W:{bg:'#ffd400',fg:'#111',sh:'t'},P:{bg:'#fff',fg:'#111',sh:'p'},M:{bg:'#0057b8',fg:'#fff',sh:'c'},Y:{bg:'#0b3d91',fg:'#fff',sh:'pin'}};
const SG=[['E001','Acil çıkış','run'],['E007','Toplanma noktası','ppl'],['E003','İlk yardım','cross'],['E004','Acil durum telefonu','tel'],['E011','Göz duşu','eye'],['E012','Acil duş','shw'],
['F001','Yangın söndürme tüpü','ext'],['F002','Yangın dolabı / hortum','hose'],['F003','Yangın merdiveni','lad'],['F005','Yangın alarm butonu','btn'],['F004','Yangın telefonu','tel'],
['W001','Genel tehlike','!'],['W012','Elektrik tehlikesi','bolt'],['W021','Yanıcı madde','flame'],['W014','Forklift / araç trafiği','fork'],['W023','Aşındırıcı madde','corr'],['W017','Sıcak yüzey','heat'],
['P001','Genel yasak','none'],['P002','Sigara içilmez','smk'],['P003','Açık alev yasak','flame'],['P011','Suyla söndürme yasak','drop'],
['M001','Genel zorunluluk','!'],['M003','Kulak koruyucu','ear'],['M004','Göz koruyucu','gog'],['M008','Koruyucu ayakkabı','shoe'],['M009','Koruyucu eldiven','glv'],['M014','Baret','hat'],['M016','Toz maskesi','mask'],['YOU','Buradasınız','pin']];
const SN={};SG.forEach(s=>SN[s[0]]=s);
const PR={'CNC / torna':['b','#7b8fa6',1.8,'CNC','cnc',3,2,'U'],'Pres':['b','#6f7f92',2.2,'Pres','press',3,2.5,'U'],'Genel makine':['b','#7b8fa6',1.6,'Makine','mach',2.5,2,'U'],'Kompresör':['b','#2f6fb5',1.5,'Kompresör','comp',2,1.2,'U'],'Konveyör':['b','#8a929c',1,'Konveyör','conv',6,1,'U'],'Dikiş makinesi':['b','#e8eef3',1.1,'Dikiş','sew',1.2,1,'U'],'Kesim / iş tezgâhı':['b','#c9a36b',.9,'Tezgâh','table',4,1.5,'U'],'Elektrik panosu':['b','#8d96a3',1.9,'Pano','panel',.8,1.2,'U'],
'Ofis masası':['b','#c9a36b',.9,'','desk',1.8,.9,'O'],'Sandalye':['b','#3b4350',1,'','chair',.6,.6,'O'],'Koltuk':['b','#6c7f99',1,'','sofa',2,.9,'O'],'Toplantı masası':['b','#c9a36b',.8,'','table',3,1.5,'O'],'Dolap / arşiv':['b','#b9a07a',1.8,'','cab',1.2,.6,'O'],'Kapı':['b','#8a5a2b',2.1,'','door',1,.2,'O'],'Merdiven':['b','#c9ccd1',2.6,'','stair',3,1.5,'O'],
'Klozet (WC)':['b','#f4f6f8',1.2,'','wc',.8,1.1,'S'],'Lavabo':['b','#e9edf1',.85,'','sink',1,.7,'S'],'Mutfak tezgâhı':['b','#e9edf1',.9,'','sink',2.5,.7,'S'],'Soyunma dolabı':['b','#8fa0b3',1.8,'','cab',1.6,.5,'S'],'Yemek masası':['b','#c9a36b',.75,'','table',2,1,'S'],
'Raf (3 katlı)':['b','#d49a3a',2.5,'Raf','shelf',5,1,'D'],'Palet / koli':['b','#c9a36b',1.2,'','pallet',1.2,1.2,'D'],'Konteyner':['b','#2b6cb0',2.5,'Konteyner','',6,2.4,'D'],'Kolon':['b','#9aa3ad',3,'','',.6,.6,'D'],
'Forklift':['b','#f2c200',1.8,'Forklift','forklift',2.2,1.2,'A'],'Binek araç':['b','#cfd4da',1.4,'','car',2,1,'A'],
'Oda / bölüm':['z','#e8eef7',0,'Bölüm','',4,3,'Z'],'Atölye alanı':['z','#dfe9f6',0,'Atölye','',8,6,'Z'],'Depo alanı':['z','#fff1cf',0,'Depo','',5,4,'Z'],'Ofis':['z','#e3f3e8',0,'Ofis','',4,4,'Z'],'WC':['z','#e9e4f5',0,'WC','',2,2,'Z'],'Toplanma alanı':['z','#bfe8cc',0,'TOPLANMA ALANI','',5,3,'Z'],'Tehlikeli alan':['z','#f9d0cc',0,'Tehlikeli alan','',3,3,'Z'],
'Yaya yolu (yeşil)':['z','#74c98f',0,'','ped',8,1.4,'Z'],'Yaya geçidi (zebra)':['z','#4a4f57',0,'','zebra',1.6,1.4,'Z'],'İş makinesi / araç yolu':['z','#9aa0a8',0,'','veh',10,2,'Z'],'Sarı-siyah uyarı bandı':['z','#f2c200',0,'','hz',6,.8,'Z'],'Kırmızı-beyaz yasak alan':['z','#d62718',0,'','rw',3,3,'Z'],'Forklift park alanı':['z','#e5e7ea',0,'Park','yl',3,2,'Z']};
let S=[],G={w:24,h:16,cell:2.5},I={st:'isgceo',notes:[],lej:'used',at:'',yt:'',firma:'',bolum:'',tarih:'2026-10-08',rev:'Rev.00',haz:'',onay:'',tup:200,ns:'112',ys:'110'},V={rot:0,z:1,px:0,py:0},cw=800,ch=500,tool='select',sel=null,sgn='E001',drag=null,prev=null,H=[],RH=[];
const cx=()=>cw/2+V.px,cy=()=>ch/2+V.py,TW=()=>48*V.z,TH=()=>24*V.z,U=()=>20*V.z,Wt=()=>V.rot%2?G.h:G.w,Ht=()=>V.rot%2?G.w:G.h;
function rt(x,y){const r=V.rot,w=G.w,h=G.h;return r==0?[x,y]:r==1?[h-y,x]:r==2?[w-x,h-y]:[y,w-x]}
function P(x,y,z=0){const[a,b]=rt(x,y);return[cx()+(a-b-(Wt()-Ht())/2)*TW()/2,cy()+(a+b-(Wt()+Ht())/2)*TH()/2-z*U()]}
function inv(sx,sy){const u=(sx-cx())/(TW()/2)+(Wt()-Ht())/2,v=(sy-cy())/(TH()/2)+(Wt()+Ht())/2,a=(u+v)/2,b=(v-u)/2,r=V.rot,w=G.w,h=G.h;return r==0?[a,b]:r==1?[b,h-a]:r==2?[w-a,h-b]:[w-b,a]}
function fit(){V.px=V.py=0;V.z=Math.min(cw*.94/((Wt()+Ht())*24),ch*.85/((Wt()+Ht())*12+70))}
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}
function gl(c,k,x,y,r,col){c.save();c.translate(x,y);c.scale(r,r);c.fillStyle=c.strokeStyle=col;c.lineWidth=.14;c.lineCap=c.lineJoin='round';
const L=(...a)=>{c.beginPath();c.moveTo(a[0],a[1]);for(let i=2;i<a.length;i+=2)c.lineTo(a[i],a[i+1]);c.stroke()},F=(...a)=>{c.beginPath();c.moveTo(a[0],a[1]);for(let i=2;i<a.length;i+=2)c.lineTo(a[i],a[i+1]);c.closePath();c.fill()},O=(x,y,q)=>{c.beginPath();c.arc(x,y,q,0,7);c.fill()},A=(x,y,q)=>{c.beginPath();c.arc(x,y,q,0,7);c.stroke()};
switch(k){case'run-':c.scale(-1,1);case'run':O(.1,-.6,.16);L(.1,-.4,-.05,.1);L(-.05,.1,-.4,.5);L(-.05,.1,.25,.3,.2,.6);L(.1,-.35,.5,-.1);L(.1,-.35,-.3,-.1);break;
case'ext':F(-.2,-.1,.2,-.1,.2,.7,-.2,.7);F(-.1,-.45,.1,-.45,.1,-.1,-.1,-.1);L(.1,-.38,.45,-.2);break;
case'hose':A(0,0,.5);A(0,0,.2);break;case'lad':L(-.3,-.6,-.3,.6);L(.3,-.6,.3,.6);for(let i=-.4;i<.6;i+=.3)L(-.3,i,.3,i);break;
case'btn':c.strokeRect(-.5,-.5,1,1);O(0,0,.25);break;case'tel':c.font='bold .55px Arial';c.textAlign='center';c.textBaseline='middle';c.fillText('112',0,0);break;
case'cross':F(-.15,-.5,.15,-.5,.15,-.15,.5,-.15,.5,.15,.15,.15,.15,.5,-.15,.5,-.15,.15,-.5,.15,-.5,-.15,-.15,-.15);break;
case'ppl':O(-.3,-.15,.13);O(.3,-.15,.13);O(0,-.25,.15);c.fillRect(-.42,0,.24,.3);c.fillRect(.18,0,.24,.3);c.fillRect(-.14,-.05,.28,.38);L(-.55,-.3,-.55,-.55,-.3,-.55);L(.55,-.3,.55,-.55,.3,-.55);L(-.55,.55,-.55,.6,-.3,.6);L(.55,.55,.55,.6,.3,.6);break;
case'eye':c.beginPath();c.ellipse(0,0,.55,.3,0,0,7);c.stroke();O(0,0,.15);break;case'shw':L(-.4,-.5,.1,-.5,.1,-.2);O(0,.1,.06);O(.2,.2,.06);O(.1,.4,.06);O(-.1,.35,.06);break;
case'bolt':F(.1,-.6,-.35,.1,-.02,.1,-.1,.6,.35,-.15,.02,-.15);break;
case'flame':c.beginPath();c.moveTo(0,-.6);c.bezierCurveTo(.5,-.1,.5,.5,0,.55);c.bezierCurveTo(-.5,.5,-.5,0,-.15,-.2);c.bezierCurveTo(-.15,-.35,-.05,-.45,0,-.6);c.fill();break;
case'fork':F(-.5,-.1,.1,-.1,.1,.3,-.5,.3);L(.2,-.5,.2,.35);O(-.35,.45,.1);O(0,.45,.1);break;
case'drop':c.beginPath();c.moveTo(0,-.55);c.quadraticCurveTo(.5,.1,0,.5);c.quadraticCurveTo(-.5,.1,0,-.55);c.fill();break;
case'heat':L(-.3,-.5,-.1,-.25,-.3,0,-.1,.25);L(.2,-.5,.4,-.25,.2,0,.4,.25);L(-.5,.5,.5,.5);break;
case'smk':F(-.5,.2,.2,.2,.2,.4,-.5,.4);L(.3,.2,.3,.4);L(0,-.1,.1,-.3,0,-.5);break;
case'ear':c.beginPath();c.arc(0,0,.45,3.3,6.1);c.stroke();c.fillRect(-.55,-.1,.25,.45);c.fillRect(.3,-.1,.25,.45);break;
case'gog':A(-.28,0,.24);A(.28,0,.24);L(-.04,0,.04,0);break;case'shoe':F(-.5,-.3,-.1,-.3,-.1,.1,.5,.3,.5,.5,-.5,.5);break;
case'glv':case'hand':c.fillRect(-.3,-.1,.6,.6);for(let i=-.3;i<.3;i+=.17)c.fillRect(i,-.5,.12,.45);break;
case'hat':c.beginPath();c.arc(0,.1,.45,Math.PI,0);c.fill();c.fillRect(-.55,.1,1.1,.14);break;
case'mask':c.beginPath();c.ellipse(0,.05,.5,.35,0,0,7);c.fill();L(-.5,0,-.6,-.3);L(.5,0,.6,-.3);break;
case'expl':O(0,.2,.32);L(.15,-.1,.3,-.35);L(.3,-.35,.5,-.3);O(.5,-.3,.05);break;
case'rad':for(let i=0;i<3;i++){c.beginPath();c.moveTo(0,0);c.arc(0,0,.55,i*2.094+.4,i*2.094+1.7);c.closePath();c.fill()}break;
case'laser':O(-.4,.4,.12);L(-.4,.4,.5,-.5);L(-.4,.4,.5,-.1);L(-.4,.4,.1,-.55);break;
case'rays':O(-.4,0,.1);for(const q of[.3,.55]){c.beginPath();c.arc(-.4,0,q,-.8,.8);c.stroke()}break;
case'mag':c.beginPath();c.arc(0,.05,.38,0,Math.PI);c.stroke();c.fillRect(-.5,-.45,.24,.5);c.fillRect(.26,-.45,.24,.5);break;
case'fall':L(-.5,-.1,0,-.1,0,.3,.5,.3);O(-.3,-.45,.1);L(-.3,-.3,-.05,.05);break;
case'bio':A(0,-.22,.26);A(-.24,.2,.26);A(.24,.2,.26);break;
case'snow':L(-.5,0,.5,0);L(0,-.5,0,.5);L(-.35,-.35,.35,.35);L(-.35,.35,.35,-.35);break;
case'slip':O(-.2,-.45,.1);L(-.2,-.3,.05,.05);L(-.5,.3,-.2,.15,.1,.3,.4,.15);break;
case'load':L(0,-.6,0,-.15);F(-.3,-.1,.3,-.1,.45,.5,-.45,.5);break;
case'skull':A(0,-.15,.33);O(-.13,-.18,.07);O(.13,-.18,.07);L(-.45,.3,.45,.6);L(.45,.3,-.45,.6);break;
case'ox':A(0,.25,.28);c.beginPath();c.moveTo(0,-.55);c.quadraticCurveTo(.25,-.28,0,-.05);c.quadraticCurveTo(-.25,-.28,0,-.55);c.fill();break;
case'corr':for(const q of[-.22,.22]){c.beginPath();c.moveTo(q,-.5);c.quadraticCurveTo(q+.25,-.15,q,.1);c.quadraticCurveTo(q-.25,-.15,q,-.5);c.fill()}L(-.5,.4,.5,.4);break;
case'xmark':c.lineWidth=.26;L(-.4,-.4,.4,.4);L(.4,-.4,-.4,.4);break;
case'crush':c.fillRect(-.5,-.5,1,.18);c.fillRect(-.5,.32,1,.18);O(0,0,.2);L(0,-.32,0,-.18);L(0,.18,0,.32);break;
case'walk':O(0,-.5,.12);L(0,-.35,0,.1);L(0,.1,-.2,.5);L(0,.1,.2,.5);L(-.25,-.2,.25,-.2);break;
case'tap':F(-.45,-.3,.2,-.3,.2,-.1,-.45,-.1);L(.2,-.2,.4,-.2,.4,.1);O(.4,.25,.06);break;
case'phone':c.strokeRect(-.25,-.5,.5,1);O(0,.35,.05);break;
case'lift':c.strokeRect(-.42,-.5,.84,1);F(0,-.32,-.15,-.12,.15,-.12);F(0,.32,-.15,.12,.15,.12);break;
case'suit':F(-.15,-.5,.15,-.5,.5,-.2,.35,.1,.2,-.05,.2,.55,-.2,.55,-.2,-.05,-.35,.1,-.5,-.2);break;
case'face':c.strokeRect(-.45,-.45,.9,.45);O(0,.3,.2);break;
case'resp':c.beginPath();c.ellipse(0,.1,.36,.3,0,0,7);c.fill();c.fillRect(-.08,-.5,.16,.3);break;
case'harn':L(-.35,-.5,.35,.5);L(.35,-.5,-.35,.5);A(0,0,.14);break;
case'book':c.strokeRect(-.4,-.45,.8,.9);L(-.2,-.2,.2,-.2);L(-.2,0,.2,0);L(-.2,.2,.1,.2);break;
case'str':c.fillRect(-.55,.1,1.1,.12);c.fillRect(-.45,-.25,.9,.3);L(-.4,.3,-.4,.5);L(.4,.3,.4,.5);O(-.3,-.4,.1);break;
case'ar0':case'ar1':case'ar2':case'ar3':c.rotate(+k[2]*Math.PI/2);F(-.5,-.15,.1,-.15,.1,-.45,.55,0,.1,.45,.1,.15,-.5,.15);break;
case'!':c.font='bold 1.1px Arial';c.textAlign='center';c.textBaseline='middle';c.fillText('!',0,.05);break}c.restore()}
function sign(c,code,x,y,s,off){const d=SN[code];if(!d)return;const t=code=='YOU'?'Y':code[0],k=CAT[t];c.save();c.lineJoin='round';
if(k.sh=='s'){c.fillStyle=k.bg;rr(c,x-s,y-s,2*s,2*s,s*.18);c.fill();c.strokeStyle='#fff';c.lineWidth=s*.07;rr(c,x-s*.9,y-s*.9,1.8*s,1.8*s,s*.14);c.stroke();gl(c,d[2],x,y,s*.6,k.fg)}
else if(k.sh=='t'){c.fillStyle=k.bg;c.strokeStyle='#111';c.lineWidth=s*.14;c.beginPath();c.moveTo(x,y-s);c.lineTo(x+s*1.1,y+s*.9);c.lineTo(x-s*1.1,y+s*.9);c.closePath();c.fill();c.stroke();gl(c,d[2],x,y+s*.28,s*.42,k.fg)}
else if(k.sh=='c'){c.fillStyle=k.bg;c.beginPath();c.arc(x,y,s,0,7);c.fill();c.strokeStyle='#fff';c.lineWidth=s*.07;c.beginPath();c.arc(x,y,s*.9,0,7);c.stroke();gl(c,d[2],x,y,s*.55,k.fg)}
else if(k.sh=='p'){c.fillStyle='#fff';c.beginPath();c.arc(x,y,s,0,7);c.fill();gl(c,d[2],x,y,s*.5,'#111');c.strokeStyle='#d62718';c.lineWidth=s*.2;c.beginPath();c.arc(x,y,s*.88,0,7);c.stroke();c.beginPath();c.moveTo(x-s*.62,y-s*.62);c.lineTo(x+s*.62,y+s*.62);c.stroke()}
else{c.fillStyle=k.bg;c.beginPath();c.arc(x,y,s*.7,Math.PI*.8,Math.PI*2.2);c.lineTo(x,y+s*1.1);c.closePath();c.fill();c.fillStyle='#fff';c.beginPath();c.arc(x,y,s*.28,0,7);c.fill()}c.restore()}
function shade(h,f){const n=parseInt(h.slice(1),16);return'rgb('+[n>>16,n>>8&255,n&255].map(v=>Math.round(v*f))+')'}
function poly(c,pts,fill,st,lw){c.beginPath();pts.forEach((p,i)=>i?c.lineTo(p[0],p[1]):c.moveTo(p[0],p[1]));c.closePath();if(fill){c.fillStyle=fill;c.fill()}if(st){c.strokeStyle=st;c.lineWidth=lw||1;c.stroke()}}
function box(c,b,hl){if(!b.l&&!b.k&&!b.sub&&(b.hz||1)>=2.5&&T().wall)b=Object.assign({},b,{c:T().wall});const z0=b.z0||0,hz=b.hz||1,co=[[b.x,b.y],[b.x+b.w,b.y],[b.x+b.w,b.y+b.h],[b.x,b.y+b.h]],fs=[];
for(let i=0;i<4;i++){const p=co[i],q=co[(i+1)%4],A=rt(p[0],p[1]),B=rt(q[0],q[1]);fs.push({p,q,d:A[0]+A[1]+B[0]+B[1],h:Math.abs(A[0]-B[0])>Math.abs(A[1]-B[1])})}
fs.sort((a,b)=>a.d-b.d);fs.forEach(f=>poly(c,[P(f.p[0],f.p[1],z0),P(f.q[0],f.q[1],z0),P(f.q[0],f.q[1],z0+hz),P(f.p[0],f.p[1],z0+hz)],shade(b.c,f.h?.78:.6),'rgba(0,0,0,.3)',.8));
poly(c,co.map(p=>P(p[0],p[1],z0+hz)),b.c,hl?'#e11':'rgba(0,0,0,.35)',hl?2.5:.8);
if(b.l&&b.w>=.8){const m=P(b.x+b.w/2,b.y+b.h/2,hz);c.fillStyle='#111';c.font='bold '+Math.max(9,10*V.z)+'px Arial';c.textAlign='center';c.textBaseline='middle';c.fillText(b.l,m[0],m[1])}}
function arrowPts(a){const k=[[-.4,-.15],[.1,-.15],[.1,-.35],[.45,0],[.1,.35],[.1,.15],[-.4,.15]],d=a.d;return k.map(p=>{let u=p[0],v=p[1];for(let i=0;i<d;i++){const t=u;u=-v;v=t}return P(a.x+u,a.y+v)})}
function draw(c,ex){if(!ex){c.clearRect(0,0,cw,ch);c.fillStyle=T().sbg;c.fillRect(0,0,cw,ch)}
poly(c,[P(0,0),P(G.w,0),P(G.w,G.h),P(0,G.h)],T().floor,'#9aa7b8',1);
if(!ex){c.strokeStyle='rgba(120,140,170,.25)';c.lineWidth=.6;for(let i=1;i<G.w;i++){const a=P(i,0),b=P(i,G.h);c.beginPath();c.moveTo(a[0],a[1]);c.lineTo(b[0],b[1]);c.stroke()}for(let j=1;j<G.h;j++){const a=P(0,j),b=P(G.w,j);c.beginPath();c.moveTo(a[0],a[1]);c.lineTo(b[0],b[1]);c.stroke()}}
S.forEach((z,i)=>{if(z.t=='z')zdraw(c,z,i==sel&&!ex)});
S.forEach((r,i)=>{if(r.t=='r'&&r.p.length>1){c.save();c.strokeStyle=T().route;c.lineWidth=5*V.z+1;c.setLineDash([10*V.z+3,6*V.z+2]);c.lineCap='round';c.beginPath();r.p.forEach((p,j)=>{const q=P(p[0],p[1]);j?c.lineTo(q[0],q[1]):c.moveTo(q[0],q[1])});c.stroke();c.setLineDash([]);const a=P(...r.p[r.p.length-2]),b=P(...r.p[r.p.length-1]),an=Math.atan2(b[1]-a[1],b[0]-a[0]),s=9*V.z+4;c.fillStyle=T().route;c.beginPath();c.moveTo(b[0]+Math.cos(an)*s,b[1]+Math.sin(an)*s);c.lineTo(b[0]+Math.cos(an+2.5)*s,b[1]+Math.sin(an+2.5)*s);c.lineTo(b[0]+Math.cos(an-2.5)*s,b[1]+Math.sin(an-2.5)*s);c.fill();if(i==sel&&!ex){c.strokeStyle='#e11';c.lineWidth=2;c.stroke()}c.restore()}
else if(r.t=='a')poly(c,arrowPts(r),T().arrow,i==sel&&!ex?'#e11':'#006b2c',i==sel&&!ex?2.5:1)});
const L=[];S.forEach((o,i)=>{if(o.t=='b'){const m=rt(o.x+o.w/2,o.y+o.h/2);L.push([m[0]+m[1],o,i])}else if(o.t=='s'){const m=rt(o.x,o.y);L.push([m[0]+m[1]+.02,o,i])}});L.sort((a,b)=>a[0]-b[0]);
const s=Math.max(9,13*V.z);L.forEach(([d,o,i])=>{const hl=i==sel&&!ex;if(o.t=='b')obj(c,o,hl);else{const g=P(o.x,o.y);if(o.k=='YOU'){sign(c,o.k,g[0],g[1]-s*1.1,s*1.15)}else{const t=P(o.x,o.y,1.6);c.strokeStyle='#444';c.lineWidth=1.4;c.beginPath();c.moveTo(g[0],g[1]);c.lineTo(t[0],t[1]);c.stroke();c.fillStyle='#444';c.beginPath();c.arc(g[0],g[1],2.5,0,7);c.fill();sign(c,o.k,t[0],t[1]-s,s);if(o.ar!=null&&o.ar!==''){const ox=o.ar==0?2.3*s:o.ar==2?-2.3*s:0,oy=o.ar==1?2.3*s:o.ar==3?-2.3*s:0;sign(c,'D'+['R','D','L','U'][o.ar],t[0]+ox,t[1]-s+oy,s*.85)}}
if(hl){c.strokeStyle='#e11';c.lineWidth=2;c.beginPath();c.arc(g[0],g[1]-s*1.2,s*1.45,0,7);c.stroke()}if(o.l){c.fillStyle='#111';c.font='bold '+Math.max(8,9*V.z)+'px Arial';c.textAlign='center';c.fillText(o.l,g[0],g[1]+11*V.z+4)}}});
if(prev&&!ex){c.setLineDash([6,4]);poly(c,[P(prev.x,prev.y),P(prev.x+prev.w,prev.y),P(prev.x+prev.w,prev.y+prev.h),P(prev.x,prev.y+prev.h)],'rgba(29,78,137,.15)','#1d4e89',2);c.setLineDash([])}
if(!ex){const n=P(G.w+.5,0);c.fillStyle='#c0392b';c.font='bold 12px Arial';c.textAlign='left';}}
function R(){draw(X,false)}
function size(){const d=window.devicePixelRatio||1,b=C.getBoundingClientRect();cw=b.width;ch=b.height;C.width=cw*d;C.height=ch*d;X.setTransform(d,0,0,d,0,0);R()}
function snap(v,q=1){return Math.round(v/q)*q}
function hit(g){for(let i=S.length-1;i>=0;i--){const o=S[i];if((o.t=='s'||o.t=='a')&&Math.hypot(o.x-g[0],o.y-g[1])<.8)return i;if(o.t=='r'&&o.p.some((p,j)=>j&&dseg(g,o.p[j-1],p)<.5))return i}
for(let i=S.length-1;i>=0;i--){const o=S[i];if(o.t=='b'&&g[0]>=o.x-.1&&g[0]<=o.x+o.w+.1&&g[1]>=o.y-.1&&g[1]<=o.y+o.h+.1)return i}
for(let i=S.length-1;i>=0;i--){const o=S[i];if(o.t=='z'&&g[0]>=o.x&&g[0]<=o.x+o.w&&g[1]>=o.y&&g[1]<=o.y+o.h)return i}return null}
function dseg(p,a,b){const dx=b[0]-a[0],dy=b[1]-a[1],l=dx*dx+dy*dy||1;let t=Math.max(0,Math.min(1,((p[0]-a[0])*dx+(p[1]-a[1])*dy)/l));return Math.hypot(p[0]-a[0]-t*dx,p[1]-a[1]-t*dy)}
function push(){H.push(JSON.stringify(S));if(H.length>60)H.shift();RH=[];save()}
function save(){clearTimeout(save.t);save.t=setTimeout(()=>{try{parent.postMessage({tip:'degisti'},'*')}catch(e){}},300)}
function undo(){if(H.length>1){RH.push(H.pop());S=JSON.parse(H[H.length-1]);sel=null;ui()}}
function redo(){if(RH.length){const s=RH.pop();H.push(s);S=JSON.parse(s);sel=null;ui()}}
function ui(){R();props();chk();save()}
function pos(e){const b=C.getBoundingClientRect();return[e.clientX-b.left,e.clientY-b.top]}
C.addEventListener('pointerdown',e=>{C.setPointerCapture(e.pointerId);const p=pos(e),g=inv(...p);
if(tool=='select'){const i=hit(g);sel=i;if(i!=null)drag={m:'mv',g,o:JSON.stringify(S[i])};else drag={m:'pan',p,px:V.px,py:V.py};ui()}
else if(tool=='obj'||tool=='wall')drag={m:'dr',s:[snap(g[0]),snap(g[1])]};
else if(tool=='sign'){S.push({t:'s',k:sgn,x:snap(g[0],.5),y:snap(g[1],.5),l:''});sel=S.length-1;push();ui()}
else if(tool=='arrow'){S.push({t:'a',x:snap(g[0],.5),y:snap(g[1],.5),d:(arD+4-V.rot)%4});sel=S.length-1;push();ui()}
else if(tool=='route'){const l=S[S.length-1];if(l&&l.t=='r'&&l.open)l.p.push([snap(g[0],.5),snap(g[1],.5)]);else S.push({t:'r',open:1,p:[[snap(g[0],.5),snap(g[1],.5)]]});sel=S.length-1;push();ui()}});
C.addEventListener('pointermove',e=>{if(!drag)return;const p=pos(e),g=inv(...p);
if(drag.m=='pan'){V.px=drag.px+p[0]-drag.p[0];V.py=drag.py+p[1]-drag.p[1];R()}
else if(drag.m=='mv'){const o=JSON.parse(drag.o),dx=snap(g[0]-drag.g[0],.5),dy=snap(g[1]-drag.g[1],.5),t=S[sel];if(t.t=='r')t.p=o.p.map(q=>[q[0]+dx,q[1]+dy]);else{t.x=o.x+dx;t.y=o.y+dy}drag.mvd=1;R()}
else if(drag.m=='dr'){const a=drag.s,b=[snap(g[0]),snap(g[1])];if(tool=='wall'){if(Math.abs(b[0]-a[0])>=Math.abs(b[1]-a[1]))prev={x:Math.min(a[0],b[0]),y:a[1]-.125,w:Math.abs(b[0]-a[0])||.25,h:.25};else prev={x:a[0]-.125,y:Math.min(a[1],b[1]),w:.25,h:Math.abs(b[1]-a[1])||.25}}else prev={x:Math.min(a[0],b[0]),y:Math.min(a[1],b[1]),w:Math.abs(b[0]-a[0]),h:Math.abs(b[1]-a[1])};R()}});
C.addEventListener('pointerup',()=>{if(!drag)return;if(drag.m=='dr'){const d=PR[$('#pre').value];if(tool=='obj'&&d&&(!prev||(prev.w<1&&prev.h<1)))prev={x:drag.s[0],y:drag.s[1],w:d[5]||3,h:d[6]||2};if(prev){if(tool=='wall')S.push({t:'b',x:prev.x,y:prev.y,w:prev.w,h:prev.h,hz:2.6,c:'#8d96a3',l:''});else{prev.w=Math.max(prev.w,.2);prev.h=Math.max(prev.h,.2);S.push(d[0]=='z'?{t:'z',x:prev.x,y:prev.y,w:prev.w,h:prev.h,c:d[1],l:d[3],p:d[4]||''}:{t:'b',x:prev.x,y:prev.y,w:prev.w,h:prev.h,hz:d[2],c:d[1],l:d[3],k:d[4]||undefined})}sel=S.length-1;push()}}if(drag.mvd)push();drag=null;prev=null;ui()});
C.addEventListener('wheel',e=>{e.preventDefault();V.z*=e.deltaY<0?1.1:.9;R()},{passive:false});
function setTool(t){tool=t;document.querySelectorAll('#bar [data-t]').forEach(b=>b.classList.toggle('on',b.dataset.t==t));$('#hint').textContent={select:'Öğeyi seç ve sürükle; boş alanı sürükleyerek kaydır.',obj:'Alan veya nesneyi yer planında sürükleyerek çiz.',wall:'Duvar için başlangıçtan bitişe sürükle.',sign:'İşaret seçip yer planına dokun: '+(SN[sgn]||[0,''])[1],arrow:'Zemin yön okunu yerleştirmek için dokun; seçip döndür.',route:'Rota noktalarını sırayla dokunarak ekle, sonra "Rotayı bitir".'}[t]}
document.querySelectorAll('#bar [data-t]').forEach(b=>b.onclick=()=>setTool(b.dataset.t));
$('#fin').onclick=()=>{S.forEach(r=>delete r.open);ui()};$('#un').onclick=undo;$('#re').onclick=redo;
$('#rv').onclick=()=>{V.rot=(V.rot+1)%4;fit();ui()};$('#zi').onclick=()=>{V.z*=1.15;R()};$('#zo').onclick=()=>{V.z/=1.15;R()};$('#ft').onclick=()=>{fit();R()};
document.addEventListener('keydown',e=>{if(e.target.tagName=='INPUT'||e.target.tagName=='SELECT')return;if((e.key=='Delete'||e.key=='Backspace')&&sel!=null){S.splice(sel,1);sel=null;push();ui()}if(e.key=='r'&&sel!=null&&S[sel].t=='a'){S[sel].d=(S[sel].d+1)%4;push();ui()}if((e.ctrlKey||e.metaKey)&&e.key=='z'){e.preventDefault();undo()}});
$('#pre').innerHTML=Object.keys(PR).map(k=>`<option>${k}</option>`).join('');
function pal(){const f=$('#cat').value,g=$('#sg');g.innerHTML='';SG.filter(s=>!f||(f=='Y'?s[0]=='YOU':s[0][0]==f&&s[0]!='YOU')).forEach(s=>{const b=document.createElement('button'),c=document.createElement('canvas');c.width=c.height=44;c.style.width=c.style.height='36px';sign(c.getContext('2d'),s[0],22,22,17);b.append(c,Object.assign(document.createElement('span'),{textContent:s[1]}));b.title=s[1];b.onclick=()=>{sgn=s[0];setTool('sign')};g.append(b)})}
$('#cat').onchange=pal;$('#pre').onchange=()=>setTool('obj');
function props(){const it=S[sel],p=$('#pr');if(!it){p.innerHTML='<small>Bir öğe seçin ya da yeni öğe yerleştirin.</small>';return}
const f=(k,t,ty='number')=>`<label>${t}<input data-k="${k}" type="${ty}" step=".5" value="${it[k]??''}"></label>`;let h='';
if(it.t=='s')h=`<b>${SN[it.k][0]=='YOU'?'':it.k+' – '}${SN[it.k][1]}</b>`+f('l','Etiket','text')+`<div class="row">${f('x','X (kare)')}${f('y','Y (kare)')}</div>`+ARSEL;
else if(it.t=='a')h=`<b>Yön oku</b><div class="row">${f('x','X')}${f('y','Y')}</div><button data-a="rot">90° döndür (R)</button>`;
else if(it.t=='r')h=`<b>Tahliye rotası (${it.p.length} nokta)</b><button data-a="back">Son noktayı sil</button>`;
else h=`<b>${it.t=='z'?'Alan':'Nesne'}</b>`+f('l','Ad','text')+`<label>Renk<input data-k="c" type="color" value="${it.c}"></label><div class="row">${f('x','X')}${f('y','Y')}</div><div class="row">${f('w','Genişlik')}${f('h','Derinlik')}</div>`+(it.t=='b'?f('hz','Yükseklik'):PSEL);
p.innerHTML=h+'<div class="row"><button data-a="dup">Kopyala</button><button class="red" data-a="del">Sil</button></div>';const sa=p.querySelector('[data-k="ar"]');if(sa)sa.value=it.ar==null?'':it.ar;const sp=p.querySelector('[data-k="p"]');if(sp)sp.value=it.p||''}
$('#pr').oninput=e=>{const k=e.target.dataset.k,it=S[sel];if(!k||!it)return;it[k]=e.target.type=='number'?+e.target.value:e.target.value;R()};
$('#pr').onchange=()=>{push();chk()};
$('#pr').onclick=e=>{const a=e.target.dataset.a,it=S[sel];if(!a||!it)return;if(a=='del'){S.splice(sel,1);sel=null}else if(a=='rot')it.d=(it.d+1)%4;else if(a=='back')it.p.pop();else if(a=='dup'){const c=JSON.parse(JSON.stringify(it));if(c.p)c.p=c.p.map(q=>[q[0]+1,q[1]+1]);else{c.x+=1;c.y+=1}S.push(c);sel=S.length-1}push();ui()};
function chk(){const n=k=>S.filter(i=>i.t=='s'&&i.k==k).length;let ar=S.filter(i=>i.t=='z'&&!/toplan/i.test(i.l||'')).reduce((a,z)=>a+z.w*z.h,0)*G.cell*G.cell;if(!ar)ar=G.w*G.h*G.cell*G.cell;
const need=Math.max(1,Math.ceil(ar/(+I.tup||200))),ex=ar>100?2:1,el=S.some(o=>o.t=='b'&&/elektr|pano/i.test(o.l||'')),
rs=[[n('E001')+n('E002')>=ex,`Acil çıkış işareti (E001/E002): ${n('E001')+n('E002')} / en az ${ex}`],[n('E007')>=1,`Toplanma noktası E007: ${n('E007')} / en az 1`],[n('YOU')==1,`"Buradasınız" işareti: ${n('YOU')} / tam 1`],[n('F001')>=need,`Söndürme tüpü F001: ${n('F001')} / ≈${need} (${Math.round(ar)} m², ${I.tup} m² başına 1)`],[n('F005')>=1,`Yangın alarm butonu F005: ${n('F005')} / en az 1`],[n('E003')>=1,`İlk yardım E003: ${n('E003')} / en az 1`],[S.some(o=>o.t=='r'&&o.p.length>1)||S.filter(o=>o.t=='a').length>=3,'Tahliye yönü: rota veya en az 3 yön oku'],[!el||n('W012')>=1,'Elektrik panosu varsa W012 uyarı işareti'],[!S.some(o=>o.t=='b'&&/forklift/i.test(o.l||''))||n('W014')>=1,'Forklift varsa W014 uyarı işareti']];
$('#chk').innerHTML='<ul>'+rs.map(r=>`<li class="${r[0]?'ok':'no'}">${r[0]?'✔':'✖'} ${r[1]}</li>`).join('')+'</ul><small>Otomatik ön kontroldür. Tüp sayısı tehlike sınıfına göre değişir; nihai doğrulama İSG uzmanı tarafından yapılmalıdır. Dayanak: Güvenlik ve Sağlık İşaretleri Yönetmeliği, Binaların Yangından Korunması Hakkında Yönetmelik, İşyerlerinde Acil Durumlar Hakkında Yönetmelik, TS EN ISO 7010, TS ISO 23601.</small>'}
function inf(){const F=[['firma','İşletme adı','text'],['bolum','Bölüm / kat','text'],['tarih','Plan tarihi','date'],['rev','Revizyon','text'],['haz','Hazırlayan (İSG uzmanı)','text'],['onay','Onaylayan (işveren)','text'],['ns','Acil çağrı no','text'],['ys','İtfaiye no','text'],['tup','Tüp başına alan (m²)','number']];
$('#inf').innerHTML=F.map(f=>`<label>${f[1]}<input data-i="${f[0]}" type="${f[2]}" value="${I[f[0]]}"></label>`).join('')+LJ()+`<div class="row"><label>Genişlik (kare)<input id="gw" type="number" value="${G.w}"></label><label>Derinlik (kare)<input id="gh" type="number" value="${G.h}"></label><label>Kare (m)<input id="gc" type="number" step=".5" value="${G.cell}"></label></div>`}
$('#inf').oninput=e=>{const k=e.target.dataset.i;if(k){I[k]=e.target.value;save();chk()}else if(e.target.id){G.w=+$('#gw').value||24;G.h=+$('#gh').value||16;G.cell=+$('#gc').value||2.5;fit();ui()}};
function wrap(c,t,x,y,w,lh){let l='';t.split(' ').forEach(s=>{if(c.measureText(l+s).width>w){c.fillText(l,x,y);y+=lh;l=''}l+=s+' '});c.fillText(l,x,y);return y+lh}
const SE={E001:'Emergency exit',E007:'Muster point',E003:'First aid',E004:'Emergency telephone',E011:'Eye wash station',E012:'Safety shower',F001:'Fire extinguisher',F002:'Fire hose reel',F003:'Fire ladder',F005:'Fire alarm call point',F004:'Fire telephone',W001:'General warning',W012:'Electrical hazard',W021:'Flammable material',W014:'Forklift / vehicles',W023:'Corrosive substance',W017:'Hot surface',P001:'General prohibition',P002:'No smoking',P003:'No open flame',P011:'Do not extinguish with water',M001:'General mandatory action',M003:'Wear ear protection',M004:'Wear eye protection',M008:'Wear safety footwear',M009:'Wear gloves',M014:'Wear head protection',M016:'Wear dust mask',YOU:'You are here'};
const STY=[
{id:'isgceo',n:'Standart (lacivert başlık)',s:'Görsel 1 · lacivert başlık, sol talimat panelleri',lay:'L',h:['#0f2f5c','#1d4e89'],logo:1,pc:['#1d4e89','#d62718'],bg:'#fff',lang:'tr',floor:'#fbfcfe',route:'#009a44',arrow:'#00b24a',wall:null,sbg:'#f2f5fa'},
{id:'g3d',n:'3B Tahliye · Yeşil',s:'Görsel 3-4 · yeşil bant, sol ve sağ talimat',lay:'LR',h:['#3ea85f','#2e8b57'],ctr:1,pc:['#2e9e54','#d62718'],bg:'#e3e5e8',lang:'en',floor:'#f4f5f6',route:'#16c35a',arrow:'#16c35a',wall:'#e8e8e8',sbg:'#d9dce0'},
{id:'race',n:'RACE · Mavi çerçeve',s:'Görsel 6 · R-A-C-E adımları, vaziyet planı',lay:'L',h:['#1565b4','#1565b4'],hl:1,ctr:1,race:1,title:'EVACUATION SIGN AND DIAGRAM',pc:['#1565b4','#1565b4'],bg:'#fff',lang:'en',floor:'#e9e9e9',route:'#4cb848',arrow:'#4cb848',wall:'#f2f2f2',sbg:'#fff'},
{id:'tr',n:'TS ISO 23601 · Klasik',s:'Görsel 7 · yeşil/kırmızı simgeler, sembol dizini',lay:'LR',h:['#1a7f4b','#c0392b'],hl:1,ic:1,ctr:1,pc:['#1a9a52','#d62718'],bg:'#fff',lang:'tr',floor:'#fff',route:'#2ecc71',arrow:'#00b24a',wall:null,sbg:'#fff'},
{id:'tur',n:'Yönetmelik · Yeşil-Turuncu',s:'Görsel 8 · turuncu şerit, iki kutulu talimat',lay:'L',h:['#16876a','#16876a'],stripe:'#f39c12',ic:1,ctr:1,pc:['#16876a','#e8602c'],bg:'#fff',lang:'tr',floor:'#fff',route:'#7ed6a8',arrow:'#16876a',wall:null,sbg:'#fff'},
{id:'umka',n:'Tam genişlik bina planı',s:'Görsel 9 · yeşil-kırmızı bant, iki dilli, alt lejand',lay:'F',h:['#1f8a4c','#c0222b'],ctr:1,pc:['#1f8a4c','#c0222b'],bg:'#fff',lang:'bi',floor:'#aeaeae',route:'#2ecc40',arrow:'#2ecc40',wall:'#8d887d',sbg:'#fff'},
{id:'hasta',n:'Kurumsal · İki dilli',s:'Görsel 10 · TR/EN, sol-sağ talimat, vaziyet planı',lay:'LR',h:['#1f9a5a','#1d7fb8'],ctr:1,pc:['#1f9a5a','#d62718'],bg:'#fff',lang:'bi',floor:'#f1f3f5',route:'#2ea84f',arrow:'#2ea84f',wall:'#8e98a5',sbg:'#f4f6f8'},
{id:'alt',n:'Alt panelli · Logolu',s:'Görsel 12 · beyaz başlık, alt üç panel',lay:'B',h:['#1a7f4b','#c0392b'],hl:1,ic:1,ctr:1,pc:['#1a9a52','#d62718'],bg:'#fff',lang:'bi',floor:'#e8f4f8',route:'#1a9a52',arrow:'#1a9a52',wall:null,sbg:'#fff'},
{id:'kat',n:'Kat planı · Siyah başlık',s:'Görsel 5 · siyah bant, alt panel ve vaziyet planı',lay:'B',h:['#111','#111'],pc:['#1a9a52','#d62718'],bg:'#fff',lang:'bi',floor:'#fff',route:'#2e9e54',arrow:'#2e9e54',wall:'#d0d0d0',sbg:'#fff'},
{id:'tek',n:'Tek kat · Vurgulu rota',s:'Görsel 11 · sade başlık, yeşil rota zemini',lay:'F',h:['#2e9e54','#2e9e54'],slim:1,pc:['#2e9e54','#d62718'],bg:'#fff',lang:'tr',floor:'#e5f5ea',route:'#2ecc71',arrow:'#2ecc71',wall:'#c9ced4',sbg:'#fff'}];
function T(){return STY.find(s=>s.id==I.st)||STY[0]}
const TX={tr:{t:'ACİL DURUM VE YANGIN TAHLİYE PLANI',a:'ACİL DURUMDA',f:'YANGIN DURUMUNDA',r:'YANGIN DURUMUNDA',v:'VAZİYET PLANI',l:'LEJAND – İŞARETLER (TS EN ISO 7010)',rl:['K','A','S','T'],
as:n=>['Sakin olun, paniğe kapılmayın.','Acil çağrı merkezini arayın: '+n+'. Adınızı, olayı ve yerinizi söyleyin.','Acil çıkış işaretlerini ve yeşil yön oklarını takip edin.','Toplanma noktasına gidin, sayım yapılana kadar bekleyin.'],
fs:(n,y)=>['Yangın alarm butonuna basın, çevrenizi uyarın.','İtfaiyeyi arayın: '+y+' / '+n+'.','Eğitimliyseniz uygun tüple müdahale edin; kontrol dışıysa tahliye edin.','Dumanda alçalarak ilerleyin, asansör kullanmayın.','Toplanma noktasına gidin, geri dönmeyin.'],
rc:(n,y)=>['KURTARIN: Tehlikedeki kişileri uzaklaştırın.','ALARM VERİN: Çevrenizi uyarın, '+n+' arayın.','SINIRLAYIN: Güvenliyse kapıları kapatın.','TAHLİYE: En yakın acil çıkıştan toplanma noktasına gidin.']},
en:{t:'EMERGENCY AND FIRE EVACUATION PLAN',a:'IN AN EMERGENCY',f:'FIRE INSTRUCTIONS',r:'IN CASE OF FIRE',v:'SITE PLAN',l:'LEGEND – SIGNS (ISO 7010)',rl:['R','A','C','E'],
as:n=>['Stay calm and do not panic.','Call '+n+' and state your name, location and the incident.','Follow the emergency exit signs and green arrows.','Go to the muster point and wait for the head count.'],
fs:(n,y)=>['Raise the alarm at the nearest call point.','Call the fire brigade: '+y+' / '+n+'.','Attack the fire only if trained; otherwise evacuate.','Keep low in smoke; do not use elevators.','Go to the muster point, do not go back.'],
rc:(n,y)=>['REMOVE people from immediate danger.','ALERT people nearby and raise the alarm ('+n+').','CONFINE fire and smoke: close doors if safe.','EVACUATE via the nearest emergency exit.']}};
function pan(c,x,y,w,col,t,ls,lt){c.fillStyle=col;c.fillRect(x,y,w,34);c.fillStyle='#fff';c.font='bold 17px Arial';c.textAlign='left';c.fillText(t,x+10,y+23);let yy=y+60;
ls.forEach((l,i)=>{c.fillStyle=col;c.beginPath();c.arc(x+22,yy-5,12,0,7);c.fill();c.fillStyle='#fff';c.font='bold 14px Arial';c.textAlign='center';c.fillText(lt?lt[i]:i+1,x+22,yy);c.textAlign='left';c.fillStyle='#111';c.font='15px Arial';yy=wrap(c,l,x+44,yy,w-58,19)+8});
c.strokeStyle=col;c.lineWidth=3;c.strokeRect(x,y,w,yy-y-4);return yy+10}
function vaz(c,x,y,w,h,col,t){c.fillStyle='#e8ebe6';c.fillRect(x,y+30,w,h-30);c.fillStyle='#fff';c.fillRect(x,y+30+(h-30)*.62,w,12);c.fillRect(x+w*.7,y+30,12,h-30);c.fillStyle='#f4d35e';c.fillRect(x+w*.12,y+45,w*.4,(h-30)*.42);sign(c,'E007',x+w*.8,y+30+(h-30)*.3,14);sign(c,'YOU',x+w*.3,y+30+(h-30)*.58,14);c.fillStyle=col;c.fillRect(x,y,w,30);c.fillStyle='#fff';c.font='bold 16px Arial';c.textAlign='left';c.fillText(t,x+10,y+21);c.strokeStyle=col;c.lineWidth=3;c.strokeRect(x,y,w,h)}
function compose(){const st=T(),W=1754,Hh=1240,e=document.createElement('canvas');e.width=W;e.height=Hh;const c=e.getContext('2d'),en=st.lang=='en',tx=en?TX.en:TX.tr,HD=st.slim?70:110,top=HD+15,c0=st.pc[0],c1=st.pc[1],ns=I.ns,ys=I.ys,AT=(I.at||'').trim()?I.at.split('\n').filter(x=>x.trim()):tx.as(ns),YT=(I.yt||'').trim()?I.yt.split('\n').filter(x=>x.trim()):tx.fs(ns,ys);
c.fillStyle=st.bg;c.fillRect(0,0,W,Hh);
if(st.hl){c.fillStyle='#fff';c.fillRect(0,0,W,HD);c.fillStyle=st.h[0];c.fillRect(0,HD-8,W,8)}else{const g=c.createLinearGradient(0,0,W,0);g.addColorStop(0,st.h[0]);g.addColorStop(1,st.h[1]);c.fillStyle=g;c.fillRect(0,0,W,HD)}
if(st.stripe){c.fillStyle=st.stripe;c.fillRect(0,0,W,10)}
const tc=st.hl?st.h[0]:'#fff',ttl=st.title||(st.id=='kat'?'Acil Durum ve Tahliye Krokisi':tx.t),sub=st.lang=='bi'?(st.id=='kat'?'Emergency and Evacuation Plan':'Emergency and Fire Evacuation Plan'):'',info=(I.firma||'İşletme adı')+' – '+(I.bolum||'Bölüm / kat'),ty=st.slim?34:50,sy=st.slim?58:84;
let lx=30;if(false){c.fillStyle='#d64545';rr(c,20,14,124,HD-28,10);c.fill();c.fillStyle='#fff';c.font='bold 26px Arial';c.textAlign='center';c.fillText('',82,HD/2+9);lx=165}
if(st.ic){sign(c,'E001',58,HD/2-6,32);c.fillStyle='#d62718';rr(c,W-92,HD/2-38,64,64,8);c.fill();gl(c,'flame',W-60,HD/2-6,22,'#fff')}
c.fillStyle=tc;const tcx=st.ctr?W/2:lx;c.textAlign=st.ctr?'center':'left';c.font='bold '+(st.slim?30:36)+'px Arial';c.fillText(ttl,tcx,ty);c.font='19px Arial';c.fillText(sub?sub+'  |  '+info:info,tcx,sy);
c.textAlign='right';const rx=st.ic?W-112:W-30;c.font='bold 16px Arial';c.fillText('TS ISO 23601 · TS EN ISO 7010',rx,ty-4);c.font='14px Arial';c.fillText(I.rev+' · '+I.tarih+' · 1 kare = '+G.cell+' m',rx,sy-4);
const sr=st.lay=='LR'?{x:380,y:top,w:W-760,h:800}:st.lay=='L'?{x:380,y:top,w:W-400,h:800}:st.lay=='F'?{x:20,y:top,w:W-40,h:800}:{x:20,y:top,w:W-40,h:680},B=st.lay=='B',Ly=top+(B?885:815),tm=!B&&TMON();if(tm)sr.h-=130;
if(st.lay=='LR'){pan(c,20,top,340,c0,tx.a,AT,0);vaz(c,20,top+sr.h-170,340,170,c0,tx.v);pan(c,W-360,top,340,c1,tx.f,YT,0)}
else if(st.lay=='L'){let y=st.race?pan(c,20,top,340,c0,tx.r,tx.rc(ns,ys),tx.rl):pan(c,20,top,340,c0,tx.a,AT,0);if(!st.race)y=pan(c,20,y,340,c1,tx.f,YT,0);const vy=Math.max(y,top+sr.h-180),vh=Math.min(170,top+sr.h-vy);if(vh>=90)vaz(c,20,vy,340,vh,c0,tx.v)}
else if(B){const by=sr.y+sr.h+15;pan(c,20,by,560,c0,tx.a,AT,0);pan(c,590,by,560,c1,tx.f,YT,0);vaz(c,1160,by,W-1180,175,c0,tx.v)}
c.save();c.beginPath();c.rect(sr.x,sr.y,sr.w,sr.h);c.clip();c.translate(sr.x,sr.y);c.fillStyle=st.sbg;c.fillRect(0,0,sr.w,sr.h);const sv=[cw,ch,V.z,V.px,V.py];cw=sr.w;ch=sr.h;fit();draw(c,true);[cw,ch,V.z,V.px,V.py]=sv;c.restore();c.strokeStyle=c0;c.lineWidth=3;c.strokeRect(sr.x,sr.y,sr.w,sr.h);if(tm)tmBand(c,20,sr.y+sr.h+8,W-40);
const used=[...new Set(S.filter(o=>o.t=='s').map(o=>o.k))],nc=B?5:4,cl=(W-60)/nc,nm=k=>en?SE[k]:st.lang=='bi'?SN[k][1]+' / '+SE[k]:SN[k][1];
c.fillStyle=st.h[0];c.fillRect(20,Ly,W-40,30);c.fillStyle='#fff';c.font='bold 18px Arial';c.textAlign='left';c.fillText(tx.l,30,Ly+21);
used.forEach((k,i)=>{const x=30+(i%nc)*cl,y=Ly+65+Math.floor(i/nc)*44;sign(c,k,x+18,y,17);c.fillStyle='#111';c.font=(st.lang=='bi'?14:16)+'px Arial';c.textAlign='left';c.fillText((k=='YOU'?'':k+' ')+nm(k),x+46,y+6)});
const ext=[...new Set(S.filter(z=>z.t=='z'&&z.p).map(z=>z.p))].map(p=>[PE[p][en?1:0],S.find(z=>z.t=='z'&&z.p==p).c,null]);[...new Set(S.filter(o=>o.k&&SH[o.k]).map(o=>o.k))].forEach(k=>{const n=Object.keys(PR).find(a=>PR[a][4]==k);if(n)ext.push([n,null,tho(PR[n])])});if(S.some(o=>o.t=='a'||o.t=='r'))ext.push([en?'Evacuation direction / route':'Tahliye yönü / rota',st.route,null]);
ext.forEach((e2,j)=>{const i=used.length+j,x=30+(i%nc)*cl,y=Ly+65+Math.floor(i/nc)*44;if(e2[2])c.drawImage(e2[2],x,y-20,40,40);else{c.fillStyle=e2[1];c.fillRect(x,y-8,34,16);c.strokeStyle='#555';c.lineWidth=1;c.strokeRect(x,y-8,34,16)}c.fillStyle='#111';c.font='16px Arial';c.textAlign='left';c.fillText(e2[0],x+46,y+6)});
c.strokeStyle=st.h[0];c.lineWidth=2;c.strokeRect(20,1185,W-40,45);c.fillStyle='#111';c.font='16px Arial';c.textAlign='left';c.fillText((en?'Prepared by: ':'Hazırlayan: ')+(I.haz||'………………')+'     '+(en?'Approved by: ':'Onaylayan: ')+(I.onay||'………………')+'     '+(en?'Signature: ':'İmza: ')+'……………',30,1214);c.textAlign='right';c.fillText((en?'Emergency: ':'Acil: ')+ns+' · '+(en?'Fire brigade: ':'İtfaiye: ')+ys,W-30,1214);return e}
function thumb(st){const c=document.createElement('canvas');c.width=300;c.height=212;const x=c.getContext('2d');x.fillStyle=st.bg;x.fillRect(0,0,300,212);const hd=st.slim?16:28;
if(st.hl){x.fillStyle='#fff';x.fillRect(0,0,300,hd);x.fillStyle=st.h[0];x.fillRect(0,hd-4,300,4)}else{const g=x.createLinearGradient(0,0,300,0);g.addColorStop(0,st.h[0]);g.addColorStop(1,st.h[1]);x.fillStyle=g;x.fillRect(0,0,300,hd)}
if(st.stripe){x.fillStyle=st.stripe;x.fillRect(0,0,300,4)}
const B=st.lay=='B',sx=st.lay=='LR'||st.lay=='L'?64:8,sw=st.lay=='LR'?172:st.lay=='L'?228:284,sy=hd+6,sh=B?100:152,pn=(a,b,w,h,co)=>{x.fillStyle=co;x.fillRect(a,b,w,10);x.strokeStyle=co;x.lineWidth=2;x.strokeRect(a,b,w,h)};
if(st.lay=='LR'){pn(8,sy,50,100,st.pc[0]);pn(242,sy,50,100,st.pc[1])}else if(st.lay=='L'){pn(8,sy,50,60,st.pc[0]);if(!st.race)pn(8,sy+68,50,60,st.pc[1])}else if(B){pn(8,sy+sh+6,110,44,st.pc[0]);pn(122,sy+sh+6,110,44,st.pc[1]);pn(236,sy+sh+6,56,44,st.pc[0])}
x.fillStyle=st.sbg;x.fillRect(sx,sy,sw,sh);x.strokeStyle=st.pc[0];x.lineWidth=2;x.strokeRect(sx,sy,sw,sh);const mx=sx+sw/2,my=sy+sh/2;
x.beginPath();x.moveTo(mx,my-34);x.lineTo(mx+60,my);x.lineTo(mx,my+34);x.lineTo(mx-60,my);x.closePath();x.fillStyle=st.floor;x.fill();x.strokeStyle=st.wall||'#9aa7b8';x.lineWidth=3;x.stroke();
x.strokeStyle=st.route;x.lineWidth=3;x.setLineDash([6,4]);x.beginPath();x.moveTo(mx-24,my-4);x.lineTo(mx+24,my+10);x.stroke();x.setLineDash([]);x.fillStyle=st.h[0];x.fillRect(8,200,284,6);return c}
function stl(){const l=$('#stl');l.innerHTML='';STY.forEach(s=>{const b=document.createElement('button');b.className='sc'+(s.id==I.st?' on':'');const t=thumb(s);t.style.cssText='width:100%;border:1px solid var(--ln);border-radius:4px;display:block';b.append(t);b.insertAdjacentHTML('beforeend',`<b>${s.n}</b><small>${s.s}</small>`);b.onclick=()=>{I.st=s.id;save();stl();R()};l.append(b)})}
$('#pv').onclick=()=>{$('#ovi').src=compose().toDataURL('image/png');$('#ov').hidden=false};$('#ovc').onclick=()=>{$('#ov').hidden=true};
[['P004','Geçiş yasak','walk','No thoroughfare'],['P005','İçme suyu değildir','tap','Not drinking water'],['P006','Forklift ve taşıma araçları giremez','fork','No access for industrial vehicles'],['P010','Dokunmak yasak','hand','Do not touch'],['P013','Cep telefonu yasak','phone','No mobile phones'],['P020','Yangında asansör kullanılmaz','lift','Do not use lift in case of fire'],
['W002','Patlayıcı madde','expl','Explosive material'],['W003','Radyoaktif madde','rad','Radioactive material'],['W004','Lazer ışını','laser','Laser beam'],['W005','İyonlaştırıcı olmayan radyasyon','rays','Non-ionizing radiation'],['W006','Manyetik alan','mag','Magnetic field'],['W008','Yüksekten düşme','fall','Danger of falling'],['W009','Biyolojik risk','bio','Biological hazard'],['W010','Düşük sıcaklık / donma','snow','Low temperature'],['W011','Kaygan zemin','slip','Slippery surface'],['W015','Askıda yük','load','Suspended load'],['W016','Zehirli madde','skull','Toxic material'],['W018','Zararlı / tahriş edici madde','xmark','Harmful or irritant material'],['W024','El ezilme tehlikesi','crush','Crushing of hands'],['W028','Oksitleyici madde','ox','Oxidizing material'],
['M002','Kullanma kılavuzunu oku','book','Read instruction manual'],['M010','Koruyucu giysi giy','suit','Wear protective clothing'],['M013','Yüz siperi tak','face','Wear face shield'],['M017','Solunum koruyucu tak','resp','Wear respiratory protection'],['M018','Emniyet kemeri / düşme önleyici','harn','Wear safety harness'],
['DR','Yön işareti – sağ','ar0','Direction sign – right'],['DD','Yön işareti – aşağı','ar1','Direction sign – down'],['DL','Yön işareti – sol','ar2','Direction sign – left'],['DU','Yön işareti – yukarı','ar3','Direction sign – up'],['E002','Acil çıkış (ters yön)','run-','Emergency exit (mirrored)'],['E013','Sedye','str','Stretcher']].forEach(s=>{SG.push(s.slice(0,3));SN[s[0]]=SG[SG.length-1];SE[s[0]]=s[3]});CAT.D=CAT.E;
const GN={P:'YASAKLAMA İŞARETLERİ',W:'UYARI İŞARETLERİ',M:'EMREDİCİ İŞARETLER',E:'ACİL ÇIKIŞ VE İLK YARDIM',F:'YANGINLA MÜCADELE',D:'YÖN İŞARETLERİ'},GC={P:'#d62718',W:'#e6b800',M:'#0057b8',E:'#009a44',F:'#d62718',D:'#009a44'};
const esc=s=>String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
const NP={'Acil durumda genel kurallar':['Sakin olun, paniğe kapılmayın.','Alarmı duyunca çalışmayı bırakın; makine ve ekipmanı güvenli duruma getirin.','Acil çıkış işaretlerini ve yeşil yön oklarını takip edin.','Koşmayın, itmeyin, geri dönmeyin.','Toplanma alanında sayım yapılmadan ayrılmayın.'],
'Yangın anında yapılacaklar':['Yangın alarm butonuna basarak çevrenizi uyarın.','İtfaiyeyi (110) veya acil çağrı merkezini (112) arayın.','Yalnızca eğitimliyseniz ve yangın küçükse uygun tüple müdahale edin.','Elektrik panolarını ve yanıcı madde beslemesini mümkünse kesin.','Dumanda alçalarak ilerleyin; asansör kullanmayın.','Kapıları kapatarak yangının yayılmasını sınırlayın.'],
'Deprem anında':['Çök – Kapan – Tutun: sağlam bir masanın altına girin ya da iç duvar dibinde başınızı koruyun.','Cam, pencere, raf ve devrilebilecek eşyalardan uzak durun.','Sarsıntı bitince asansör kullanmadan merdivenle tahliye olun.','Elektrik, doğalgaz ve su vanalarını kapatın.','Açık alandaki toplanma noktasına gidin.'],
'Kimyasal döküntü / sızıntı':['Alanı boşaltın, çevredekileri uyarın.','Güvenlik Bilgi Formu\'nu (GBF) esas alın.','Uygun KKD olmadan müdahale etmeyin.','Ateş kaynaklarını uzaklaştırın, havalandırın.','Gerekirse 112\'yi arayın; döküntüyü yetkili ekip temizlesin.'],
'Elektrik çarpması':['Önce enerjiyi kesin; kazazedeye çıplak elle dokunmayın.','Kuru ve yalıtkan bir malzemeyle kazazedeyi kaynaktan ayırın.','112\'yi arayın.','Solunum ve dolaşım yoksa eğitimliyseniz temel yaşam desteği uygulayın.'],
'Doğalgaz / gaz kaçağı':['Ateş yakmayın, elektrik düğmelerine dokunmayın.','Pencere ve kapıları açın, gaz vanasını kapatın.','Binayı boşaltın; telefonu dışarıda kullanın.','Doğalgaz acil (187) ve 112\'yi arayın.'],
'İlk yardım ilkeleri':['Önce ortam güvenliğini sağlayın.','112\'yi arayın; yerinizi ve durumu net söyleyin.','Bilinç ve solunumu kontrol edin.','Kanamayı temiz bir bezle baskı yaparak durdurun.','Gerekmedikçe yaralıyı hareket ettirmeyin; ilk yardımcı sorumluya haber verin.'],
'Acil durum telefonları':['Ambulans / acil çağrı: 112','İtfaiye: 110','Polis: 155','Jandarma: 156','Doğalgaz arıza: 187','AFAD: 122','Zehir danışma: 114'],
'Toplanma alanı kuralları':['Toplanma alanı bina ve araç yolundan uzak, açık ve güvenli bir yerdir.','Birim sorumlusunun yanında toplanın, sayıma katılın.','Eksik kişi varsa acil durum ekibine bildirin.','Yetkili izin verene kadar binaya girmeyin.'],
'Tahliye sorumlusu görevleri':['Alarmda bölümündeki kişilerin çıkışını yönlendirir.','Tuvalet, depo ve kapalı odaları kontrol eder.','Son kişi çıktıktan sonra kapıları kapatır (kilitlemeden).','Toplanma alanında sayım yapar, eksikleri bildirir.']};
function nts(){const l=I.notes||(I.notes=[]);$('#nt').innerHTML=`<label>Hazır metin<select id="np">${Object.keys(NP).map(k=>`<option>${k}</option>`).join('')}</select></label><div class="row"><button id="na">Metni ekle</button><button id="nb">Boş not</button></div><small>Eklenen yazılar çıktıda "Bilgilendirme" ek sayfasında yer alır.</small>`+l.map((n,i)=>`<div style="border-top:1px solid var(--ln);margin-top:8px"><label>Başlık<input data-n="${i}" data-f="t" value="${esc(n.t)}"></label><label>Metin (her satır bir madde)<textarea data-n="${i}" data-f="l" rows="5">${esc(n.l)}</textarea></label><div class="row"><input data-n="${i}" data-f="c" type="color" value="${n.c||'#1d4e89'}"><button data-d="${i}">Sil</button></div></div>`).join('')}
$('#nt').oninput=e=>{const d=e.target.dataset;if(d.n!=null){I.notes[+d.n][d.f]=e.target.value;save()}};
$('#nt').onclick=e=>{const t=e.target;if(t.id=='na'){const k=$('#np').value;I.notes.push({t:k,l:NP[k].join('\n'),c:'#1d4e89'})}else if(t.id=='nb')I.notes.push({t:'Acil durum notu',l:'',c:'#d62718'});else if(t.dataset.d!=null)I.notes.splice(+t.dataset.d,1);else return;save();nts()};
function LJ(){return `<label>Lejand<select data-i="lej"><option value="used"${I.lej=='used'?' selected':''}>Yalnızca kullanılan işaretler</option><option value="all"${I.lej=='all'?' selected':''}>Tüm yönetmelik işaretleri (ek sayfa)</option></select></label><label>Acil durum talimatı metni (boşsa varsayılan)<textarea data-i="at" rows="4">${esc(I.at)}</textarea></label><label>Yangın talimatı metni (boşsa varsayılan)<textarea data-i="yt" rows="4">${esc(I.yt)}</textarea></label>`}
function shell(title){const st=T(),W=1754,e=document.createElement('canvas');e.width=W;e.height=1240;const c=e.getContext('2d');c.fillStyle='#fff';c.fillRect(0,0,W,1240);const g=c.createLinearGradient(0,0,W,0);g.addColorStop(0,st.h[0]);g.addColorStop(1,st.h[1]);c.fillStyle=g;c.fillRect(0,0,W,80);c.fillStyle='#fff';c.font='bold 30px Arial';c.textAlign='left';c.fillText(title,30,50);c.textAlign='right';c.font='16px Arial';c.fillText((I.firma||'İşletme adı')+' – '+(I.bolum||'Bölüm / kat')+' · '+I.rev+' · '+I.tarih,W-30,48);c.strokeStyle=st.h[0];c.lineWidth=2;c.strokeRect(20,1185,W-40,45);c.fillStyle='#111';c.font='16px Arial';c.textAlign='left';c.fillText('Hazırlayan: '+(I.haz||'………………')+'     Onaylayan: '+(I.onay||'………………')+'     İmza: ……………',30,1214);return[e,c]}
function pgN(){const[e,c]=shell('ACİL DURUM BİLGİLENDİRME YAZILARI'),w=(1754-80)/3,ys=(TMON()&&T().lay=='B')?[250,250,250]:[100,100,100];if(ys[0]>200)tmBand(c,20,100,1714);(I.notes||[]).forEach(n=>{const k=ys.indexOf(Math.min(...ys)),ls=String(n.l).split('\n').filter(x=>x.trim());ys[k]=pan(c,20+k*(w+20),ys[k],w,n.c||'#1d4e89',n.t||'Not',ls,ls.map(()=>'•'))});return e}
function pgC(){const[e,c]=shell('İŞARET KATALOĞU – GÜVENLİK VE SAĞLIK İŞARETLERİ YÖNETMELİĞİ · TS EN ISO 7010'),cl=(1754-40)/6;['P','W','M','E','F','D'].forEach((g,gi)=>{const x=20+gi*cl;c.fillStyle=GC[g];c.fillRect(x,100,cl-10,34);c.fillStyle=g=='W'?'#111':'#fff';c.font='bold 15px Arial';c.textAlign='left';c.fillText(GN[g],x+8,123);SG.filter(s=>s[0][0]==g).forEach((s,i)=>{const y=170+i*44;sign(c,s[0],x+24,y,18);c.fillStyle='#111';c.font='14px Arial';c.textAlign='left';wrap(c,s[0]+' '+s[1],x+52,y-2,cl-70,16)})});
c.fillStyle='#555';c.font='14px Arial';c.textAlign='left';c.fillText('Kodlar TS EN ISO 7010 esaslıdır. Yönetmelik eki ile karşılaştırarak kullanın. Şekil ve renk: yasak = kırmızı daire/çizgi, uyarı = sarı üçgen, emredici = mavi daire, acil çıkış/ilk yardım = yeşil kare, yangın = kırmızı kare.',30,1165);return e}
const SH={cnc:[[0,0,1,1,0,.55,0],[.05,.05,.62,.9,.55,1,'#a9cdea'],[.72,.12,.22,.4,.55,.95,'#2b2f36']],
press:[[0,0,1,1,0,.2,0],[.05,.3,.18,.4,.2,1,'#555c66'],[.77,.3,.18,.4,.2,1,'#555c66'],[0,.1,1,.8,.82,1,0]],
mach:[[0,0,1,1,0,.7,0],[.1,.1,.5,.8,.7,1,'#2b2f36'],[.7,.2,.2,.2,.7,1.2,'#d62718']],
comp:[[.05,.05,.9,.5,.3,.85,'#2f6fb5'],[.2,.58,.6,.37,.3,.7,'#444b55'],[.05,.05,.1,.1,0,.3,'#222'],[.85,.05,.1,.1,0,.3,'#222'],[.05,.85,.1,.1,0,.3,'#222'],[.85,.85,.1,.1,0,.3,'#222'],[.4,.15,.2,.15,.85,1,'#d62718']],
conv:[[0,0,1,1,.4,.55,'#333'],[0,0,1,.08,.4,.7,'#aab4c0'],[0,.92,1,.08,.4,.7,'#aab4c0'],[.05,.2,.05,.6,0,.4,'#666'],[.5,.2,.05,.6,0,.4,'#666'],[.9,.2,.05,.6,0,.4,'#666']],
sew:[[0,0,1,1,.75,.85,'#f4f4f4'],[.1,.1,.1,.1,0,.75,'#777'],[.8,.1,.1,.1,0,.75,'#777'],[.1,.8,.1,.1,0,.75,'#777'],[.8,.8,.1,.1,0,.75,'#777'],[.35,.2,.45,.3,.85,1.25,'#c8ccd6'],[.55,.2,.1,.1,1.25,1.4,'#d62718']],
table:[[0,0,1,1,.85,1,0],[.04,.06,.05,.1,0,.85,'#444'],[.91,.06,.05,.1,0,.85,'#444'],[.04,.84,.05,.1,0,.85,'#444'],[.91,.84,.05,.1,0,.85,'#444']],
panel:[[0,0,1,1,0,1,0],[.2,.2,.6,.6,1,1.04,'#555'],[.4,.4,.2,.2,1.04,1.1,'#d62718']],
desk:[[0,0,1,1,.8,1,0],[.02,.05,.07,.12,0,.8,'#3a2f25'],[.91,.05,.07,.12,0,.8,'#3a2f25'],[.02,.83,.07,.12,0,.8,'#3a2f25'],[.91,.83,.07,.12,0,.8,'#3a2f25'],[.35,.1,.3,.1,1,1.55,'#1b1f24'],[.3,.45,.4,.18,1,1.05,'#d9dde2']],
chair:[[.1,.1,.8,.8,.4,.55,0],[.1,.05,.8,.12,.55,1.1,0],[.4,.4,.2,.2,0,.4,'#222']],
sofa:[[0,0,1,.3,0,1,0],[0,.3,.12,.7,0,.6,0],[.88,.3,.12,.7,0,.6,0],[.12,.3,.76,.7,0,.4,'#8a9bb0']],
cab:[[0,0,1,1,0,1,0],[.48,0,.04,1,1,1.02,'#444']],
door:[[0,0,1,1,0,1,0]],
stair:Array.from({length:6},(_,i)=>[0,i/6,1,1/6,0,(i+1)/6,i%2?'#bfc3c9':0]),
wc:[[.1,0,.8,.28,0,1.2,0],[.15,.28,.7,.68,0,.8,0],[.3,.38,.4,.5,.8,.84,'#9db3c6']],
sink:[[0,0,1,1,0,.85,0],[.12,.2,.76,.6,.85,.9,'#8fb0cf']],
shelf:[[0,0,.05,1,0,1,'#3b6ea8'],[.95,0,.05,1,0,1,'#3b6ea8'],[0,0,1,1,.02,.06,0],[0,0,1,1,.34,.38,0],[0,0,1,1,.66,.7,0],[.1,.15,.22,.7,.06,.3,'#c8a46a'],[.45,.15,.25,.7,.06,.26,'#b8864b'],[.15,.15,.3,.7,.38,.6,'#c8a46a'],[.55,.15,.3,.7,.7,.92,'#b8864b']],
pallet:[[0,0,1,1,0,.14,'#c9a36b'],[.06,.06,.88,.88,.14,1,'#bd8f55']],
forklift:[[.1,.15,.55,.7,.12,.5,0],[.15,.2,.35,.6,.5,1,'#2b2f36'],[.72,.2,.1,.6,0,1.05,'#6b7480'],[.82,.1,.18,.15,0,.08,'#444'],[.82,.75,.18,.15,0,.08,'#444'],[.1,.05,.18,.1,0,.14,'#222'],[.1,.85,.18,.1,0,.14,'#222'],[.5,.05,.18,.1,0,.14,'#222'],[.5,.85,.18,.1,0,.14,'#222']],
car:[[0,.1,1,.8,.1,.45,0],[.25,.2,.45,.6,.45,.8,'#9ec5e8'],[.1,.05,.15,.1,0,.14,'#222'],[.7,.05,.15,.1,0,.14,'#222'],[.1,.85,.15,.1,0,.14,'#222'],[.7,.85,.15,.1,0,.14,'#222']]};
const PE={hz:['Sarı-siyah uyarı bandı','Hazard stripes'],rw:['Kırmızı-beyaz yasak alan','Restricted area'],ped:['Yaya yolu','Pedestrian walkway'],zebra:['Yaya geçidi','Pedestrian crossing'],veh:['Araç / iş makinesi yolu','Vehicle / machinery route'],yl:['Sarı çerçeveli alan','Marked area']};
const ARSEL='<label>Yön oku (işaretin yanında)<select data-k="ar"><option value="">Yok</option><option value="0">Sağ →</option><option value="1">Aşağı ↓</option><option value="2">Sol ←</option><option value="3">Yukarı ↑</option></select></label>',PSEL='<label>Zemin deseni<select data-k="p"><option value="">Düz renk</option><option value="hz">Sarı-siyah uyarı</option><option value="rw">Kırmızı-beyaz yasak</option><option value="ped">Yaya yolu</option><option value="zebra">Yaya geçidi</option><option value="veh">Araç / iş makinesi yolu</option><option value="yl">Sarı çerçeve</option></select></label>';
function obj(c,o,hl){const sh=SH[o.k];if(!sh)return box(c,o,hl);const hz=o.hz||1;sh.forEach((p,i)=>box(c,{x:o.x+p[0]*o.w,y:o.y+p[1]*o.h,w:p[2]*o.w,h:p[3]*o.h,z0:p[4]*hz,hz:(p[5]-p[4])*hz,c:p[6]||o.c,l:'',sub:1},hl&&i==0));
if(o.l){const m=P(o.x+o.w/2,o.y+o.h/2,hz*1.2);c.fillStyle='#111';c.font='bold '+Math.max(9,10*V.z)+'px Arial';c.textAlign='center';c.textBaseline='middle';c.fillText(o.l,m[0],m[1]-6)}}
function zdraw(c,z,hl){const q=[P(z.x,z.y),P(z.x+z.w,z.y),P(z.x+z.w,z.y+z.h),P(z.x,z.y+z.h)],p=z.p,s=.35,ln=(a,b,u,v,col,lw,dash)=>{const A=P(a,b),B=P(u,v);c.strokeStyle=col;c.lineWidth=lw;c.setLineDash(dash||[]);c.beginPath();c.moveTo(A[0],A[1]);c.lineTo(B[0],B[1]);c.stroke();c.setLineDash([])};
poly(c,q,z.c,null);
if(p){c.save();c.beginPath();q.forEach((a,i)=>i?c.lineTo(a[0],a[1]):c.moveTo(a[0],a[1]));c.closePath();c.clip();const x=z.x,y=z.y,w=z.w,h=z.h,lw=Math.max(1.5,3*V.z),H=w>=h,ed=(col,m,mid)=>{if(H){ln(x,y+m,x+w,y+m,col,lw);ln(x,y+h-m,x+w,y+h-m,col,lw);if(mid)ln(x,y+h/2,x+w,y+h/2,col,lw*.7,[10,8])}else{ln(x+m,y,x+m,y+h,col,lw);ln(x+w-m,y,x+w-m,y+h,col,lw);if(mid)ln(x+w/2,y,x+w/2,y+h,col,lw*.7,[10,8])}};
if(p=='hz'||p=='rw'){for(let t=-h;t<w;t+=s*2)poly(c,[P(x+t,y),P(x+t+s,y),P(x+t+s+h,y+h),P(x+t+h,y+h)],p=='hz'?'#222':'#fff',null)}
else if(p=='zebra'){const L=H?w:h;for(let t=.15;t<L-.2;t+=.5)poly(c,H?[P(x+t,y),P(x+t+.25,y),P(x+t+.25,y+h),P(x+t,y+h)]:[P(x,y+t),P(x+w,y+t),P(x+w,y+t+.25),P(x,y+t+.25)],'#fff',null)}
else if(p=='ped')ed('#fff',.12,1);else if(p=='veh')ed('#f2c200',.14,1);
else if(p=='yl'){ln(x+.12,y+.12,x+w-.12,y+.12,'#f2c200',lw*1.4);ln(x+w-.12,y+.12,x+w-.12,y+h-.12,'#f2c200',lw*1.4);ln(x+w-.12,y+h-.12,x+.12,y+h-.12,'#f2c200',lw*1.4);ln(x+.12,y+h-.12,x+.12,y+.12,'#f2c200',lw*1.4)}
c.restore()}
poly(c,q,null,hl?'#e11':'rgba(0,0,0,.35)',hl?2.5:1);
if(z.l){const m=P(z.x+z.w/2,z.y+z.h/2);c.fillStyle='rgba(15,39,66,.75)';c.font='bold '+Math.max(9,11*V.z)+'px Arial';c.textAlign='center';c.fillText(z.l,m[0],m[1])}}
function tho(d){const c=document.createElement('canvas');c.width=c.height=72;const x=c.getContext('2d'),sv=[G,V,cw,ch,sel],w=Math.min(d[5]||3,8),h=Math.min(d[6]||2,8);G={w:w+1,h:h+1,cell:2.5};V={rot:0,z:1,px:0,py:0};cw=ch=72;fit();sel=null;
if(d[0]=='z')zdraw(x,{x:.5,y:.5,w,h,c:d[1],l:'',p:d[4]},false);else obj(x,{x:.5,y:.5,w,h,hz:d[2],c:d[1],k:d[4]||undefined,l:''},false);[G,V,cw,ch,sel]=sv;return c}
function opal(){const f=$('#ocat').value,g=$('#og');g.innerHTML='';Object.keys(PR).filter(k=>!f||PR[k][7]==f).forEach(k=>{const b=document.createElement('button');b.append(tho(PR[k]),Object.assign(document.createElement('span'),{textContent:k}));b.onclick=()=>{$('#pre').value=k;setTool('obj')};g.append(b)})}
$('#ocat').onchange=opal;
var arD=0;$('#ar').innerHTML='<label style="flex-basis:100%">Zemin yön oku (bakış yönüne göre)</label>';[['↗ Yukarı-sağ',3],['↖ Yukarı-sol',2],['↘ Aşağı-sağ',0],['↙ Aşağı-sol',1]].forEach(([n,s])=>{const b=document.createElement('button');b.textContent=n;b.onclick=()=>{arD=s;setTool('arrow')};$('#ar').append(b)});
const DT=()=>[{n:'Söndürme Ekibi',c:'#d62718'},{n:'Kurtarma Ekibi',c:'#e8a000'},{n:'Koruma ve Güvenlik Ekibi',c:'#1d4e89'},{n:'İlk Yardım Ekibi',c:'#009a44'}].map(t=>Object.assign({lead:'',mem:'',tel:''},t)),TMON=()=>(I.teams||[]).some(t=>t.lead||t.mem||t.tel);
function tms(){const l=I.teams||(I.teams=DT());$('#tm').innerHTML=l.map((t,i)=>`<div style="border-top:1px solid var(--ln);margin-top:8px"><label>Ekip adı<input data-m="${i}" data-f="n" value="${esc(t.n)}"></label><label>Ekip lideri<input data-m="${i}" data-f="lead" value="${esc(t.lead)}"></label><label>Üyeler (virgülle)<input data-m="${i}" data-f="mem" value="${esc(t.mem)}"></label><label>İletişim / dahili<input data-m="${i}" data-f="tel" value="${esc(t.tel)}"></label><div class="row"><input type="color" data-m="${i}" data-f="c" value="${t.c||'#1d4e89'}"><button data-x="${i}">Sil</button></div></div>`).join('')+'<div class="row"><button id="ta">Ekip ekle</button></div><small>Ekip bilgisi girince plan sayfasında "Acil durum ekipleri" lejand bandı oluşur.</small>'}
$('#tm').oninput=e=>{const d=e.target.dataset;if(d.m!=null){I.teams[+d.m][d.f]=e.target.value;save()}};
$('#tm').onclick=e=>{const t=e.target;if(t.id=='ta')I.teams.push({n:'Yeni ekip',c:'#6c5ce7',lead:'',mem:'',tel:''});else if(t.dataset.x!=null)I.teams.splice(+t.dataset.x,1);else return;save();tms()};
function tmBand(c,x,y,w){const L=(I.teams||[]).filter(t=>t.n),k=L.length||1,cw_=(w-10*(k-1))/k;c.fillStyle='#0f2f5c';c.font='bold 14px Arial';c.textAlign='left';c.fillText('ACİL DURUM EKİPLERİ',x,y+13);
L.forEach((t,i)=>{const X=x+i*(cw_+10),Y=y+20;c.fillStyle=t.c||'#1d4e89';c.fillRect(X,Y,cw_,24);c.fillStyle='#fff';c.font='bold 14px Arial';c.fillText(String(t.n).toUpperCase(),X+8,Y+17);c.strokeStyle=t.c||'#1d4e89';c.lineWidth=2;c.strokeRect(X,Y,cw_,96);c.fillStyle='#111';c.font='13px Arial';let yy=Y+42;yy=wrap(c,'Lider: '+(t.lead||'-'),X+8,yy,cw_-16,16);yy=wrap(c,'Üyeler: '+(t.mem||'-'),X+8,yy,cw_-16,16);c.fillText('İletişim: '+(t.tel||'-'),X+8,Math.min(yy+2,Y+90))})}
function composeAll(){const p=[compose()];if((I.notes||[]).length||(TMON()&&T().lay=='B'))p.push(pgN());if(I.lej=='all')p.push(pgC());return p}
$('#png').onclick=()=>composeAll().forEach((p,i)=>{const a=document.createElement('a');a.download='acil-durum-krokisi-'+(i+1)+'.png';a.href=p.toDataURL('image/png');a.click()});
$('#prt').onclick=()=>{$('#prn').innerHTML=composeAll().map(p=>`<img src="${p.toDataURL('image/png')}">`).join('');setTimeout(()=>window.print(),200)};
$('#pv').onclick=()=>{$('#ovs').innerHTML=composeAll().map(p=>`<img src="${p.toDataURL('image/png')}">`).join('');$('#ov').hidden=false};
$('#sv').onclick=()=>{const a=document.createElement('a');a.download='kroki.json';a.href='data:application/json,'+encodeURIComponent(JSON.stringify({S,G,I}));a.click()};
$('#ld').onclick=()=>$('#fi').click();$('#fi').onchange=e=>{const f=e.target.files[0];if(!f)return;f.text().then(t=>{try{const d=JSON.parse(t);S=d.S||[];G=d.G||G;I=Object.assign(I,d.I);sel=null;inf();nts();fit();push();ui()}catch(x){alert('Dosya okunamadı: geçerli bir kroki JSON dosyası seçin.')}})};
function demo(){const z=(x,y,w,h,c,l,p)=>({t:'z',x,y,w,h,c,l,p}),b=(x,y,w,h,hz,c,l,k)=>({t:'b',x,y,w,h,hz,c,l,k}),s=(k,x,y,l='')=>({t:'s',k,x,y,l});
S=[z(.25,.25,16,12,'#dfe9f6','Üretim atölyesi'),z(16.25,.25,6.5,5,'#fff1cf','Depo'),z(16.25,7.25,6.5,5,'#e3f3e8','Ofis'),z(1,13,6,2.5,'#bfe8cc','TOPLANMA ALANI'),z(.8,5.4,15,1.4,'#9aa0a8','','veh'),z(9,6.8,1.4,5.4,'#74c98f','','ped'),z(9,5.4,1.4,1.4,'#4a4f57','','zebra'),z(2.5,7.4,4,.5,'#f2c200','','hz'),
b(0,0,23,.25,2.6,'#8d96a3',''),b(0,0,.25,12.5,2.6,'#8d96a3',''),b(0,12.25,8,.25,2.6,'#8d96a3',''),b(10,12.25,13,.25,2.6,'#8d96a3',''),b(22.75,0,.25,5,2.6,'#8d96a3',''),b(22.75,7,.25,5.5,2.6,'#8d96a3',''),b(16,0,.25,5.5,2.6,'#8d96a3',''),b(16,6.5,.25,6,2.6,'#8d96a3',''),
b(3,2.5,3,2,1.8,'#7b8fa6','CNC-1','cnc'),b(8,2.5,3,2,1.8,'#7b8fa6','CNC-2','cnc'),b(3,8.2,3,2.5,2.2,'#6f7f92','Pres','press'),b(12,8.5,3,1.5,.9,'#c9a36b','Tezgâh','table'),b(17,1,5,1,2.5,'#d49a3a','Raf','shelf'),b(18,3,1.2,1.2,1.2,'#c9a36b','','pallet'),b(16.4,9,.6,1.2,1.9,'#8d96a3','Pano','panel'),b(6.5,5.9,2.2,1.2,1.8,'#f2c200','Forklift','forklift'),b(17,8.2,1.8,.9,.9,'#c9a36b','','desk'),b(17.6,9.3,.6,.6,1,'#3b4350','','chair'),b(19.8,8.2,1.8,.9,.9,'#c9a36b','','desk'),b(21.3,10.6,.9,1.2,1.2,'#f4f6f8','','wc'),b(19.6,11.1,1.2,.7,.85,'#e9edf1','','sink'),
s('E001',9,12.1),s('E001',22.6,6),s('E007',4,14.2),s('F001',1,1.5),s('F001',15.5,6),s('F001',9.8,11.5),s('F005',1,6.5),s('F005',21,8),s('E003',18,8.5),s('W012',16.5,8.4),s('W014',9,6.5),s('M003',4.5,5.8),s('P002',18.5,3.2),s('YOU',12,10.8),
{t:'r',p:[[12,10.8],[9,10.8],[9,14],[6,14.2]]},{t:'a',x:9,y:11.5,d:1},{t:'a',x:9,y:13,d:1},{t:'a',x:10.8,y:10.8,d:2}];I.teams=DT().map((t,i)=>Object.assign(t,{lead:'Ekip lideri adı',mem:'Üye 1, Üye 2, Üye 3',tel:'Dahili '+(i+1)*111}));tms();sel=null;fit();push();ui()}
$('#ex').onclick=demo;
window.addEventListener('resize',()=>{size();});
inf();pal();setTool('select');size();
stl();nts();tms();opal();fit();H=[JSON.stringify(S)];ui();

/* ---- mehse köprüsü ---- */
$('#mk').onclick=()=>{const p=composeAll();parent.postMessage({tip:'kaydet',veri:{S,G,I},png:p[0].toDataURL('image/png')},'*')};
window.addEventListener('message',e=>{const m=e.data||{};if(m.tip!=='yukle')return;
 if(m.tema)document.documentElement.setAttribute('data-theme',m.tema);
 const d=m.veri;if(d&&Array.isArray(d.S)){S=d.S;G=Object.assign(G,d.G||{});I=Object.assign(I,d.I||{})}else{S=[]}
 const b=m.bilgi||{};['firma','bolum','tarih','haz','onay'].forEach(k=>{if(b[k]&&!(d&&d.I&&d.I[k]))I[k]=b[k]});
 sel=null;inf();nts();tms();stl();opal();fit();H=[JSON.stringify(S)];RH=[];ui()});
try{parent.postMessage({tip:'hazir'},'*')}catch(e){}
</script></body></html>

@endverbatim
