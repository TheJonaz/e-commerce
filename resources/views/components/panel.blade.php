@props([
    'panelKey' => 'panel',
    'heading' => null,
])

@php
    $key = e($panelKey);
@endphp

<style>
    .ec-panel { margin-bottom: 1.5rem; }
    .ec-panel-header {
        display: flex !important;
        align-items: stretch !important;
        justify-content: space-between !important;
        padding: 0 0 0 1.5rem !important;
        min-height: 44px !important;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }
    .ec-panel-header[data-collapsed="true"] { border-bottom: 0; }
    .ec-panel-title { margin: 0 !important; padding: 0.5rem 0 !important; align-self: center !important; flex: 1 1 auto !important; min-width: 0 !important; }
    .ec-panel-actions { display: flex !important; align-items: stretch !important; flex-shrink: 0 !important; margin-left: auto !important; }
    .ec-panel-btn {
        width: 46px !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent;
        color: #334155;
        cursor: pointer;
        font: inherit;
        font-weight: 400;
        font-size: 1rem;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        line-height: 1;
    }
    .ec-panel-btn:hover { background: rgba(0,0,0,0.06); }
    .ec-panel-btn-close { font-size: 1.05rem; border-top-right-radius: 0.75rem !important; }
    .ec-panel-btn-close:hover { background: #e81123; color: #fff; }
    .dark .ec-panel-btn { color: #cbd5e1; }
    .dark .ec-panel-btn:hover { background: rgba(255,255,255,0.06); }
    .dark .ec-panel-btn-close:hover { background: #e81123; color: #fff; }
    .ec-panel-body { padding: 1.25rem 1.5rem !important; }
    .ec-panel-closed { display: none !important; }
</style>

<div
    x-data="{
        state: localStorage.getItem('panel:{{ $key }}') || 'open',
        toggleCollapse() {
            this.state = this.state === 'collapsed' ? 'open' : 'collapsed';
            localStorage.setItem('panel:{{ $key }}', this.state);
            window.dispatchEvent(new CustomEvent('panel-state-changed', { detail: { key: '{{ $key }}', state: this.state } }));
        },
        close() {
            this.state = 'closed';
            localStorage.setItem('panel:{{ $key }}', this.state);
            window.dispatchEvent(new CustomEvent('panel-state-changed', { detail: { key: '{{ $key }}', state: this.state } }));
        }
    }"
    x-init="window.addEventListener('panel-state-changed', (e) => {
        if (e.detail.key === '{{ $key }}') state = e.detail.state;
    })"
    x-cloak
    :class="state === 'closed' ? 'ec-panel-closed' : ''"
    class="fi-section ec-panel rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
>
    <header
        :data-collapsed="state === 'collapsed'"
        class="ec-panel-header"
    >
        <h3 class="text-base font-semibold text-gray-950 dark:text-white ec-panel-title">{{ $heading }}</h3>
        <div class="ec-panel-actions">
            <button type="button" @click="toggleCollapse" title="Minimera"
                x-text="state === 'collapsed' ? '☐' : '–'"
                class="ec-panel-btn"></button>
            <button type="button" @click="close" title="Stäng"
                class="ec-panel-btn ec-panel-btn-close">✕</button>
        </div>
    </header>
    <div x-show="state === 'open'" class="ec-panel-body">
        {{ $slot }}
    </div>
</div>
