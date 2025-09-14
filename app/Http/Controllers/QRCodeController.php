<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Services\QRCodeService;
use Illuminate\Http\Request;

class QRCodeController extends Controller
{
    public function printTableQR($tableId)
    {
        $table = Table::findOrFail($tableId);
        $url = url("/order/{$table->name}");
        
        // Generate QR code using service
        $qrCode = QRCodeService::generate($url, 'svg', 300);
            
        return view('qr-print', compact('table', 'qrCode', 'url'));
    }
    
    public function downloadTableQR($tableId)
    {
        $table = Table::findOrFail($tableId);
        $url = url("/order/{$table->name}");
        
        // Try PNG first, fallback to SVG
        $format = QRCodeService::getBestFormat();
        $qrCode = QRCodeService::generate($url, $format, 500);
        
        if ($format === 'png') {
            return response($qrCode)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', "attachment; filename=\"qr-table-{$table->name}.png\"");
        } else {
            return response($qrCode)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', "attachment; filename=\"qr-table-{$table->name}.svg\"");
        }
    }
}