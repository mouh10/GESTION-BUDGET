import {
  Chart, BarController, BarElement, LineController, LineElement, PointElement,
  CategoryScale, LinearScale, Tooltip, Legend, Filler,
} from 'chart.js';

Chart.defaults.font.family = "'Public Sans Variable', 'Public Sans', system-ui, sans-serif";
Chart.defaults.color = '#64748b';
Chart.register(BarController, BarElement, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

const fmt = (n) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Math.round(n || 0));
const nombre = (v) => {
  const n = parseFloat(String(v ?? '').replace(/\s/g, '').replace(',', '.'));
  return Number.isFinite(n) ? n : 0;
};

/* Menu latéral sur mobile */
document.addEventListener('click', (e) => {
  const bouton = e.target.closest('[data-menu-toggle]');
  if (bouton) {
    document.getElementById('menu-lateral')?.classList.toggle('-translate-x-full');
    document.getElementById('menu-fond')?.classList.toggle('hidden');
  }
});

/* Raccourci Ctrl+K / Cmd+K : recherche */
document.addEventListener('keydown', (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    const champ = document.querySelector('[data-recherche]');
    if (champ) { e.preventDefault(); champ.focus(); champ.select(); }
  }
  if (e.key === 'Escape') {
    document.querySelectorAll('details[data-deroulant][open]').forEach((d) => d.removeAttribute('open'));
  }
});

/* Fermer les menus déroulants au clic extérieur */
document.addEventListener('click', (e) => {
  document.querySelectorAll('details[data-deroulant][open]').forEach((d) => {
    if (!d.contains(e.target)) d.removeAttribute('open');
  });
});

/* Messages : fermeture manuelle, et disparition automatique des confirmations */
document.addEventListener('click', (e) => {
  const bouton = e.target.closest('[data-fermer]');
  if (bouton) bouton.parentElement.remove();
});
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-flash]').forEach((el) => {
    setTimeout(() => { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 6000);
  });
  // Garde l'entrée active du menu visible
  document.querySelector('[data-menu-defilement] .nav-lien.actif')?.scrollIntoView({ block: 'nearest' });
});

/* Confirmation avant les actions sensibles */
document.addEventListener('submit', (e) => {
  const message = e.target.dataset.confirm;
  if (message && !window.confirm(message)) {
    e.preventDefault();
  }
});

/* Formulaire de crédits / engagement : affiche le disponible de la ligne choisie */
function majInfoLigne(select) {
  const cible = document.querySelector(select.dataset.infoLigne);
  const option = select.selectedOptions[0];
  if (!cible) return;
  cible.textContent = option && option.dataset.info ? option.dataset.info : '';
  const aeBloc = document.querySelector('[data-bloc-ae]');
  if (aeBloc && option) aeBloc.classList.toggle('hidden', option.dataset.aeDistinctes !== '1');
}
document.addEventListener('change', (e) => {
  if (e.target.matches('[data-info-ligne]')) majInfoLigne(e.target);
});
document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-info-ligne]').forEach(majInfoLigne));

/* Soumission auto des sélecteurs (exercice courant, filtres) */
document.addEventListener('change', (e) => {
  if (e.target.matches('[data-auto-submit]')) {
    e.target.form.submit();
  }
});

/* Lignes dynamiques : écritures, factures, budgets */
function initialiserLignes(conteneur) {
  const corps = conteneur.querySelector('[data-lignes-corps]');
  const modele = conteneur.querySelector('template');
  let index = corps.querySelectorAll('[data-ligne]').length;

  const recalculer = () => {
    const type = conteneur.dataset.lignes;
    if (type === 'ecriture') calculEcriture(conteneur);
    if (type === 'modification') calculModification(conteneur);
  };

  const ajouter = () => {
    const html = modele.innerHTML.replaceAll('__INDEX__', index++);
    corps.insertAdjacentHTML('beforeend', html);
    recalculer();
  };

  conteneur.addEventListener('click', (e) => {
    if (e.target.closest('[data-ajouter-ligne]')) {
      e.preventDefault();
      ajouter();
    }
    const suppr = e.target.closest('[data-supprimer-ligne]');
    if (suppr) {
      e.preventDefault();
      if (corps.querySelectorAll('[data-ligne]').length > 1) {
        suppr.closest('[data-ligne]').remove();
        recalculer();
      }
    }
    const equilibrer = e.target.closest('[data-equilibrer]');
    if (equilibrer) {
      e.preventDefault();
      equilibrerEcriture(conteneur);
    }
  });

  conteneur.addEventListener('input', recalculer);
  conteneur.addEventListener('change', recalculer);

  // Saisie d'un débit : on vide le crédit de la même ligne (et inversement).
  conteneur.addEventListener('input', (e) => {
    const ligne = e.target.closest('[data-ligne]');
    if (!ligne) return;
    if (e.target.matches('[data-debit]') && nombre(e.target.value) > 0) ligne.querySelector('[data-credit]').value = '';
    if (e.target.matches('[data-credit]') && nombre(e.target.value) > 0) ligne.querySelector('[data-debit]').value = '';
  });

  const minimum = parseInt(conteneur.dataset.minimum || '1', 10);
  while (corps.querySelectorAll('[data-ligne]').length < minimum) ajouter();
  recalculer();
}

