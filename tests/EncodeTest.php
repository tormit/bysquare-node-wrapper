<?php
/**
 * @author Tormi Talv <tormi.talv@sportlyzer.com> 2022
 * @since 2022-08-26 14:28:17
 * @version 1.0
 */

namespace Tests;

class EncodeTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        \Tests\Util\TestHelper::prepareOutput();
    }

    /**
     * @covers \Tormit\BysquareNodeWrapper\BySquare::renderQrData
     */
    public function testQrData()
    {
        $payment = [
            'invoiceId' => '24070111',
            'payments' =>
                [
                    0 =>
                        [
                            'type' => 1,
                            'amount' => 123.45,
                            'bankAccounts' =>
                                [
                                    0 =>
                                        [
                                            'iban' => 'SK7886440391969053985795',
                                            'bic' => 'GXMNTU8Q',
                                        ],
                                ],
                            'currencyCode' => 'EUR',
                            'variableSymbol' => '2620011172460',
                        ],
                ],
        ];

        $bsq = new \Tormit\BysquareNodeWrapper\BySquare($payment, 250);
        $bsq->setDebug(true);

        $bsqOutput = new \Symfony\Component\Console\Output\BufferedOutput();
        $bsq->setOutput($bsqOutput);

        $this->assertEquals(
            '0005I0005KSGF2HH03QMJJ207H80N0MBU0B0RGUL4MV1J1NAC9PD591SNV639MIV7GVPV3BQBJCDO22A6MU92HQ8LLQ0ET3IGSRNN79FQIT4F3UAB2B3VLA8H8N5LEB8ATD1FVVV0I54000',
            $bsq->renderQrData()
        );

        $debugOutput = $bsqOutput->fetch();
        $this->assertStringContainsString('bysquare debug: binary:', $debugOutput);

        $qrResult = $bsq->renderQr();

        $savedImage = \Tests\Util\TestHelper::getOutputDir() . DIRECTORY_SEPARATOR . 'test_qr.png';
        $qrResult->saveToFile($savedImage);

        $this->assertFileExists($savedImage);
        $this->assertGreaterThan(30, filesize($savedImage));
    }
}