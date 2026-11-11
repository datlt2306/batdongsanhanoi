/**
 * Google Maps cho trang chi tiết dự án
 * Hiển thị bản đồ và các tiện ích xung quanh
 */

// Callback function cho Google Maps API
window.initProjectMap = function() {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        showMapError('Google Maps API chưa được load. Vui lòng kiểm tra API key và các API đã được bật.');
        return;
    }
    initializeMap();
};

// Hàm xử lý lỗi khi load Google Maps API
window.gm_authFailure = function() {
    showMapError('Lỗi xác thực Google Maps API (REQUEST_DENIED). Vui lòng kiểm tra API key và các API đã được bật (Maps JavaScript API, Places API).');
};

// Hàm khởi tạo bản đồ
function initializeMap() {
    'use strict';

    // Kiểm tra API key
    if (typeof bdsMapConfig !== 'undefined' && !bdsMapConfig.hasApiKey) {
        showMapError('Chưa cấu hình Google Maps API key. Vui lòng thêm API key vào wp-config.php hoặc ACF Options.');
        return;
    }

    // Đợi DOM sẵn sàng
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        initMap();
    }
}

// Hàm hiển thị lỗi
function showMapError(message) {
    const mapElement = document.getElementById('project-map');
    if (mapElement) {
        mapElement.innerHTML = `
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 40px; text-align: center; background: #fef2f2; border-radius: 12px;">
                <div style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h3 style="color: #dc2626; margin: 0 0 12px; font-size: 18px;">Lỗi Google Maps</h3>
                <p style="color: #6b7280; margin: 0 0 20px; font-size: 14px; line-height: 1.6;">${message}</p>
                <div style="background: #fff; padding: 16px; border-radius: 8px; text-align: left; max-width: 500px; font-size: 12px; color: #374151;">
                    <strong style="display: block; margin-bottom: 8px;">Hướng dẫn khắc phục:</strong>
                    <ol style="margin: 0; padding-left: 20px; line-height: 1.8;">
                        <li>Tạo API key tại <a href="https://console.cloud.google.com/google/maps-apis" target="_blank" style="color: #dc2626;">Google Cloud Console</a></li>
                        <li><strong>Bật các API cần thiết:</strong> Maps JavaScript API, Places API</li>
                        <li>Thêm API key vào <code>wp-config.php</code>: <br><code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 4px;">define('GOOGLE_MAPS_API_KEY', 'YOUR_KEY');</code></li>
                        <li>Hoặc thêm vào ACF Options với field name: <code>google_maps_api_key</code></li>
                        <li>Kiểm tra API restrictions (nếu có) phải cho phép domain của bạn</li>
                        <li>Đảm bảo billing đã được kích hoạt trong Google Cloud Console</li>
                    </ol>
                </div>
            </div>
        `;
    }
}

