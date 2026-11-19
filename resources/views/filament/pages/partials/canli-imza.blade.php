@props([
    'baslik' => '📱 Ekrandan Canlı İmza Al (Telefon / Tablet / Fare)',
    'aciklama' => 'Sahada kağıt çıktı almadan çalışana doğrudan telefon veya tablet ekranından parmakla imzalatın; imza PDF tutanağına otomatik basılır.',
])

<div x-data="{
    acik: @js(filled($canliImza ?? null)),
    ciziyor: false,
    dolu: @js(filled($canliImza ?? null)),
    ctx: null,
    init() {
        this.$nextTick(() => this.hazirla());
    },
    hazirla() {
        const c = this.$refs.tuval;
        if (!c) return;
        this.ctx = c.getContext('2d');
        this.ctx.strokeStyle = '#0f172a';
        this.ctx.lineWidth = 2.4;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
    },
    konum(e) {
        const c = this.$refs.tuval;
        const r = c.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return {
            x: (t.clientX - r.left) * (c.width / r.width),
            y: (t.clientY - r.top) * (c.height / r.height)
        };
    },
    basla(e) {
        if (!this.ctx) this.hazirla();
        this.ciziyor = true;
        const p = this.konum(e);
        this.ctx.beginPath();
        this.ctx.moveTo(p.x, p.y);
    },
    ciz(e) {
        if (!this.ciziyor) return;
        const p = this.konum(e);
        this.ctx.lineTo(p.x, p.y);
        this.ctx.stroke();
        this.dolu = true;
    },
    bitir() {
        if (!this.ciziyor) return;
        this.ciziyor = false;
        if (this.dolu && this.$refs.tuval) {
            $wire.set('canliImza', this.$refs.tuval.toDataURL('image/png'));
        }
    },
    temizle() {
        const c = this.$refs.tuval;
        if (c && this.ctx) {
            this.ctx.clearRect(0, 0, c.width, c.height);
        }
        this.dolu = false;
        $wire.set('canliImza', null);
    }
}" style="border:1px dashed rgb(107 114 128 / .4);border-radius:.75rem;padding:.85rem 1rem;margin-top:.75rem;background:rgb(15 23 42 / .02)">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap">
        <div>
            <div style="font-weight:700;font-size:.84rem;display:flex;align-items:center;gap:.45rem">
                <span>{{ $baslik }}</span>
                <template x-if="dolu">
                    <span style="font-size:.72rem;padding:.15rem .5rem;border-radius:999px;background:rgb(16 185 129 / .15);color:#047857;font-weight:700">
                        ✓ İmza Alındı
                    </span>
                </template>
            </div>
            <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.15rem">{{ $aciklama }}</div>
        </div>
        <x-filament::button size="xs" color="gray" type="button" x-on:click="acik = !acik; if (acik) $nextTick(() => hazirla())">
            <span x-text="acik ? 'İmza Alanını Gizle' : '✍️ İmza Ekranını Aç'"></span>
        </x-filament::button>
    </div>

    <div x-show="acik" x-cloak style="margin-top:.75rem">
        <div style="background:#fff;border:1.5px solid #cbd5e1;border-radius:.6rem;padding:.4rem;max-width:460px">
            <canvas x-ref="tuval" width="440" height="150"
                style="width:100%;height:150px;display:block;touch-action:none;cursor:crosshair"
                x-on:mousedown="basla($event)"
                x-on:mousemove="ciz($event)"
                x-on:mouseup="bitir()"
                x-on:mouseleave="bitir()"
                x-on:touchstart.prevent="basla($event)"
                x-on:touchmove.prevent="ciz($event)"
                x-on:touchend.prevent="bitir()"></canvas>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;margin-top:.5rem">
            <x-filament::button size="xs" color="danger" type="button" x-on:click="temizle()">
                İmzayı Temizle
            </x-filament::button>
            <span style="font-size:.73rem;color:rgb(107 114 128)">
                Parmağınızla veya fareyle beyaz kutunun içine imzalayın.
            </span>
        </div>
    </div>
</div>
