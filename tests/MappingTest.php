<?php

namespace yii2tech\tests\unit\embedded;

use yii2tech\embedded\Mapping;

class MappingTest extends TestCase
{
    public function testSetupValue()
    {
        $owner = new \stdClass();

        $mapping = new Mapping();
        $mapping->multiple = false;

        $value = new \stdClass();
        $mapping->setValue($value);
        $this->assertSame($value, $mapping->getValue($owner));

        $this->expectException('yii\base\InvalidArgumentException');
        $mapping->setValue('foo');
    }

    public function testSetupMultipleValue()
    {
        $owner = new \stdClass();

        $mapping = new Mapping();
        $mapping->multiple = true;

        $value = [
            new \stdClass(),
            new \stdClass(),
        ];
        $mapping->setValue($value);
        $actualValue = $mapping->getValue($owner);
        $this->assertTrue($actualValue instanceof \ArrayAccess);

        $this->expectException('yii\base\InvalidArgumentException');
        $mapping->setValue('foo');
    }

    /**
     * @depends testSetupValue
     */
    public function testIsValueInitialized()
    {
        $mapping = new Mapping();
        $mapping->multiple = false;

        $this->assertFalse($mapping->getIsValueInitialized());

        $value = new \stdClass();
        $mapping->setValue($value);

        $this->assertTrue($mapping->getIsValueInitialized());
    }
}