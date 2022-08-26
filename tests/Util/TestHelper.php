<?php
declare(strict_types=1);
/**
 * @author Tormi Talv <tormi.talv@sportlyzer.com> 2022
 * @since 2022-08-25 19:04:50
 * @version 1.0
 */

namespace Tests\Util;

class TestHelper
{
	public static function prepareOutput(): void
	{
		(new \Symfony\Component\Filesystem\Filesystem())->mkdir(self::getOutputDir());
	}

	public static function getOutputDir(): string
	{
		return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'output';
	}
}
