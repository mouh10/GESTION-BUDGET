@php $l = $engagement->ligneCredit; @endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Bon d’engagement {{ $engagement->numero }}</title>@vite(['resources/css/app.css'])</head>
<body class="bg-white">
<div class="mx-auto max-w-3xl p-10 text-sm">
    <div class="no-print mb-6 flex gap-2"><button onclick="window.print()" class="btn-primaire">Imprimer / PDF</button><a href="{{ route('engagements.show', $engagement) }}" class="btn-secondaire">Retour</a></div>
    <div class="flex justify-between">
        <div><p class="font-semibold uppercase">{{ config('gestion.entreprise.tutelle') }}</p><p>{{ config('gestion.entreprise.nom') }}</p><p class="text-slate-500">{{ $l->service->libelle }}</p></div>
        <div class="text-right"><p class="titre text-xl font-bold">BON D’ENGAGEMENT</p><p class="font-medium">N° {{ $engagement->numero }}</p><p>du {{ date_fr($engagement->date) }}</p></div>
    </div>
    <table class="mt-8 w-full border border-slate-300">
        <tbody class="divide-y divide-slate-200">
            <tr><td class="w-56 bg-slate-50 p-2 font-medium">Programme</td><td class="p-2">{{ $l->action->programme->intitule }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Action</td><td class="p-2">{{ $l->action->intitule }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Nature économique</td><td class="p-2">{{ $l->nature->intitule }} — Titre {{ $l->nature->titre }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Source de financement</td><td class="p-2">{{ \App\Models\LigneCredit::SOURCES[$l->source] }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Bénéficiaire</td><td class="p-2">{{ $engagement->tiers->nom }}{{ $engagement->tiers->ninea ? ' — NINEA '.$engagement->tiers->ninea : '' }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Objet</td><td class="p-2">{{ $engagement->objet }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Pièce</td><td class="p-2">{{ \App\Models\Engagement::TYPES[$engagement->type] }}{{ $engagement->marche ? ' — Marché '.$engagement->marche->numero : '' }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Montant</td><td class="p-2 text-base font-bold">{{ fcfa($engagement->montant) }}</td></tr>
        </tbody>
    </table>
    <div class="mt-12 grid grid-cols-2 gap-8 text-center">
        <div><p class="font-medium">L’ordonnateur</p><div class="mt-16 border-t border-slate-400 pt-1 text-xs text-slate-500">Date, cachet et signature</div></div>
        <div><p class="font-medium">Visa du contrôleur financier</p>
            @if ($engagement->statut === 'vise')<p class="mt-2 text-emerald-700">Visé le {{ $engagement->vise_le?->format('d/m/Y') }}{{ $engagement->viseur ? ' — '.$engagement->viseur->name : '' }}</p>@endif
            <div class="mt-12 border-t border-slate-400 pt-1 text-xs text-slate-500">Date, cachet et signature</div></div>
    </div>
</div>
</body>
</html>