// Hàm khởi tạo bản đồ chính
function initMap() {
    'use strict';

    const mapElement = document.getElementById('project-map');
    if (!mapElement) {
        return;
    }

    // Lấy dữ liệu từ data attributes
    const projectLat = parseFloat(mapElement.dataset.lat);
    const projectLng = parseFloat(mapElement.dataset.lng);
    const projectAddress = mapElement.dataset.address || '';
    const projectTitle = mapElement.dataset.title || 'Dự án';

    if (!projectLat || !projectLng) {
        console.error('Thiếu thông tin vị trí dự án');
        return;
    }

    // Khởi tạo bản đồ
    const projectLocation = { lat: projectLat, lng: projectLng };
    const map = new google.maps.Map(mapElement, {
        center: projectLocation,
        zoom: 15,
        mapTypeControl: false,
        streetViewControl: true,
        fullscreenControl: true,
        styles: [
            {
                featureType: 'poi',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            }
        ]
    });

    // Marker cho dự án
    const projectMarker = new google.maps.Marker({
        position: projectLocation,
        map: map,
        title: projectTitle,
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg width="40" height="50" viewBox="0 0 40 50" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 0C9 0 0 9 0 20c0 15 20 30 20 30s20-15 20-30c0-11-9-20-20-20z" fill="#dc2626"/>
                    <circle cx="20" cy="20" r="8" fill="#fff"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(40, 50),
            anchor: new google.maps.Point(20, 50)
        }
    });

    // Info window cho dự án
    const infoWindow = new google.maps.InfoWindow({
        content: `
            <div style="padding: 8px; min-width: 200px;">
                <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600;">${projectTitle}</h3>
                ${projectAddress ? `<p style="margin: 0; font-size: 14px; color: #666;">${projectAddress}</p>` : ''}
            </div>
        `
    });

    projectMarker.addListener('click', () => {
        infoWindow.open(map, projectMarker);
    });

    // Places service
    const placesService = new google.maps.places.PlacesService(map);
    let currentPlaceType = 'school';
    let placesMarkers = [];
    let placesList = [];

    // Place type mapping
    const placeTypeMap = {
        'school': 'school',
        'supermarket': 'supermarket',
        'park': 'park',
        'hospital': 'hospital',
        'restaurant': 'restaurant'
    };

    // Hàm xóa tất cả markers
    function clearMarkers() {
        placesMarkers.forEach(marker => marker.setMap(null));
        placesMarkers = [];
        placesList = [];
    }

    // Hàm tìm kiếm places
    function searchNearbyPlaces(placeType) {
        clearMarkers();

        if (!placeType) {
            updatePlacesList([]);
            return;
        }

        const request = {
            location: projectLocation,
            radius: 2000, // 2km
            type: placeTypeMap[placeType] || placeType
        };

        placesService.nearbySearch(request, (results, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK && results) {
                results.forEach((place, index) => {
                    if (index < 20) { // Giới hạn 20 kết quả
                        createPlaceMarker(place, placeType);
                        placesList.push(place);
                    }
                });
                updatePlacesList(placesList);
            } else if (status === google.maps.places.PlacesServiceStatus.REQUEST_DENIED) {
                const placesListElement = document.getElementById('places-list');
                const placesCountElement = document.getElementById('places-count');
                if (placesListElement && placesCountElement) {
                    placesCountElement.textContent = 'Lỗi: Places API bị từ chối. Vui lòng kiểm tra API key và bật Places API trong Google Cloud Console.';
                    placesListElement.innerHTML = `
                        <div style="padding: 16px; background: #fef2f2; border-radius: 8px; color: #dc2626; font-size: 14px;">
                            <strong>Lỗi REQUEST_DENIED:</strong> Places API không được phép truy cập. Vui lòng:
                            <ol style="margin: 8px 0 0 20px; line-height: 1.8;">
                                <li>Kiểm tra API key có đúng không</li>
                                <li>Bật Places API trong <a href="https://console.cloud.google.com/google/maps-apis" target="_blank" style="color: #dc2626;">Google Cloud Console</a></li>
                                <li>Kiểm tra API restrictions (nếu có) phải cho phép domain của bạn</li>
                            </ol>
                        </div>
                    `;
                }
            } else {
                updatePlacesList([]);
            }
        });
    }

    // Hàm tạo marker cho place
    function createPlaceMarker(place, type) {
        const iconColors = {
            'school': '#4CAF50',
            'supermarket': '#2196F3',
            'park': '#8BC34A',
            'hospital': '#F44336',
            'restaurant': '#FF9800'
        };

        const icon = {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="16" cy="16" r="14" fill="${iconColors[type] || '#666'}" stroke="#fff" stroke-width="2"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(32, 32),
            anchor: new google.maps.Point(16, 16)
        };

        const marker = new google.maps.Marker({
            position: place.geometry.location,
            map: map,
            icon: icon,
            title: place.name
        });

        // Info window cho place
        const placeInfoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 8px; min-width: 200px;">
                    <h4 style="margin: 0 0 4px; font-size: 14px; font-weight: 600;">${place.name}</h4>
                    ${place.vicinity ? `<p style="margin: 0; font-size: 12px; color: #666;">${place.vicinity}</p>` : ''}
                </div>
            `
        });

        marker.addListener('click', () => {
            placeInfoWindow.open(map, marker);
        });

        placesMarkers.push(marker);
    }

    // Hàm cập nhật danh sách places
    function updatePlacesList(places) {
        const placesListElement = document.getElementById('places-list');
        const placesCountElement = document.getElementById('places-count');

        if (!placesListElement || !placesCountElement) {
            return;
        }

        const typeLabels = {
            'school': 'trường học',
            'supermarket': 'siêu thị',
            'park': 'công viên',
            'hospital': 'bệnh viện',
            'restaurant': 'nhà hàng'
        };

        const typeLabel = typeLabels[currentPlaceType] || 'địa điểm';

        if (places.length === 0) {
            placesCountElement.textContent = `Không tìm thấy ${typeLabel} nào trong vòng 2 km`;
            placesListElement.innerHTML = '';
            return;
        }

        placesCountElement.textContent = `Có ${places.length} ${typeLabel} trong vòng 2 km`;

        placesListElement.innerHTML = places.map(place => {
            const distance = calculateDistance(
                projectLocation.lat,
                projectLocation.lng,
                place.geometry.location.lat(),
                place.geometry.location.lng()
            );
            const walkingTime = Math.round(distance / 80); // ~80m/phút đi bộ

            return `
                <div class="project-detail__map-place-item">
                    <div class="project-detail__map-place-info">
                        <h4 class="project-detail__map-place-name">${place.name}</h4>
                        <p class="project-detail__map-place-address">${place.vicinity || ''}</p>
                    </div>
                    <div class="project-detail__map-place-distance">
                        <span class="project-detail__map-place-distance-value">${distance < 1000 ? Math.round(distance) + ' m' : (distance / 1000).toFixed(2) + ' km'}</span>
                        <span class="project-detail__map-place-walking">🚶 ${walkingTime} phút đi bộ</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Hàm tính khoảng cách (Haversine formula)
    function calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371e3; // Bán kính Trái Đất tính bằng mét
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lng2 - lng1) * Math.PI / 180;

        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

        return R * c; // Khoảng cách tính bằng mét
    }

    // Xử lý click vào filter buttons
    const filterButtons = document.querySelectorAll('.project-detail__map-filter');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Xóa active class từ tất cả buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Thêm active class cho button được click
            this.classList.add('active');

            const placeType = this.dataset.type;
            currentPlaceType = placeType;

            if (placeType === 'location') {
                // Hiển thị vị trí người dùng (nếu có quyền)
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const userLocation = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            
                            // Tạo marker cho vị trí người dùng
                            clearMarkers();
                            const userMarker = new google.maps.Marker({
                                position: userLocation,
                                map: map,
                                icon: {
                                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="16" cy="16" r="12" fill="#4285F4" stroke="#fff" stroke-width="2"/>
                                            <circle cx="16" cy="16" r="6" fill="#fff"/>
                                        </svg>
                                    `),
                                    scaledSize: new google.maps.Size(32, 32),
                                    anchor: new google.maps.Point(16, 16)
                                },
                                title: 'Vị trí của bạn'
                            });
                            placesMarkers.push(userMarker);
                            
                            // Tính khoảng cách
                            const distance = calculateDistance(
                                projectLocation.lat,
                                projectLocation.lng,
                                userLocation.lat,
                                userLocation.lng
                            );
                            
                            updatePlacesList([{
                                name: 'Vị trí của bạn',
                                vicinity: '',
                                geometry: {
                                    location: {
                                        lat: () => userLocation.lat,
                                        lng: () => userLocation.lng
                                    }
                                }
                            }]);
                        },
                        () => {
                            alert('Không thể lấy vị trí của bạn. Vui lòng kiểm tra quyền truy cập vị trí.');
                        }
                    );
                } else {
                    alert('Trình duyệt của bạn không hỗ trợ Geolocation API.');
                }
            } else {
                searchNearbyPlaces(placeType);
            }
        });
    });

    // Tự động load trường học khi trang load
    searchNearbyPlaces('school');
}

