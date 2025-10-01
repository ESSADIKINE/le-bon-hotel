=== Le Bon Hotel ===
Contributors: lebonplugins
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Le Bon Hotel transforms WordPress into a hotel directory complete with REST API integration, booking widgets, and beautiful front-end listings.

== Description ==

* Manage hotels with the dedicated **Hotel** custom post type (`lbhotel_hotel`).
* Capture detailed hotel data: star rating, address, check-in/out, room inventory, amenities, contact details, galleries, and booking URLs.
* Display hotels on the front-end with responsive cards, an interactive map data feed, and Gutenberg-ready shortcodes.
* Integrate via the REST API namespace `lbhotel/v1` for decoupled experiences and headless builds.
* Migrate existing "Le Bon Resto" restaurants with one click and keep all gallery images, ratings, and addresses.

== Installation ==

1. Upload the `le-bon-hotel` folder to the `/wp-content/plugins/` directory or install via the WordPress admin.
2. Activate the plugin through the **Plugins** screen.
3. Visit **Hotels → Settings** to configure defaults (check-in/out time, currency, booking widget).
4. If you previously used the restaurant version, click **Run migration** on the settings page to convert data.

== Shortcodes ==

* `[lbhotel_list limit=10 city="Casablanca" stars="4"]`
* `[lbhotel_single id=123]`

== REST API ==

Fetch hotels with `GET /wp-json/lbhotel/v1/hotels`. Example response schema:

```
{
  "id": 42,
  "title": "Le Bon Hotel Central",
  "permalink": "https://example.com/hotel/le-bon-hotel-central",
  "star_rating": 4,
  "currency": "MAD",
  "meta": {
    "address": "123 Boulevard Hassan II",
    "city": "Casablanca",
    "checkin_time": "14:00",
    "checkout_time": "12:00",
    "rooms_total": 120,
    "avg_price_per_night": 850,
    "has_free_breakfast": true,
    "has_parking": true,
    "booking_url": "https://bookings.example.com/le-bon-hotel-central"
  }
}
```

== Migration ==

The `lbhotel_migrate_from_restaurant()` helper maps restaurant meta (address, rating, gallery images, etc.) to the new hotel structure. Trigger it from **Hotels → Settings**.

== Uninstall ==

Removing the plugin through the WordPress UI deletes plugin options and custom metadata created by Le Bon Hotel.
