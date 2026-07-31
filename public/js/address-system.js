/**
 * Central Address System (Cities & Districts of Turkey)
 * Automatically binds to .address-city and .address-district select dropdowns
 */
document.addEventListener('DOMContentLoaded', function() {
    const basePath = window.location.pathname.startsWith('/SaintMonarc') ? '/SaintMonarc' : '';

    function initAddressSelectors() {
        const citySelects = document.querySelectorAll('.address-city');
        citySelects.forEach(select => {
            if (select.getAttribute('data-address-bound')) return;
            select.setAttribute('data-address-bound', 'true');

            const savedCity = select.getAttribute('data-selected') || '';

            // Fetch all cities
            fetch(basePath + '/api/address/cities')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.cities) {
                        // Keep track of current selected value if any
                        const currentValue = select.value || savedCity;
                        
                        select.innerHTML = '<option value="">-- İl Seçin --</option>';
                        data.cities.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city;
                            option.textContent = city;
                            if (currentValue && currentValue.toLowerCase() === city.toLowerCase()) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });

                        // If there was a pre-selected city, load its districts
                        if (select.value) {
                            loadDistricts(select, select.value);
                        }
                    }
                });

            // Listen to changes
            select.addEventListener('change', function() {
                loadDistricts(this, this.value);
            });
        });
    }

    function loadDistricts(citySelect, cityName) {
        const targetSelector = citySelect.getAttribute('data-target') || '.address-district';
        // Locate matching district dropdown within the same form or container
        let districtSelect = null;
        if (citySelect.form) {
            districtSelect = citySelect.form.querySelector(targetSelector);
        }
        if (!districtSelect) {
            districtSelect = document.querySelector(targetSelector);
        }
        
        if (!districtSelect) return;

        if (!cityName) {
            districtSelect.innerHTML = '<option value="">-- Önce İl Seçin --</option>';
            return;
        }

        const savedDistrict = districtSelect.getAttribute('data-selected') || '';

        fetch(basePath + '/api/address/districts?city=' + encodeURIComponent(cityName))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.districts) {
                    const currentDistVal = districtSelect.value || savedDistrict;
                    districtSelect.innerHTML = '<option value="">-- İlçe Seçin --</option>';
                    data.districts.forEach(dist => {
                        const option = document.createElement('option');
                        option.value = dist;
                        option.textContent = dist;
                        if (currentDistVal && currentDistVal.toLowerCase() === dist.toLowerCase()) {
                            option.selected = true;
                        }
                        districtSelect.appendChild(option);
                    });
                }
            });
    }

    // Run initialization
    initAddressSelectors();

    // Export globally
    window.AddressSystem = {
        init: initAddressSelectors,
        load: loadDistricts
    };
});
