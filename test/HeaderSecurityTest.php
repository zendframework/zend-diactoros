<?php
namespace PhlyTest\Http;

use Phly\Http\HeaderSecurity;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests for Phly\Http\HeaderSecurity.
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
        return array(
            array("This is a\n test", "This is a test"),
            array("This is a\r test", "This is a test"),
            array("This is a\n\r test", "This is a test"),
            array("This is a\r\n  test", "This is a\r\n  test"),
            array("This is a \r\ntest", "This is a test"),
            array("This is a \r\n\n test", "This is a  test"),
            array("This is a\n\n test", "This is a test"),
            array("This is a\r\r test", "This is a test"),
            array("This is a \r\r\n test", "This is a \r\n test"),
            array("This is a \r\n\r\ntest", "This is a test"),
            array("This is a \r\n\n\r\n test", "This is a \r\n test")
        );
    }

    /**
     * @dataProvider getFilterValues
     * @group ZF2015-04
     */
    public function testFiltersValuesPerRfc7230($value, $expected)
    {
        $this->assertEquals($expected, HeaderSecurity::filter($value));
    }

    public function validateValues()
    {
        return array(
            array("This is a\n test", 'assertFalse'),
            array("This is a\r test", 'assertFalse'),
            array("This is a\n\r test", 'assertFalse'),
            array("This is a\r\n  test", 'assertTrue'),
            array("This is a \r\ntest", 'assertFalse'),
            array("This is a \r\n\n test", 'assertFalse'),
            array("This is a\n\n test", 'assertFalse'),
            array("This is a\r\r test", 'assertFalse'),
            array("This is a \r\r\n test", 'assertFalse'),
            array("This is a \r\n\r\ntest", 'assertFalse'),
            array("This is a \r\n\n\r\n test", 'assertFalse')
        );
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
        return array(
            array("This is a\n test"),
            array("This is a\r test"),
            array("This is a\n\r test"),
            array("This is a \r\ntest"),
            array("This is a \r\n\n test"),
            array("This is a\n\n test"),
            array("This is a\r\r test"),
            array("This is a \r\r\n test"),
            array("This is a \r\n\r\ntest"),
            array("This is a \r\n\n\r\n test")
        );
    }

    /**
     * @dataProvider assertValues
     * @group ZF2015-04
     */
    public function testAssertValidRaisesExceptionForInvalidValue($value)
    {
        $this->setExpectedException('InvalidArgumentException');
        HeaderSecurity::assertValid($value);
    }
}
