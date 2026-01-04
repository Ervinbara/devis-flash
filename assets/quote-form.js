// Gestion des items (lignes de devis)
let itemIndex = document.getElementById('items-container').dataset.index || 1;

function addItem() {
    const container = document.getElementById('items-container');
    const prototype = container.dataset.prototype;
    
    // Remplacer __name__ par l'index
    const newItem = prototype.replace(/__name__/g, itemIndex);
    
    // Créer un élément wrapper
    const wrapper = document.createElement('div');
    wrapper.classList.add('item-row');
    wrapper.innerHTML = `
        <div class="item-fields">
            ${newItem}
        </div>
        <button type="button" class="btn-remove-item" onclick="removeItem(this)">🗑️</button>
    `;
    
    container.appendChild(wrapper);
    itemIndex++;
    
    // Recalculer les totaux
    calculateTotals();
}

function removeItem(button) {
    const itemRow = button.closest('.item-row');
    const container = document.getElementById('items-container');
    
    // Empêcher la suppression si c'est le seul item
    if (container.querySelectorAll('.item-row').length <= 1) {
        alert('Vous devez avoir au moins une ligne de devis');
        return;
    }
    
    itemRow.remove();
    calculateTotals();
}

// Calcul automatique des totaux
function calculateTotals() {
    const items = document.querySelectorAll('.item-row');
    let totalHt = 0;
    
    items.forEach(item => {
        const quantity = parseFloat(item.querySelector('input[id*="quantity"]')?.value) || 0;
        const unitPrice = parseFloat(item.querySelector('input[id*="unitPriceHt"]')?.value) || 0;
        totalHt += quantity * unitPrice;
    });
    
    const vatRate = parseFloat(document.querySelector('select[id*="vatRate"]')?.value) || 20;
    const vatAmount = totalHt * (vatRate / 100);
    const totalTtc = totalHt + vatAmount;
    
    // Afficher les totaux
    document.getElementById('totalHt').textContent = formatPrice(totalHt);
    document.getElementById('vatRateDisplay').textContent = vatRate;
    document.getElementById('vatAmount').textContent = formatPrice(vatAmount);
    document.getElementById('totalTtc').textContent = formatPrice(totalTtc);
}

function formatPrice(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Écouter les changements sur les inputs
document.addEventListener('DOMContentLoaded', function() {
    // Calcul initial
    calculateTotals();
    
    // Écouter les changements sur tous les inputs de quantité et prix
    document.getElementById('quoteForm').addEventListener('input', function(e) {
        if (e.target.matches('input[id*="quantity"], input[id*="unitPriceHt"]')) {
            calculateTotals();
        }
    });
    
    // Écouter les changements sur le taux de TVA
    const vatSelect = document.querySelector('select[id*="vatRate"]');
    if (vatSelect) {
        vatSelect.addEventListener('change', calculateTotals);
    }
});

// Validation avant soumission
document.getElementById('quoteForm')?.addEventListener('submit', function(e) {
    const items = document.querySelectorAll('.item-row');
    let hasEmptyItem = false;
    
    items.forEach(item => {
        const label = item.querySelector('input[id*="label"]')?.value.trim();
        if (!label) {
            hasEmptyItem = true;
        }
    });
    
    if (hasEmptyItem) {
        e.preventDefault();
        alert('Veuillez remplir toutes les lignes de devis ou les supprimer');
        return false;
    }
});
