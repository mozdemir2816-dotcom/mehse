<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>İSG TABELA & GÜVENLİK LEVHASI MASTER TASARIM PLATFORMU</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Montserrat:wght@700;900&family=Roboto+Condensed:wght@700&family=Oswald:wght@600;700&display=swap" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            impact: ['Impact', '"Arial Black"', 'sans-serif'],
            arial: ['Arial', '"Helvetica Neue"', 'sans-serif'],
            roboto: ['"Roboto Condensed"', 'sans-serif'],
            montserrat: ['Montserrat', 'sans-serif'],
            oswald: ['Oswald', 'sans-serif']
          },
          colors: {
            isgRed: '#D02C2F',
            isgYellow: '#FFC82E',
            isgBlue: '#005596',
            isgGreen: '#008752',
            isgOrange: '#F96302'
          }
        }
      }
    }
  </script>

  <style>
    /* Montaj Vida Delikleri */
    .screw-hole {
      position: absolute;
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: radial-gradient(circle, #cbd5e1 30%, #64748b 70%, #1e293b 100%);
      border: 1.5px solid #334155;
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.6), 0 1px 2px rgba(255,255,255,0.8);
      z-index: 25;
      pointer-events: none;
    }
    .screw-tl { top: 12px; left: 12px; }
    .screw-tr { top: 12px; right: 12px; }
    .screw-bl { bottom: 12px; left: 12px; }
    .screw-br { bottom: 12px; right: 12px; }
    .screw-ml { top: 50%; left: 10px; transform: translateY(-50%); }
    .screw-mr { top: 50%; right: 10px; transform: translateY(-50%); }

    /* Çift Yönlü Bayrak Tabela Duvar Flanşı */
    .flag-bracket {
      position: absolute;
      top: 15%;
      bottom: 15%;
      left: -26px;
      width: 26px;
      background: linear-gradient(90deg, #1e293b, #475569);
      border-radius: 6px 0 0 6px;
      border-left: 3px solid #0f172a;
      box-shadow: -6px 8px 15px rgba(0,0,0,0.35);
      z-index: 10;
      display: flex;
      flex-direction: column;
      justify-content: space-around;
      align-items: center;
      padding: 10px 0;
    }
    .flag-bolt {
      width: 10px;
      height: 10px;
      background: #94a3b8;
      border-radius: 50%;
      border: 2px solid #0f172a;
    }

    /* A-Tipi Katlanır Zemin Levhası Menteşesi */
    .a-frame-top {
      position: absolute;
      top: -18px;
      left: 15%;
      right: 15%;
      height: 18px;
      background: #334155;
      border-radius: 6px 6px 0 0;
      border: 2px solid #0f172a;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #e2e8f0;
      font-size: 9px;
      font-weight: 800;
      letter-spacing: 1px;
      z-index: 10;
    }

    /* Çıktı & Baskı */
    @media print {
      body * { visibility: hidden !important; }
      #printArea, #printArea * { visibility: visible !important; }
      #printArea {
        position: fixed !important;
        inset: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100vw !important;
        height: 100vh !important;
      }
    }

    /* İnce Kaydırma Çubuğu */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
  </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased min-h-screen flex flex-col">

  <!-- Üst Başlık (Header) -->
  <header class="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-50 shadow-md">
    <div class="max-w-[1780px] mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-3">

      <div class="flex items-center gap-3 w-full md:w-auto">
        <div class="w-10 h-10 rounded-lg bg-red-600 flex items-center justify-center text-white shadow-md shadow-red-600/30 shrink-0">
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L1 21h22L12 2zm0 3.99L19.53 19H4.47L12 5.99zM11 10h2v4h-2zm0 6h2v2h-2z"/>
          </svg>
        </div>
        <div>
          <h1 class="text-base sm:text-lg font-black tracking-tight text-white leading-none">
            İSG TABELA & GÜVENLİK LEVHASI MASTER TASARIM PLATFORMU
          </h1>
          <p class="text-[11px] text-slate-400 font-medium tracking-wide mt-1 flex items-center gap-2">
            <span>T.C. Sağlık ve Güvenlik İşaretleri Yönetmeliği (Ek-2)</span>
            <span class="text-slate-600">•</span>
            <span class="text-amber-400 font-bold">ANSI Z535 & ISO 7010 Tam Uyum</span>
          </p>
        </div>
      </div>

      <!-- Araç Çubuğu / Aksiyon Butonları -->
      <div class="flex items-center flex-wrap gap-2 justify-end w-full md:w-auto">
        <input type="text" id="tasarimBaslikInput" placeholder="Tasarım adı (örn. Trafo Odası Levhası)"
          oninput="state.baslik = this.value"
          class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 text-white border border-slate-700 placeholder-slate-500 w-48">

        <label class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition flex items-center gap-1.5 cursor-pointer shadow-sm" title="JSON Şablon Yükle">
          <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
          Şablon Yükle
          <input type="file" id="jsonLoadInput" accept=".json" onchange="loadJSONTemplate(event)" class="hidden">
        </label>

        <button onclick="saveJSONTemplate()" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition flex items-center gap-1.5 shadow-sm" title="JSON Şablon İndir">
          <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
          Şablon Kaydet
        </button>

        <button onclick="window.print()" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition flex items-center gap-1.5 shadow-sm">
          <svg class="w-3.5 h-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          Yazdır / PDF
        </button>

        <button onclick="exportSVG()" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-700 hover:bg-emerald-600 text-white transition flex items-center gap-1.5 shadow-sm">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Vektörel SVG
        </button>

        <button onclick="exportPNG()" class="px-3.5 py-1.5 text-xs font-bold rounded-lg bg-red-600 hover:bg-red-500 text-white transition flex items-center gap-1.5 shadow-md shadow-red-600/30">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Yüksek Kalite PNG
        </button>

        <button id="kaydetMehseBtn" onclick="tabelayiKaydet()" class="px-3.5 py-1.5 text-xs font-bold rounded-lg bg-blue-700 hover:bg-blue-600 text-white transition flex items-center gap-1.5 shadow-md shadow-blue-700/30">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-5 10V4m0 0L8 8m4-4l4 4"/></svg>
          <span id="kaydetMehseBtnText">mehse'ye Kaydet</span>
        </button>
      </div>

    </div>
  </header>

  <!-- Ana Çalışma Gövdesi (Sol: 4 Kademe Kontrol, Sağ: Canlı Tuval) -->
  <div class="max-w-[1780px] w-full mx-auto p-4 flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

    <!-- SOL PANEL: 4 KADEMELİ İŞ AKIŞI (5 Kolon) -->
    <aside class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col h-[calc(100vh-5.5rem)] sticky top-18">

      <!-- 4 Kademe Sekme Butonları (SafetySign Style Steps) -->
      <div class="grid grid-cols-4 border-b border-slate-200 bg-slate-50 text-xs font-bold shrink-0 text-center" id="stepsBar">
        <button onclick="setStep(1)" class="step-btn py-3 border-b-2 border-red-600 text-red-600 bg-white flex flex-col items-center justify-center gap-0.5" data-step="1">
          <span class="text-[10px] text-slate-400 font-extrabold uppercase">Kademe 1</span>
          <span>Format & Başlık</span>
        </button>
        <button onclick="setStep(2)" class="step-btn py-3 text-slate-600 hover:text-slate-900 flex flex-col items-center justify-center gap-0.5" data-step="2">
          <span class="text-[10px] text-slate-400 font-extrabold uppercase">Kademe 2</span>
          <span>Piktogram (1-4)</span>
        </button>
        <button onclick="setStep(3)" class="step-btn py-3 text-slate-600 hover:text-slate-900 flex flex-col items-center justify-center gap-0.5" data-step="3">
          <span class="text-[10px] text-slate-400 font-extrabold uppercase">Kademe 3</span>
          <span>İkaz & Metin</span>
        </button>
        <button onclick="setStep(4)" class="step-btn py-3 text-slate-600 hover:text-slate-900 flex flex-col items-center justify-center gap-0.5" data-step="4">
          <span class="text-[10px] text-slate-400 font-extrabold uppercase">Kademe 4</span>
          <span>Ebat & Simülasyon</span>
        </button>
      </div>

      <!-- Kaydırılabilir Adım Gövdesi -->
      <div class="p-4 overflow-y-auto flex-1 space-y-5 text-xs text-slate-700">

        <!-- ================= KADEME 1: FORMAT & BAŞLIK ================= -->
        <div id="step-1" class="step-pane space-y-4">

          <!-- Tabela Standart Tarzı -->
          <div>
            <label class="block font-bold text-slate-900 mb-1.5">Tabela Standardı ve Türü</label>
            <div class="grid grid-cols-2 gap-2">
              <button onclick="setSignStandard('ansi')" class="std-btn p-2 rounded-lg border-2 border-red-600 bg-red-50 text-red-700 font-bold text-left flex items-center justify-between" data-std="ansi">
                <span>ANSI Z535 Şablonu</span>
                <span class="text-[10px] opacity-75 font-normal">Üst Başlıklı</span>
              </button>
              <button onclick="setSignStandard('yonetmelik_pure')" class="std-btn p-2 rounded-lg border-2 border-slate-200 bg-white text-slate-700 font-bold text-left flex items-center justify-between hover:bg-slate-50" data-std="yonetmelik_pure">
                <span>Yönetmelik Saf Levha</span>
                <span class="text-[10px] opacity-75 font-normal">Piktogram Odaklı</span>
              </button>
            </div>
          </div>

          <!-- ANSI Başlık Seviyesi Seçici -->
          <div id="ansiHeaderSection">
            <label class="block font-bold text-slate-900 mb-1.5">Sinyal Kelimesi & Seviyesi</label>
            <div class="grid grid-cols-2 gap-2">
              <button onclick="applySignalPreset('TEHLİKE', 'DANGER', '#D02C2F', '#ffffff', true)" class="p-2 rounded-lg bg-red-600 text-white font-black text-left flex items-center justify-between shadow-sm">
                <span>TEHLİKE</span>
                <span class="text-[10px] opacity-80 font-normal">DANGER</span>
              </button>
              <button onclick="applySignalPreset('UYARI', 'WARNING', '#F96302', '#000000', true)" class="p-2 rounded-lg bg-amber-500 text-black font-black text-left flex items-center justify-between shadow-sm">
                <span>UYARI</span>
                <span class="text-[10px] opacity-80 font-normal">WARNING</span>
              </button>
              <button onclick="applySignalPreset('DİKKAT', 'CAUTION', '#FFC82E', '#000000', true)" class="p-2 rounded-lg bg-yellow-400 text-black font-black text-left flex items-center justify-between shadow-sm">
                <span>DİKKAT</span>
                <span class="text-[10px] opacity-80 font-normal">CAUTION</span>
              </button>
              <button onclick="applySignalPreset('BİLGİ', 'NOTICE', '#005596', '#ffffff', false)" class="p-2 rounded-lg bg-blue-600 text-white font-black text-left flex items-center justify-between shadow-sm">
                <span>BİLGİ</span>
                <span class="text-[10px] opacity-80 font-normal">NOTICE</span>
              </button>
              <button onclick="applySignalPreset('GÜVENLİK TALİMATI', 'SAFETY FIRST', '#008752', '#ffffff', false)" class="p-2 rounded-lg bg-emerald-600 text-white font-black text-left flex items-center justify-between col-span-2 shadow-sm">
                <span>GÜVENLİK TALİMATI / İLKYARDIM</span>
                <span class="text-[10px] opacity-80 font-normal">SAFETY FIRST</span>
              </button>
            </div>

            <!-- ANSI Güvenlik Üçgeni -->
            <div class="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-lg mt-2">
              <div>
                <span class="font-bold text-slate-800">ANSI İkaz Üçgeni</span>
                <p class="text-[10px] text-slate-500">Başlık solundaki emniyet ikaz sembolü</p>
              </div>
              <input type="checkbox" id="showTriangleToggle" checked onchange="updateSign()" class="accent-red-600 w-4 h-4 cursor-pointer">
            </div>
          </div>

          <!-- Renk Özelleştirmeleri -->
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <span class="font-bold text-slate-900 block">Levha Renk Tanımları</span>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Başlık Zemin Rengi</label>
                <div class="flex items-center gap-2">
                  <input type="color" id="headerBgInput" value="#D02C2F" oninput="updateSign()" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                  <span id="headerBgCode" class="font-mono text-[11px] text-slate-500">#D02C2F</span>
                </div>
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Başlık Yazı Rengi</label>
                <div class="flex items-center gap-2">
                  <input type="color" id="headerTextInput" value="#ffffff" oninput="updateSign()" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                  <span id="headerTextCode" class="font-mono text-[11px] text-slate-500">#ffffff</span>
                </div>
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Gövde Zemin Rengi</label>
                <div class="flex items-center gap-2">
                  <input type="color" id="bodyBgInput" value="#ffffff" oninput="updateSign()" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                  <span id="bodyBgCode" class="font-mono text-[11px] text-slate-500">#ffffff</span>
                </div>
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Gövde Yazı Rengi</label>
                <div class="flex items-center gap-2">
                  <input type="color" id="bodyTextInput" value="#000000" oninput="updateSign()" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                  <span id="bodyTextCode" class="font-mono text-[11px] text-slate-500">#000000</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Çerçeve, Radyus ve Montaj Delikleri -->
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <span class="font-bold text-slate-900 block">Çerçeve & Montaj Ayarları</span>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Dış Çerçeve Kalınlığı</label>
                <select id="borderWidthSelect" onchange="updateSign()" class="w-full bg-white border border-slate-300 rounded px-2 py-1.5 text-xs font-semibold">
                  <option value="0">Çerçevesiz</option>
                  <option value="2">2 px (İnce)</option>
                  <option value="4" selected>4 px (Standart)</option>
                  <option value="6">6 px (Kalın)</option>
                  <option value="8">8 px (Ağır Sanayi)</option>
                </select>
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Çerçeve Rengi</label>
                <div class="flex items-center gap-2 mt-1">
                  <input type="color" id="borderColorInput" value="#000000" oninput="updateSign()" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                  <span id="borderColorCode" class="font-mono text-[11px] text-slate-500">#000000</span>
                </div>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between text-[11px] mb-1">
                <span>Köşe Radyusu: <strong id="radiusTxt">8</strong>px</span>
                <input type="range" id="borderRadiusSlider" min="0" max="32" value="8" oninput="updateSign()" class="w-32 accent-red-600">
              </div>
            </div>

            <div class="flex items-center justify-between pt-1 border-t border-slate-200">
              <div>
                <span class="font-bold text-slate-800">ANSI Çift Kontur Emniyet Çizgisi</span>
                <p class="text-[10px] text-slate-500">İç emniyet boşluğu beyaz/siyah çizgi</p>
              </div>
              <input type="checkbox" id="doubleBorderToggle" checked onchange="updateSign()" class="accent-red-600 w-4 h-4 cursor-pointer">
            </div>

            <div class="flex items-center justify-between pt-1 border-t border-slate-200">
              <div>
                <span class="font-bold text-slate-800">Montaj Vida Delikleri</span>
                <p class="text-[10px] text-slate-500">Üretim delik referans halkaları</p>
              </div>
              <select id="screwOptionSelect" onchange="updateSign()" class="bg-white border border-slate-300 rounded px-2 py-1 text-xs font-semibold">
                <option value="4_corners">4 Köşe Delikli</option>
                <option value="2_sides">2 Yan Delikli</option>
                <option value="none">Deliksiz (Yapışkanlı Folyo)</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end">
            <button onclick="setStep(2)" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-bold rounded-lg transition flex items-center gap-1">
              Kademe 2: Piktogram Düzenine İlerle →
            </button>
          </div>
        </div>

        <!-- ================= KADEME 2: PİKTOGRAM DÜZENİ & YÖNETMELİK KÜTÜPHANESİ ================= -->
        <div id="step-2" class="step-pane hidden space-y-4">

          <!-- Piktogram Konumu -->
          <div>
            <label class="block font-bold text-slate-900 mb-1.5">Piktogram Yerleşimi</label>
            <div class="grid grid-cols-5 gap-1.5 text-center">
              <button onclick="setPicPosition('top')" class="pos-btn p-2 rounded-lg border font-bold text-[11px] border-red-600 bg-red-50 text-red-700" data-pos="top">Üstte</button>
              <button onclick="setPicPosition('left')" class="pos-btn p-2 rounded-lg border font-bold text-[11px] border-slate-200 bg-white text-slate-700 hover:bg-slate-50" data-pos="left">Solda</button>
              <button onclick="setPicPosition('right')" class="pos-btn p-2 rounded-lg border font-bold text-[11px] border-slate-200 bg-white text-slate-700 hover:bg-slate-50" data-pos="right">Sağda</button>
              <button onclick="setPicPosition('bottom')" class="pos-btn p-2 rounded-lg border font-bold text-[11px] border-slate-200 bg-white text-slate-700 hover:bg-slate-50" data-pos="bottom">Altta</button>
              <button onclick="setPicPosition('none')" class="pos-btn p-2 rounded-lg border font-bold text-[11px] border-slate-200 bg-white text-slate-700 hover:bg-slate-50" data-pos="none">Yok</button>
            </div>
          </div>

          <!-- Piktogram Slot Sayısı (1, 2, 3, 4) -->
          <div>
            <label class="block font-bold text-slate-900 mb-1.5">Slot Adedi (Kaç Piktogram?)</label>
            <div class="grid grid-cols-4 gap-2">
              <button onclick="setPicCount(1)" class="count-btn p-2 rounded-lg border font-black text-xs border-red-600 bg-red-600 text-white" data-count="1">1 Piktogram</button>
              <button onclick="setPicCount(2)" class="count-btn p-2 rounded-lg border font-black text-xs border-slate-200 bg-white text-slate-700 hover:bg-slate-100" data-count="2">2 Piktogram</button>
              <button onclick="setPicCount(3)" class="count-btn p-2 rounded-lg border font-black text-xs border-slate-200 bg-white text-slate-700 hover:bg-slate-100" data-count="3">3 Piktogram</button>
              <button onclick="setPicCount(4)" class="count-btn p-2 rounded-lg border font-black text-xs border-slate-200 bg-white text-slate-700 hover:bg-slate-100" data-count="4">4 Piktogram</button>
            </div>
          </div>

          <!-- Aktif Slot Seçici -->
          <div class="p-3 bg-red-50 border border-red-200 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-red-900">Düzenlenen Piktogram Yuvası:</span>
              <span id="activeSlotIndicator" class="bg-red-600 text-white font-extrabold px-2 py-0.5 rounded text-[11px]">Slot 1</span>
            </div>
            <div class="flex gap-2" id="slotSelectorContainer">
              <!-- JS ile üretilir -->
            </div>
          </div>

          <!-- Özel Görsel / Logo Yükleme -->
          <div class="p-2.5 bg-blue-50/70 border border-blue-200 rounded-lg space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="font-bold text-blue-900">Bu Slota Özel Görsel / Logo Yükle</span>
              <span class="text-[10px] text-blue-600">SVG, PNG, JPG</span>
            </div>
            <input type="file" id="slotUploadInput" accept="image/*,.svg" onchange="handleCustomSlotUpload(event)" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
            <button id="clearCustomSlotBtn" onclick="clearCustomSlot()" class="hidden text-[10px] text-red-600 font-bold hover:underline">
              ✕ Yüklenen Resmi Kaldır (Yönetmelik Piktogramına Dön)
            </button>
          </div>

          <!-- Piktogram Çerçeve Geometrisi (ISO 7010) -->
          <div>
            <label class="block font-bold text-slate-900 mb-1.5">Piktogram Çerçeve Geometrisi (Yönetmelik Şekli)</label>
            <div class="grid grid-cols-4 gap-1.5 text-center">
              <button onclick="setPicWrapper('none')" class="wrap-btn p-1.5 border rounded font-semibold text-[10px] border-red-600 bg-red-50 text-red-700" data-wrap="none">Düz / Saf</button>
              <button onclick="setPicWrapper('warning_triangle')" class="wrap-btn p-1.5 border rounded font-bold text-[10px] border-slate-300 hover:bg-slate-100" data-wrap="warning_triangle">▲ Sarı Üçgen</button>
              <button onclick="setPicWrapper('mandatory_circle')" class="wrap-btn p-1.5 border rounded font-bold text-[10px] border-slate-300 hover:bg-slate-100" data-wrap="mandatory_circle">● Mavi Daire</button>
              <button onclick="setPicWrapper('prohibition_circle')" class="wrap-btn p-1.5 border rounded font-bold text-[10px] border-slate-300 hover:bg-slate-100" data-wrap="prohibition_circle">⊘ Kırmızı Yasak</button>
            </div>
          </div>

          <!-- T.C. Sağlık ve Güvenlik İşaretleri Yönetmeliği Ek-2 Kategorileri -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="font-bold text-slate-900">Yönetmelik Ek-2 Piktogram Kütüphanesi</label>
              <span id="piktogramCountBadge" class="text-[10px] bg-slate-200 text-slate-700 font-bold px-1.5 py-0.5 rounded">48 İşaret</span>
            </div>

            <!-- Canlı Arama -->
            <div class="relative mb-2">
              <input type="text" id="piktogramSearchInput" oninput="filterPiktogramsBySearch()" placeholder="Yönetmelik piktogramı ara (örn. Baret, Toksik, Yangın, Çıkış)..." class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-red-600 focus:outline-none">
              <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <!-- Kategori Rozetleri (Ek-2 Madde 3.1 - 3.5) -->
            <div class="flex flex-wrap gap-1 mb-2" id="regulationBadgesContainer">
              <!-- JS ile üretilir -->
            </div>

            <!-- Piktogram Seçim Izgarası -->
            <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 max-h-56 overflow-y-auto p-2 border border-slate-200 rounded-lg bg-slate-50" id="piktogramSelectionGrid">
              <!-- JS ile SVG ikonlar basılır -->
            </div>
          </div>

          <!-- Piktogram Boyutu -->
          <div class="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
            <span class="font-bold text-slate-800">Piktogram Ölçeği</span>
            <div class="flex items-center gap-2">
              <input type="range" id="picScaleSlider" min="50" max="150" value="100" oninput="updateSign()" class="w-32 accent-red-600">
              <span id="picScaleTxt" class="font-mono font-bold w-10 text-right text-xs">100%</span>
            </div>
          </div>

          <div class="flex justify-between">
            <button onclick="setStep(1)" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg transition">
              ← Kademe 1
            </button>
            <button onclick="setStep(3)" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-bold rounded-lg transition">
              Kademe 3: İkaz Metinlerine İlerle →
            </button>
          </div>
        </div>

        <!-- ================= KADEME 3: İKAZ METİNLERİ & TİPOGRAFİ ================= -->
        <div id="step-3" class="step-pane hidden space-y-4">

          <!-- Sinyal Başlık Metinleri -->
          <div>
            <label class="block font-bold text-slate-900 mb-1">Sinyal Bandı Metinleri</label>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <span class="text-[10px] text-slate-500 block mb-0.5">Türkçe Başlık</span>
                <input type="text" id="signalTrInput" value="TEHLİKE" oninput="updateSign()" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-black text-xs uppercase">
              </div>
              <div>
                <span class="text-[10px] text-slate-500 block mb-0.5">İngilizce Başlık (Opsiyonel)</span>
                <input type="text" id="signalEnInput" value="DANGER" oninput="updateSign()" class="w-full px-2.5 py-1.5 border border-slate-300 rounded font-bold text-xs uppercase">
              </div>
            </div>
          </div>

          <!-- Ana Uyarı Mesajı (Başlık) -->
          <div>
            <label class="block font-bold text-slate-900 mb-1">Ana Uyarı / Tehlike Başlığı (1. Satır)</label>
            <input type="text" id="mainTitleInput" value="YÜKSEK GERİLİM - 380 VOLT" oninput="updateSign()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-black uppercase focus:ring-2 focus:ring-red-600 focus:outline-none">
          </div>

          <!-- Açıklayıcı Metin (Talimat) -->
          <div>
            <label class="block font-bold text-slate-900 mb-1">Açıklayıcı Metin / İSG Yönergesi (2. Satır)</label>
            <textarea id="descTextInput" rows="3" oninput="updateSign()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs leading-relaxed focus:ring-2 focus:ring-red-600 focus:outline-none">Panolara ve elektrik hücrelerine yetkisiz personelin müdahalesi kesinlikle yasaktır. Bakım öncesi enerjiyi kesiniz ve kilitleyiniz (LOTO).</textarea>
          </div>

          <!-- 3. Ek Satır / Çift Dil (İngilizce) -->
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <label class="font-bold text-slate-800">Çift Dil / İngilizce Çeviri Ekle</label>
              <div class="flex items-center gap-2">
                <button type="button" id="aiCevirBtn" onclick="aiCevir()" title="Türkçe başlık ve açıklamayı ANSI / OSHA levha İngilizcesine çevirir"
                  class="px-2 py-1 rounded-md bg-violet-600 hover:bg-violet-700 text-white text-[11px] font-bold">🤖 AI ile Çevir</button>
                <input type="checkbox" id="showEnToggle" checked onchange="updateSign()" class="accent-red-600 w-4 h-4 cursor-pointer">
              </div>
            </div>
            <div id="enFieldsContainer" class="space-y-1.5">
              <input type="text" id="enTitleInput" value="HIGH VOLTAGE - 380 VOLTS" oninput="updateSign()" class="w-full px-2.5 py-1.5 border border-slate-300 rounded text-xs font-black uppercase">
              <textarea id="enDescInput" rows="2" oninput="updateSign()" class="w-full px-2.5 py-1.5 border border-slate-300 rounded text-[11px]">Authorized personnel only. Lockout and tagout power sources before opening or servicing.</textarea>
            </div>
          </div>

          <!-- Tipografi & Punto Kontrolleri -->
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <span class="font-bold text-slate-900 block">Yazı Tipi & Hizalama</span>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Yazı Tipi (Font)</label>
                <select id="fontFamilySelect" onchange="updateSign()" class="w-full bg-white border border-slate-300 rounded px-2 py-1.5 text-xs font-bold">
                  <option value="font-roboto">Roboto Condensed (ANSI Standart)</option>
                  <option value="font-impact">Impact (Ağır İkaz)</option>
                  <option value="font-sans">Inter (Modern Güvenlik)</option>
                  <option value="font-montserrat">Montserrat (Endüstriyel)</option>
                  <option value="font-oswald">Oswald (Kompakt)</option>
                  <option value="font-arial">Arial (Klasik)</option>
                </select>
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Metin Hizalama</label>
                <select id="textAlignSelect" onchange="updateSign()" class="w-full bg-white border border-slate-300 rounded px-2 py-1.5 text-xs font-semibold">
                  <option value="text-center">Ortala</option>
                  <option value="text-left">Sola Hizala</option>
                  <option value="text-right">Sağa Hizala</option>
                </select>
              </div>
            </div>

            <div class="space-y-2 pt-1 border-t border-slate-200">
              <div class="flex items-center justify-between text-[11px]">
                <span>Ana Başlık Punto: <strong id="valTitleSize">22</strong>px</span>
                <input type="range" id="titleSizeSlider" min="14" max="44" value="22" oninput="updateSign()" class="w-32 accent-red-600">
              </div>
              <div class="flex items-center justify-between text-[11px]">
                <span>Açıklama Punto: <strong id="valDescSize">12</strong>px</span>
                <input type="range" id="descSizeSlider" min="9" max="22" value="12" oninput="updateSign()" class="w-32 accent-red-600">
              </div>
            </div>
          </div>

          <div class="flex justify-between">
            <button onclick="setStep(2)" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg transition">
              ← Kademe 2
            </button>
            <button onclick="setStep(4)" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-bold rounded-lg transition">
              Kademe 4: Fiziksel Simülasyona İlerle →
            </button>
          </div>
        </div>

        <!-- ================= KADEME 4: FİZİKSEL SİMÜLASYON & EBATLAR ================= -->
        <div id="step-4" class="step-pane hidden space-y-4">

          <!-- Tabela Yapısal Tipi -->
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
            <label class="block font-bold text-slate-900">Fiziksel Tabela Formatı (Simülasyon)</label>
            <div class="grid grid-cols-3 gap-2">
              <label class="border-2 border-slate-200 has-[:checked]:border-red-600 has-[:checked]:bg-red-50 p-2.5 rounded-lg cursor-pointer flex flex-col items-center gap-1 text-center">
                <input type="radio" name="signFormType" value="single" checked onchange="updateSign()" class="hidden">
                <div class="w-8 h-8 border-2 border-slate-700 bg-white rounded flex items-center justify-center font-bold text-[10px]">1Y</div>
                <span class="font-bold text-[11px]">Tek Yüz Düz</span>
              </label>

              <label class="border-2 border-slate-200 has-[:checked]:border-red-600 has-[:checked]:bg-red-50 p-2.5 rounded-lg cursor-pointer flex flex-col items-center gap-1 text-center">
                <input type="radio" name="signFormType" value="flag" onchange="updateSign()" class="hidden">
                <div class="flex items-center">
                  <div class="w-1.5 h-7 bg-slate-800 rounded-l"></div>
                  <div class="w-8 h-7 border-2 border-slate-700 border-l-0 bg-white rounded-r flex items-center justify-center font-bold text-[10px]">2Y</div>
                </div>
                <span class="font-bold text-[11px]">Çift Yönlü Bayrak</span>
              </label>

              <label class="border-2 border-slate-200 has-[:checked]:border-red-600 has-[:checked]:bg-red-50 p-2.5 rounded-lg cursor-pointer flex flex-col items-center gap-1 text-center">
                <input type="radio" name="signFormType" value="floor" onchange="updateSign()" class="hidden">
                <div class="w-7 h-8 border-2 border-slate-700 bg-yellow-200 rounded flex flex-col items-center justify-center font-bold text-[10px]">
                  <div class="w-4 h-0.5 bg-slate-700 mb-0.5"></div>
                  <span>A</span>
                </div>
                <span class="font-bold text-[11px]">Katlanır A-Tipi</span>
              </label>
            </div>
          </div>

          <!-- Standart Endüstriyel Ebatlar -->
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900">Standart Endüstriyel Ebat Oranları</span>
              <span id="currentCmIndicator" class="font-mono font-bold text-red-600">35 x 50 cm</span>
            </div>

            <div class="grid grid-cols-3 gap-2">
              <button onclick="applyPresetDim(25, 35, 420, 588)" class="p-1.5 border border-slate-300 rounded font-semibold text-[11px] hover:bg-slate-200">25 x 35 cm</button>
              <button onclick="applyPresetDim(35, 50, 480, 680)" class="p-1.5 border border-red-500 bg-red-50 text-red-700 rounded font-bold text-[11px]">35 x 50 cm</button>
              <button onclick="applyPresetDim(50, 70, 540, 756)" class="p-1.5 border border-slate-300 rounded font-semibold text-[11px] hover:bg-slate-200">50 x 70 cm</button>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-200">
              <button onclick="applyPresetDim(18, 25, 380, 520)" class="p-1.5 border border-slate-200 rounded text-[10px] text-slate-600 hover:bg-slate-100">18 x 25 cm (Pano İçi)</button>
              <button onclick="applyPresetDim(70, 100, 580, 820)" class="p-1.5 border border-slate-200 rounded text-[10px] text-slate-600 hover:bg-slate-100">70 x 100 cm (Şantiye)</button>
            </div>

            <div class="space-y-1.5 pt-1">
              <div class="flex items-center justify-between text-[11px]">
                <span>Genişlik (En): <strong id="valWidth">480</strong>px</span>
                <input type="range" id="widthSlider" min="360" max="720" value="480" oninput="updateSign()" class="w-36 accent-red-600">
              </div>
              <div class="flex items-center justify-between text-[11px]">
                <span>Yükseklik (Boy): <strong id="valHeight">680</strong>px</span>
                <input type="range" id="heightSlider" min="360" max="850" value="680" oninput="updateSign()" class="w-36 accent-red-600">
              </div>
            </div>
          </div>

          <!-- Malzeme ve İmalat Özellikleri (Bilgilendirici & Notlar) -->
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
            <span class="font-bold text-slate-900 block">Tabela Üretim & Şartname Notu</span>
            <input type="text" id="specNoteInput" value="0.50mm Eloksallı Alüminyum / UV Baskı / -30°C +80°C Dayanımlı" oninput="updateSign()" class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs text-slate-700" placeholder="Şartname veya firma kodu...">
            <p class="text-[10px] text-slate-500 leading-relaxed">
              T.C. Sağlık ve Güvenlik İşaretleri Yönetmeliği uyarınca levhalar ortam şartlarına, neme ve UV ışığına dayanıklı malzemeden imal edilmelidir.
            </p>
          </div>

          <div class="flex justify-between">
            <button onclick="setStep(3)" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg transition">
              ← Kademe 3
            </button>
            <button onclick="tabelayiKaydet()" class="px-4 py-2 bg-blue-700 hover:bg-blue-600 text-white font-bold rounded-lg transition shadow-md shadow-blue-700/30">
              Tasarımı mehse'ye Kaydet ✓
            </button>
          </div>
        </div>

      </div>

      <!-- Alt Durum Çubuğu -->
      <div class="p-3 bg-slate-100 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between shrink-0">
        <span class="font-semibold text-slate-600">T.C. ÇSGB Yönetmeliği & Ek-2 Uyumlu</span>
        <span class="text-emerald-700 font-bold">● Vektörel Baskıya Hazır</span>
      </div>

    </aside>

    <!-- SAĞ PANEL: CANLI ÖNİZLEME VE GÖRSEL TUVAL (7 Kolon) -->
    <main class="lg:col-span-7 flex flex-col gap-4">

      <!-- Üst Önizleme Bilgi Çubuğu & Zoom -->
      <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="font-bold text-slate-800">Canlı Önizleme Tuvali</span>
          <span class="text-slate-400">|</span>
          <span id="formatStatusText" class="text-slate-600 font-mono font-medium">Tek Yüz Düz Levha • 35 x 50 cm</span>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-slate-500 font-medium">Ölçek:</span>
          <button onclick="changeZoom(-0.1)" class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 font-bold flex items-center justify-center">-</button>
          <span id="zoomText" class="font-mono font-bold w-10 text-center">100%</span>
          <button onclick="changeZoom(0.1)" class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 font-bold flex items-center justify-center">+</button>
        </div>
      </div>

      <!-- Çizim ve Görüntüleme Kutusu (Print / Canvas Container) -->
      <div class="bg-slate-200/90 rounded-2xl border border-slate-300 p-6 md:p-10 flex items-center justify-center min-h-[640px] overflow-auto shadow-inner relative" id="printArea">

        <!-- Cetvel Dokusu -->
        <div class="absolute inset-0 opacity-10 pointer-events-none bg-[radial-gradient(#000_1px,transparent_1px)] [background-size:16px_16px]"></div>

        <!-- Tabela Kapsayıcısı ve Zoom Ölçeği -->
        <div id="signScaleWrapper" class="transition-transform duration-100 origin-center flex items-center justify-center relative">

          <!-- Çift Yönlü Bayrak Tabela Flanşı -->
          <div id="flagBracketElem" class="flag-bracket hidden">
            <div class="flag-bolt"></div>
            <div class="flag-bolt"></div>
            <div class="flag-bolt"></div>
          </div>

          <!-- A-Tipi Katlanır Ayak Menteşesi -->
          <div id="aFrameTopElem" class="a-frame-top hidden">
            ▲ ÇİFT TARAFLI A-TİPİ İSG ZEMİN LEVHASI ▲
          </div>

          <!-- ASIL CANLI İSG LEVHASI ELEMANI -->
          <div id="masterLiveSign" class="relative bg-white text-black transition-all shadow-2xl flex flex-col overflow-hidden select-none" style="width: 480px; min-height: 680px; border: 4px solid #000000; border-radius: 8px;">

            <!-- Vida Delikleri Referans Noktaları -->
            <div id="screwTL" class="screw-hole screw-tl"></div>
            <div id="screwTR" class="screw-hole screw-tr"></div>
            <div id="screwBL" class="screw-hole screw-bl"></div>
            <div id="screwBR" class="screw-hole screw-br"></div>
            <div id="screwML" class="screw-hole screw-ml hidden"></div>
            <div id="screwMR" class="screw-hole screw-mr hidden"></div>

            <!-- ANSI İç Beyaz Çizgisi -->
            <div id="innerBorderElem" class="absolute inset-1.5 border-2 border-white pointer-events-none z-20 hidden"></div>

            <!-- TABELA DİNAMİK İÇERİK ŞABLONU -->
            <div id="signDynamicBody" class="w-full h-full flex flex-col flex-1">
              <!-- JS updateSign() tarafından inşa edilir -->
            </div>

            <!-- Şartname / Alt Bilgi Dipnotu -->
            <div id="specFootnote" class="py-1 px-3 bg-white/90 border-t border-slate-200 text-[9px] text-slate-500 font-mono tracking-tighter flex items-center justify-between z-10">
              <span id="specFootnoteText">0.50mm Eloksallı Alüminyum / UV Baskı</span>
              <span>T.C. İSG YÖNETMELİK EK-2</span>
            </div>

          </div>

        </div>

      </div>

      <!-- Bilgilendirme Kartı -->
      <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-900 flex items-start gap-3">
        <div class="text-xl">📘</div>
        <div class="space-y-1">
          <div class="font-bold">T.C. Sağlık ve Güvenlik İşaretleri Yönetmeliği Esasları</div>
          <p class="text-blue-800 text-[11px] leading-relaxed">
            Yönetmelik Ek-2'ye göre; <strong>Yasaklayıcı işaretler</strong> daire biçiminde, beyaz zemin üzerine siyah piktogram ve kırmızı çerçeve/diyagonal çizgili olmalıdır.
            <strong>Uyarı işaretleri</strong> üçgen biçiminde, sarı zemin üzerine siyah piktogramlıdır.
            <strong>Emredici işaretler</strong> daire biçiminde mavi zemin üzerine beyaz piktogramlı;
            <strong>Acil çıkış ve ilkyardım işaretleri</strong> ise dikdörtgen veya kare biçiminde yeşil zemin üzerine beyaz piktogramlıdır.
          </p>
        </div>
      </div>

    </main>

  </div>

  <!-- Gizli Canvas (PNG Render) -->
  <canvas id="rasterCanvas" class="hidden"></canvas>

  <!-- =========================================================================
       T.C. SAĞLIK VE GÜVENLİK İŞARETLERİ YÖNETMELİĞİ (EK-2) TAM PİKTOGRAM VERİTABANI
       ========================================================================= -->
  <script>
    const REGULATION_PICTOGRAMS = {
      // -----------------------------------------------------------------------
      // 1. YASAKLAYICI İŞARETLER (EK-2 MADDE 3.1)
      // -----------------------------------------------------------------------
      "no_smoking": {
        name: "Sigara İçilmez",
        section: "3.1 Yasaklayıcı",
        cat: "yasak",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="42" fill="#ffffff" stroke="#D02C2F" stroke-width="8"/><rect x="20" y="52" width="40" height="10" fill="#000"/><rect x="62" y="52" width="10" height="10" fill="#000"/><path d="M68,48 C64,42 72,38 68,30 M75,48 C71,42 79,38 75,30" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round"/><line x1="20.3" y1="20.3" x2="79.7" y2="79.7" stroke="#D02C2F" stroke-width="8"/></svg>`
      },
      "no_flame": {
        name: "Açık Alev Kullanmak Yasaktır",
        section: "3.1 Yasaklayıcı",
        cat: "yasak",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="42" fill="#ffffff" stroke="#D02C2F" stroke-width="8"/><rect x="46" y="56" width="8" height="28" rx="2" fill="#000"/><ellipse cx="50" cy="54" rx="7" ry="8" fill="#000"/><path transform="translate(50,46) scale(0.85)" d="M0,0 C-12,0 -16,-10 -11,-20 C-9,-14 -6,-12 -4,-12 C-8,-22 -2,-32 4,-38 C3,-28 10,-24 12,-16 C13,-22 12,-26 11,-28 C18,-20 18,-8 12,-3 C9,0 4,0 0,0 Z" fill="#000"/><line x1="20.3" y1="20.3" x2="79.7" y2="79.7" stroke="#D02C2F" stroke-width="8"/></svg>`
      },
      "no_pedestrian": {
        name: "Yaya Giremez",
        section: "3.1 Yasaklayıcı",
        cat: "yasak",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="42" fill="#ffffff" stroke="#D02C2F" stroke-width="8"/><circle cx="52" cy="24" r="7" fill="#000"/><polyline points="50,33 46,55" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="49,37 43,46 40,55" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="50,37 57,45 60,53" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="46,55 40,68 36,79" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="46,55 53,67 58,78" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><line x1="20.3" y1="20.3" x2="79.7" y2="79.7" stroke="#D02C2F" stroke-width="8"/></svg>`
      },
      "no_water": {
        name: "Suyla Söndürmek Yasaktır",
        section: "3.1 Yasaklayıcı",
        cat: "yasak",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="42" fill="#ffffff" stroke="#D02C2F" stroke-width="8"/><g transform="rotate(-25 34 30)"><path d="M22,22 L48,22 L44,42 L26,42 Z" fill="#000"/><rect x="20" y="19" width="30" height="4" fill="#000"/></g><path d="M50,30 C56,34 58,40 58,46" fill="none" stroke="#000" stroke-width="3.5" stroke-linecap="round"/><circle cx="58" cy="51" r="2.6" fill="#000"/><circle cx="53" cy="56" r="2.6" fill="#000"/><circle cx="61" cy="57" r="2.4" fill="#000"/><path transform="translate(55,84) scale(0.9)" d="M0,0 C-12,0 -16,-10 -11,-20 C-9,-14 -6,-12 -4,-12 C-8,-22 -2,-32 4,-38 C3,-28 10,-24 12,-16 C13,-22 12,-26 11,-28 C18,-20 18,-8 12,-3 C9,0 4,0 0,0 Z" fill="#000"/><line x1="20.3" y1="20.3" x2="79.7" y2="79.7" stroke="#D02C2F" stroke-width="8"/></svg>`
      },
      "no_drinking": {
        name: "İçilmez (İçme Suyu Değildir)",
        section: "3.1 Yasaklayıcı",
        cat: "yasak",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="42" fill="#ffffff" stroke="#D02C2F" stroke-width="8"/><path d="M22,30 L44,30 L44,26 L52,26 L52,30 C60,30 64,34 64,42 L64,46 L56,46 L56,42 C56,40 55,39 53,39 L22,39 Z" fill="#000"/><path d="M60,50 C62,54 62,56 60,57 C58,56 58,54 60,50 Z" fill="#000"/><path d="M48,60 L72,60 L69,82 L51,82 Z" fill="#000"/><line x1="20.3" y1="20.3" x2="79.7" y2="79.7" stroke="#D02C2F" stroke-width="8"/></svg>`
      },
      "no_entry": {
        name: "Yetkisiz Kimse Giremez",
        section: "3.1 Yasaklayıcı",
        cat: "yasak",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="42" fill="#ffffff" stroke="#D02C2F" stroke-width="8"/><circle cx="42" cy="24" r="7" fill="#000"/><polyline points="42,33 42,56" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="42,37 36,47 35,56" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="42,37 52,33 58,26" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="42,56 38,79" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="42,56 46,79" fill="none" stroke="#000" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="54" y="12" width="10" height="13" rx="3" fill="#000"/><line x1="20.3" y1="20.3" x2="79.7" y2="79.7" stroke="#D02C2F" stroke-width="8"/></svg>`
      },
      "no_industrial_vehicle": {
        name: "İş Makinesi Giremez",
        section: "3.1 Yasaklayıcı",
        cat: "yasak",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="42" fill="#ffffff" stroke="#D02C2F" stroke-width="8"/><g transform="translate(7,8) scale(0.82)"><rect x="22" y="44" width="30" height="20" rx="2" fill="#000"/><path d="M30,44 L30,30 L48,30 L52,44" fill="none" stroke="#000" stroke-width="4" stroke-linejoin="round"/><rect x="56" y="24" width="5" height="42" fill="#000"/><rect x="61" y="58" width="18" height="4" fill="#000"/><circle cx="30" cy="68" r="7" fill="#000"/><circle cx="50" cy="68" r="7" fill="#000"/></g><line x1="20.3" y1="20.3" x2="79.7" y2="79.7" stroke="#D02C2F" stroke-width="8"/></svg>`
      },
      "no_touch": {
        name: "Dokunma",
        section: "3.1 Yasaklayıcı",
        cat: "yasak",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="42" fill="#ffffff" stroke="#D02C2F" stroke-width="8"/><path d="M36,80 C30,70 26,62 26,56 C26,52 31,51 33,55 L37,62 L37,30 C37,26 43,26 43,30 L43,52 L43,24 C43,20 49,20 49,24 L49,52 L49,26 C49,22 55,22 55,26 L55,53 L55,32 C55,28 61,28 61,32 L61,62 C61,72 57,80 52,84 Z" fill="#000"/><line x1="20.3" y1="20.3" x2="79.7" y2="79.7" stroke="#D02C2F" stroke-width="8"/></svg>`
      },

      // -----------------------------------------------------------------------
      // 2. UYARI İŞARETLERİ (EK-2 MADDE 3.2)
      // -----------------------------------------------------------------------
      "warn_flammable": {
        name: "Parlayıcı Madde veya Yüksek Isı",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><path transform="translate(50,76) scale(0.95)" d="M0,0 C-12,0 -16,-10 -11,-20 C-9,-14 -6,-12 -4,-12 C-8,-22 -2,-32 4,-38 C3,-28 10,-24 12,-16 C13,-22 12,-26 11,-28 C18,-20 18,-8 12,-3 C9,0 4,0 0,0 Z" fill="#000"/><rect x="30" y="78" width="40" height="5" fill="#000"/></svg>`
      },
      "warn_explosive": {
        name: "Patlayıcı Madde",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><polygon points="50,40 54,52 66,46 58,56 70,62 57,63 60,76 50,67 40,76 43,63 30,62 42,56 34,46 46,52" fill="#000"/><rect x="26" y="40" width="5" height="5" transform="rotate(20 28 42)" fill="#000"/><rect x="69" y="42" width="5" height="5" transform="rotate(-25 71 44)" fill="#000"/><rect x="24" y="78" width="52" height="5" fill="#000"/></svg>`
      },
      "warn_toxic": {
        name: "Toksik (Zehirli) Madde",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><line x1="32" y1="56" x2="68" y2="80" stroke="#000" stroke-width="5" stroke-linecap="round"/><line x1="68" y1="56" x2="32" y2="80" stroke="#000" stroke-width="5" stroke-linecap="round"/><circle cx="30" cy="54" r="3.5" fill="#000"/><circle cx="33" cy="59" r="3.5" fill="#000"/><circle cx="70" cy="54" r="3.5" fill="#000"/><circle cx="67" cy="59" r="3.5" fill="#000"/><circle cx="30" cy="82" r="3.5" fill="#000"/><circle cx="33" cy="77" r="3.5" fill="#000"/><circle cx="70" cy="82" r="3.5" fill="#000"/><circle cx="67" cy="77" r="3.5" fill="#000"/><path d="M50,34 C61,34 66,42 65,51 C64,56 61,58 59,60 L59,66 L41,66 L41,60 C39,58 36,56 35,51 C34,42 39,34 50,34 Z" fill="#000"/><circle cx="44" cy="50" r="4.2" fill="#FED402"/><circle cx="56" cy="50" r="4.2" fill="#FED402"/><path d="M50,55 L47.5,60 L52.5,60 Z" fill="#FED402"/><rect x="44" y="62" width="2" height="4" fill="#FED402"/><rect x="49" y="62" width="2" height="4" fill="#FED402"/><rect x="54" y="62" width="2" height="4" fill="#FED402"/></svg>`
      },
      "warn_corrosive": {
        name: "Aşındırıcı (Korozif) Madde",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><rect x="30" y="34" width="7" height="16" rx="1.5" transform="rotate(-35 33 42)" fill="#000"/><rect x="58" y="34" width="7" height="16" rx="1.5" transform="rotate(35 62 42)" fill="#000"/><circle cx="38" cy="56" r="2" fill="#000"/><circle cx="62" cy="56" r="2" fill="#000"/><path d="M24,74 C24,66 30,64 34,66 L44,66 L44,72 L36,72 C34,76 28,78 24,74 Z" fill="#000"/><path d="M56,66 L76,66 L76,74 L68,74 L66,70 L63,74 L56,74 Z" fill="#000"/><rect x="20" y="78" width="60" height="4" fill="#000"/></svg>`
      },
      "warn_radioactive": {
        name: "Radyoaktif Madde",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><path d="M46.75,56.37 L40.00,44.68 A20,20 0 0 1 60.00,44.68 L53.25,56.37 A6.5,6.5 0 0 0 46.75,56.37 Z" fill="#000"/><path d="M56.50,62.00 L70.00,62.00 A20,20 0 0 1 60.00,79.32 L53.25,67.63 A6.5,6.5 0 0 0 56.50,62.00 Z" fill="#000"/><path d="M46.75,67.63 L40.00,79.32 A20,20 0 0 1 30.00,62.00 L43.50,62.00 A6.5,6.5 0 0 0 46.75,67.63 Z" fill="#000"/><circle cx="50" cy="62" r="4.2" fill="#000"/></svg>`
      },
      "warn_overhead_load": {
        name: "Asılı Yük",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><rect x="48.5" y="30" width="3" height="12" fill="#000"/><path d="M50,42 C56,42 57,50 51,51 L49,51" fill="none" stroke="#000" stroke-width="3.5" stroke-linecap="round"/><path d="M50,51 L38,62 M50,51 L62,62" stroke="#000" stroke-width="2.5"/><rect x="34" y="62" width="32" height="18" fill="#000"/></svg>`
      },
      "warn_forklift": {
        name: "İş Makinesi / Forklift",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><g transform="translate(19,33) scale(0.62)"><rect x="22" y="44" width="30" height="20" rx="2" fill="#000"/><path d="M30,44 L30,30 L48,30 L52,44" fill="none" stroke="#000" stroke-width="4" stroke-linejoin="round"/><rect x="56" y="24" width="5" height="42" fill="#000"/><rect x="61" y="58" width="18" height="4" fill="#000"/><circle cx="30" cy="68" r="7" fill="#000"/><circle cx="50" cy="68" r="7" fill="#000"/></g></svg>`
      },
      "warn_electric": {
        name: "Elektrik Tehlikesi / Yüksek Gerilim",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><polygon points="56,34 40,60 50,60 42,78 39,76 42,86 51,78 47,79 64,54 54,54 62,34" fill="#000"/></svg>`
      },
      "warn_general": {
        name: "Genel Tehlike",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><path d="M45.5,38 L54.5,38 L52.5,68 L47.5,68 Z" fill="#000"/><circle cx="50" cy="76" r="5" fill="#000"/></svg>`
      },
      "warn_laser": {
        name: "Lazer Işını",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><circle cx="58" cy="62" r="6" fill="#000"/><line x1="58" y1="62" x2="72.0" y2="62.0" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="70.1" y2="69.0" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="65.0" y2="74.1" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="58.0" y2="76.0" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="51.0" y2="74.1" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="45.9" y2="69.0" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="45.9" y2="55.0" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="51.0" y2="49.9" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="58.0" y2="48.0" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="65.0" y2="49.9" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><line x1="58" y1="62" x2="70.1" y2="55.0" stroke="#000" stroke-width="2.5" stroke-linecap="round"/><rect x="22" y="60.5" width="34" height="3" fill="#000"/></svg>`
      },
      "warn_oxidizing": {
        name: "Oksitleyici Madde",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><path transform="translate(50,66) scale(0.75)" d="M0,0 C-12,0 -16,-10 -11,-20 C-9,-14 -6,-12 -4,-12 C-8,-22 -2,-32 4,-38 C3,-28 10,-24 12,-16 C13,-22 12,-26 11,-28 C18,-20 18,-8 12,-3 C9,0 4,0 0,0 Z" fill="#000"/><circle cx="50" cy="71" r="8" fill="none" stroke="#000" stroke-width="5"/><rect x="30" y="81" width="40" height="4" fill="#000"/></svg>`
      },
      "warn_non_ionizing": {
        name: "İyonlaştırıcı Olmayan Radyasyon",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><path d="M47,48 L53,48 L58,82 L42,82 Z" fill="#000"/><circle cx="50" cy="44" r="4.5" fill="#000"/><path d="M41,37.7 A9,9 0 0 0 41,50.3 M59,37.7 A9,9 0 0 1 59,50.3" fill="none" stroke="#000" stroke-width="2.6" stroke-linecap="round"/><path d="M35,33.5 A15,15 0 0 0 35,54.5 M65,33.5 A15,15 0 0 1 65,54.5" fill="none" stroke="#000" stroke-width="2.6" stroke-linecap="round"/></svg>`
      },
      "warn_magnetic": {
        name: "Kuvvetli Manyetik Alan",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><path d="M38,44 L46,44 L46,62 C46,67 54,67 54,62 L54,44 L62,44 L62,62 C62,78 38,78 38,62 Z" fill="#000"/><rect x="38" y="40" width="8" height="6" fill="#FED402" stroke="#000" stroke-width="1.5"/><rect x="54" y="40" width="8" height="6" fill="#FED402" stroke="#000" stroke-width="1.5"/><path d="M32,40 C26,52 28,70 34,78 M68,40 C74,52 72,70 66,78" fill="none" stroke="#000" stroke-width="2.2" stroke-dasharray="3 3"/></svg>`
      },
      "warn_falling": {
        name: "Düşme Tehlikesi",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><rect x="20" y="68" width="22" height="14" fill="#000"/><circle cx="60" cy="44" r="5" fill="#000"/><polyline points="56,52 48,64" fill="none" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><polyline points="55,54 64,60 70,56" fill="none" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><polyline points="54,55 46,50" fill="none" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><polyline points="48,64 56,72 62,78" fill="none" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><polyline points="48,64 40,70" fill="none" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>`
      },
      "warn_biological": {
        name: "Biyolojik Risk",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><g transform="translate(50,62)"><g transform="rotate(0)"><circle cx="0" cy="-9" r="9.5" fill="#000"/><circle cx="0" cy="-12" r="6.5" fill="#FED402"/></g><g transform="rotate(120)"><circle cx="0" cy="-9" r="9.5" fill="#000"/><circle cx="0" cy="-12" r="6.5" fill="#FED402"/></g><g transform="rotate(240)"><circle cx="0" cy="-9" r="9.5" fill="#000"/><circle cx="0" cy="-12" r="6.5" fill="#FED402"/></g><circle cx="0" cy="0" r="5.5" fill="none" stroke="#000" stroke-width="2.5"/><circle cx="0" cy="0" r="2.6" fill="#FED402"/><rect x="-1" y="-3" width="2" height="6" transform="rotate(60) translate(0,-4)" fill="#FED402"/><rect x="-1" y="-3" width="2" height="6" transform="rotate(180) translate(0,-4)" fill="#FED402"/><rect x="-1" y="-3" width="2" height="6" transform="rotate(300) translate(0,-4)" fill="#FED402"/></g></svg>`
      },
      "warn_low_temp": {
        name: "Düşük Sıcaklık / Donma Tehlikesi",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><g transform="translate(50,62)"><g transform="rotate(0)"><line x1="0" y1="-20" x2="0" y2="20" stroke="#000" stroke-width="3.2" stroke-linecap="round"/><path d="M-5,-15 L0,-10 L5,-15 M-5,15 L0,10 L5,15" fill="none" stroke="#000" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></g><g transform="rotate(60)"><line x1="0" y1="-20" x2="0" y2="20" stroke="#000" stroke-width="3.2" stroke-linecap="round"/><path d="M-5,-15 L0,-10 L5,-15 M-5,15 L0,10 L5,15" fill="none" stroke="#000" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></g><g transform="rotate(120)"><line x1="0" y1="-20" x2="0" y2="20" stroke="#000" stroke-width="3.2" stroke-linecap="round"/><path d="M-5,-15 L0,-10 L5,-15 M-5,15 L0,10 L5,15" fill="none" stroke="#000" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></g></g></svg>`
      },
      "warn_sharp": {
        name: "Kesici-Delici Alet / İğne Batması",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><g transform="translate(50,62) scale(0.78) translate(-50,-62)"><g transform="rotate(-30 50 62)"><rect x="34" y="57" width="24" height="10" rx="1" fill="#000"/><rect x="26" y="59" width="8" height="6" fill="#000"/><rect x="22" y="55" width="4" height="14" fill="#000"/><rect x="58" y="60" width="6" height="4" fill="#000"/><polygon points="64,61 82,62 64,63" fill="#000"/></g><path d="M70,74 C72,78 72,80 70,81 C68,80 68,78 70,74 Z" fill="#000"/></g></svg>`
      },
      "warn_manual_lifting": {
        name: "Elle Kaldırma / Kas-İskelet Riski",
        section: "3.2 Uyarı",
        cat: "uyari",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><polygon points="50,6 96,90 4,90" fill="#FED402" stroke="#000" stroke-width="6" stroke-linejoin="round"/><circle cx="40" cy="44" r="5" fill="#000"/><polyline points="43,51 54,60" fill="none" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><polyline points="47,54 54,64" fill="none" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><polyline points="54,60 48,70 50,82" fill="none" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><polyline points="54,60 58,70 58,82" fill="none" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><rect x="52" y="62" width="16" height="13" fill="#000"/><path d="M70,44 L70,58 M66,48 L70,44 L74,48" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`
      },

      // -----------------------------------------------------------------------
      // 3. EMREDİCİ / ZORUNLULUK İŞARETLERİ (EK-2 MADDE 3.3)
      // -----------------------------------------------------------------------
      "cmd_glasses": {
        name: "Gözlük Tak",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><circle cx="50" cy="44" r="15" fill="#ffffff"/><path d="M26,84 C26,70 36,63 50,63 C64,63 74,70 74,84 Z" fill="#ffffff"/><rect x="34" y="38" width="32" height="10" rx="4" fill="#005AA9"/><rect x="37" y="40" width="11" height="6" rx="2.5" fill="#ffffff"/><rect x="52" y="40" width="11" height="6" rx="2.5" fill="#ffffff"/></svg>`
      },
      "cmd_helmet": {
        name: "Baret Tak",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><circle cx="50" cy="44" r="15" fill="#ffffff"/><path d="M26,84 C26,70 36,63 50,63 C64,63 74,70 74,84 Z" fill="#ffffff"/><path d="M30,38 C30,22 40,16 50,16 C60,16 70,22 70,38 L74,38 L74,42 L26,42 L26,38 Z" fill="#ffffff" stroke="#005AA9" stroke-width="2"/></svg>`
      },
      "cmd_ear": {
        name: "Kulaklık Tak",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><circle cx="50" cy="44" r="15" fill="#ffffff"/><path d="M26,84 C26,70 36,63 50,63 C64,63 74,70 74,84 Z" fill="#ffffff"/><path d="M32,44 C32,22 68,22 68,44" fill="none" stroke="#005AA9" stroke-width="5"/><path d="M32,44 C32,22 68,22 68,44" fill="none" stroke="#ffffff" stroke-width="2.5"/><rect x="27" y="38" width="10" height="16" rx="4" fill="#ffffff" stroke="#005AA9" stroke-width="2"/><rect x="63" y="38" width="10" height="16" rx="4" fill="#ffffff" stroke="#005AA9" stroke-width="2"/></svg>`
      },
      "cmd_mask": {
        name: "Maske Tak",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><circle cx="50" cy="44" r="15" fill="#ffffff"/><path d="M26,84 C26,70 36,63 50,63 C64,63 74,70 74,84 Z" fill="#ffffff"/><path d="M37,46 C42,44 58,44 63,46 L60,58 C56,61 44,61 40,58 Z" fill="#ffffff" stroke="#005AA9" stroke-width="2.5"/><path d="M37,47 L30,42 M63,47 L70,42" stroke="#005AA9" stroke-width="2"/><circle cx="50" cy="53" r="2.5" fill="#005AA9"/></svg>`
      },
      "cmd_boots": {
        name: "İş Ayakkabısı Giy",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><path d="M32,22 L52,22 L52,52 C52,56 56,58 62,60 L72,63 C76,64 78,67 78,71 L78,76 L28,76 L28,26 C28,24 30,22 32,22 Z" fill="#ffffff"/><rect x="26" y="77" width="54" height="5" rx="1.5" fill="#ffffff"/><path d="M60,63 C64,64 66,68 66,76" fill="none" stroke="#005AA9" stroke-width="2"/></svg>`
      },
      "cmd_gloves": {
        name: "Eldiven Giy",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><path d="M38,82 L38,58 C34,56 30,52 28,46 C27,43 31,41 33,43 L38,49 L38,26 C38,22 44,22 44,26 L44,44 L44,20 C44,16 50,16 50,20 L50,44 L50,22 C50,18 56,18 56,22 L56,46 L56,28 C56,24 62,24 62,28 L62,60 C62,68 60,72 58,74 L58,82 Z" fill="#ffffff"/><rect x="36" y="76" width="24" height="4" fill="#005AA9"/></svg>`
      },
      "cmd_clothing": {
        name: "Koruyucu Giysi Giy",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><path d="M38,22 L44,22 C45,26 55,26 56,22 L62,22 L76,36 L70,44 L64,38 L64,80 L36,80 L36,38 L30,44 L24,36 Z" fill="#ffffff"/><line x1="50" y1="28" x2="50" y2="80" stroke="#005AA9" stroke-width="2"/></svg>`
      },
      "cmd_face_shield": {
        name: "Yüz Siperi Tak",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><circle cx="50" cy="44" r="15" fill="#ffffff"/><path d="M26,84 C26,70 36,63 50,63 C64,63 74,70 74,84 Z" fill="#ffffff"/><path d="M32,32 L68,32 L66,62 C60,66 40,66 34,62 Z" fill="none" stroke="#005AA9" stroke-width="2.5"/><rect x="32" y="28" width="36" height="5" fill="#ffffff" stroke="#005AA9" stroke-width="1.5"/></svg>`
      },
      "cmd_harness": {
        name: "Emniyet Kemeri Bağla",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><circle cx="46" cy="26" r="6" fill="#ffffff"/><polyline points="46,35 46,58" fill="none" stroke="#ffffff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/><polyline points="46,39 38,50 37,58" fill="none" stroke="#ffffff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/><polyline points="46,39 54,50 55,58" fill="none" stroke="#ffffff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/><polyline points="46,58 42,78" fill="none" stroke="#ffffff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/><polyline points="46,58 50,78" fill="none" stroke="#ffffff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/><path d="M41,38 L51,56 M51,38 L41,56 M40,60 L52,60" stroke="#005AA9" stroke-width="2.5" fill="none"/><polyline points="48,40 62,30 66,14" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="66" cy="14" r="3" fill="none" stroke="#ffffff" stroke-width="2.5"/></svg>`
      },
      "cmd_general": {
        name: "Genel Emredici İşaret",
        section: "3.3 Emredici",
        cat: "emredici",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><circle cx="50" cy="50" r="44" fill="#005AA9"/><path d="M45.5,22 L54.5,22 L52.5,64 L47.5,64 Z" fill="#ffffff"/><circle cx="50" cy="74" r="5.5" fill="#ffffff"/></svg>`
      },

      // -----------------------------------------------------------------------
      // 4. ACİL ÇIKIŞ VE İLK YARDIM İŞARETLERİ (EK-2 MADDE 3.4)
      // -----------------------------------------------------------------------
      "exit_left": {
        name: "Acil Çıkış / Kaçış Yolu (Sol)",
        section: "3.4 Acil & İlkyardım",
        cat: "acil",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#008851"/><g transform="translate(100,0) scale(-1,1)"><rect x="64" y="16" width="20" height="50" fill="none" stroke="#ffffff" stroke-width="4"/><rect x="70" y="16" width="14" height="50" fill="#ffffff"/><circle cx="44" cy="22" r="5.5" fill="#ffffff"/><polyline points="41,30 35,46" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="40,32 50,38 58,34" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="40,32 30,36 24,44" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="35,46 46,54 44,66" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="35,46 28,56 18,58" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M18,80 L56,80 L56,74 L70,82 L56,90 L56,84 L18,84 Z" fill="#ffffff"/></g></svg>`
      },
      "exit_right": {
        name: "Acil Çıkış / Kaçış Yolu (Sağ)",
        section: "3.4 Acil & İlkyardım",
        cat: "acil",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#008851"/><rect x="64" y="16" width="20" height="50" fill="none" stroke="#ffffff" stroke-width="4"/><rect x="70" y="16" width="14" height="50" fill="#ffffff"/><circle cx="44" cy="22" r="5.5" fill="#ffffff"/><polyline points="41,30 35,46" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="40,32 50,38 58,34" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="40,32 30,36 24,44" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="35,46 46,54 44,66" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="35,46 28,56 18,58" fill="none" stroke="#ffffff" stroke-width="6.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M18,80 L56,80 L56,74 L70,82 L56,90 L56,84 L18,84 Z" fill="#ffffff"/></svg>`
      },
      "first_aid": {
        name: "İlk Yardım",
        section: "3.4 Acil & İlkyardım",
        cat: "acil",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#008851"/><path d="M40,18 L60,18 L60,40 L82,40 L82,60 L60,60 L60,82 L40,82 L40,60 L18,60 L18,40 L40,40 Z" fill="#ffffff"/></svg>`
      },
      "stretcher": {
        name: "Sedye",
        section: "3.4 Acil & İlkyardım",
        cat: "acil",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#008851"/><rect x="16" y="56" width="68" height="7" rx="2" fill="#ffffff"/><rect x="10" y="58" width="8" height="3" fill="#ffffff"/><rect x="82" y="58" width="8" height="3" fill="#ffffff"/><path d="M26,63 L22,76 M74,63 L78,76" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/><path d="M45,22 L55,22 L55,30 L63,30 L63,40 L55,40 L55,48 L45,48 L45,40 L37,40 L37,30 L45,30 Z" fill="#ffffff"/></svg>`
      },
      "safety_shower": {
        name: "Güvenlik Duşu",
        section: "3.4 Acil & İlkyardım",
        cat: "acil",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#008851"/><path d="M30,16 L60,16 L60,22 L50,22 L50,26 L58,32 L42,32 L50,26" fill="#ffffff" stroke="#ffffff" stroke-width="2" stroke-linejoin="round"/><line x1="44" y1="36" x2="41" y2="44" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/><line x1="50" y1="36" x2="50" y2="44" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/><line x1="56" y1="36" x2="59" y2="44" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/><circle cx="50" cy="52" r="5" fill="#ffffff"/><polyline points="50,59 50,74" fill="none" stroke="#ffffff" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="50,62 43,70" fill="none" stroke="#ffffff" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="50,62 57,70" fill="none" stroke="#ffffff" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="50,74 46,88" fill="none" stroke="#ffffff" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/><polyline points="50,74 54,88" fill="none" stroke="#ffffff" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`
      },
      "eye_wash": {
        name: "Göz Duşu",
        section: "3.4 Acil & İlkyardım",
        cat: "acil",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#008851"/><path d="M38,22 C52,22 60,30 60,42 L64,48 L60,50 L60,56 C60,60 56,62 50,62 L50,74 L32,74 L32,58 C26,54 24,46 26,36 C28,28 32,22 38,22 Z" fill="#ffffff"/><circle cx="52" cy="38" r="2.5" fill="#008851"/><path d="M74,84 C74,64 68,50 58,40" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-dasharray="4 4"/><rect x="68" y="84" width="14" height="6" fill="#ffffff"/></svg>`
      },
      "emergency_phone": {
        name: "Acil Durum Telefonu",
        section: "3.4 Acil & İlkyardım",
        cat: "acil",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#008851"/><path d="M26,30 C30,24 36,24 38,28 L44,40 C45,43 43,45 41,46 L37,48 C40,56 46,62 54,66 L56,62 C57,60 60,58 62,59 L74,65 C78,67 78,73 72,78 C64,84 50,80 38,68 C26,56 20,40 26,30 Z" fill="#ffffff"/><path d="M64,16 L72,16 L72,24 L80,24 L80,32 L72,32 L72,40 L64,40 L64,32 L56,32 L56,24 L64,24 Z" fill="#ffffff"/></svg>`
      },
      "assembly_point": {
        name: "Acil Toplanma Alanı",
        section: "3.4 Acil & İlkyardım",
        cat: "acil",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#008851"/><circle cx="38" cy="48" r="4" fill="#ffffff"/><polyline points="38,55 38,67" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="38,57 33,64" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="38,57 43,64" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="38,67 35,77" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="38,67 41,77" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="50" cy="48" r="4" fill="#ffffff"/><polyline points="50,55 50,67" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="50,57 45,64" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="50,57 55,64" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="50,67 47,77" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="50,67 53,77" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="62" cy="48" r="4" fill="#ffffff"/><polyline points="62,55 62,67" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="62,57 57,64" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="62,57 67,64" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="62,67 59,77" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><polyline points="62,67 65,77" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><g transform="rotate(0 50 50)"><path d="M28,28 L28,15 L23.5,19.5 L16,12 L12,16 L19.5,23.5 L15,28 Z" fill="#ffffff"/></g><g transform="rotate(90 50 50)"><path d="M28,28 L28,15 L23.5,19.5 L16,12 L12,16 L19.5,23.5 L15,28 Z" fill="#ffffff"/></g><g transform="rotate(180 50 50)"><path d="M28,28 L28,15 L23.5,19.5 L16,12 L12,16 L19.5,23.5 L15,28 Z" fill="#ffffff"/></g><g transform="rotate(270 50 50)"><path d="M28,28 L28,15 L23.5,19.5 L16,12 L12,16 L19.5,23.5 L15,28 Z" fill="#ffffff"/></g></svg>`
      },

      // -----------------------------------------------------------------------
      // 5. YANGINLA MÜCADELE İŞARETLERİ (EK-2 MADDE 3.5)
      // -----------------------------------------------------------------------
      "fire_hose": {
        name: "Yangın Hortumu",
        section: "3.5 Yangınla Mücadele",
        cat: "yangin",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#D02C2F"/><circle cx="42" cy="44" r="22" fill="none" stroke="#ffffff" stroke-width="5"/><circle cx="42" cy="44" r="13" fill="none" stroke="#ffffff" stroke-width="4"/><circle cx="42" cy="44" r="4" fill="#ffffff"/><path d="M42,66 C44,78 58,80 66,74" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round"/><path d="M64,70 L80,62 L82,68 L68,78 Z" fill="#ffffff"/></svg>`
      },
      "fire_ladder": {
        name: "Yangın Merdiveni",
        section: "3.5 Yangınla Mücadele",
        cat: "yangin",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#D02C2F"/><rect x="32" y="16" width="6" height="70" fill="#ffffff"/><rect x="62" y="16" width="6" height="70" fill="#ffffff"/><rect x="38" y="24" width="24" height="5" fill="#ffffff"/><rect x="38" y="38" width="24" height="5" fill="#ffffff"/><rect x="38" y="52" width="24" height="5" fill="#ffffff"/><rect x="38" y="66" width="24" height="5" fill="#ffffff"/></svg>`
      },
      "fire_extinguisher": {
        name: "Yangın Söndürme Cihazı",
        section: "3.5 Yangınla Mücadele",
        cat: "yangin",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#D02C2F"/><rect x="40" y="32" width="22" height="52" rx="6" fill="#ffffff"/><rect x="46" y="22" width="10" height="10" fill="#ffffff"/><path d="M46,24 L34,20 L34,26 L46,28 Z" fill="#ffffff"/><path d="M56,26 C66,26 70,34 70,46" fill="none" stroke="#ffffff" stroke-width="3"/><rect x="67" y="46" width="6" height="12" rx="1" fill="#ffffff"/><path transform="translate(24,50) scale(0.45)" d="M0,0 C-12,0 -16,-10 -11,-20 C-9,-14 -6,-12 -4,-12 C-8,-22 -2,-32 4,-38 C3,-28 10,-24 12,-16 C13,-22 12,-26 11,-28 C18,-20 18,-8 12,-3 C9,0 4,0 0,0 Z" fill="#ffffff"/></svg>`
      },
      "fire_alarm_call": {
        name: "Yangın Butonu / Acil Yangın Telefonu",
        section: "3.5 Yangınla Mücadele",
        cat: "yangin",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#D02C2F"/><rect x="38" y="24" width="44" height="44" rx="3" fill="#ffffff"/><rect x="45" y="31" width="30" height="30" rx="2" fill="#D02C2F"/><circle cx="60" cy="46" r="8" fill="#ffffff"/><path d="M14,78 L28,68 L44,56 C47,54 50,57 48,60 L42,66 L50,68 L50,84 L22,86 Z" fill="#ffffff" stroke="#D02C2F" stroke-width="2"/><path transform="translate(22,46) scale(0.45)" d="M0,0 C-12,0 -16,-10 -11,-20 C-9,-14 -6,-12 -4,-12 C-8,-22 -2,-32 4,-38 C3,-28 10,-24 12,-16 C13,-22 12,-26 11,-28 C18,-20 18,-8 12,-3 C9,0 4,0 0,0 Z" fill="#ffffff"/></svg>`
      },
      "fire_arrow_dir": {
        name: "Yangın Yön Oku",
        section: "3.5 Yangınla Mücadele",
        cat: "yangin",
        svg: `<svg viewBox="0 0 100 100" class="w-full h-full"><rect x="6" y="6" width="88" height="88" rx="8" fill="#D02C2F"/><path d="M50,16 L80,48 L62,48 L62,84 L38,84 L38,48 L20,48 Z" fill="#ffffff"/></svg>`
      }
    };

    // -------------------------------------------------------------------------
    // PLATFORM DURUMU (APPLICATION STATE) — başlangıç değerleri mehse'den gelir
    // -------------------------------------------------------------------------
    let state = Object.assign({
      baslik: "",
      currentStep: 1,
      standardType: "ansi", // "ansi" veya "yonetmelik_pure"
      formType: "single",   // "single", "flag", "floor"

      headerBg: "#D02C2F",
      headerTextCol: "#ffffff",
      bodyBg: "#ffffff",
      bodyTextCol: "#000000",
      borderColor: "#000000",
      borderWidth: 4,
      borderRadius: 8,
      doubleBorder: true,
      screwOption: "4_corners", // "4_corners", "2_sides", "none"

      signalTr: "TEHLİKE",
      signalEn: "DANGER",
      hasTriangle: true,

      picPosition: "top", // "top", "left", "right", "bottom", "none"
      picCount: 1,        // 1, 2, 3, 4
      activeSlotIndex: 0,
      slots: ["warn_electric", "cmd_helmet", "cmd_boots", "no_entry"],
      slotCustomImages: [null, null, null, null],
      picWrapper: "none", // "none", "warning_triangle", "mandatory_circle", "prohibition_circle"
      picScale: 100,

      mainTitle: "YÜKSEK GERİLİM - 380 VOLT",
      descText: "Panolara ve elektrik hücrelerine yetkisiz personelin müdahalesi kesinlikle yasaktır. Bakım öncesi enerjiyi kesiniz ve kilitleyiniz (LOTO).",
      showEn: true,
      enTitle: "HIGH VOLTAGE - 380 VOLTS",
      enDesc: "Authorized personnel only. Lockout and tagout power sources before opening or servicing.",
      specNote: "0.50mm Eloksallı Alüminyum / UV Baskı / -30°C +80°C Dayanımlı",

      fontFamily: "font-roboto",
      textAlign: "text-center",
      titleSize: 22,
      descSize: 12,

      width: 480,
      height: 680,
      zoom: 1.0,
      activeRegFilter: "all"
    }, @json($veri ?? []));

    // -------------------------------------------------------------------------
    // BAŞLATMA
    // -------------------------------------------------------------------------
    document.addEventListener("DOMContentLoaded", () => {
      syncFormFromState();
      initRegulationBadges();
      initPiktogramGrid();
      updateSlotSelectorButtons();
      updateSign();
    });

    /** Sunucudan/JSON'dan gelen `state`'i form alanlarına yansıtır (updateSign() formdan okur). */
    function syncFormFromState() {
      document.getElementById("tasarimBaslikInput").value = state.baslik || "";

      document.querySelectorAll(".std-btn").forEach(b => {
        const isCurrent = b.getAttribute("data-std") === state.standardType;
        b.className = isCurrent
          ? "std-btn p-2 rounded-lg border-2 border-red-600 bg-red-50 text-red-700 font-bold text-left flex items-center justify-between"
          : "std-btn p-2 rounded-lg border-2 border-slate-200 bg-white text-slate-700 font-bold text-left flex items-center justify-between hover:bg-slate-50";
      });
      const ansiHeaderSection = document.getElementById("ansiHeaderSection");
      if (state.standardType === "yonetmelik_pure") {
        ansiHeaderSection.classList.add("opacity-40", "pointer-events-none");
      } else {
        ansiHeaderSection.classList.remove("opacity-40", "pointer-events-none");
      }

      const formRadio = document.querySelector(`input[name="signFormType"][value="${state.formType}"]`);
      if (formRadio) formRadio.checked = true;

      document.getElementById("headerBgInput").value = state.headerBg;
      document.getElementById("headerTextInput").value = state.headerTextCol;
      document.getElementById("bodyBgInput").value = state.bodyBg;
      document.getElementById("bodyTextInput").value = state.bodyTextCol;
      document.getElementById("borderColorInput").value = state.borderColor;
      document.getElementById("borderWidthSelect").value = String(state.borderWidth);
      document.getElementById("borderRadiusSlider").value = String(state.borderRadius);
      document.getElementById("doubleBorderToggle").checked = !!state.doubleBorder;
      document.getElementById("screwOptionSelect").value = state.screwOption;
      document.getElementById("showTriangleToggle").checked = !!state.hasTriangle;

      document.querySelectorAll(".pos-btn").forEach(b => {
        const isCurr = b.getAttribute("data-pos") === state.picPosition;
        b.className = isCurr
          ? "pos-btn p-2 rounded-lg border font-bold text-[11px] border-red-600 bg-red-50 text-red-700"
          : "pos-btn p-2 rounded-lg border font-bold text-[11px] border-slate-200 bg-white text-slate-700 hover:bg-slate-50";
      });
      document.querySelectorAll(".count-btn").forEach(b => {
        const isCurr = parseInt(b.getAttribute("data-count")) === state.picCount;
        b.className = isCurr
          ? "count-btn p-2 rounded-lg border font-black text-xs border-red-600 bg-red-600 text-white"
          : "count-btn p-2 rounded-lg border font-black text-xs border-slate-200 bg-white text-slate-700 hover:bg-slate-100";
      });
      document.querySelectorAll(".wrap-btn").forEach(b => {
        const isCurr = b.getAttribute("data-wrap") === state.picWrapper;
        b.className = isCurr
          ? "wrap-btn p-1.5 border rounded font-semibold text-[10px] border-red-600 bg-red-50 text-red-700"
          : "wrap-btn p-1.5 border rounded font-semibold text-[10px] border-slate-300 hover:bg-slate-100 text-slate-700";
      });

      document.getElementById("signalTrInput").value = state.signalTr || "TEHLİKE";
      document.getElementById("signalEnInput").value = state.signalEn || "DANGER";
      document.getElementById("mainTitleInput").value = state.mainTitle || "";
      document.getElementById("descTextInput").value = state.descText || "";
      document.getElementById("showEnToggle").checked = !!state.showEn;
      document.getElementById("enTitleInput").value = state.enTitle || "";
      document.getElementById("enDescInput").value = state.enDesc || "";
      document.getElementById("specNoteInput").value = state.specNote || "";

      document.getElementById("fontFamilySelect").value = state.fontFamily;
      document.getElementById("textAlignSelect").value = state.textAlign;
      document.getElementById("titleSizeSlider").value = String(state.titleSize);
      document.getElementById("descSizeSlider").value = String(state.descSize);
      document.getElementById("picScaleSlider").value = String(state.picScale);
      document.getElementById("widthSlider").value = String(state.width);
      document.getElementById("heightSlider").value = String(state.height);
    }

    // -------------------------------------------------------------------------
    // 4 KADEME KONTROLÜ (STEP SWITCHER)
    // -------------------------------------------------------------------------
    function setStep(stepNum) {
      state.currentStep = stepNum;
      document.querySelectorAll(".step-pane").forEach(p => p.classList.add("hidden"));
      document.querySelectorAll(".step-btn").forEach(b => {
        b.classList.remove("border-b-2", "border-red-600", "text-red-600", "bg-white");
        b.classList.add("text-slate-600");
      });

      const pane = document.getElementById(`step-${stepNum}`);
      if (pane) pane.classList.remove("hidden");

      const btn = document.querySelector(`.step-btn[data-step="${stepNum}"]`);
      if (btn) {
        btn.classList.add("border-b-2", "border-red-600", "text-red-600", "bg-white");
        btn.classList.remove("text-slate-600");
      }
    }

    // Tabela Standardı (ANSI vs Yönetmelik Saf Levha)
    function setSignStandard(std) {
      state.standardType = std;
      document.querySelectorAll(".std-btn").forEach(b => {
        const isCurrent = b.getAttribute("data-std") === std;
        b.className = isCurrent
          ? "std-btn p-2 rounded-lg border-2 border-red-600 bg-red-50 text-red-700 font-bold text-left flex items-center justify-between"
          : "std-btn p-2 rounded-lg border-2 border-slate-200 bg-white text-slate-700 font-bold text-left flex items-center justify-between hover:bg-slate-50";
      });

      const ansiHeaderSection = document.getElementById("ansiHeaderSection");
      if (std === "yonetmelik_pure") {
        ansiHeaderSection.classList.add("opacity-40", "pointer-events-none");
      } else {
        ansiHeaderSection.classList.remove("opacity-40", "pointer-events-none");
      }
      updateSign();
    }

    // Sinyal Şablonu Uygula
    function applySignalPreset(tr, en, bg, textCol, triangle) {
      state.signalTr = tr;
      state.signalEn = en;
      state.headerBg = bg;
      state.headerTextCol = textCol;
      state.hasTriangle = triangle;

      document.getElementById("signalTrInput").value = tr;
      document.getElementById("signalEnInput").value = en;
      document.getElementById("headerBgInput").value = bg;
      document.getElementById("headerTextInput").value = textCol;
      document.getElementById("showTriangleToggle").checked = triangle;

      updateSign();
    }

    // Piktogram Konumu
    function setPicPosition(pos) {
      state.picPosition = pos;
      document.querySelectorAll(".pos-btn").forEach(b => {
        const isCurr = b.getAttribute("data-pos") === pos;
        b.className = isCurr
          ? "pos-btn p-2 rounded-lg border font-bold text-[11px] border-red-600 bg-red-50 text-red-700"
          : "pos-btn p-2 rounded-lg border font-bold text-[11px] border-slate-200 bg-white text-slate-700 hover:bg-slate-50";
      });
      updateSign();
    }

    // Piktogram Slot Adedi (1, 2, 3, 4)
    function setPicCount(cnt) {
      state.picCount = cnt;
      if (state.activeSlotIndex >= cnt) state.activeSlotIndex = 0;

      document.querySelectorAll(".count-btn").forEach(b => {
        const isCurr = parseInt(b.getAttribute("data-count")) === cnt;
        b.className = isCurr
          ? "count-btn p-2 rounded-lg border font-black text-xs border-red-600 bg-red-600 text-white"
          : "count-btn p-2 rounded-lg border font-black text-xs border-slate-200 bg-white text-slate-700 hover:bg-slate-100";
      });

      updateSlotSelectorButtons();
      updateSign();
    }

    // Slot Seçici Butonları
    function updateSlotSelectorButtons() {
      const box = document.getElementById("slotSelectorContainer");
      box.innerHTML = "";

      for (let i = 0; i < state.picCount; i++) {
        const isAct = (state.activeSlotIndex === i);
        const btn = document.createElement("button");
        btn.className = `flex-1 py-1.5 px-2 rounded-lg text-xs font-bold border transition flex items-center justify-center gap-1 ${
          isAct ? 'bg-red-600 text-white border-red-600 shadow-sm' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'
        }`;
        const piktName = state.slotCustomImages[i] ? "Özel Resim" : (REGULATION_PICTOGRAMS[state.slots[i]]?.name || "Piktogram");
        btn.innerHTML = `<span>Slot ${i + 1}</span> <span class="text-[9px] opacity-80 truncate max-w-[80px]">(${piktName})</span>`;
        btn.onclick = () => {
          state.activeSlotIndex = i;
          document.getElementById("activeSlotIndicator").textContent = `Slot ${i + 1}`;
          updateSlotSelectorButtons();
          initPiktogramGrid();
        };
        box.appendChild(btn);
      }

      document.getElementById("activeSlotIndicator").textContent = `Slot ${state.activeSlotIndex + 1}`;
      const clearBtn = document.getElementById("clearCustomSlotBtn");
      if (state.slotCustomImages[state.activeSlotIndex]) {
        clearBtn.classList.remove("hidden");
      } else {
        clearBtn.classList.add("hidden");
      }
    }

    // Özel Resim Yükleme
    function handleCustomSlotUpload(e) {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (evt) => {
        state.slotCustomImages[state.activeSlotIndex] = evt.target.result;
        updateSlotSelectorButtons();
        updateSign();
      };
      reader.readAsDataURL(file);
    }

    function clearCustomSlot() {
      state.slotCustomImages[state.activeSlotIndex] = null;
      document.getElementById("slotUploadInput").value = "";
      updateSlotSelectorButtons();
      updateSign();
    }

    // Piktogram Çerçeve Geometrisi
    function setPicWrapper(wrap) {
      state.picWrapper = wrap;
      document.querySelectorAll(".wrap-btn").forEach(b => {
        const isCurr = b.getAttribute("data-wrap") === wrap;
        b.className = isCurr
          ? "wrap-btn p-1.5 border rounded font-semibold text-[10px] border-red-600 bg-red-50 text-red-700"
          : "wrap-btn p-1.5 border rounded font-semibold text-[10px] border-slate-300 hover:bg-slate-100 text-slate-700";
      });
      updateSign();
    }

    // -------------------------------------------------------------------------
    // YÖNETMELİK KATEGORİLERİ & PİKTOGRAM IZGARASI
    // -------------------------------------------------------------------------
    function initRegulationBadges() {
      const categories = [
        { id: "all", label: "Tümü (Ek-2)" },
        { id: "yasak", label: "3.1 Yasaklayıcı" },
        { id: "uyari", label: "3.2 Uyarı" },
        { id: "emredici", label: "3.3 Emredici" },
        { id: "acil", label: "3.4 Acil & İlkyardım" },
        { id: "yangin", label: "3.5 Yangınla Mücadele" }
      ];

      const container = document.getElementById("regulationBadgesContainer");
      container.innerHTML = "";
      categories.forEach((c, idx) => {
        const btn = document.createElement("button");
        btn.className = `px-2 py-0.5 text-[10px] font-bold rounded-full border transition ${
          (c.id === state.activeRegFilter)
            ? 'bg-slate-900 text-white border-slate-900'
            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'
        }`;
        btn.textContent = c.label;
        btn.onclick = () => {
          document.querySelectorAll("#regulationBadgesContainer button").forEach(b => {
            b.className = "px-2 py-0.5 text-[10px] font-bold rounded-full border bg-white text-slate-600 border-slate-200 hover:bg-slate-100";
          });
          btn.className = "px-2 py-0.5 text-[10px] font-bold rounded-full border bg-slate-900 text-white border-slate-900";
          state.activeRegFilter = c.id;
          initPiktogramGrid();
        };
        container.appendChild(btn);
      });
    }

    function initPiktogramGrid(searchQuery = "") {
      const grid = document.getElementById("piktogramSelectionGrid");
      grid.innerHTML = "";

      const keys = Object.keys(REGULATION_PICTOGRAMS);
      const activeSlotKey = state.slots[state.activeSlotIndex];
      const hasCustom = !!state.slotCustomImages[state.activeSlotIndex];

      let displayed = 0;

      keys.forEach(key => {
        const item = REGULATION_PICTOGRAMS[key];
        const matchCat = (state.activeRegFilter === "all" || item.cat === state.activeRegFilter);
        const matchSearch = (!searchQuery || item.name.toLowerCase().includes(searchQuery.toLowerCase()) || item.section.toLowerCase().includes(searchQuery.toLowerCase()));

        if (matchCat && matchSearch) {
          displayed++;
          const btn = document.createElement("button");
          const isSelected = (activeSlotKey === key && !hasCustom);
          btn.className = `p-1.5 rounded-lg border bg-white hover:border-red-600 transition flex flex-col items-center justify-center gap-1 ${
            isSelected ? 'border-2 border-red-600 ring-2 ring-red-100' : 'border-slate-200'
          }`;
          btn.title = `${item.name} (${item.section})`;
          btn.onclick = () => {
            state.slots[state.activeSlotIndex] = key;
            state.slotCustomImages[state.activeSlotIndex] = null;
            updateSlotSelectorButtons();
            initPiktogramGrid(searchQuery);
            updateSign();
          };

          btn.innerHTML = `
            <div class="w-8 h-8 flex items-center justify-center">${item.svg}</div>
            <span class="text-[9px] text-slate-700 truncate w-full text-center font-medium">${item.name}</span>
          `;
          grid.appendChild(btn);
        }
      });

      document.getElementById("piktogramCountBadge").textContent = `${displayed} İşaret`;
    }

    function filterPiktogramsBySearch() {
      const q = document.getElementById("piktogramSearchInput").value.trim();
      initPiktogramGrid(q);
    }

    // Ebat Hazır Oranları
    function applyPresetDim(cmW, cmH, pxW, pxH) {
      state.width = pxW;
      state.height = pxH;
      document.getElementById("widthSlider").value = pxW;
      document.getElementById("heightSlider").value = pxH;
      document.getElementById("currentCmIndicator").textContent = `${cmW} x ${cmH} cm`;
      updateSign();
    }

    // Zoom Ayarlama
    function changeZoom(delta) {
      state.zoom = Math.max(0.5, Math.min(1.4, state.zoom + delta));
      document.getElementById("signScaleWrapper").style.transform = `scale(${state.zoom})`;
      document.getElementById("zoomText").textContent = `${Math.round(state.zoom * 100)}%`;
    }

    // -------------------------------------------------------------------------
    // TEKİL PİKTOGRAM KUTUSU ÇİZİCİ
    // -------------------------------------------------------------------------
    function renderSingleSlotHTML(slotIdx) {
      const customImg = state.slotCustomImages[slotIdx];
      const iconKey = state.slots[slotIdx] || "warn_electric";

      let coreSvg = "";
      if (customImg) {
        coreSvg = `<img src="${customImg}" class="w-full h-full object-contain" alt="Yüklenen Görsel">`;
      } else {
        coreSvg = REGULATION_PICTOGRAMS[iconKey]?.svg || REGULATION_PICTOGRAMS["warn_electric"].svg;
      }

      // Şekil Geometrisi (ISO 7010)
      if (state.picWrapper === "warning_triangle") {
        return `
          <div class="relative w-full h-full flex items-center justify-center p-1" style="transform:scale(${state.picScale / 100})">
            <svg viewBox="0 0 100 100" class="w-full h-full drop-shadow">
              <polygon points="50,6 94,92 6,92" fill="#FED402" stroke="#000000" stroke-width="6" stroke-linejoin="round"/>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center pt-2 p-3">
              <div class="w-12 h-12 flex items-center justify-center">${coreSvg}</div>
            </div>
          </div>
        `;
      } else if (state.picWrapper === "mandatory_circle") {
        return `
          <div class="relative w-full h-full flex items-center justify-center p-1" style="transform:scale(${state.picScale / 100})">
            <div class="w-24 h-24 rounded-full bg-[#005AA9] border-4 border-white shadow flex items-center justify-center p-3 text-white">
              <div class="w-14 h-14 [&_svg]:fill-white [&_svg]:stroke-white [&_polygon]:fill-white [&_path]:fill-white">${coreSvg}</div>
            </div>
          </div>
        `;
      } else if (state.picWrapper === "prohibition_circle") {
        return `
          <div class="relative w-full h-full flex items-center justify-center p-1" style="transform:scale(${state.picScale / 100})">
            <div class="relative w-24 h-24 rounded-full border-8 border-[#D02C2F] bg-white flex items-center justify-center p-3">
              <div class="w-14 h-14">${coreSvg}</div>
              <div class="absolute w-[115%] h-2 bg-[#D02C2F] rotate-45 rounded"></div>
            </div>
          </div>
        `;
      }

      // Düz / Saf İkon
      return `
        <div class="w-full h-full flex items-center justify-center p-1.5" style="transform:scale(${state.picScale / 100})">
          <div class="w-24 h-24 max-w-full max-h-full flex items-center justify-center">${coreSvg}</div>
        </div>
      `;
    }

    // Çoklu Slot Alanı HTML
    function renderSlotsAreaHTML() {
      if (state.picPosition === "none") return "";

      let slotsHTML = "";
      for (let i = 0; i < state.picCount; i++) {
        slotsHTML += `
          <div class="flex-1 flex items-center justify-center min-w-0 min-h-0">
            ${renderSingleSlotHTML(i)}
          </div>
        `;
      }

      let containerClass = "flex items-center justify-around";
      if (state.picPosition === "left" || state.picPosition === "right") {
        containerClass = (state.picCount > 2) ? "grid grid-cols-2 gap-1 p-2" : "flex flex-col items-center justify-around p-2";
      }

      return `<div class="piktograms-holder ${containerClass} w-full h-full">${slotsHTML}</div>`;
    }

    // -------------------------------------------------------------------------
    // CANLI GÜNCELLEME & RENDER MOTORU (UPDATE SIGN)
    // -------------------------------------------------------------------------
    function updateSign() {
      // Form Elemanlarından Oku
      const formRadio = document.querySelector('input[name="signFormType"]:checked');
      if (formRadio) state.formType = formRadio.value;

      state.headerBg = document.getElementById("headerBgInput").value;
      state.headerTextCol = document.getElementById("headerTextInput").value;
      state.bodyBg = document.getElementById("bodyBgInput").value;
      state.bodyTextCol = document.getElementById("bodyTextInput").value;
      state.borderColor = document.getElementById("borderColorInput").value;
      state.borderWidth = parseInt(document.getElementById("borderWidthSelect").value);
      state.borderRadius = parseInt(document.getElementById("borderRadiusSlider").value);
      state.doubleBorder = document.getElementById("doubleBorderToggle").checked;
      state.screwOption = document.getElementById("screwOptionSelect").value;
      state.hasTriangle = document.getElementById("showTriangleToggle").checked;

      state.signalTr = document.getElementById("signalTrInput").value.toUpperCase();
      state.signalEn = document.getElementById("signalEnInput").value.toUpperCase();
      state.mainTitle = document.getElementById("mainTitleInput").value.toUpperCase();
      state.descText = document.getElementById("descTextInput").value;
      state.showEn = document.getElementById("showEnToggle").checked;
      state.enTitle = document.getElementById("enTitleInput").value.toUpperCase();
      state.enDesc = document.getElementById("enDescInput").value;
      state.specNote = document.getElementById("specNoteInput").value;

      state.fontFamily = document.getElementById("fontFamilySelect").value;
      state.textAlign = document.getElementById("textAlignSelect").value;
      state.titleSize = parseInt(document.getElementById("titleSizeSlider").value);
      state.descSize = parseInt(document.getElementById("descSizeSlider").value);
      state.picScale = parseInt(document.getElementById("picScaleSlider").value);
      state.width = parseInt(document.getElementById("widthSlider").value);
      state.height = parseInt(document.getElementById("heightSlider").value);

      // Kod Göstergeleri
      document.getElementById("headerBgCode").textContent = state.headerBg;
      document.getElementById("headerTextCode").textContent = state.headerTextCol;
      document.getElementById("bodyBgCode").textContent = state.bodyBg;
      document.getElementById("bodyTextCode").textContent = state.bodyTextCol;
      document.getElementById("borderColorCode").textContent = state.borderColor;
      document.getElementById("radiusTxt").textContent = state.borderRadius;
      document.getElementById("valTitleSize").textContent = state.titleSize;
      document.getElementById("valDescSize").textContent = state.descSize;
      document.getElementById("picScaleTxt").textContent = `${state.picScale}%`;
      document.getElementById("valWidth").textContent = state.width;
      document.getElementById("valHeight").textContent = state.height;

      // Konteyner Boyut ve Stilleri
      const board = document.getElementById("masterLiveSign");
      board.style.width = `${state.width}px`;
      board.style.minHeight = `${state.height}px`;
      board.style.borderRadius = `${state.borderRadius}px`;
      board.style.border = `${state.borderWidth}px solid ${state.borderColor}`;
      board.style.backgroundColor = state.bodyBg;

      // Tabela Formatı (Bayrak / A-Tipi)
      const flagElem = document.getElementById("flagBracketElem");
      const aFrameElem = document.getElementById("aFrameTopElem");
      const statusText = document.getElementById("formatStatusText");

      if (state.formType === "flag") {
        flagElem.classList.remove("hidden");
        aFrameElem.classList.add("hidden");
        statusText.textContent = `Çift Yönlü Flanşlı Bayrak • ${state.width}x${state.height} px`;
      } else if (state.formType === "floor") {
        flagElem.classList.add("hidden");
        aFrameElem.classList.remove("hidden");
        statusText.textContent = `Katlanır A-Tipi Zemin • ${state.width}x${state.height} px`;
      } else {
        flagElem.classList.add("hidden");
        aFrameElem.classList.add("hidden");
        statusText.textContent = `Tek Yüz Düz Levha • ${state.width}x${state.height} px`;
      }

      // Montaj Vida Delikleri
      const sTL = document.getElementById("screwTL");
      const sTR = document.getElementById("screwTR");
      const sBL = document.getElementById("screwBL");
      const sBR = document.getElementById("screwBR");
      const sML = document.getElementById("screwML");
      const sMR = document.getElementById("screwMR");

      [sTL, sTR, sBL, sBR, sML, sMR].forEach(el => el.classList.add("hidden"));

      if (state.screwOption === "4_corners") {
        [sTL, sTR, sBL, sBR].forEach(el => el.classList.remove("hidden"));
      } else if (state.screwOption === "2_sides") {
        [sML, sMR].forEach(el => el.classList.remove("hidden"));
      }

      // Çift Kontur Çizgisi
      const innerBorder = document.getElementById("innerBorderElem");
      if (state.doubleBorder) {
        innerBorder.classList.remove("hidden");
        innerBorder.style.borderColor = state.bodyBg === "#ffffff" ? "#000000" : "#ffffff";
        innerBorder.style.borderRadius = `${Math.max(0, state.borderRadius - 4)}px`;
      } else {
        innerBorder.classList.add("hidden");
      }

      // Şartname Dipnotu
      document.getElementById("specFootnoteText").textContent = state.specNote || "T.C. İSG YÖNETMELİK STANDARDI";

      // ANSI Üçgen İkonu
      const triangleIconHTML = state.hasTriangle ? `
        <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2L1 21h22L12 2zm0 3.99L19.53 19H4.47L12 5.99zM11 10h2v4h-2zm0 6h2v2h-2z"/>
        </svg>
      ` : '';

      // İngilizce Metin Bloğu
      const englishHTML = state.showEn ? `
        <div class="mt-2 pt-2 border-t border-slate-300/80">
          <div class="font-black text-[12px] tracking-tight uppercase" style="color:${state.bodyTextCol}; opacity:0.9;">${state.enTitle}</div>
          <div class="text-[10px] mt-0.5 leading-snug" style="color:${state.bodyTextCol}; opacity:0.75;">${state.enDesc}</div>
        </div>
      ` : '';

      // Piktogram Alanı
      const piktogramsHTML = renderSlotsAreaHTML();

      // Metin Bloğu
      const textBlockHTML = `
        <div class="p-4 flex flex-col justify-center ${state.textAlign}" style="color:${state.bodyTextCol}">
          <h2 class="${state.fontFamily} font-black leading-tight uppercase tracking-tight" style="font-size: ${state.titleSize}px;">
            ${state.mainTitle}
          </h2>
          <p class="font-medium mt-1.5 leading-relaxed" style="font-size: ${state.descSize}px;">
            ${state.descText}
          </p>
          ${englishHTML}
        </div>
      `;

      // Sinyal Başlık Şeridi
      const headerStripHTML = (state.standardType === "ansi") ? `
        <div class="py-3 px-4 flex items-center justify-center gap-2 uppercase font-black tracking-wider border-b-[3px] border-black shadow-sm" style="background-color: ${state.headerBg}; color: ${state.headerTextCol};">
          ${triangleIconHTML}
          <span class="text-xl md:text-2xl ${state.fontFamily}">${state.signalTr}</span>
          ${state.signalEn ? `<span class="text-xs opacity-85 font-medium ml-1">/ ${state.signalEn}</span>` : ''}
        </div>
      ` : '';

      const contentBox = document.getElementById("signDynamicBody");

      // Yerleşim Motoru
      if (state.picPosition === "top") {
        contentBox.innerHTML = `
          ${headerStripHTML}
          <div class="flex-1 flex items-center justify-center py-4 px-3" style="background-color:${state.bodyBg}">
            ${piktogramsHTML}
          </div>
          <div class="border-t-2 border-black" style="background-color:${state.bodyBg}">
            ${textBlockHTML}
          </div>
        `;
      } else if (state.picPosition === "bottom") {
        contentBox.innerHTML = `
          ${headerStripHTML}
          <div class="border-b-2 border-black" style="background-color:${state.bodyBg}">
            ${textBlockHTML}
          </div>
          <div class="flex-1 flex items-center justify-center py-4 px-3" style="background-color:${state.bodyBg}">
            ${piktogramsHTML}
          </div>
        `;
      } else if (state.picPosition === "left") {
        contentBox.innerHTML = `
          ${headerStripHTML}
          <div class="flex-1 grid grid-cols-12" style="background-color:${state.bodyBg}">
            <div class="col-span-5 border-r-2 border-black flex items-center justify-center p-2">
              ${piktogramsHTML}
            </div>
            <div class="col-span-7 flex flex-col justify-center">
              ${textBlockHTML}
            </div>
          </div>
        `;
      } else if (state.picPosition === "right") {
        contentBox.innerHTML = `
          ${headerStripHTML}
          <div class="flex-1 grid grid-cols-12" style="background-color:${state.bodyBg}">
            <div class="col-span-7 flex flex-col justify-center border-r-2 border-black">
              ${textBlockHTML}
            </div>
            <div class="col-span-5 flex items-center justify-center p-2">
              ${piktogramsHTML}
            </div>
          </div>
        `;
      } else {
        // Piktogramsız
        contentBox.innerHTML = `
          ${headerStripHTML}
          <div class="flex-1 flex flex-col justify-center p-6" style="background-color:${state.bodyBg}">
            ${textBlockHTML}
          </div>
        `;
      }
    }

    // -------------------------------------------------------------------------
    // DIŞA AKTARMA: VEKTÖREL SVG, YÜKSEK KALİTE PNG, JSON KAYIT/YÜKLEME, mehse KAYIT
    // -------------------------------------------------------------------------
    function exportSVG() {
      const board = document.getElementById("masterLiveSign");
      const clone = board.cloneNode(true);

      const w = board.offsetWidth;
      const h = board.offsetHeight;

      const serializer = new XMLSerializer();
      const htmlContent = serializer.serializeToString(clone);

      const svgDoc = `
        <svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}">
          <foreignObject width="100%" height="100%">
            <div xmlns="http://www.w3.org/1999/xhtml">
              <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Roboto+Condensed:wght@700&display=swap');
                * { box-sizing: border-box; }
              </style>
              ${htmlContent}
            </div>
          </foreignObject>
        </svg>
      `;

      const blob = new Blob([svgDoc], { type: "image/svg+xml;charset=utf-8" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `ISG_Levha_${state.signalTr}_${Date.now()}.svg`;
      link.click();
      URL.revokeObjectURL(url);
    }

    /** Tuvali yüksek çözünürlüklü PNG'ye (data URL) dönüştürür; indirme VEYA mehse'ye kaydetme için kullanılır. */
    function rasterizeToPNG(callback) {
      const board = document.getElementById("masterLiveSign");
      const w = board.offsetWidth;
      const h = board.offsetHeight;

      const scale = 2; // Yüksek çözünürlük
      const canvas = document.getElementById("rasterCanvas");
      canvas.width = w * scale;
      canvas.height = h * scale;
      const ctx = canvas.getContext("2d");
      ctx.scale(scale, scale);

      const clone = board.cloneNode(true);
      const serializer = new XMLSerializer();
      const htmlContent = serializer.serializeToString(clone);

      const svgDoc = `
        <svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}">
          <foreignObject width="100%" height="100%">
            <div xmlns="http://www.w3.org/1999/xhtml">
              ${htmlContent}
            </div>
          </foreignObject>
        </svg>
      `;

      const img = new Image();
      const svgBlob = new Blob([svgDoc], { type: "image/svg+xml;charset=utf-8" });
      const url = URL.createObjectURL(svgBlob);

      img.onload = () => {
        ctx.fillStyle = state.bodyBg || "#ffffff";
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0);
        URL.revokeObjectURL(url);
        callback(canvas.toDataURL("image/png"));
      };

      img.src = url;
    }

    function exportPNG() {
      rasterizeToPNG((pngUrl) => {
        const a = document.createElement("a");
        a.href = pngUrl;
        a.download = `ISG_Levha_${state.signalTr}_${Date.now()}.png`;
        a.click();
      });
    }

    /** 🤖 AI ile Çevir (02.11.2026): metin dış sayfaya gider (Gemini), çeviri postMessage ile döner. */
    function aiCevir() {
      const btn = document.getElementById("aiCevirBtn");
      btn.disabled = true;
      btn.textContent = "Çevriliyor…";
      parent.postMessage({
        tip: "cevir",
        baslik: document.getElementById("mainTitleInput").value,
        aciklama: document.getElementById("descTextInput").value,
        sinyal: state.signalTr || "",
      }, "*");
    }

    window.addEventListener("message", (e) => {
      if (e.source !== parent) { return; }
      const m = e.data || {};
      if (m.tip !== "ceviri") { return; }
      const btn = document.getElementById("aiCevirBtn");
      btn.disabled = false;
      if (m.hata) {
        btn.textContent = "⚠ " + m.hata;
        setTimeout(() => { btn.textContent = "🤖 AI ile Çevir"; }, 4000);
        return;
      }
      btn.textContent = "✓ Çevrildi";
      setTimeout(() => { btn.textContent = "🤖 AI ile Çevir"; }, 2500);
      if (m.baslik) { document.getElementById("enTitleInput").value = m.baslik; }
      if (m.aciklama) { document.getElementById("enDescInput").value = m.aciklama; }
      if (m.sinyal) { document.getElementById("signalEnInput").value = m.sinyal; }
      document.getElementById("showEnToggle").checked = true;
      updateSign();
    });

    /** mehse'ye kaydet: tasarım JSON'u + o anki görünümün PNG'si, dış sayfaya postMessage ile gönderilir. */
    function tabelayiKaydet() {
      const btn = document.getElementById("kaydetMehseBtn");
      const txt = document.getElementById("kaydetMehseBtnText");
      btn.disabled = true;
      txt.textContent = "Kaydediliyor...";

      rasterizeToPNG((pngUrl) => {
        parent.postMessage({ tip: "kaydet", baslik: state.baslik || "", veri: state, png: pngUrl }, "*");
        btn.disabled = false;
        txt.textContent = "mehse'ye Kaydet";
      });
    }

    // JSON Şablon Kaydet
    function saveJSONTemplate() {
      const templateData = JSON.stringify(state, null, 2);
      const blob = new Blob([templateData], { type: "application/json" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `ISG_Sablon_${state.signalTr}_${Date.now()}.json`;
      a.click();
      URL.revokeObjectURL(url);
    }

    // JSON Şablon Yükle
    function loadJSONTemplate(e) {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (evt) => {
        try {
          const loaded = JSON.parse(evt.target.result);
          Object.assign(state, loaded);

          syncFormFromState();
          updateSlotSelectorButtons();
          initPiktogramGrid();
          updateSign();
          alert("✓ İSG Tabela şablonu başarıyla yüklendi.");
        } catch (err) {
          alert("Geçersiz JSON şablon dosyası!");
        }
      };
      reader.readAsText(file);
    }
  </script>
</body>
</html>