function calculEcriture(c) {
  let d = 0; let cr = 0;
  c.querySelectorAll('[data-debit]').forEach((i) => { d += nombre(i.value); });
  c.querySelectorAll('[data-credit]').forEach((i) => { cr += nombre(i.value); });
  const ecart = Math.round((d - cr) * 100) / 100;
  c.querySelector('[data-total-debit]').textContent = fmt(d);
  c.querySelector('[data-total-credit]').textContent = fmt(cr);
  const zone = c.querySelector('[data-ecart]');
  zone.textContent = ecart === 0 ? (d > 0 ? 'Équilibrée' : '—') : `Écart : ${fmt(ecart)}`;
  zone.className = ecart === 0 && d > 0 ? 'badge-vert' : (ecart === 0 ? 'badge-gris' : 'badge-rouge');
}

function equilibrerEcriture(c) {
  let d = 0; let cr = 0;
  c.querySelectorAll('[data-debit]').forEach((i) => { d += nombre(i.value); });
  c.querySelectorAll('[data-credit]').forEach((i) => { cr += nombre(i.value); });
  const ecart = Math.round((d - cr) * 100) / 100;
  if (ecart === 0) return;
  const lignes = [...c.querySelectorAll('[data-ligne]')];
  const vide = lignes.find((l) => !nombre(l.querySelector('[data-debit]').value) && !nombre(l.querySelector('[data-credit]').value));
  if (!vide) {
    c.querySelector('[data-ajouter-ligne]').click();
    return equilibrerEcriture(c);
  }
  vide.querySelector(ecart > 0 ? '[data-credit]' : '[data-debit]').value = Math.abs(ecart);
  calculEcriture(c);
}

function calculModification(c) {
  let ae = 0; let cp = 0;
  c.querySelectorAll('[data-ligne]').forEach((l) => {
    ae += nombre(l.querySelector('[data-ae]')?.value);
    cp += nombre(l.querySelector('[data-cp]')?.value);
  });
  c.querySelector('[data-total-ae]').textContent = fmt(ae);
  c.querySelector('[data-total-cp]').textContent = fmt(cp);
}

/* Graphique du tableau de bord */
function initialiserGraphique(canvas) {
  // Format attendu : { labels: [...], series: [{ label, data, couleur }] }
  const donnees = JSON.parse(canvas.dataset.graphique);
  new Chart(canvas, {
    type: 'bar',
    data: {
      labels: donnees.labels,
      datasets: donnees.series.map((s) => ({
        label: s.label, data: s.data, backgroundColor: s.couleur, borderRadius: 6, maxBarThickness: 26,
      })),
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, useBorderRadius: true, borderRadius: 3 } },
        tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label} : ${fmt(ctx.parsed.y)} FCFA` } },
      },
      scales: {
        x: { grid: { display: false }, border: { display: false } },
        y: { ticks: { callback: (v) => (Math.abs(v) >= 1e6 ? `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(v / 1e6)} M` : fmt(v)) } },
      },
    },
  });
}

/* Courbe blanche dans les grandes cartes colorées du tableau de bord */
function initialiserCourbe(canvas) {
  const d = JSON.parse(canvas.dataset.courbe);
  const blanc = 'rgba(255,255,255,0.95)';
  const discret = 'rgba(255,255,255,0.7)';
  new Chart(canvas, {
    type: 'line',
    data: { labels: d.labels, datasets: [{ data: d.data, borderColor: blanc, backgroundColor: 'rgba(255,255,255,0.18)', fill: true, tension: 0.35, pointRadius: 3, pointBackgroundColor: blanc, borderWidth: 2 }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${fmt(ctx.parsed.y)} FCFA` } } },
      scales: {
        x: { grid: { display: false }, border: { display: false }, ticks: { color: discret, font: { size: 10 } } },
        y: { grid: { color: 'rgba(255,255,255,0.15)' }, border: { display: false }, ticks: { color: discret, font: { size: 10 }, maxTicksLimit: 4, callback: (v) => (Math.abs(v) >= 1e6 ? `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v / 1e6)} M` : fmt(v)) } },
      },
    },
  });
}

