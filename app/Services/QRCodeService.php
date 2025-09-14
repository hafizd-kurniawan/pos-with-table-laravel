<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Generate QR code with fallback support
     */
    public static function generate($data, $format = 'svg', $size = 200)
    {
        try {
            return QrCode::format($format)
                ->size($size)
                ->margin(1)
                ->generate($data);
        } catch (\Exception $e) {
            // Fallback to SVG if other formats fail
            if ($format !== 'svg') {
                return QrCode::format('svg')
                    ->size($size)
                    ->margin(1)
                    ->generate($data);
            }
            throw $e;
        }
    }
    
    /**
     * Generate data URL for QR code
     */
    public static function generateDataUrl($data, $format = 'svg', $size = 200)
    {
        $qrCode = self::generate($data, $format, $size);
        
        if ($format === 'svg') {
            return 'data:image/svg+xml;base64,' . base64_encode($qrCode);
        } else {
            return 'data:image/png;base64,' . base64_encode($qrCode);
        }
    }
    
    /**
     * Check if imagick is available
     */
    public static function isImagickAvailable()
    {
        return extension_loaded('imagick');
    }
    
    /**
     * Get best available format
     */
    public static function getBestFormat()
    {
        return self::isImagickAvailable() ? 'png' : 'svg';
    }
}
