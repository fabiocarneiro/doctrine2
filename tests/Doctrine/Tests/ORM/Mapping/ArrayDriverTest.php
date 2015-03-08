<?php

namespace Doctrine\Tests\ORM\Mapping;

use PHPUnit_Framework_TestCase as TestCase;
use Doctrine\ORM\Mapping\Driver\ArrayDriver;

class ArrayDriverTest extends TestCase
{
    public function testDriverLoadsMetadata()
    {
        $locator = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\FileLocator');
        $locator
            ->expects($this->once())
            ->method('findMappingFile')
            ->with('Doctrine\Tests\Models\Cache\City')
            ->willReturn(__DIR__ . '/array/Doctrine.Tests.Models.Cache.City.mapping.php');

        $classMetadata = $this
            ->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $driver        = new ArrayDriver($locator, array());

        $driver->loadMetadataForClass('Doctrine\Tests\Models\Cache\City', $classMetadata);
    }
}
