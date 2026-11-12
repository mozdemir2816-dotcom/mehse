<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#1f2937">
<style>
    :root { --zemin:#f3f4f6; --kart:#fff; --yazi:#111827; --soluk:#4b5563; --cizgi:#d1d5db; --ana:#1f2937;
        --ok:#15803d; --ok-z:#dcfce7; --red:#b91c1c; --red-z:#fee2e2; --sari:#a16207; --sari-z:#fef9c3; --gri-z:#e5e7eb; }
    @media (prefers-color-scheme: dark) { :root { --zemin:#0b0f17; --kart:#161b26; --yazi:#f3f4f6; --soluk:#a1a7b3; --cizgi:#2d3443; --ana:#e5e7eb;
        --ok:#4ade80; --ok-z:#052e16; --red:#f87171; --red-z:#450a0a; --sari:#facc15; --sari-z:#422006; --gri-z:#262c38; } }
    * { box-sizing:border-box }
    html { -webkit-text-size-adjust:100% }
    body { margin:0; background:var(--zemin); color:var(--yazi); font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
        padding:0 0 calc(96px + env(safe-area-inset-bottom)) }
    .sar { max-width:640px; margin:0 auto; padding:16px }
    .bas { background:var(--kart); border-bottom:1px solid var(--cizgi); padding:16px; padding-top:calc(16px + env(safe-area-inset-top)) }
    .bas .firma { font-size:.85rem; color:var(--soluk); font-weight:600 }
    .bas h1 { margin:2px 0 6px; font-size:1.35rem; line-height:1.25 }
    .kunye { display:flex; flex-wrap:wrap; gap:4px 14px; font-size:.85rem; color:var(--soluk) }
    .kunye b { color:var(--yazi); font-weight:600 }
    .serit { border-radius:12px; padding:14px 16px; margin:16px 0 0; font-weight:700; display:flex; gap:10px; align-items:flex-start }
    .serit small { display:block; font-weight:500; margin-top:2px }
    .serit.red { background:var(--red-z); color:var(--red) } .serit.ok { background:var(--ok-z); color:var(--ok) } .serit.sari { background:var(--sari-z); color:var(--sari) }
    .sekme { display:flex; gap:8px; position:sticky; top:0; z-index:5; background:var(--zemin); padding:12px 0 8px }
    .sekme a { flex:1; text-align:center; text-decoration:none; color:var(--yazi); background:var(--kart); border:1px solid var(--cizgi);
        border-radius:999px; padding:12px; font-weight:700; min-height:48px }
    h2 { font-size:1.1rem; margin:24px 0 10px }
    .kart { background:var(--kart); border:1px solid var(--cizgi); border-radius:14px; padding:14px 16px; margin-bottom:10px }
    .soru { font-weight:600; margin:0 0 10px }
    .soru .no { color:var(--soluk); font-weight:700; margin-right:4px }
    .secim { display:grid; grid-template-columns:repeat(3,1fr); gap:8px }
    .secim input { position:absolute; opacity:0; pointer-events:none }
    .secim label { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; min-height:56px; border-radius:12px;
        border:2px solid var(--cizgi); font-weight:700; font-size:.9rem; cursor:pointer; user-select:none; text-align:center; padding:4px }
    .secim label .i { font-size:1.25rem; line-height:1 }
    .secim input:focus-visible + label { outline:3px solid var(--ana); outline-offset:2px }
    .secim input[value=U]:checked + label { background:var(--ok); border-color:var(--ok); color:#fff }
    .secim input[value=UD]:checked + label { background:var(--red); border-color:var(--red); color:#fff }
    .secim input[value=Y]:checked + label { background:var(--soluk); border-color:var(--soluk); color:#fff }
    @media (prefers-color-scheme: dark) { .secim input:checked + label { color:#0b0f17 !important } }
    .not-alani { display:none; margin-top:10px }
    .kart:has(input[value=UD]:checked) { border-color:var(--red); box-shadow:0 0 0 1px var(--red) }
    .kart:has(input[value=UD]:checked) .not-alani { display:block }
    input[type=text], input[type=password], input[inputmode], textarea { width:100%; font:inherit; font-size:16px; color:var(--yazi); background:var(--kart);
        border:2px solid var(--cizgi); border-radius:12px; padding:12px 14px; min-height:52px }
    input:focus, textarea:focus { outline:none; border-color:var(--ana) }
    label.etiket { display:block; font-weight:700; margin:0 0 6px }
    .hata { background:var(--red-z); color:var(--red); border-radius:12px; padding:12px 14px; font-weight:600; margin:16px 0 0 }
    .alt { position:fixed; left:0; right:0; bottom:0; background:var(--kart); border-top:1px solid var(--cizgi);
        padding:12px 16px calc(12px + env(safe-area-inset-bottom)); z-index:10 }
    .alt .ic { max-width:640px; margin:0 auto; display:flex; gap:10px; align-items:center }
    .alt .sayac { font-size:.85rem; color:var(--soluk); min-width:70px; font-weight:600 }
    .dugme { flex:1; display:inline-flex; align-items:center; justify-content:center; min-height:56px; border:0; border-radius:14px;
        background:var(--ana); color:var(--kart); font:inherit; font-weight:800; font-size:1.05rem; cursor:pointer; text-decoration:none; padding:0 18px }
    .dugme.ikincil { background:var(--gri-z); color:var(--yazi) }
    .talimat h3 { font-size:1rem; margin:18px 0 6px }
    .talimat ol, .talimat ul { margin:0; padding-left:1.3rem } .talimat li { margin:4px 0 }
    .talimat p { margin:0 0 6px; color:var(--soluk) }
    .soluk { color:var(--soluk); font-size:.85rem }
    @media (prefers-reduced-motion: no-preference) { html { scroll-behavior:smooth } }
</style>
