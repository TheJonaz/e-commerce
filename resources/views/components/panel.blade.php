@props([
    'panelKey' => 'panel',
    'heading' => null,
])

@php
    $key = e($panelKey);
@endphp

@once
    <style>
        .ec-panel-header {
            display: flex !important;
            align-items: stretch;
            justify-content: space-between;
            padding: 0 0 0 1.5rem;
            min-height: 44px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        .ec-panel-header[data-collapsed="true"] { border-bottom: 0; }
        .ec-panel-title { margin: 0; padding: 0.5rem 0; align-self: center; flex: 1; min-width: 0; }
        .ec-panel-actions { display: flex; align-items: stretch; flex-shrink: 0; margin-left: auto; }
        .ec-panel-btn {
            width: 46px;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #334155;
            cursor: pointer;
            font: inherit;
            font-weight: 400;
            font-size: 1rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .ec-panel-btn:hover { background: rgba(0,0,0,0.06); }
        .ec-panel-btn-close { font-size: 1.05rem; border-top-right-radius: 0.75rem; }
        .ec-panel-btn-close:hover { background: #e81123; color: #fff; }
        .dark .ec-panel-btn { color: #cbd5e1; }
        .dark .ec-panel-btn:hover { background: rgba(255,255,255,0.06); }
        .dark .ec-panel-btn-close:hover { background: #e81123; color: #fff; }
    </style>
@endonce

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
    x-show="state !== 'closed'"
    x-cloak
    class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
    style="margin-bottom: 1.5rem"
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
    <div x-show="state === 'open'" x-collapse class="px-6 py-4">
        {{ $slot }}
    </div>
</div>
