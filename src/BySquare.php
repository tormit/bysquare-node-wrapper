<?php
declare(strict_types=1);
/**
 * @author Tormi Talv <tormi.talv@sportlyzer.com> 2022
 * @since 2022-08-26 12:47:14
 * @version 1.0
 */

namespace Tormit\BysquareNodeWrapper;

class BySquare
{
    private array $paymentData;
    private int $qrSizePx;
    private string $bysquareBinPath = '/usr/bin/bysquare';

    /**
     * @param array $paymentData
     * @param int $qrSizePx
     * @see https://github.com/xseman/bysquare/blob/develop/README.md#Model
     */
    public function __construct(array $paymentData = [], int $qrSizePx = 250)
    {
        $this->paymentData = $paymentData;
        $this->qrSizePx = $qrSizePx;
    }

    public function renderQrData(): string
    {
        $cmd = new \Symfony\Component\Process\Process([$this->bysquareBinPath], null, null, json_encode($this->paymentData));
        $cmd->mustRun();

        return trim($cmd->getOutput());
    }

    public function renderQr(): \Endroid\QrCode\Writer\Result\ResultInterface
    {
        $tmpLogoFile = __DIR__
                       . DIRECTORY_SEPARATOR
                       . '..'
                       . DIRECTORY_SEPARATOR
                       . 'assets'
                       . DIRECTORY_SEPARATOR
                       . 'paybysquare_full_250.png';

        $qrBuilder = \Endroid\QrCode\Builder\Builder
            ::create()
            ->writer(new \Endroid\QrCode\Writer\PngWriter())
            ->writerOptions([])
            ->data($this->renderQrData())
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh())
            ->size($this->qrSizePx)
            ->margin(3)
            ->roundBlockSizeMode(new \Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin())
            //->labelText('Pay by Square')
            //->labelFont(new \Endroid\QrCode\Label\Font\NotoSans(20))
            //->labelAlignment(new \Endroid\QrCode\Label\Alignment\LabelAlignmentCenter())
        ;


        //if (\Symfony\Component\Filesystem\Path::getExtension($tmpLogoFile, true) === 'svg') {
        //    $qrBuilder->logoResizeToWidth($this->qrSizePx);
        //    $qrBuilder->logoResizeToHeight($this->qrSizePx);
        //}

        $result = $qrBuilder->build();


        if (!$result instanceof \Endroid\QrCode\Writer\Result\PngResult) {
            throw new BySquareException('Writer must be png');
        }

        // Add logo to custom location. No option in ender/qr-code.
        return $this->addLogo(new \Endroid\QrCode\Logo\Logo($tmpLogoFile, null, null, false), $result);
    }

    public function setBysquareBinPath(string $bysquareBinPath): void
    {
        $this->bysquareBinPath = $bysquareBinPath;
    }

    private function addLogo(
        \Endroid\QrCode\Logo\LogoInterface $logo,
        \Endroid\QrCode\Writer\Result\PngResult $result
    ): \Endroid\QrCode\Writer\Result\PngResult {
        /** @var resource $image */
        $qrImage = $result->getImage();

        $logoImageData = \Endroid\QrCode\ImageData\LogoImageData::createForLogo($logo);

        $qrBaseWidth = imagesx($qrImage);
        $qrBaseHeight = imagesy($qrImage);

        // Resize canvas
        $resizedCanvasQr = imagecreatetruecolor($qrBaseWidth, $qrBaseHeight + 50);
        imagefill($resizedCanvasQr, 0, 0, imagecolorallocate($resizedCanvasQr, 255, 255, 255));
        imagecopy($resizedCanvasQr, $qrImage, 0, 0, 0, 0, $qrBaseWidth, $qrBaseHeight);

        // Add logo
        $bottomOffsetP = 5;
        $rightOffsetP = 2;

        $dstX = $qrBaseWidth * (1 - $rightOffsetP / 100) - $logoImageData->getWidth();
        $dstY = $qrBaseHeight * (1 - $bottomOffsetP / 100);
        imagecopyresampled(
            $resizedCanvasQr,
            $logoImageData->getImage(),
            (int)$dstX,
            // Bottom of the qr
            (int)$dstY,
            0,
            0,
            $logoImageData->getWidth(),
            $logoImageData->getHeight(),
            imagesx($logoImageData->getImage()),
            imagesy($logoImageData->getImage())
        );


        return new \Endroid\QrCode\Writer\Result\PngResult($resizedCanvasQr);
    }
}