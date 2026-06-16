document.addEventListener('DOMContentLoaded', function() {
    const detectBtn = document.getElementById('detectLocationBtn');
    if (detectBtn) {
        detectBtn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert("Geolocation is not supported by your browser");
                return;
            }

            detectBtn.disabled = true;
            document.getElementById('locationLoader').classList.remove('d-none');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    fetch('get_localities.php')
                        .then(response => response.json())
                        .then(localities => {
                            let nearest = null;
                            let minDistance = Infinity;

                            localities.forEach(loc => {
                                const dist = calculateDistance(lat, lon, loc.latitude, loc.longitude);
                                if (dist < minDistance) {
                                    minDistance = dist;
                                    nearest = loc.name;
                                }
                            });

                            if (nearest) {
                                window.location.href = 'local-shops.php?place=' + encodeURIComponent(nearest);
                            } else {
                                alert("Could not determine nearest location.");
                                resetLoader();
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert("Error fetching locality data.");
                            resetLoader();
                        });
                },
                (error) => {
                    alert("Error getting location: " + error.message);
                    resetLoader();
                }
            );
        });
    }

    function resetLoader() {
        if (detectBtn) detectBtn.disabled = false;
        const loader = document.getElementById('locationLoader');
        if (loader) loader.classList.add('d-none');
    }

    // Haversine Formula
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }
});
