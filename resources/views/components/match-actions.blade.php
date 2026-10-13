@props(['event', 'match'])

@if (auth()->user()?->isAdmin() && $event->isOpen())
    <div x-data="{ confirming: false }" class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ route('fun-games.matches.edit', [$event, $match]) }}" aria-label="Edit match {{ $match->sideName(1) }} melawan {{ $match->sideName(2) }}" class="min-h-11 inline-flex items-center px-3 rounded-xl border border-[#333741] text-xs font-semibold text-[#dea538] hover:bg-[#252830]">Edit Match</a>
        <button type="button" x-on:click="confirming = !confirming" :aria-expanded="confirming" class="min-h-11 px-3 rounded-xl text-xs font-semibold text-rose-300 hover:bg-rose-500/10 cursor-pointer">Hapus</button>
        <form method="POST" action="{{ route('fun-games.matches.destroy', [$event, $match]) }}" x-show="confirming" x-cloak class="w-full flex flex-wrap items-center justify-end gap-2 text-xs">
            @csrf
            @method('DELETE')
            <span class="text-[#959595]">Hapus match ini? Statistik akan disesuaikan.</span>
            <button type="button" x-on:click="confirming = false" class="min-h-11 px-3 text-[#d4d4d4] cursor-pointer">Batal</button>
            <button type="submit" class="min-h-11 px-3 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-300 font-semibold cursor-pointer">Ya, Hapus Match</button>
        </form>
    </div>
@endif
