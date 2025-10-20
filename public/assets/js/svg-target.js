/**
 * Composant SVG Target pour l'affichage dynamique des cibles d'archerie
 * Inspiré de l'implémentation React Native SimpleTarget.tsx
 */

class SVGTarget {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            size: 300,
            rings: 10,
            targetCategory: 'blason_80',
            showPreviousHits: true,
            ...options
        };
        
        this.hits = [];
        this.init();
    }

    init() {
        if (!this.container) {
            console.error('Container not found:', this.containerId);
            return;
        }
        
        this.render();
    }

    /**
     * Met à jour les impacts de flèches
     */
    setHits(hits) {
        this.hits = hits || [];
        this.render();
    }

    /**
     * Ajoute un impact
     */
    addHit(hit) {
        this.hits.push(hit);
        this.render();
    }

    /**
     * Calcule le score basé sur la distance du centre
     */
    calculateScore(x, y, targetRadius) {
        const distance = Math.sqrt(x * x + y * y);
        const ringWidth = targetRadius / this.options.rings;
        
        // Calcul du score basé sur la distance
        let score = 0;
        for (let i = this.options.rings - 1; i >= 0; i--) {
            const ringOuterRadius = targetRadius - i * ringWidth;
            const ringInnerRadius = ringOuterRadius - ringWidth;
            
            if (distance <= ringOuterRadius && distance >= ringInnerRadius) {
                score = i + 1;
                break;
            }
        }
        
        // Règle spécifique TRISPOT: seules les zones 6 à 10 scorent
        if (this.options.targetCategory.toLowerCase() === 'trispot' && score < 6) {
            score = 0;
        }
        
        return score;
    }

    /**
     * Génère la palette de couleurs selon la catégorie de cible
     */
    getColorPalette() {
        const isTrispot = this.options.targetCategory.toLowerCase() === 'trispot';
        const basePalette = [
            '#FFFFFF', '#FFFFFF', '#212121', '#212121', 
            '#1976D2', '#1976D2', '#D32F2F', '#D32F2F', 
            '#FFD700', '#FFD700'
        ];
        
        return basePalette.map((color, i) => {
            if (isTrispot && i < 5) {
                return '#EEEEEE'; // Fond neutre pour masquer les anneaux extérieurs
            }
            return color;
        });
    }

    /**
     * Génère le SVG de la cible
     */
    generateTargetSVG() {
        const size = this.options.size;
        const centerX = size / 2;
        const centerY = size / 2;
        const targetScale = this.options.rings / (this.options.rings + 1);
        const targetRadius = (size / 2) * targetScale;
        const ringWidth = targetRadius / this.options.rings;
        
        const palette = this.getColorPalette();
        const isTrispot = this.options.targetCategory.toLowerCase() === 'trispot';
        
        let svg = `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" class="target-svg">`;
        
        // Génération des anneaux
        for (let i = 0; i < this.options.rings; i++) {
            const radius = targetRadius - i * ringWidth;
            const color = palette[i];
            const strokeWidth = (isTrispot && i < 5) ? 0 : 1;
            const strokeColor = (isTrispot && i < 5) ? 'none' : 'black';
            
            svg += `<circle 
                cx="${centerX}" 
                cy="${centerY}" 
                r="${radius}" 
                fill="${color}" 
                stroke="${strokeColor}" 
                stroke-width="${strokeWidth}"
            />`;
        }
        
        // Ajout des impacts de flèches
        this.hits.forEach((hit, index) => {
            if (hit.hit_x !== null && hit.hit_y !== null) {
                const hitX = centerX + (hit.hit_x * targetRadius / 100); // Conversion des coordonnées relatives
                const hitY = centerY + (hit.hit_y * targetRadius / 100);
                
                // Différencier les impacts des volées précédentes
                const isPreviousEnd = hit.endNumber !== undefined;
                const fillColor = isPreviousEnd ? "rgba(0, 0, 255, 0.6)" : "rgba(255, 0, 0, 0.8)";
                const strokeColor = "white";
                const radius = isPreviousEnd ? 2 : 3;
                
                svg += `<circle 
                    cx="${hitX}" 
                    cy="${hitY}" 
                    r="${radius}" 
                    fill="${fillColor}" 
                    stroke="${strokeColor}"
                    stroke-width="1"
                    class="hit-point"
                    data-score="${hit.score}"
                    data-arrow="${hit.arrow_number || index + 1}"
                />`;
                
                // Ajout du numéro de flèche
                svg += `<text 
                    x="${hitX}" 
                    y="${hitY + 1}" 
                    text-anchor="middle" 
                    font-size="8" 
                    fill="white" 
                    font-weight="bold"
                    class="arrow-number"
                >${hit.arrow_number || index + 1}</text>`;
            }
        });
        
        svg += '</svg>';
        return svg;
    }

    /**
     * Génère les statistiques de la cible
     */
    generateStats() {
        if (this.hits.length === 0) return '';
        
        const totalScore = this.hits.reduce((sum, hit) => sum + (hit.score || 0), 0);
        const averageScore = totalScore / this.hits.length;
        const maxScore = Math.max(...this.hits.map(hit => hit.score || 0));
        const minScore = Math.min(...this.hits.map(hit => hit.score || 0));
        
        return `
            <div class="target-stats">
                <div class="stat-item">
                    <span class="stat-label">Total:</span>
                    <span class="stat-value">${totalScore}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Moyenne:</span>
                    <span class="stat-value">${averageScore.toFixed(1)}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Max:</span>
                    <span class="stat-value">${maxScore}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Min:</span>
                    <span class="stat-value">${minScore}</span>
                </div>
            </div>
        `;
    }

    /**
     * Rend la cible dans le conteneur
     */
    render() {
        if (!this.container) return;
        
        const targetHTML = this.generateTargetSVG();
        const statsHTML = this.generateStats();
        
        this.container.innerHTML = `
            <div class="svg-target-container">
                <div class="target-wrapper">
                    ${targetHTML}
                </div>
                ${statsHTML}
            </div>
        `;
        
        // Ajout des événements pour l'interactivité
        this.addInteractivity();
    }

    /**
     * Ajoute l'interactivité à la cible
     */
    addInteractivity() {
        const hitPoints = this.container.querySelectorAll('.hit-point');
        
        hitPoints.forEach(point => {
            point.addEventListener('mouseenter', (e) => {
                const score = e.target.getAttribute('data-score');
                const arrow = e.target.getAttribute('data-arrow');
                this.showTooltip(e, `Flèche ${arrow}: ${score} points`);
            });
            
            point.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    }

    /**
     * Affiche une tooltip
     */
    showTooltip(event, text) {
        this.hideTooltip(); // Supprimer l'ancienne tooltip
        
        const tooltip = document.createElement('div');
        tooltip.className = 'target-tooltip';
        tooltip.textContent = text;
        
        document.body.appendChild(tooltip);
        
        const rect = event.target.getBoundingClientRect();
        tooltip.style.left = rect.left + window.scrollX + 'px';
        tooltip.style.top = (rect.top + window.scrollY - 30) + 'px';
    }

    /**
     * Cache la tooltip
     */
    hideTooltip() {
        const existingTooltip = document.querySelector('.target-tooltip');
        if (existingTooltip) {
            existingTooltip.remove();
        }
    }

    /**
     * Met à jour la catégorie de cible
     */
    setTargetCategory(category) {
        this.options.targetCategory = category;
        this.render();
    }

    /**
     * Redimensionne la cible
     */
    resize(newSize) {
        this.options.size = newSize;
        this.render();
    }
}

// Fonction utilitaire pour créer une cible SVG
function createSVGTarget(containerId, hits = [], options = {}) {
    const target = new SVGTarget(containerId, options);
    target.setHits(hits);
    return target;
}

// Export pour utilisation globale
window.SVGTarget = SVGTarget;
window.createSVGTarget = createSVGTarget;
