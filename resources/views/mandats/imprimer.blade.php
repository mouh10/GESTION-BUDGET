@php $e = $mandat->liquidation->engagement; $l = $e->ligneCredit; @endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Mandat {{ $mandat->numero }}</title>@vite(['resources/css/app.css'])</head>
<body class="bg-white">
<div class="mx-auto max-w-3xl p-10 text-sm">
    <div class="no-print mb-6 flex gap-2"><button onclick="window.print()" class="btn-primaire">Imprimer / PDF</button><a href="{{ route('mandats.show', $mandat) }}" class="btn-secondaire">Retour</a></div>
    <div class="flex justify-between">
        <div><p class="font-semibold uppercase">{{ config('gestion.entreprise.tutelle') }}</p><p>{{ config('gestion.entreprise.nom') }}</p><p class="text-slate-500">Ordonnateur : {{ $l->service->libelle }}</p></div>
        <div class="text-right"><p class="titre text-xl font-bold">MANDAT DE PAIEMENT</p><p class="font-medium">N° {{ $mandat->numero }}</p><p>du {{ date_fr($mandat->date) }}</p></div>
    </div>
    <table class="mt-8 w-full border border-slate-300">
        <tbody class="divide-y divide-slate-200">
            <tr><td class="w-56 bg-slate-50 p-2 font-medium">Imputation</td><td class="p-2">{{ $l->imputation() }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Programme / action</td><td class="p-2">{{ $l->action->programme->libelle }} › {{ $l->action->libelle }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Nature</td><td class="p-2">{{ $l->nature->intitule }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Engagement / liquidation</td><td class="p-2">{{ $e->numero }} / {{ $mandat->liquidation->numero }} — {{ $mandat->liquidation->reference_facture }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Créancier</td><td class="p-2">{{ $e->tiers->nom }}{{ $e->tiers->rib ? ' — RIB '.$e->tiers->rib : '' }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Objet</td><td class="p-2">{{ $e->objet }}</td></tr>
            <tr><td class="bg-slate-50 p-2 font-medium">Montant à payer</td><td class="p-2 text-base font-bold">{{ fcfa($mandat->montant) }}</td></tr>
        </tbody>
    </table>
    <div class="mt-12 grid grid-cols-2 gap-8 text-center">
        <div><p class="font-medium">L’ordonnateur</p><div class="mt-16 border-t border-slate-400 pt-1 text-xs text-slate-500">« Vu, bon à payer » — date et signature</div></div>
        <div><p class="font-medium">Le comptable public</p><div class="mt-16 border-t border-slate-400 pt-1 text-xs text-slate-500">Pris en charge le — « Vu, bon à payer »</div></div>
    </div>
</div>
</body>
</html>
