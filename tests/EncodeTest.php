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
            'InvoiceID' => '1234567890',
            'PaymentDueDate' => '2022-08-31',
            'BIC' => 'XXXXXXXXXXX',
            'IBAN' => 'SK3112000000198742637541',
            'Amount' => 323.45,
            'CurrencyCode' => 'EUR',
            'VariableSymbol' => '1234567890',
            'ConstantSymbol' => '308',
            'Payments' => 0,
            'PaymentOptions' => 0,
            'BankAccounts' => 0
        ];

        $bsq = new \Tormit\BysquareNodeWrapper\BySquare($payment, 250);
        $bsq->setDebug(true);

        $bsqOutput = new \Symfony\Component\Console\Output\BufferedOutput();
        $bsq->setOutput($bsqOutput);

        $this->assertEquals(
            '00078000FSLK6OS1KP0B22NEL9H09QNH4K5FI01JUTOJO2303LOJ4D4V48928DJ1KIOJJQ4QGTGA24GPEHL9HJUL8IH822E6LHKM5HBJ9B7889RL9I91VOFK6V9QS1NH27VE90O0',
            $bsq->renderQrData()
        );

        $debugOutput = $bsqOutput->fetch();
        $this->assertStringContainsString('bysquare debug: binary: /usr/bin/bysquare', $debugOutput);

        $qrResult = $bsq->renderQr();

        $savedImage = \Tests\Util\TestHelper::getOutputDir() . DIRECTORY_SEPARATOR . 'test_qr.png';
        $qrResult->saveToFile($savedImage);

        $this->assertFileExists($savedImage);
        $this->assertGreaterThan(30, filesize($savedImage));
    }
}