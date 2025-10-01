const hotels = [
    {
        name: "Riad Andalus",
        city: "Marrakesh",
        region: "Marrakesh-Safi",
        country: "Morocco",
        rating: 5,
        price: 320,
        distance: 4,
        availableFrom: "2024-06-01",
        description:
            "Immerse yourself in opulent courtyards and hand-crafted zellige mosaics moments from Jemaa el-Fnaa.",
        coordinates: { lat: 31.6295, lng: -7.9811 },
        images: [
            "https://images.unsplash.com/photo-1496417263034-38ec4f0b665a?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1528909514045-2fa4ac7a08ba?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1526546344624-0f3c8533d1f9?auto=format&fit=crop&w=900&q=60"
        ],
        booking_url: "https://example.com/booking/riad-andalus",
        details_url: "https://example.com/hotels/riad-andalus"
    },
    {
        name: "Kasbah Panorama",
        city: "Ouarzazate",
        region: "Drâa-Tafilalet",
        country: "Morocco",
        rating: 4,
        price: 180,
        distance: 12,
        availableFrom: "2024-05-18",
        description:
            "Desert-inspired suites overlooking ancient kasbahs with sunrise breakfasts on the terrace.",
        coordinates: { lat: 30.9335, lng: -6.9370 },
        images: [
            "https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1464036388609-747537735ef5?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1543353071-873f17a7a088?auto=format&fit=crop&w=900&q=60"
        ],
        booking_url: "https://example.com/booking/kasbah-panorama",
        details_url: "https://example.com/hotels/kasbah-panorama"
    },
    {
        name: "Atlantic Breeze Resort",
        city: "Essaouira",
        region: "Marrakesh-Safi",
        country: "Morocco",
        rating: 4.5,
        price: 210,
        distance: 8,
        availableFrom: "2024-05-04",
        description:
            "Seaside elegance blending Portuguese fort walls with contemporary Moroccan artistry.",
        coordinates: { lat: 31.5085, lng: -9.7595 },
        images: [
            "https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1469796466635-455ede028aca?auto=format&fit=crop&w=900&q=60"
        ],
        booking_url: "https://example.com/booking/atlantic-breeze",
        details_url: "https://example.com/hotels/atlantic-breeze"
    },
    {
        name: "Sahara Oasis Lodge",
        city: "Merzouga",
        region: "Drâa-Tafilalet",
        country: "Morocco",
        rating: 4.2,
        price: 145,
        distance: 18,
        availableFrom: "2024-04-26",
        description:
            "Luxury desert tents with private plunge pools and star-studded night skies.",
        coordinates: { lat: 31.1000, lng: -4.0000 },
        images: [
            "https://images.unsplash.com/photo-1533105079780-92b9be482077?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1466096115517-bceecbfb6fde?auto=format&fit=crop&w=900&q=60"
        ],
        booking_url: "https://example.com/booking/sahara-oasis",
        details_url: "https://example.com/hotels/sahara-oasis"
    },
    {
        name: "Atlas Summit Hotel",
        city: "Imlil",
        region: "Marrakesh-Safi",
        country: "Morocco",
        rating: 3.8,
        price: 125,
        distance: 6,
        availableFrom: "2024-05-27",
        description:
            "Mountain retreat with cedar-wood hammams and panoramic views of Toubkal National Park.",
        coordinates: { lat: 31.1325, lng: -7.9200 },
        images: [
            "https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1455587734955-081b22074882?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=60"
        ],
        booking_url: "https://example.com/booking/atlas-summit",
        details_url: "https://example.com/hotels/atlas-summit"
    },
    {
        name: "Medina Garden Suites",
        city: "Fes",
        region: "Fès-Meknès",
        country: "Morocco",
        rating: 5,
        price: 290,
        distance: 14,
        availableFrom: "2024-04-19",
        description:
            "Restored riad with tranquil fountains, aromatic orange blossom patios, and hammam spa treatments.",
        coordinates: { lat: 34.0331, lng: -5.0003 },
        images: [
            "https://images.unsplash.com/photo-1528909514045-2fa4ac7a08ba?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=60",
            "https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=900&q=60"
        ],
        booking_url: "https://example.com/booking/medina-garden",
        details_url: "https://example.com/hotels/medina-garden"
    }
];

const ITEMS_PER_PAGE = 3;
let currentPage = 1;
let filteredHotels = [...hotels];
const sliderIntervals = [];

const hotelListEl = document.getElementById('hotelList');
const hotelCountEl = document.getElementById('hotelCount');
const searchInput = document.getElementById('searchInput');
const distanceSelect = document.getElementById('distanceSelect');
const ratingSelect = document.getElementById('ratingSelect');
const sortSelect = document.getElementById('sortSelect');
const prevPageBtn = document.getElementById('prevPage');
const nextPageBtn = document.getElementById('nextPage');

function formatCount(count) {
    return `${count} hotel${count === 1 ? '' : 's'} found`;
}

function createStarRating(rating) {
    const rounded = Math.round(rating);
    return '★'.repeat(rounded).padEnd(5, '☆');
}

function clearSliderIntervals() {
    while (sliderIntervals.length) {
        const interval = sliderIntervals.pop();
        clearInterval(interval);
    }
}

function initializeSliders() {
    document.querySelectorAll('.hotel-slider').forEach((slider) => {
        const images = slider.querySelectorAll('img');
        if (!images.length) return;
        let current = 0;
        images[current].classList.add('active');
        const interval = setInterval(() => {
            images[current].classList.remove('active');
            current = (current + 1) % images.length;
            images[current].classList.add('active');
        }, 4000);
        sliderIntervals.push(interval);
    });
}

