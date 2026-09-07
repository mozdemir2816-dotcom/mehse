<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 9px; }
    .sayfa { padding: 20px 24px; }
    /* Toplu çıktıda her JSA yeni sayfada başlar (tekli çıktıda tek .sayfa var, tetiklenmez). */
    .sayfa + .sayfa { page-break-before: always; }
    .ust { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .ust td { vertical-align: middle; }
    .ust .logo { width: 90px; }
    .ust .logo img { max-width: 88px; max-height: 48px; }
    h1 { font-size: 13px; margin: 0; text-align: center; }
    .kunye { text-align: center; font-size: 8.5px; color: #333; margin-top: 3px; }
    .kapsam { font-size: 8px; color: #444; margin: 4px 0 8px; text-align: justify; }
    table.jsa { width: 100%; border-collapse: collapse; }
    table.jsa th, table.jsa td { border: 1px solid #888; padding: 3px 4px; vertical-align: top; text-align: left; }
    table.jsa th { background: #eee; font-size: 8.5px; }
    table.jsa td { font-size: 8px; }
    .no { width: 22px; text-align: center; }
    .risk { text-align: center; white-space: nowrap; }
    .rozet { display: inline-block; color: #fff; padding: 1px 5px; border-radius: 3px; font-size: 7.5px; }
    .r-kritik { background: #7f1d1d; }
    .r-yuksek { background: #dc2626; }
    .r-orta { background: #f59e0b; }
    .r-dusuk { background: #16a34a; }
    .r-none { background: #9ca3af; }
    .cok-satir { white-space: pre-line; }
    h2 { font-size: 10px; margin: 14px 0 5px; border-bottom: 1px solid #111; padding-bottom: 2px; }
    ol.notlar { margin: 0; padding-left: 16px; }
    ol.notlar li, ul.notlar li { font-size: 8px; margin-bottom: 2px; }
    ul.notlar { margin: 0; padding-left: 14px; list-style: none; }
    table.imza { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.imza th, table.imza td { border: 1px solid #888; padding: 5px 6px; font-size: 8px; }
    table.imza th { background: #eee; }
    table.imza td { height: 26px; }
    table.imza td.kase { height: 42px; text-align: center; vertical-align: middle; }
</style>
