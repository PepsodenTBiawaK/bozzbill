<section aria-labelledby="correction-confirmation-heading" class="rounded-2xl border border-[#dea538]/25 bg-[#dea538]/5 p-4 sm:p-5 space-y-4">
    <div class="space-y-1.5">
        <h2 id="correction-confirmation-heading" class="text-sm font-bold text-[#dea538]">Konfirmasi Koreksi Histori</h2>
        <p id="correction-impact" class="text-xs leading-relaxed text-[#d4d4d4]">Event tetap selesai. Koreksi ini mengubah catatan pertandingan dan statistik terkait. Alasan serta akun yang mengubah akan dicatat.</p>
        <p class="text-xs leading-relaxed text-[#959595]">Jika pemenang match berubah, juara/podium event tetap mengikuti penobatan yang sudah tercatat.</p>
    </div>
    <input type="hidden" name="match_version" value="{{ old('match_version', $match->correctionVersion()) }}" />
    <div>
        <label for="correction-reason" class="block text-xs font-semibold text-[#d4d4d4] mb-1.5">Alasan koreksi</label>
        <textarea id="correction-reason" name="correction_reason" rows="3" maxlength="500" required placeholder="Contoh: Skor Fadil seharusnya 3, sebelumnya tercatat 2." aria-invalid="{{ $errors->has('correction_reason') ? 'true' : 'false' }}" class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none">{{ old('correction_reason') }}</textarea>
    </div>
    <div>
        <label for="correction-password" class="block text-xs font-semibold text-[#d4d4d4] mb-1.5">Password Superadmin Anda</label>
        <input id="correction-password" name="current_password" type="password" autocomplete="current-password" required aria-invalid="{{ $errors->has('current_password') ? 'true' : 'false' }}" aria-describedby="correction-password-help" class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none" />
        <p id="correction-password-help" class="mt-1.5 text-xs text-[#959595]">Masukkan password akun Anda setiap kali menyimpan koreksi.</p>
    </div>
    <label class="min-h-11 flex items-start gap-2.5 cursor-pointer">
        <input name="correction_confirmed" type="checkbox" value="1" required aria-describedby="correction-impact" class="mt-0.5 size-4 shrink-0 accent-[#dea538] cursor-pointer" />
        <span class="text-xs leading-relaxed text-[#d4d4d4]">Saya sudah memeriksa perubahan ini dan yakin ingin mengoreksi histori pertandingan.</span>
    </label>
</section>
