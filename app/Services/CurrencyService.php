<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    protected array $currencies = [];

    public function __construct()
    {
        $this->currencies = Cache::remember('currencies_active', 3600, function () {
            return Currency::active()->get()->keyBy('code')->toArray();
        });
    }

    /**
     * Convert amount from USD to target currency.
     */
    public function convert(float $amount, string $toCurrency = 'USD'): float
    {
        if ($toCurrency === 'USD' || !isset($this->currencies[$toCurrency])) {
            return $amount;
        }

        return $amount * $this->currencies[$toCurrency]['exchange_rate'];
    }

    /**
     * Format an amount in the given currency.
     */
    public function format(float $amountUsd, string $currencyCode = 'USD'): string
    {
        $converted = $this->convert($amountUsd, $currencyCode);
        $currency = $this->currencies[$currencyCode] ?? $this->currencies['USD'];

        $formatted = number_format($converted, $currency['decimal_places'], '.', ',');
        return $currency['symbol'] . ' ' . $formatted;
    }

    /**
     * Get all active currencies for the selector.
     */
    public function getAvailable(): array
    {
        return $this->currencies;
    }

    /**
     * Get the current user's selected currency code.
     */
    public static function getCurrentCode(): string
    {
        // Check query param (via middleware attribute) first, then cookie
        return request()->attributes->get('currency')
            ?? request()->query('currency')
            ?? request()->cookie('currency', 'USD');
    }

    /**
     * Get exchange rate for a currency.
     */
    public function getRate(string $currencyCode): float
    {
        return $this->currencies[$currencyCode]['exchange_rate'] ?? 1.0;
    }

    /**
     * Clear the cache (after admin updates rates).
     */
    public static function clearCache(): void
    {
        Cache::forget('currencies_active');
    }
}
