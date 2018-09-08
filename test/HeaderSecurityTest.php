<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\HeaderSecurity;

/**
 * Tests for Zend\Diactoros\HeaderSecurity.
 *
 * Tests are largely derived from those for Zend\Http\Header\HeaderValue in
 * Zend Framework, released with the copyright and license below.
 *
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
class HeaderSecurityTest extends TestCase
{
    /**
     * Data for filter value
     */
    public function getFilterValues()
    {
        return [
            ["This is a\n test", "This is a test"],
            ["This is a\r test", "This is a test"],
            ["This is a\n\r test", "This is a test"],
            ["This is a\r\n  test", "This is a\r\n  test"],
            ["This is a \r\ntest", "This is a test"],
            ["This is a \r\n\n test", "This is a  test"],
            ["This is a\n\n test", "This is a test"],
            ["This is a\r\r test", "This is a test"],
            ["This is a \r\r\n test", "This is a \r\n test"],
            ["This is a \r\n\r\ntest", "This is a test"],
            ["This is a \r\n\n\r\n test", "This is a \r\n test"]
        ];
    }

    /**
     * @dataProvider getFilterValues
     * @group ZF2015-04
     */
    public function testFiltersValuesPerRfc7230($value, $expected)
    {
        $this->assertSame($expected, HeaderSecurity::filter($value));
    }

    public function validateValues()
    {
        return [
            ["This is a\n test", 'assertFalse'],
            ["This is a\r test", 'assertFalse'],
            ["This is a\n\r test", 'assertFalse'],
            ["This is a\r\n  test", 'assertTrue'],
            ["This is a \r\ntest", 'assertFalse'],
            ["This is a \r\n\n test", 'assertFalse'],
            ["This is a\n\n test", 'assertFalse'],
            ["This is a\r\r test", 'assertFalse'],
            ["This is a \r\r\n test", 'assertFalse'],
            ["This is a \r\n\r\ntest", 'assertFalse'],
            ["This is a \r\n\n\r\n test", 'assertFalse'],
            ["This is a \xFF test", 'assertFalse'],
            ["This is a \x7F test", 'assertFalse'],
            ["This is a \x7E test", 'assertTrue'],
        ];
    }

    /**
     * @dataProvider validateValues
     * @group ZF2015-04
     */
    public function testValidatesValuesPerRfc7230($value, $assertion)
    {
        $this->{$assertion}(HeaderSecurity::isValid($value));
    }

    public function assertValues()
    {
        return [
            ["This is a\n test"],
            ["This is a\r test"],
            ["This is a\n\r test"],
            ["This is a \r\ntest"],
            ["This is a \r\n\n test"],
            ["This is a\n\n test"],
            ["This is a\r\r test"],
            ["This is a \r\r\n test"],
            ["This is a \r\n\r\ntest"],
            ["This is a \r\n\n\r\n test"]
        ];
    }

    /**
     * @dataProvider assertValues
     * @group ZF2015-04
     */
    public function testAssertValidRaisesExceptionForInvalidValue($value)
    {
        $this->expectException(InvalidArgumentException::class);

        HeaderSecurity::assertValid($value);
    }
}
