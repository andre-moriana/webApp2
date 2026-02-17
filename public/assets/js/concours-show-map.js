/**
 * Carte et itinéraire pour la page concours show
 */
(function() {
    'use strict';

    var showMap = null;
    var showMarker = null;

    function initShowMap() {
        var modal = document.getElementById('mapModal');
        if (!modal) return;
        var lat = parseFloat(modal.getAttribute('data-lat')) || 0;
        var lng = parseFloat(modal.getAttribute('data-lng')) || 0;
        var address = modal.getAttribute('data-address') || 'Non renseigné';

        if (showMap) {
            showMap.remove();
            showMap = null;
            showMarker = null;
        }

        var container = document.getElementById('map-show-container');
        if (!container) return;

        showMap = L.map('map-show-container').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(showMap);

        showMarker = L.marker([lat, lng]).addTo(showMap);
        showMarker.bindPopup('<strong>' + address + '</strong><br><small>Coordonnées: ' + lat.toFixed(6) + ', ' + lng.toFixed(6) + '</small>').openPopup();

        setTimeout(function() {
            showMap.invalidateSize();
        }, 100);
    }

    window.openMapModal = function() {
        var modalElement = document.getElementById('mapModal');
        if (!modalElement) return;
        var modal = new bootstrap.Modal(modalElement);
        modalElement.addEventListener('shown.bs.modal', function onShown() {
            initShowMap();
            modalElement.removeEventListener('shown.bs.modal', onShown);
        }, { once: true });
        modal.show();
    };

    window.createItinerary = function(service) {
        service = service || 'google';
        var modal = document.getElementById('mapModal');
        if (!modal) return;
        var lat = parseFloat(modal.getAttribute('data-lat')) || 0;
        var lng = parseFloat(modal.getAttribute('data-lng')) || 0;
        var url = '';
        switch (service) {
            case 'google':
                url = 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng;
                break;
            case 'osm':
                url = 'https://www.openstreetmap.org/directions?to=' + lat + ',' + lng;
                break;
            case 'waze':
                url = 'https://www.waze.com/ul?ll=' + lat + ',' + lng + '&navigate=yes';
                break;
            case 'native':
                var ua = navigator.userAgent || navigator.vendor || window.opera;
                var isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
                url = isIOS ? 'http://maps.apple.com/?daddr=' + lat + ',' + lng + '&dirflg=d' : 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng;
                break;
            default:
                url = 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng;
        }
        window.open(url, '_blank');
    };
})();
