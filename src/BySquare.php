<?php
declare(strict_types=1);

/**
 * @author Tormi Talv <tormi.talv@sportlyzer.com> 2022
 * @since 2022-08-26 12:47:14
 * @since 2024-07-20 bySquare 2.x support
 * @version 1.0
 */

namespace Tormit\BysquareNodeWrapper;

class BySquare
{
    private array $paymentData;
    private int $qrSizePx;
    private string $bysquareBinPath = '/usr/bin/bysquare';
    private ?\Symfony\Component\Console\Output\OutputInterface $output;
    private bool $debug = false;

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
        $paymentData = json_encode($this->paymentData);

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'payment_data_');
        $tempFilePath = $tempFile . '.json';
        rename($tempFile, $tempFilePath);

        // Write payment data to the temporary file
        file_put_contents($tempFilePath, $paymentData);

        if ($this->debug) {
            $this->getOutput()->writeln(sprintf('bysquare debug: binary: %s', $this->bysquareBinPath));
            $this->getOutput()->writeln(sprintf('bysquare debug: size: %d', $this->qrSizePx));
            $this->getOutput()->writeln(sprintf('bysquare debug: data: %s', $paymentData));
        }

        try {
            $command = $this->bysquareBinPath . ' --encode ' . escapeshellarg($tempFilePath) . ' 2>&1';
            $output = $this->runCommand($command);
        } catch (\Exception $e) {
            $this->getOutput()->writeln(sprintf('bysquare error: %s', $e->getMessage()));
            throw $e;
        } finally {
            // Clean up the temporary file
            unlink($tempFilePath);
        }

        return $output;
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


        $qrData = $this->renderQrData();

        if ($this->debug) {
            $this->getOutput()->writeln(sprintf('bysquare debug: encodedData: %s', $qrData));
        }
        $qrBuilder = \Endroid\QrCode\Builder\Builder
            ::create()
            ->writer(new \Endroid\QrCode\Writer\PngWriter())
            ->writerOptions([])
            ->data($qrData)
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh())
            ->size($this->qrSizePx)
            ->margin(3)
            ->roundBlockSizeMode(new \Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin());

        $result = $qrBuilder->build();

        if (!$result instanceof \Endroid\QrCode\Writer\Result\PngResult) {
            throw new BySquareException('Writer must be png');
        }

        // Add logo to custom location. No option in ender/qr-code.
        return $this->addLogo(new \Endroid\QrCode\Logo\Logo($tmpLogoFile, null, null, false), $result);
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
        $bottomOffsetP = 4;
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

    public function decodeQrData(string $qrString): array
    {
        $command = $this->bysquareBinPath . ' --decode ' . escapeshellarg($qrString) . ' 2>&1';
        $output = $this->runCommand($command);

        return json_decode($output, true);
    }

    /**
     * @param string $bysquareBinPath
     * @return static
     */
    public function setBysquareBinPath(string $bysquareBinPath): BySquare
    {
        $this->bysquareBinPath = $bysquareBinPath;

        return $this;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface|null $output
     * @return static
     */
    public function setOutput(?\Symfony\Component\Console\Output\OutputInterface $output): BySquare
    {
        $this->output = $output;

        return $this;
    }

    private function getOutput(): ?\Symfony\Component\Console\Output\OutputInterface
    {
        return $this->output ?? new \Symfony\Component\Console\Output\NullOutput();
    }

    /**
     * @param bool $debug
     * @return static
     */
    public function setDebug(bool $debug): BySquare
    {
        $this->debug = $debug;

        return $this;
    }

    public function getDescriptorspec(): array
    {
        return [
            0 => ['pty'],              // PTY for stdin
            1 => ['pty'],              // PTY for stdout
            2 => ['pty']               // PTY for stderr
        ];
    }

    public function runCommand(string $command): string
    {
        $process = proc_open(
            $command,
            $this->getDescriptorspec(),
            $pipes
        );

        if (!is_resource($process)) {
            throw new \RuntimeException('Process not open');
        }

        $output = @stream_get_contents($pipes[1]);  // Read from the PTY stdout
        fclose($pipes[0]);  // Close stdin PTY
        fclose($pipes[1]);  // Close stdout PTY
        fclose($pipes[2]);  // Close stderr PTY
        $exitCode = proc_close($process);

        if (!$output) {
            throw new \RuntimeException('Command execution failed: No output');
        }

        if ($exitCode !== 0 || str_contains($output, 'file') || str_contains($output, 'Error')) {
            throw new \RuntimeException(sprintf('Command execution failed(%d): %s', $exitCode, $output));
        }

        $this->getOutput()->writeln('Raw output: ' . $output);

        return trim($output);
    }
}