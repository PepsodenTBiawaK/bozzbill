@props(['players', 'name', 'label', 'value' => '', 'required' => false, 'exclude' => '[]', 'allowed' => 'null', 'filter' => false, 'query' => ''])
@php
    $roster = $players->map(fn ($player) => ['id' => (string) $player->id, 'name' => $player->name, 'handicap' => $player->handicap->value])->values()->all();
    $fieldId = 'picker-'.$name;
@endphp
<div {{ $attributes->merge(['class' => 'relative space-y-1.5']) }}
    x-data="playerPicker(@js($roster), @js((string) $value))" x-modelable="selectedId"
    x-init="@if($filter) query = @js($query); @endif"
    x-effect="setRestrictions({{ $exclude }}, {{ $allowed }})"
    x-on:click.outside="open = false">
    <label for="{{ $fieldId }}" class="block text-xs font-semibold text-[#d4d4d4]">{{ $label }}</label>
    @unless($filter)
        <input type="hidden" name="{{ $name }}" :value="selectedId" value="{{ $value }}" />
    @endunless
    <div class="relative">
        <input id="{{ $fieldId }}" x-ref="search" type="text" @if($filter) name="{{ $name }}" @endif x-model="query"
            x-effect="$el.setCustomValidity({{ $filter ? 'false' : 'true' }} && query && !selectedId ? 'Pilih pemain dari hasil pencarian.' : '')"
            x-on:input="search()" x-on:focus="open = true" x-on:keydown.down.prevent="move(1); $nextTick(() => $refs.list.querySelectorAll('[role=option]')[active]?.scrollIntoView({ block: 'nearest' }))"
            x-on:keydown.up.prevent="move(-1); $nextTick(() => $refs.list.querySelectorAll('[role=option]')[active]?.scrollIntoView({ block: 'nearest' }))"
            x-on:keydown.enter="if (open && options().length) { $event.preventDefault(); chooseActive(); }"
            x-on:keydown.escape.prevent="open = false" x-on:keydown.tab="open = false"
            role="combobox" aria-autocomplete="list" aria-controls="{{ $fieldId }}-options" :aria-expanded="open"
            :aria-activedescendant="open && active >= 0 ? '{{ $fieldId }}-option-' + options()[active]?.id : null"
            autocomplete="off" placeholder="Ketik nama atau HC pemain" @required($required)
            class="w-full min-h-11 bg-[#151619] border border-[#333741] text-white text-sm rounded-xl pl-3 pr-10 py-2.5 focus:outline-none focus:border-[#dea538]" />
        @unless($required)
            <button type="button" x-show="query" x-on:click="clear(); $refs.search.focus()" aria-label="Kosongkan {{ $label }}" class="absolute right-0 top-0 min-h-11 w-10 text-[#959595] hover:text-white cursor-pointer">&times;</button>
        @endunless
    </div>
    <div id="{{ $fieldId }}-options" x-ref="list" x-show="open" x-cloak role="listbox" aria-label="Hasil pencarian {{ $label }}"
        class="absolute z-30 top-full left-0 right-0 max-h-60 overflow-y-auto rounded-xl border border-[#383d4a] bg-[#202227] shadow-xl p-1">
        <template x-for="(player, index) in options()" :key="player.id">
            <button type="button" role="option" :id="'{{ $fieldId }}-option-' + player.id" :aria-selected="player.id === selectedId" tabindex="-1"
                x-on:mousedown.prevent x-on:click="choose(player)" :class="{ 'bg-[#dea538]/10': index === active }"
                class="w-full min-h-11 px-3 py-2 rounded-lg flex items-center justify-between gap-3 text-left text-sm text-white hover:bg-[#dea538]/10 cursor-pointer">
                <span class="min-w-0 break-words" x-text="player.name"></span>
                <span class="shrink-0 text-xs font-semibold text-[#dea538]" x-text="'HC ' + player.handicap"></span>
            </button>
        </template>
        <p x-show="options().length === 0" class="px-3 py-3 text-xs text-[#959595]">Nama pemain tidak ditemukan.</p>
    </div>
</div>
