<?php

/*
 * This file is part of the webmozart/key-value-store package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\KeyValueStore\Tests\Decorator;

use Doctrine\Common\Cache\Cache;
use PHPUnit_Framework_MockObject_MockObject;
use Webmozart\KeyValueStore\Api\InvalidKeyException;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\KeyValueStore\Api\NoSuchKeyException;
use Webmozart\KeyValueStore\Api\SerializationFailedException;
use Webmozart\KeyValueStore\Api\WriteException;
use Webmozart\KeyValueStore\Decorator\CachingDecorator;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CachingDecoratorTest extends AbstractDecoratorTest
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Cache
     */
    private $cache;

    protected function createDecorator(KeyValueStore $innerStore)
    {
        $this->cache = $this->getMock('Webmozart\KeyValueStore\Tests\Fixtures\TestClearableCache');

        return new CachingDecorator($this->innerStore, $this->cache);
    }

    public function testGetDelegate()
    {
        // We test the get in another way
        $this->assertTrue(true);
    }

    public function testGetMultipleDelegate()
    {
        // We test the getMultiple in another way
        $this->assertTrue(true);
    }

    public function testGetMultipleOrFailDelegate()
    {
        // We test the getMultipleOrFail in another way
        $this->assertTrue(true);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateFailsIfNeitherClearableNorFlushable()
    {
        $this->cache = $this->getMock('Doctrine\Common\Cache\Cache');

        new CachingDecorator($this->innerStore, $this->cache);
    }

    public function testSetWritesToCache()
    {
        $this->innerStore->expects($this->once())
            ->method('set')
            ->with('key', 'value');

        $this->cache->expects($this->once())
            ->method('save')
            ->with('key', 'value');

        $this->decorator->set('key', 'value');
    }

    public function testSetWritesTtlIfGiven()
    {
        $this->decorator = new CachingDecorator($this->innerStore, $this->cache, 100);

        $this->innerStore->expects($this->once())
            ->method('set')
            ->with('key', 'value');

        $this->cache->expects($this->once())
            ->method('save')
            ->with('key', 'value', 100);

        $this->decorator->set('key', 'value');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\SerializationFailedException
     */
    public function testSetDoesNotWriteToCacheIfKeyValueStoreFails()
    {
        $this->innerStore->expects($this->once())
            ->method('set')
            ->with('key', 'value')
            ->willThrowException(new SerializationFailedException());

        $this->cache->expects($this->never())
            ->method('save');

        $this->decorator->set('key', 'value');
    }

    public function testGetReturnsFromCacheIfCached()
    {
        $this->cache->expects($this->at(0))
            ->method('contains')
            ->with('key')
            ->willReturn(true);

        $this->innerStore->expects($this->never())
            ->method('getOrFail');

        $this->cache->expects($this->at(1))
            ->method('fetch')
            ->with('key')
            ->willReturn('value');

        $this->assertSame('value', $this->decorator->get('key'));
    }

    public function testGetWritesToCacheIfNotCached()
    {
        $this->cache->expects($this->at(0))
            ->method('contains')
            ->with('key')
            ->willReturn(false);

        $this->innerStore->expects($this->once())
            ->method('getOrFail')
            ->with('key')
            ->willReturn('value');

        $this->cache->expects($this->at(1))
            ->method('save')
            ->with('key', 'value');

        $this->assertSame('value', $this->decorator->get('key'));
    }

    public function testGetWritesTtlIfNotCached()
    {
        $this->decorator = new CachingDecorator($this->innerStore, $this->cache, 100);

        $this->cache->expects($this->at(0))
            ->method('contains')
            ->with('key')
            ->willReturn(false);

        $this->innerStore->expects($this->once())
            ->method('getOrFail')
            ->with('key')
            ->willReturn('value');

        $this->cache->expects($this->at(1))
            ->method('save')
            ->with('key', 'value', 100);

        $this->assertSame('value', $this->decorator->get('key'));
    }

    public function testGetDoesNotSaveToCacheIfKeyNotFound()
    {
        $this->cache->expects($this->once())
            ->method('contains')
            ->with('key')
            ->willReturn(false);

        $this->innerStore->expects($this->once())
            ->method('getOrFail')
            ->willThrowException(NoSuchKeyException::forKey('key'));

        $this->cache->expects($this->never())
            ->method('save');

        $this->assertSame('default', $this->decorator->get('key', 'default'));
    }

    public function testGetOrFailReturnsFromCacheIfCached()
    {
        $this->cache->expects($this->at(0))
            ->method('contains')
            ->with('key')
            ->willReturn(true);

        $this->innerStore->expects($this->never())
            ->method('getOrFail');

        $this->cache->expects($this->at(1))
            ->method('fetch')
            ->with('key')
            ->willReturn('value');

        $this->assertSame('value', $this->decorator->getOrFail('key'));
    }

    public function testGetOrFailWritesToCacheIfNotCached()
    {
        $this->cache->expects($this->at(0))
            ->method('contains')
            ->with('key')
            ->willReturn(false);

        $this->innerStore->expects($this->once())
            ->method('getOrFail')
            ->with('key')
            ->willReturn('value');

        $this->cache->expects($this->at(1))
            ->method('save')
            ->with('key', 'value');

        $this->assertSame('value', $this->decorator->getOrFail('key'));
    }

    public function testGetOrFailWritesTtlIfNotCached()
    {
        $this->decorator = new CachingDecorator($this->innerStore, $this->cache, 100);

        $this->cache->expects($this->at(0))
            ->method('contains')
            ->with('key')
            ->willReturn(false);

        $this->innerStore->expects($this->once())
            ->method('getOrFail')
            ->with('key')
            ->willReturn('value');

        $this->cache->expects($this->at(1))
            ->method('save')
            ->with('key', 'value', 100);

        $this->assertSame('value', $this->decorator->getOrFail('key'));
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\NoSuchKeyException
     */
    public function testGetOrFailForwardsNoSuchKeyException()
    {
        $this->cache->expects($this->once())
            ->method('contains')
            ->with('key')
            ->willReturn(false);

        $this->innerStore->expects($this->once())
            ->method('getOrFail')
            ->willThrowException(NoSuchKeyException::forKey('key'));

        $this->cache->expects($this->never())
            ->method('save');

        $this->decorator->getOrFail('key');
    }

    public function testGetMultipleMergesCachedAndNonCachedEntries()
    {
        $this->cache->expects($this->exactly(3))
            ->method('contains')
            ->willReturnMap(array(
                array('a', false),
                array('b', true),
                array('c', false),
            ));

        $this->innerStore->expects($this->once())
            ->method('getMultiple')
            ->with(array('a', 2 => 'c'), 'default')
            ->willReturn(array(
                'a' => 'value1',
                'c' => 'default',
            ));

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('b')
            ->willReturn('value2');

        // We don't know which keys to save and which not
        $this->cache->expects($this->never())
            ->method('save');

        $values = $this->decorator->getMultiple(array('a', 'b', 'c'), 'default');

        // Undefined order
        ksort($values);

        $this->assertSame(array(
            'a' => 'value1',
            'b' => 'value2',
            'c' => 'default',
        ), $values);
    }

    public function testGetMultipleOrFailMergesCachedAndNonCachedEntries()
    {
        $this->cache->expects($this->exactly(3))
            ->method('contains')
            ->willReturnMap(array(
                array('a', false),
                array('b', true),
                array('c', false),
            ));

        $this->innerStore->expects($this->once())
            ->method('getMultipleOrFail')
            ->with(array('a', 2 => 'c'))
            ->willReturn(array(
                'a' => 'value1',
                'c' => 'value3',
            ));

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('b')
            ->willReturn('value2');

        $this->cache->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(array('a', 'value1'), array('c', 'value3'));

        $values = $this->decorator->getMultipleOrFail(array('a', 'b', 'c'));

        // Undefined order
        ksort($values);

        $this->assertSame(array(
            'a' => 'value1',
            'b' => 'value2',
            'c' => 'value3',
        ), $values);
    }

    public function testExistsQueriesCache()
    {
        $this->cache->expects($this->once())
            ->method('contains')
            ->with('key')
            ->willReturn(true);

        $this->innerStore->expects($this->never())
            ->method('exists');

        $this->assertTrue($this->decorator->exists('key'));
    }

    /**
     * @dataProvider provideTrueFalse
     */
    public function testExistsQueriesStoreIfNotCached($result)
    {
        $this->cache->expects($this->once())
            ->method('contains')
            ->with('key')
            ->willReturn(false);

        $this->innerStore->expects($this->once())
            ->method('exists')
            ->with('key')
            ->willReturn($result);

        $this->assertSame($result, $this->decorator->exists('key'));
    }

    public function provideTrueFalse()
    {
        return array(
            array(true),
            array(false),
        );
    }

    public function testRemoveDeletesFromCache()
    {
        $this->innerStore->expects($this->once())
            ->method('remove')
            ->with('key');

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('key');

        $this->decorator->remove('key');
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\InvalidKeyException
     */
    public function testRemoveDoesNotDeleteFromCacheIfRemovalFails()
    {
        $this->innerStore->expects($this->once())
            ->method('remove')
            ->with('key')
            ->willThrowException(new InvalidKeyException());

        $this->cache->expects($this->never())
            ->method('delete');

        $this->decorator->remove('key');
    }

    public function testClearDeletesAllFromCache()
    {
        $this->cache = $this->getMock('Webmozart\KeyValueStore\Tests\\Fixtures\TestClearableCache');
        $this->decorator = new CachingDecorator($this->innerStore, $this->cache);

        $this->innerStore->expects($this->once())
            ->method('clear');

        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->decorator->clear();
    }

    /**
     * @expectedException \Webmozart\KeyValueStore\Api\WriteException
     */
    public function testClearDoesNotDeleteAllFromCacheIfClearFails()
    {
        $this->cache = $this->getMock('Webmozart\KeyValueStore\Tests\\Fixtures\TestClearableCache');
        $this->decorator = new CachingDecorator($this->innerStore, $this->cache);

        $this->innerStore->expects($this->once())
            ->method('clear')
            ->willThrowException(new WriteException());

        $this->cache->expects($this->never())
            ->method('deleteAll');

        $this->decorator->clear();
    }

    public function testClearFlushesCache()
    {
        $this->cache = $this->getMock('Webmozart\KeyValueStore\Tests\\Fixtures\TestFlushableCache');
        $this->decorator = new CachingDecorator($this->innerStore, $this->cache);

        $this->innerStore->expects($this->once())
            ->method('clear');

        $this->cache->expects($this->once())
            ->method('flushAll');

        $this->decorator->clear();
    }

    public function testClearDeletesAllIfFlushableAndClearable()
    {
        $this->cache = $this->getMock('Webmozart\KeyValueStore\Tests\\Fixtures\TestClearableFlushableCache');
        $this->decorator = new CachingDecorator($this->innerStore, $this->cache);

        $this->innerStore->expects($this->once())
            ->method('clear');

        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->decorator->clear();
    }

    public function testKeysForwardsKeysFromStore()
    {
        $this->innerStore->expects($this->once())
            ->method('keys')
            ->willReturn(array('a', 'b', 'c'));

        $this->assertSame(array('a', 'b', 'c'), $this->decorator->keys());
    }
}