function renderHotels(list) {
    clearSliderIntervals();
    hotelListEl.innerHTML = '';

    if (!list.length) {
        const empty = document.createElement('div');
        empty.className = 'empty-state';
        empty.textContent = 'No hotels match your filters. Adjust your search to explore more stays.';
        hotelListEl.appendChild(empty);
        hotelCountEl.textContent = formatCount(0);
        updatePaginationButtons(0);
        return;
    }

    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    const pageItems = list.slice(start, end);

    pageItems.forEach((hotel) => {
        const card = document.createElement('article');
        card.className = 'hotel-card';

        const slider = document.createElement('div');
        slider.className = 'hotel-slider';
        hotel.images.forEach((src, index) => {
            const img = document.createElement('img');
            img.src = src;
            img.alt = `${hotel.name} image ${index + 1}`;
            if (index === 0) {
                img.classList.add('active');
            }
            slider.appendChild(img);
        });

        const info = document.createElement('div');
        info.className = 'hotel-info';

        const name = document.createElement('h2');
        name.textContent = hotel.name;

        const location = document.createElement('p');
        location.className = 'hotel-location';
        location.textContent = `${hotel.city}, ${hotel.region}, ${hotel.country}`;

        const rating = document.createElement('p');
        rating.className = 'hotel-rating';
        rating.textContent = createStarRating(hotel.rating);

        const price = document.createElement('p');
        price.className = 'hotel-price';
        price.textContent = `$${hotel.price} / night`;

        const description = document.createElement('p');
        description.className = 'hotel-description';
        description.textContent = hotel.description;

        info.appendChild(name);
        info.appendChild(location);
        info.appendChild(rating);
        info.appendChild(price);
        info.appendChild(description);

        const actions = document.createElement('div');
        actions.className = 'hotel-actions';

        const reserve = document.createElement('a');
        reserve.className = 'reserve-btn';
        reserve.href = hotel.booking_url;
        reserve.target = '_blank';
        reserve.rel = 'noopener noreferrer';
        reserve.textContent = 'Reserve Booking';

        const map = document.createElement('a');
        map.className = 'map-btn';
        map.href = `https://www.google.com/maps?q=${hotel.coordinates.lat},${hotel.coordinates.lng}`;
        map.target = '_blank';
        map.rel = 'noopener noreferrer';
        map.textContent = 'Show on Map';

        const details = document.createElement('a');
        details.className = 'details-btn';
        details.href = hotel.details_url;
        details.target = '_blank';
        details.rel = 'noopener noreferrer';
        details.textContent = 'View Details';

        actions.appendChild(reserve);
        actions.appendChild(map);
        actions.appendChild(details);

        card.appendChild(slider);
        card.appendChild(info);
        card.appendChild(actions);

        hotelListEl.appendChild(card);
    });

    hotelCountEl.textContent = formatCount(list.length);
    updatePaginationButtons(list.length);
    initializeSliders();
}

function updatePaginationButtons(totalItems) {
    const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE) || 1;
    prevPageBtn.disabled = currentPage <= 1;
    nextPageBtn.disabled = currentPage >= totalPages;
}

function applyFilters() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    const distanceValue = distanceSelect.value ? parseInt(distanceSelect.value, 10) : null;
    const ratingValue = ratingSelect.value ? parseFloat(ratingSelect.value) : null;

    filteredHotels = hotels.filter((hotel) => {
        const matchesSearch = searchTerm
            ? hotel.name.toLowerCase().includes(searchTerm) || hotel.city.toLowerCase().includes(searchTerm)
            : true;
        const matchesDistance = distanceValue ? hotel.distance <= distanceValue : true;
        const matchesRating = ratingValue ? hotel.rating >= ratingValue : true;
        return matchesSearch && matchesDistance && matchesRating;
    });

    applySort();
    currentPage = 1;
    renderHotels(filteredHotels);
}

function applySort() {
    const sortValue = sortSelect.value;
    const list = filteredHotels;

    switch (sortValue) {
        case 'dateAsc':
            list.sort((a, b) => new Date(a.availableFrom) - new Date(b.availableFrom));
            break;
        case 'dateDesc':
            list.sort((a, b) => new Date(b.availableFrom) - new Date(a.availableFrom));
            break;
        case 'distanceAsc':
            list.sort((a, b) => a.distance - b.distance);
            break;
        case 'distanceDesc':
            list.sort((a, b) => b.distance - a.distance);
            break;
        case 'ratingAsc':
            list.sort((a, b) => a.rating - b.rating);
            break;
        case 'ratingDesc':
            list.sort((a, b) => b.rating - a.rating);
            break;
        default:
            break;
    }
}

searchInput.addEventListener('input', () => {
    applyFilters();
});

distanceSelect.addEventListener('change', () => {
    applyFilters();
});

ratingSelect.addEventListener('change', () => {
    applyFilters();
});

sortSelect.addEventListener('change', () => {
    applySort();
    currentPage = 1;
    renderHotels(filteredHotels);
});

prevPageBtn.addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage -= 1;
        renderHotels(filteredHotels);
    }
});

nextPageBtn.addEventListener('click', () => {
    const totalPages = Math.ceil(filteredHotels.length / ITEMS_PER_PAGE);
    if (currentPage < totalPages) {
        currentPage += 1;
        renderHotels(filteredHotels);
    }
});

// Initialize page
applySort();
renderHotels(filteredHotels);