/* Onglets à l'intérieur d'une page (sans rechargement) */
document.addEventListener('click', (e) => {
  const b = e.target.closest('[data-onglet]');
  if (!b) return;
  const zone = b.closest('[data-onglets-locaux]');
  zone.querySelectorAll('[data-onglet]').forEach((x) => x.classList.toggle('actif', x === b));
  zone.querySelectorAll('[data-panneau]').forEach((p) => { p.hidden = p.dataset.panneau !== b.dataset.onglet; });
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-courbe]').forEach(initialiserCourbe);
  document.querySelectorAll('[data-lignes]').forEach(initialiserLignes);
  document.querySelectorAll('[data-graphique]').forEach(initialiserGraphique);
});

/* ------------------------------------------------------------------
 |  Lignes de tableau cliquables : un clic n'importe où sur la ligne
 |  ouvre le premier lien de la ligne (ou data-href). Ctrl/Cmd-clic ou
 |  clic molette : nouvel onglet. Les boutons, champs et liens gardent
 |  leur comportement propre ; une sélection de texte n'ouvre rien.
 * ------------------------------------------------------------------ */
const ZONES_INTERACTIVES = 'a, button, input, select, textarea, label, summary, details, form, [data-sans-clic]';

function lienDeLigne(tr) {
    if (tr.dataset.href) return tr.dataset.href;
    const a = tr.querySelector('a[href]:not([target="_blank"])');
    return a ? a.href : null;
}

function preparerLignesCliquables(racine = document) {
    racine.querySelectorAll('table.tableau:not([data-sans-clic]) > tbody > tr').forEach((tr) => {
        if (lienDeLigne(tr)) tr.classList.add('ligne-cliquable');
    });
}

function ouvrirLigne(e) {
    const tr = e.target.closest('tr.ligne-cliquable');
    if (!tr || e.target.closest(ZONES_INTERACTIVES)) return;
    if (window.getSelection && String(window.getSelection()).trim() !== '') return;
    const url = lienDeLigne(tr);
    if (!url) return;
    if (e.button === 1 || e.ctrlKey || e.metaKey) {
        window.open(url, '_blank');
    } else if (e.button === 0) {
        window.location.href = url;
    }
}
document.addEventListener('click', ouvrirLigne);
document.addEventListener('auxclick', (e) => { if (e.button === 1) ouvrirLigne(e); });

/* Colonnes d'actions (en-tête vide : Modifier, Supprimer…) masquées à l'impression. */
function marquerColonnesActions(racine = document) {
    racine.querySelectorAll('table.tableau').forEach((table) => {
        const entetes = table.tHead?.rows[table.tHead.rows.length - 1];
        if (!entetes || [...entetes.cells].some((c) => c.colSpan > 1)) return;
        const vides = [...entetes.cells].map((c, i) => (c.textContent.trim() === '' && !c.querySelector('input') ? i : -1)).filter((i) => i >= 0);
        if (!vides.length) return;
        [...table.rows].forEach((row) => {
            if ([...row.cells].some((c) => c.colSpan > 1)) return;
            vides.forEach((i) => row.cells[i]?.classList.add('col-actions'));
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    preparerLignesCliquables();
    marquerColonnesActions();

    // Page ouverte avec ?impression=1 : impression automatique une fois les graphiques dessinés.
    if (document.body.hasAttribute('data-impression')) {
        setTimeout(() => window.print(), 700);
    }
});
