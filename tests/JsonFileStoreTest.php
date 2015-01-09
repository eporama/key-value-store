<?php

/*
 * This file is part of the webmozart/key-value-store package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\KeyValueStore\Tests;

use Webmozart\KeyValueStore\JsonFileStore;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonFileStoreTest extends AbstractKeyValueStoreTest
{
    private $tempFile;

    protected function setUp()
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'JsonFileStoreTest');

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        unlink($this->tempFile);
    }

    protected function createStore()
    {
        return new JsonFileStore($this->tempFile, false);
    }

    public function provideValidValues()
    {
        $values = parent::provideValidValues();
        $values[] = array(JsonFileStore::MAX_FLOAT);

        return $values;
    }

    /**
     * @dataProvider provideValidValues
     */
    public function testSetSupportsSerializableValues($value)
    {
        if (is_float($value) && $value > JsonFileStore::MAX_FLOAT) {
            $this->setExpectedException('\DomainException');
        }

        parent::testSetSupportsSerializableValues($value);
    }
}
