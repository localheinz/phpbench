<?php

/*
 * This file is part of the PHPBench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Tests\Unit\Benchmark\Remote;

use PhpBench\Benchmark\Remote\ReflectionClass;
use PhpBench\Benchmark\Remote\ReflectionHierarchy;

class ReflectionHierarchyTest extends \PHPUnit_Framework_TestCase
{
    private $hierarchy;
    private $reflection1;
    private $reflection2;

    public function setUp()
    {
        $this->hierarchy = new ReflectionHierarchy();
        $this->reflection1 = new ReflectionClass();
        $this->reflection2 = new ReflectionClass();
    }

    /**
     * It can have reflection classes added to it.
     * It is iterable.
     * It should get the top reflection.
     */
    public function testAddReflectionsAndIterate()
    {
        $this->hierarchy->addReflectionClass($this->reflection1);
        $this->hierarchy->addReflectionClass($this->reflection2);

        foreach ($this->hierarchy as $index => $reflectionClass) {
            $this->assertInstanceOf('PhpBench\Benchmark\Remote\ReflectionClass', $reflectionClass);
        }

        $this->assertEquals(1, $index);

        $top = $this->hierarchy->getTop();
        $this->assertSame($this->reflection1, $top);
    }

    /**
     * It should throw an exception if there are no classes and the top is requested.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot get top
     */
    public function testGetTopNoClasses()
    {
        $this->hierarchy->getTop();
    }

    /**
     * It can determine if a method exists.
     */
    public function testHasMethod()
    {
        $this->reflection1->methods['foobar'] = true;
        $this->hierarchy->addReflectionClass($this->reflection1);
        $this->hierarchy->addReflectionClass($this->reflection2);

        $this->assertTrue($this->hierarchy->hasMethod('foobar'));
        $this->assertFalse($this->hierarchy->hasMethod('barfoo'));

        $this->reflection2->methods['barfoo'] = true;
        $this->assertTrue($this->hierarchy->hasMethod('barfoo'));
    }
}
