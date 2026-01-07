<?php

namespace App\Helpers;

/**
 * FormatHelper - Format angka untuk tampilan Indonesia
 * 
 * Created: 2025-11-14
 * Updated: 2025-11-14 (Enhanced untuk format Indonesia)
 * 
 * Format Indonesia:
 * - Angka bulat tanpa .00 (10, 50, 1000)
 * - Desimal pakai koma (10,5 bukan 10.5)
 * - Ribuan pakai titik (10.000 bukan 10,000)
 * - Mata uang Rupiah (Rp 10.000 bukan IDR 10,000)
 */
class FormatHelper
{
    /**
     * Format angka stock dengan format Indonesia
     * Menghilangkan .00 untuk angka bulat
     * 
     * Contoh:
     * - 10 → "10"
     * - 10.5 → "10,5"
     * - 10.50 → "10,5"
     * - 1000 → "1.000"
     * - 1000.75 → "1.000,75"
     * 
     * @param float|int|null $value
     * @param int $decimals Maximum decimal places (default: 2)
     * @return string
     */
    public static function formatStock($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '0';
        }
        
        $num = (float)$value;
        
        // Jika angka bulat (tanpa desimal), tampilkan tanpa .00
        if ($num == floor($num)) {
            return number_format($num, 0, ',', '.');
        }
        
        // Jika ada desimal, tampilkan dengan koma sebagai pemisah desimal
        // Hapus trailing zeros (10.50 jadi 10.5)
        $formatted = number_format($num, $decimals, ',', '.');
        
        // Hapus trailing zeros setelah koma
        if (strpos($formatted, ',') !== false) {
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, ',');
        }
        
        return $formatted;
    }
    
    /**
     * Format stock dengan satuan
     * 
     * Contoh:
     * - 10 kg → "10 kg"
     * - 10.5 L → "10,5 L"
     * - 1000 pcs → "1.000 pcs"
     * 
     * @param float|int|null $value
     * @param string $unit
     * @param int $decimals
     * @return string
     */
    public static function formatStockWithUnit($value, string $unit = '', int $decimals = 2): string
    {
        $formatted = self::formatStock($value, $decimals);
        return $unit ? $formatted . ' ' . $unit : $formatted;
    }
    
    /**
     * Format mata uang Rupiah (Indonesia)
     * SELALU tanpa desimal untuk uang
     * 
     * Contoh:
     * - 10000 → "Rp 10.000"
     * - 10000.50 → "Rp 10.001" (dibulatkan)
     * - 1000000 → "Rp 1.000.000"
     * 
     * @param float|int|null $value
     * @param bool $withSymbol Include "Rp" prefix
     * @return string
     */
    public static function formatCurrency($value, bool $withSymbol = true): string
    {
        if ($value === null || $value === '') {
            return $withSymbol ? 'Rp 0' : '0';
        }
        
        // Bulatkan dulu karena Rupiah tidak pakai desimal
        $rounded = round((float)$value);
        
        $formatted = number_format($rounded, 0, ',', '.');
        
        return $withSymbol ? 'Rp ' . $formatted : $formatted;
    }
    
    /**
     * Format mata uang untuk input form (nilai asli tanpa format)
     * Untuk prepopulate form dengan format yang benar
     * 
     * @param float|int|null $value
     * @return string
     */
    public static function formatCurrencyInput($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }
        
        // Return as integer string (no decimals for Rupiah)
        return (string) round((float)$value);
    }
    
    /**
     * Format persentase
     * 
     * Contoh:
     * - 10 → "10%"
     * - 10.5 → "10,5%"
     * - 10.55 → "10,55%"
     * 
     * @param float|int|null $value
     * @param int $decimals
     * @return string
     */
    public static function formatPercentage($value, int $decimals = 1): string
    {
        if ($value === null || $value === '') {
            return '0%';
        }
        
        $formatted = self::formatStock($value, $decimals);
        return $formatted . '%';
    }
    
    /**
     * Format angka biasa (general number)
     * Untuk angka yang bukan stock atau uang
     * 
     * Contoh:
     * - 10 → "10"
     * - 10.5 → "10,5"
     * - 1000000 → "1.000.000"
     * 
     * @param float|int|null $value
     * @param int $decimals
     * @return string
     */
    public static function formatNumber($value, int $decimals = 2): string
    {
        return self::formatStock($value, $decimals);
    }
    
    /**
     * Parse angka dari format Indonesia ke float
     * Untuk proses input dari user
     * 
     * Contoh:
     * - "10.000" → 10000
     * - "10.000,50" → 10000.50
     * - "10,5" → 10.5
     * 
     * @param string|null $value
     * @return float
     */
    public static function parseNumber(?string $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }
        
        // Hapus "Rp" dan spasi
        $value = str_replace(['Rp', ' '], '', $value);
        
        // Ubah titik (thousand separator) jadi kosong
        $value = str_replace('.', '', $value);
        
        // Ubah koma (decimal separator) jadi titik
        $value = str_replace(',', '.', $value);
        
        return (float) $value;
    }
    
    /**
     * Format untuk display di table/list
     * Wrapper untuk konsistensi
     * 
     * @param float|int|null $stock
     * @param string $unit
     * @return string
     */
    public static function displayStock($stock, string $unit = ''): string
    {
        return self::formatStockWithUnit($stock, $unit);
    }
    
    /**
     * Format untuk display currency di table/list
     * 
     * @param float|int|null $amount
     * @return string
     */
    public static function displayCurrency($amount): string
    {
        return self::formatCurrency($amount, true);
    }
}
