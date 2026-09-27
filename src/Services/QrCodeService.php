<?php

declare(strict_types=1);

namespace App\Services;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

final class QrCodeService
{
    public function png(string $verificationUrl): string
    {
        $qrCode = new QrCode(
            data: $verificationUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 360,
            margin: 16,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(18, 57, 79),
            backgroundColor: new Color(255, 255, 255)
        );

        return (new PngWriter())->write($qrCode)->getString();
    }
}
