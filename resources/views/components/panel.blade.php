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
    <header
        style="display: flex; align-items: stretch; justify-content: space-between; padding: 0 0 0 1.5rem; min-height: 44px; border-bottom: 1px solid rgba(0,0,0,0.08);"
        x-bind:style="state === 'collapsed' ? 'display: flex; align-items: stretch; justify-content: space-between; padding: 0 0 0 1.5rem; min-height: 44px; border-bottom: 0' : ''"
    >
        <h3 class="text-base font-semibold text-gray-950 dark:text-white" style="margin: 0; padding: 0.5rem 0; align-self: center; flex: 1; min-width: 0;">{{ $heading }}</h3>
        <div style="display: flex; align-items: stretch; flex-shrink: 0;">
            <button type="button" @click="toggleCollapse"
                title="Minimera"
                x-text="state === 'collapsed' ? '☐' : '–'"
                style="width: 46px; line-height: 1; border: 0; border-radius: 0; background: transparent; color: #334155; cursor: pointer; font-weight: 400; font-size: 1rem; padding: 0; display: inline-flex; align-items: center; justify-content: center;"
                onmouseover="this.style.background='rgba(0,0,0,0.06)'"
                onmouseout="this.style.background='transparent'"
            ></button>
            <button type="button" @click="close"
                title="Stäng"
                style="width: 46px; line-height: 1; border: 0; border-radius: 0 0.75rem 0 0; background: transparent; color: #334155; cursor: pointer; font-weight: 400; font-size: 1.05rem; padding: 0; display: inline-flex; align-items: center; justify-content: center;"
                onmouseover="this.style.background='#e81123'; this.style.color='white'"
                onmouseout="this.style.background='transparent'; this.style.color='#334155'"
            >✕</button>
        </div>
    </header>
    <div x-show="state === 'open'" x-collapse class="px-6 py-4">
        {{ $slot }}
    </div>
</div>
