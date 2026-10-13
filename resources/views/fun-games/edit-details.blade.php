<section x-data="{ editing: @js($errors->has('title') || $errors->has('notes')) }" class="rounded-2xl border border-[#333741] bg-[#202227] p-4 sm:p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold text-white">Detail Fun Game</h2>
            <p class="mt-1 text-xs text-[#959595]">Judul dan deskripsi bisa diperbaiki, termasuk pada histori event.</p>
        </div>
        <button type="button" x-on:click="editing = !editing" :aria-expanded="editing" aria-controls="edit-event-details" class="min-h-11 rounded-xl border border-[#383d4a] px-4 text-xs font-semibold text-[#dea538] hover:bg-[#dea538]/5 cursor-pointer">Edit Judul &amp; Deskripsi</button>
    </div>
    <form id="edit-event-details" method="POST" action="{{ route('fun-games.update', $funGame) }}" x-show="editing" x-cloak class="mt-4 border-t border-[#333741] pt-4 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label for="edit-event-title" class="mb-1.5 block text-xs font-semibold text-[#d4d4d4]">Judul Fun Game</label>
            <input id="edit-event-title" name="title" type="text" required maxlength="150" value="{{ old('title', $funGame->title) }}" class="min-h-11 w-full rounded-xl border border-[#333741] bg-[#16171a] px-3 py-2.5 text-sm text-white outline-none focus:border-[#dea538]" />
        </div>
        <div>
            <label for="edit-event-notes" class="mb-1.5 block text-xs font-semibold text-[#d4d4d4]">Deskripsi (opsional)</label>
            <textarea id="edit-event-notes" name="notes" rows="3" class="w-full rounded-xl border border-[#333741] bg-[#16171a] px-3 py-2.5 text-sm text-white outline-none focus:border-[#dea538]">{{ old('notes', $funGame->notes) }}</textarea>
        </div>
        <div class="flex items-center gap-3">
            <button type="submit" class="min-h-11 rounded-xl bg-[#dea538] px-4 text-xs font-bold text-[#141518] hover:bg-[#c8912c] cursor-pointer">Simpan Perubahan</button>
            <button type="button" x-on:click="editing = false" class="min-h-11 px-3 text-xs font-semibold text-[#d4d4d4] cursor-pointer">Batal</button>
        </div>
    </form>
</section>
