{{-- Tarayıcıdan Türkçe Sesli Dikte (Web Speech API) — $hedefModel Livewire özelliğine konuşulan metni ekler --}}
<span x-data="{
    dinliyor: false,
    destekli: ('webkitSpeechRecognition' in window) || ('SpeechRecognition' in window),
    rec: null,
    baslatDurdur() {
        if (!this.destekli) {
            alert('Tarayıcınız sesle yazdırma (Web Speech API) özelliğini desteklemiyor. Chrome veya Edge kullanabilirsiniz.');
            return;
        }
        if (this.dinliyor && this.rec) {
            this.rec.stop();
            this.dinliyor = false;
            return;
        }
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.rec = new SR();
        this.rec.lang = 'tr-TR';
        this.rec.continuous = false;
        this.rec.interimResults = false;
        this.rec.onstart = () => { this.dinliyor = true; };
        this.rec.onend = () => { this.dinliyor = false; };
        this.rec.onerror = () => { this.dinliyor = false; };
        this.rec.onresult = (e) => {
            const metin = Array.from(e.results).map(r => r[0].transcript).join(' ').trim();
            if (metin) {
                const mevcut = $wire.get('{{ $hedefModel }}') || '';
                $wire.set('{{ $hedefModel }}', (mevcut ? mevcut + ' ' : '') + metin);
            }
        };
        this.rec.start();
    }
}" style="display:inline-flex;align-items:center">
    <button type="button" x-on:click="baslatDurdur()"
        :style="dinliyor ? 'background:rgb(220 38 38 / .14);border-color:rgb(220 38 38);color:rgb(220 38 38)' : 'background:transparent;border-color:rgb(107 114 128 / .35);color:inherit'"
        style="font-size:.73rem;font-weight:600;padding:.2rem .55rem;border-radius:999px;border:1px solid rgb(107 114 128 / .35);cursor:pointer;display:inline-flex;align-items:center;gap:.25rem"
        title="Mikrofonla Türkçe dikte (Sesle Yaz)">
        <span x-text="dinliyor ? '🔴 Dinleniyor... (Durdur)' : '🎤 Sesle Yaz'">🎤 Sesle Yaz</span>
    </button>
</span>
