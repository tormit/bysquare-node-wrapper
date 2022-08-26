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
    public function __construct(array $paymentData = [], int $qrSizePx = 300)
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
                       . 'pay-caption.png';

        $qrBuilder = \Endroid\QrCode\Builder\Builder
            ::create()
            ->writer(new \Endroid\QrCode\Writer\PngWriter())
            ->writerOptions([])
            ->data($this->renderQrData())
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh())
            ->size($this->qrSizePx)
            ->margin(10)
            ->roundBlockSizeMode(new \Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin())
            ->logoPath($tmpLogoFile)
            ->logoResizeToWidth(300)
            ->labelText('Pay by Square')
            ->labelFont(new \Endroid\QrCode\Label\Font\NotoSans(20))
            ->labelAlignment(new \Endroid\QrCode\Label\Alignment\LabelAlignmentCenter());

        if (\Symfony\Component\Filesystem\Path::getExtension($tmpLogoFile, true) === 'svg') {
            $qrBuilder->logoResizeToWidth($this->qrSizePx);
            $qrBuilder->logoResizeToHeight($this->qrSizePx);
        }

        return $qrBuilder->build();
    }

    public function setBysquareBinPath(string $bysquareBinPath): void
    {
        $this->bysquareBinPath = $bysquareBinPath;
    }
}