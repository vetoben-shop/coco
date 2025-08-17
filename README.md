# Metals Transaction Mini-App

This folder contains a self-contained demo for creating, viewing, and printing precious metal transactions.

## Files
- `gold-transaction-new.html` – create a transaction and calculate payout using live Gold/Silver/Platinum spot prices.
- `gold-transaction-detail.html` – view or edit a saved transaction.
- `gold-transaction-print.html` – print-friendly receipt.
- `api-gold-price.php` – returns live spot prices. Uses the same gold upstream as the original site and adds silver and platinum. Prices are cached in `gold-price-cache.json` for 60 s and reused if the upstream is unavailable.
- `api-gold-save.php` – stores transactions in `gold-transactions.json` and exposes GET/POST endpoints for reading/writing records.
- `README.md` – this file.

Runtime data files are created in the same folder:
- `gold-price-cache.json` – cached prices (auto-created).
- `gold-transactions.json` – transaction records (auto-created).

No other files or folders are used.

## Requirements
- PHP 7.4+ for the API scripts.
- A web server that can serve PHP files (e.g., `php -S localhost:8000`).

## Usage
1. Start a PHP built-in server in this directory:
   ```sh
   php -S localhost:8000
   ```
2. Open `gold-transaction-new.html` in a browser. The page fetches live prices from `api-gold-price.php`, lets you enter transaction details, and saves via `api-gold-save.php`.
3. After saving, you are redirected to `gold-transaction-detail.html?id=…` where the record is shown with both saved and current payout values.
4. Use the Print buttons to open `gold-transaction-print.html` which renders a receipt and triggers the browser print dialog.

## API Overview
### `api-gold-price.php`
Returns JSON:
```json
{
  "ok": true,
  "timestamp": 1710000000,
  "source": "https://data-asg.goldprice.org/dbXRates/USD",
  "cache": {"age": 0, "mode": "live"},
  "unit": "USD/oz",
  "gold": {"symbol": "XAUUSD", "spot": 0},
  "silver": {"symbol": "XAGUSD", "spot": 0},
  "platinum": {"symbol": "XPTUSD", "spot": 0}
}
```
If live fetch fails, cached data is returned with `cache.mode` set to `file`.

### `api-gold-save.php`
- `POST` a JSON body to create or update a record. On creation an `id` like `GT-YYYYMMDD-HHMMSS-XXXX` is generated.
- `GET?id=…` returns a single record. A bare `GET` returns `{"ok":true,"count":N}`.
- All data is persisted inside `gold-transactions.json`.

## Notes
The existing gold price fetching approach is preserved; only silver and platinum were added alongside gold.
