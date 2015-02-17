<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\Models\Cache\City;
use PHPUnit_Framework_TestCase as TestCase;
use Doctrine\ORM\Mapping\Driver\ArrayDriver;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;

class ArrayDriverTest extends TestCase
{
    public function testCanLoadDriver()
    {
        $locator = $this->getMock(FileLocator::class);
        $locator
            ->expects($this->once())
            ->method('findMappingFile')
            ->with(City::class)
            ->willReturn(__DIR__ . '/array/Doctrine.Tests.Models.Cache.City.mapping.php');

        $classMetadata = $this
            ->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $driver        = new ArrayDriver($locator);

        $driver->loadMetadataForClass(City::class, $classMetadata);
    }
}
