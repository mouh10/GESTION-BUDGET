import {
  Chart, BarController, BarElement, LineController, LineElement, PointElement,
  CategoryScale, LinearScale, Tooltip, Legend,
} from 'chart.js';

Chart.defaults.font.family = "'Inter Variable', 'Inter', system-ui, sans-serif";
Chart.defaults.color = '#64748b';
Chart.register(BarController, BarElement, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend);

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

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-lignes]').forEach(initialiserLignes);
  document.querySelectorAll('[data-graphique]').forEach(initialiserGraphique);
});
