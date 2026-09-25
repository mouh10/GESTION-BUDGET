@props(['nom', 'label', 'type' => 'text', 'valeur' => null, 'aide' => null, 'requis' => false])
@php $id = str_replace(['[', ']'], ['_', ''], $nom); @endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="etiquette">{{ $label }}@if ($requis)<span class="text-red-600"> *</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $nom }}" rows="3" class="champ" {{ $attributes->except('class') }}>{{ old($nom, $valeur) }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $nom }}" value="{{ old($nom, $valeur) }}" class="champ" @required($requis) {{ $attributes->except('class') }}>
    @endif
    @if ($aide)
        <p class="aide">{{ $aide }}</p>
    @endif
    @error($nom)
        <p class="erreur-champ">{{ $message }}</p>
    @enderror
</div>
