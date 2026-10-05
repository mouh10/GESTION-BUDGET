@props([
    'titre',                 // ex. « Bon d'engagement »
    'numero' => null,
    'date' => null,
    'service' => null,       // service émetteur
    'tampon' => null,        // [libellé, couleur] : vert, rouge, orange, gris, bleu
    'reference' => null,     // ligne d'identification en pied de page
    'gestion' => null,       // année de gestion du document
])
@php
    $e = config('gestion.entreprise');
    $exercice = \App\Models\Exercice::courant();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titre }}{{ $numero ? ' '.$numero : '' }}</title>
    @vite(['resources/css/app.css'])
    <style>
        :root { --encre: #1b2f4e; --accent: #2f6fde; --trait: #cfd8e3; --doux: #f3f6fa; --gris: #5b6b80; }
        @page { size: A4 portrait; margin: 0; }
        html { font-size: 12px; }
        body { background: #e9edf2; color: #1f2937; margin: 0; }
        .barre-outils { position: sticky; top: 0; z-index: 10; display: flex; justify-content: center; gap: .5rem; padding: .75rem; background: #1b2f4e; }
        .barre-outils button, .barre-outils a { font: inherit; font-weight: 600; font-size: .95rem; border-radius: .6rem; padding: .5rem 1rem; cursor: pointer; border: 0; text-decoration: none; }
        .barre-outils .principal { background: #fff; color: var(--encre); }
        .barre-outils .secondaire { background: rgb(255 255 255 / .12); color: #fff; }
        .feuille { position: relative; box-sizing: border-box; width: 210mm; min-height: 297mm; margin: 1.5rem auto; padding: 12mm 15mm 20mm; background: #fff; box-shadow: 0 6px 30px rgb(15 23 42 / .15); overflow: hidden; }
        .feuille::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 5px; background: linear-gradient(90deg, #00853f 0 33.3%, #fdef42 33.3% 66.6%, #e31b23 66.6%); }

        .doc-entete { display: grid; grid-template-columns: minmax(64mm, auto) 1fr auto; gap: 1rem; align-items: center; padding-bottom: .8rem; border-bottom: 2px solid var(--encre); }
        .etat { text-align: center; max-width: 88mm; color: var(--encre); }
        .etat .pays { font-weight: 800; letter-spacing: .08em; text-transform: uppercase; font-size: 1.05rem; }
        .etat .devise { font-style: italic; font-size: .85rem; color: var(--gris); margin-top: 1px; }
        .etat .filet { width: 38px; height: 2px; background: var(--encre); margin: .45rem auto; }
        .etat .structure { font-weight: 700; text-transform: uppercase; font-size: .9rem; }
        .etat .service { font-size: .85rem; color: var(--gris); margin-top: 2px; }
        .cartouche { min-width: 70mm; border: 1.5px solid var(--encre); border-radius: 6px; overflow: hidden; }
        .cartouche .intitule { background: var(--encre); color: #fff; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; text-align: center; padding: .55rem .8rem; font-size: 1.05rem; }
        .cartouche dl { margin: 0; padding: .5rem .8rem; display: grid; grid-template-columns: auto 1fr; gap: .15rem .8rem; font-size: .9rem; }
        .cartouche dt { color: var(--gris); }
        .cartouche dd { margin: 0; font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }

        .doc-entete > div:nth-child(2) { display: flex; justify-content: center; }
        .tampon { width: max-content; transform: rotate(-8deg); border: 3px double currentColor; border-radius: 8px; padding: .3rem .9rem; font-weight: 800; font-size: 1.2rem; letter-spacing: .1em; text-transform: uppercase; opacity: .8; max-width: 100%; text-align: center; }
        .tampon small { display: block; font-size: .62rem; letter-spacing: .04em; font-weight: 600; text-align: center; }
        .tampon.vert { color: #0f7a45; } .tampon.rouge { color: #c62828; } .tampon.orange { color: #c26a00; } .tampon.gris { color: #6b7280; } .tampon.bleu { color: #1d4fb8; }

        .doc-section { margin-top: .85rem; }
        .doc-section > h3 { display: flex; align-items: center; gap: .5rem; margin: 0 0 .45rem; font-size: .8rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; color: var(--encre); }
        .doc-section > h3::before { content: ""; width: 4px; height: .95rem; border-radius: 2px; background: var(--accent); }
        .doc-grille { display: grid; grid-template-columns: repeat(2, 1fr); border: 1px solid var(--trait); border-radius: 6px; overflow: hidden; }
        .doc-grille > div { padding: .35rem .65rem; border-bottom: 1px solid var(--trait); }
        .doc-grille > div:nth-child(odd) { border-right: 1px solid var(--trait); }
        .doc-grille > div.large { grid-column: 1 / -1; border-right: 0; }
        .doc-grille > div:last-child, .doc-grille > div:nth-last-child(2):nth-child(odd) { border-bottom: 0; }
        .doc-grille .lib { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: var(--gris); }
        .doc-grille .val { display: block; margin-top: 1px; font-weight: 600; color: #111827; }
        .doc-grille .code { font-variant-numeric: tabular-nums; letter-spacing: .02em; }

        .doc-table { width: 100%; border-collapse: collapse; font-size: .92rem; border: 1px solid var(--trait); }
        .doc-table th { background: var(--doux); color: var(--encre); text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; padding: .45rem .6rem; border-bottom: 1.5px solid var(--encre); }
        .doc-table td { padding: .32rem .6rem; border-bottom: 1px solid var(--trait); vertical-align: top; }
        .doc-table .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .doc-table tr.fort td { font-weight: 800; background: var(--doux); }
        .doc-table tr.accent td { font-weight: 700; color: var(--accent); }

        .montant { margin-top: .9rem; display: grid; grid-template-columns: auto 1fr; border: 1.5px solid var(--encre); border-radius: 6px; overflow: hidden; }
        .montant .chiffres { background: var(--encre); color: #fff; padding: .7rem 1.1rem; display: flex; flex-direction: column; justify-content: center; }
        .montant .chiffres span { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; opacity: .8; }
        .montant .chiffres strong { font-size: 1.55rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .montant .lettres { padding: .7rem 1rem; font-size: .95rem; display: flex; flex-direction: column; justify-content: center; }
        .montant .lettres span { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: var(--gris); }
        .montant .lettres p { margin: .1rem 0 0; font-weight: 700; font-style: italic; }

        .note { margin-top: .6rem; font-size: .85rem; color: var(--gris); }
        .alerte-doc { margin-top: .8rem; border-left: 4px solid #c62828; background: #fdf0f0; padding: .5rem .8rem; font-size: .9rem; }

        .signatures { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 1rem; margin-top: .8rem; break-inside: avoid; }
        .signature { border: 1px solid var(--trait); border-radius: 6px; display: flex; flex-direction: column; min-height: 36mm; }
        .signature .qui { background: var(--doux); padding: .45rem .6rem; text-align: center; font-weight: 800; font-size: .8rem; letter-spacing: .06em; text-transform: uppercase; color: var(--encre); border-bottom: 1px solid var(--trait); }
        .signature .mention { padding: .45rem .6rem 0; text-align: center; font-size: .85rem; color: #0f7a45; font-weight: 600; min-height: 1.2rem; }
        .signature .zone { flex: 1; }
        .signature .pied { padding: .35rem .6rem; text-align: center; font-size: .74rem; color: var(--gris); border-top: 1px dashed var(--trait); }
        .fait-a { margin-top: .7rem; text-align: right; font-size: .9rem; }

        .doc-pied { position: absolute; left: 16mm; right: 16mm; bottom: 8mm; display: flex; justify-content: space-between; gap: 1rem; border-top: 1px solid var(--trait); padding-top: .35rem; font-size: .72rem; color: var(--gris); }

        @media print {
            body { background: #fff; }
            .barre-outils { display: none; }
            .feuille { margin: 0; box-shadow: none; width: 210mm; min-height: 296mm; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="barre-outils">
    <button type="button" class="principal" onclick="window.print()">Imprimer / enregistrer en PDF</button>
    <button type="button" class="secondaire" onclick="window.close()">Fermer</button>
</div>

<div class="feuille">
    <header class="doc-entete">
        <div class="etat">
            <p class="pays">{{ $e['tutelle'] }}</p>
            @if ($e['devise_nationale'])<p class="devise">{{ $e['devise_nationale'] }}</p>@endif
            <div class="filet"></div>
            <p class="structure">{{ $e['nom'] }}</p>
            @if ($service)<p class="service">{{ $service }}</p>@endif
            @if ($e['adresse'] || $e['telephone'])<p class="service">{{ collect([$e['adresse'], $e['telephone']])->filter()->join(' · ') }}</p>@endif
        </div>
        <div>
            @if ($tampon)
                <div class="tampon {{ $tampon[1] ?? 'gris' }}">{{ $tampon[0] }}@if (! empty($tampon[2]))<small>{{ $tampon[2] }}</small>@endif</div>
            @endif
        </div>
        <div class="cartouche">
            <div class="intitule">{{ $titre }}</div>
            <dl>
                @if ($numero)<dt>N°</dt><dd>{{ $numero }}</dd>@endif
                @if ($date)<dt>Date</dt><dd>{{ date_fr($date) }}</dd>@endif
                @if ($gestion ?? $exercice)<dt>Gestion</dt><dd>{{ $gestion ?? $exercice->annee() }}</dd>@endif
            </dl>
        </div>
    </header>


    {{ $slot }}

    <footer class="doc-pied">
        <span>{{ $e['nom'] }}{{ $reference ? ' · '.$reference : '' }}</span>
        <span>Édité le {{ now()->format('d/m/Y à H:i') }} par {{ auth()->user()?->name }} · {{ config('app.name', 'Gestion') }}</span>
    </footer>
</div>
</body>
</html>
