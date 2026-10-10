@props(['player'])

<div {{ $attributes->merge(['class' => 'flex items-center justify-center shrink-0 overflow-hidden']) }}>
    @if ($player->avatar)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($player->avatar) }}" alt="Foto profil {{ $player->name }}" class="w-full h-full object-cover" />
    @else
        {{ $player->initials }}
    @endif
</div>
