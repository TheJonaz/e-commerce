@props([
    'panelKey' => 'panel',
    'heading' => null,
])

@php
    $key = e($panelKey);
@endphp

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
    <header class="flex items-center justify-between gap-2 px-6 py-3" :class="state === 'collapsed' ? '' : 'border-b border-gray-200 dark:border-gray-800'" style="border-bottom-style: solid; border-bottom-width: 1px;" x-bind:style="state === 'collapsed' ? 'border-bottom: 0' : ''">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white" style="margin: 0">{{ $heading }}</h3>
        <div style="display: inline-flex; gap: 0.25rem">
            <button type="button" @click="toggleCollapse"
                title="Minimera"
                x-text="state === 'collapsed' ? '+' : '—'"
                style="width: 1.7rem; height: 1.7rem; line-height: 1; border-radius: 6px; border: 1px solid rgba(0,0,0,0.08); background: transparent; color: #64748b; cursor: pointer; font-weight: 700; font-size: 0.9rem;"
                onmouseover="this.style.background='rgba(0,0,0,0.04)'"
                onmouseout="this.style.background='transparent'"
            ></button>
            <button type="button" @click="close"
                title="Stäng"
                style="width: 1.7rem; height: 1.7rem; line-height: 1; border-radius: 6px; border: 1px solid rgba(0,0,0,0.08); background: transparent; color: #64748b; cursor: pointer; font-weight: 700; font-size: 1rem;"
                onmouseover="this.style.background='rgba(220,38,38,0.08)'; this.style.color='#dc2626'"
                onmouseout="this.style.background='transparent'; this.style.color='#64748b'"
            >×</button>
        </div>
    </header>
    <div x-show="state === 'open'" x-collapse class="px-6 py-4">
        {{ $slot }}
    </div>
</div>
