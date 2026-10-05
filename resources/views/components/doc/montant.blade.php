@props(['montant', 'libelle' => 'Montant', 'phrase' => 'Arrêté à la somme de'])
<div class="montant">
    <div class="chiffres"><span>{{ $libelle }}</span><strong>{{ fcfa($montant) }}</strong></div>
    <div class="lettres"><span>{{ $phrase }}</span><p>{{ montant_en_lettres($montant) }}</p></div>
</div>
