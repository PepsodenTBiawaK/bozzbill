<x-layouts.app>
    <div class="max-w-4xl mx-auto space-y-5">
        <div>
            <a href="{{ route('home') }}" class="inline-flex items-center min-h-11 text-xs font-semibold text-[#dea538] hover:underline">&larr; Kembali ke Home</a>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold text-white">Lencana Prestasi</h1>
                <span class="shrink-0 text-xs font-semibold text-[#dea538] bg-[#dea538]/10 border border-[#dea538]/20 rounded-full px-3 py-1.5">{{ $unlockedCount }}/{{ $achievements->count() }} Terbuka</span>
            </div>
            <p class="text-sm text-[#959595] mt-2">Lencana terbuka otomatis saat target tercapai. Progres mengikuti match, podium, dan latihan yang tercatat.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($achievements as $achievement)
                <x-achievement-card :achievement="$achievement" />
            @endforeach
        </div>
    </div>
</x-layouts.app>
