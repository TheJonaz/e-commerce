<x-filament-panels::page>
    {{ $this->form }}

    {{-- Dashboard panels (client-side, localStorage-based) --}}
    @php
        $knownPanels = [
            'visitors-overview' => 'Besöksstatistik',
            'sales-overview' => 'Säljstatistik',
        ];
    @endphp

    <div
        x-data="{
            panels: @js($knownPanels),
            states: {},
            init() {
                this.refresh();
                window.addEventListener('panel-state-changed', () => this.refresh());
            },
            refresh() {
                const next = {};
                for (const key in this.panels) {
                    next[key] = localStorage.getItem('panel:' + key) || 'open';
                }
                this.states = next;
            },
            setState(key, state) {
                localStorage.setItem('panel:' + key, state);
                window.dispatchEvent(new CustomEvent('panel-state-changed', { detail: { key, state } }));
                this.refresh();
            },
            label(s) {
                return s === 'closed' ? 'Stängd' : (s === 'collapsed' ? 'Minimerad' : 'Öppen');
            },
            color(s) {
                return s === 'closed' ? '#dc2626' : (s === 'collapsed' ? '#d97706' : '#15803d');
            },
        }"
        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        style="margin-top: 1.5rem;"
    >
        <header style="padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.08);">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white" style="margin: 0">Panels</h3>
            <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.15rem">Återställ minimerade eller stängda dashboard-paneler.</p>
        </header>
        <div style="padding: 1rem 1.5rem;">
            <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">
                        <th style="padding: 0.4rem 0.5rem 0.4rem 0;">Panel</th>
                        <th style="padding: 0.4rem 0.5rem;">Status</th>
                        <th style="padding: 0.4rem 0.5rem; text-align: right;">Åtgärd</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(name, key) in panels" :key="key">
                        <tr style="border-top: 1px solid rgba(0,0,0,0.05);">
                            <td style="padding: 0.6rem 0.5rem 0.6rem 0;" x-text="name"></td>
                            <td style="padding: 0.6rem 0.5rem;">
                                <span x-text="label(states[key])" :style="'color: ' + color(states[key]) + '; font-weight: 500;'"></span>
                            </td>
                            <td style="padding: 0.6rem 0.5rem; text-align: right;">
                                <button type="button" x-show="states[key] !== 'open'"
                                    @click="setState(key, 'open')"
                                    style="padding: 0.35rem 0.75rem; border-radius: 6px; border: 1px solid #4f46e5; background: #4f46e5; color: white; font: inherit; font-size: 0.8125rem; font-weight: 500; cursor: pointer;"
                                >Öppna igen</button>
                                <button type="button" x-show="states[key] === 'open'"
                                    @click="setState(key, 'closed')"
                                    style="padding: 0.35rem 0.75rem; border-radius: 6px; border: 1px solid rgba(0,0,0,0.1); background: transparent; color: #64748b; font: inherit; font-size: 0.8125rem; cursor: pointer;"
                                >Stäng</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
