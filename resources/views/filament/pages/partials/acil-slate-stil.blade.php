{{-- Slate/beyaz kart görünümü (Acil Durum & Yangın + Eğitim Yönetim Merkezi): işaretçi + :has ile yalnız bu sayfalarda geçerli. --}}
<div class="acil-merkez" hidden></div>
<style>
    html:not(.dark) .fi-page:has(.acil-merkez) .fi-page-content {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1rem;
    }
    html:not(.dark) .fi-page:has(.acil-merkez) .fi-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: none;
        --tw-ring-shadow: 0 0 #0000;
    }
    html:not(.dark) .fi-page:has(.acil-merkez) .fi-section-header-heading { color: #0f172a; }
    .acil-merkez-secenek { border-radius: .75rem !important; transition: background .15s, border-color .15s; }
    .acil-merkez-secenek:hover { border-color: #94a3b8 !important; }
</style>
