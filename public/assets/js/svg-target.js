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
        // Pour le blason campagne, détecter par le type de tir, pas par la catégorie
        const shootingType = window.scoredTrainingData?.shooting_type || '';
        const isBlasonCampagne = shootingType === 'Campagne';
        const numRings = isBlasonCampagne ? 6 : this.options.rings;
        const ringWidth = targetRadius / numRings;
        
        // Calcul du score basé sur la distance
        let score = 0;
        for (let i = numRings - 1; i >= 0; i--) {
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
        // Pour le blason campagne, détecter par le type de tir, pas par la catégorie
        const shootingType = window.scoredTrainingData?.shooting_type || '';
        const isBlasonCampagne = shootingType === 'Campagne';
        
        if (isBlasonCampagne) {
            // Palette spécifique au blason campagne : 6 zones
            return [
                '#212121', // Zone 1 (extérieur) - Noir
                '#212121', // Zone 2 - Noir
                '#212121', // Zone 3 - Noir
                '#212121', // Zone 4 - Noir
                '#FFD700', // Zone 5 - Jaune
                '#FFD700'  // Zone 6 (centre) - Jaune
            ];
        }
        
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
        const isTrispot = this.options.targetCategory.toLowerCase() === 'trispot';
        
        
        if (isTrispot) {
            return this.generateTrispotSVG();
        } else {
            return this.generateSingleTargetSVG();
        }
    }

    /**
     * Génère un SVG avec un seul blason
     */
    generateSingleTargetSVG() {
        const size = this.options.size;
        const centerX = size / 2;
        const centerY = size / 2;
        // Pour le blason campagne, détecter par le type de tir, pas par la catégorie
        const shootingType = window.scoredTrainingData?.shooting_type || '';
        const isBlasonCampagne = shootingType === 'Campagne';
        const numRings = isBlasonCampagne ? 6 : this.options.rings;
        const targetScale = numRings / (numRings + 1);
        const targetRadius = (size / 2) * targetScale;
        const ringWidth = targetRadius / numRings;
        
        const palette = this.getColorPalette();
        
        let svg = `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" class="target-svg">`;
        
        // Génération des anneaux
        for (let i = 0; i < numRings; i++) {
            const radius = targetRadius - i * ringWidth;
            const color = palette[i];
            
            // Gestion des couleurs de trait pour le blason campagne
            let strokeColor = 'black';
            let strokeWidth = 1;
            
            if (isBlasonCampagne) {
                strokeColor = (i === 5) ? 'black' : 'white'; // Ligne de la zone 6 (centre) = noire, autres = blanches
            }
            
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
                // Les coordonnées hit.hit_x et hit.hit_y sont relatives à une cible de référence (300px)
                // Il faut les ajuster proportionnellement à la taille d'affichage actuelle
                const referenceSize = 300; // Taille de référence de l'app mobile
                const scaleFactor = size / referenceSize; // Facteur d'échelle
                const hitX = centerX + (hit.hit_x * scaleFactor);
                const hitY = centerY + (hit.hit_y * scaleFactor);
                
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
                    stroke-width="0.5"
                    class="hit-point"
                    data-score="${hit.score}"
                    data-arrow="${hit.arrow_number || index + 1}"
                />`;
            }
        });
        
        svg += '</svg>';
        return svg;
    }

    /**
     * Génère un SVG avec 3 blasons empilés pour le trispot
     * Basé exactement sur TargetAnalysis.tsx de l'app mobile
     */
    generateTrispotSVG() {
        const size = this.options.size;
        
        // Calculs de référence de l'app mobile (SimpleTarget.tsx)
        const referenceSide = 300; // Taille de référence de l'app mobile
        const targetScale = this.options.rings / (this.options.rings + 1); // 10/11
        const referenceOuterRadius = (referenceSide / 2) * targetScale; // 150 * (10/11) = 136.363636...
        const referenceRingWidth = referenceOuterRadius / this.options.rings; // 136.363636 / 10 = 13.6363636...
        
        // Adapter à la taille réelle du conteneur trispot
        const blasonSize = size; // Taille de chaque blason dans le trispot
        const scaleFactor = blasonSize / referenceSide; // Facteur d'échelle pour adapter à la taille réelle
        const outerRadius = referenceOuterRadius * scaleFactor;
        const ringWidth = referenceRingWidth * scaleFactor;
        
        // Palette exacte de TargetAnalysis.tsx
        const isTrispot = true; // On est en mode trispot
        const basePalette = ['#FFFFFF','#FFFFFF','#212121','#212121','#1976D2','#1976D2','#D32F2F','#D32F2F','#FFD700','#FFD700'];
        const palette = basePalette.map((color, i) => (isTrispot && i < 5 ? '#EEEEEE' : color));
        
        // Hauteur pour 3 blasons empilés sans chevauchement
        const svgHeight = size * 3; // Tripler la hauteur pour avoir assez d'espace
        
        let svg = `<svg width="${size}" height="${svgHeight}" viewBox="0 0 ${size} ${svgHeight}" class="target-svg trispot-svg">`;
        
        // Générer 3 blasons empilés
        for (let targetIndex = 0; targetIndex < 3; targetIndex++) {
            const centerY = (svgHeight / 3) * (targetIndex + 0.5);
            const centerX = size / 2;
            
            // Génération des anneaux EXACTEMENT comme TargetAnalysis.tsx ligne 162
            for (let i = 0; i < this.options.rings; i++) {
                const radius = outerRadius - i * ringWidth; // Même calcul que TargetAnalysis.tsx ligne 162
                const color = palette[i];
                const strokeWidth = (isTrispot && i < 5) ? 0 : 1; // Même logique ligne 165
                const strokeColor = (isTrispot && i < 5) ? 'none' : 'black'; // Même logique ligne 164
                
                svg += `<circle 
                    cx="${centerX}" 
                    cy="${centerY}" 
                    r="${radius}" 
                    fill="${color}" 
                    stroke="${strokeColor}" 
                    stroke-width="${strokeWidth}"
                />`;
            }
        }
        
        // Filtrer les flèches valides
        const validHits = this.hits.filter(hit => 
            hit.hit_x !== null && 
            hit.hit_y !== null && 
            hit.arrow_number !== null && 
            hit.arrow_number !== undefined
        );
        
        // Ajouter les flèches EXACTEMENT comme TargetAnalysis.tsx ligne 170-180
        validHits.forEach(hit => {
            const targetIndex = hit.arrow_number - 1; // 0, 1, ou 2
            const centerY = (svgHeight / 3) * (targetIndex + 0.5);
            const centerX = size / 2;
            
            // Positionnement avec ajustement proportionnel à la taille d'affichage
            // Les coordonnées hit.hit_x et hit.hit_y sont relatives à une cible de référence (300px)
            // Il faut les ajuster proportionnellement à la taille d'affichage actuelle
            const referenceSize = 300; // Taille de référence de l'app mobile
            const scaleFactor = size / referenceSize; // Facteur d'échelle
            const hitX = centerX + (hit.hit_x * scaleFactor);
            const hitY = centerY + (hit.hit_y * scaleFactor);
            
            
            const isPreviousEnd = hit.endNumber !== undefined;
            const fillColor = isPreviousEnd ? "rgba(0, 0, 255, 0.6)" : "rgba(255, 0, 0, 1)"; // Même couleur ligne 176
            const strokeColor = "white"; // Même couleur ligne 177
            const radius = 2; // Même rayon ligne 175
            
            svg += `<circle 
                cx="${hitX}" 
                cy="${hitY}" 
                r="${radius}" 
                fill="${fillColor}" 
                stroke="${strokeColor}"
                stroke-width="1"
                class="hit-point"
                data-score="${hit.score}"
                data-arrow="${hit.arrow_number}"
                data-target="${targetIndex + 1}"
            />`;
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
