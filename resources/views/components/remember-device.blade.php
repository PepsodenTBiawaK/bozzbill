@props(['id'])

<div {{ $attributes->merge(['class' => 'space-y-1.5 pt-0.5']) }}>
    <input type="hidden" name="remember" value="0" />
    <label for="{{ $id }}" class="flex items-center gap-2 cursor-pointer select-none">
        <input
            type="checkbox"
            id="{{ $id }}"
            name="remember"
            value="1"
            aria-describedby="{{ $id }}_help"
            class="w-4 h-4 rounded bg-[#151619] border-[#333741] text-[#dea538] focus:ring-[#dea538] focus:ring-offset-0"
            @checked(old('remember', '1') === '1')
        />
        <span class="text-xs text-[#a0a5b1]">Ingat saya di perangkat ini</span>
    </label>
    <p id="{{ $id }}_help" class="text-[11px] text-[#787e8c]">Masuk otomatis hingga 30 hari di browser ini. Untuk perangkat pribadi.</p>
</div>
