<?php

namespace yiiunit\extensions\modelcollection;


use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\modelcollection\Collection;
use yiiunit\extensions\modelcollection\models\Customer;

class CollectionTest extends TestCase
{
    public function testIterator()
    {
        $models = [
            new Customer(['id' => 1]),
            new Customer(['id' => 2]),
            new Customer(['id' => 3]),
        ];
        $collection = new Collection($models);
        $it = 0;
        foreach ($collection as $model) {
            $this->assertInstanceOf(Customer::class, $model);
            $this->assertEquals($it + 1, $model->id);
            ++$it;
        }
        $this->assertEquals(3, $it);
    }

    public function testArrayAccessRead()
    {
        $models = [
            new Customer(['id' => 1]),
            new Customer(['id' => 2]),
            new Customer(['id' => 3]),
        ];
        $collection = new Collection($models);
        $this->assertInstanceOf(Customer::class, $collection[0]);
        $this->assertEquals(1, $collection[0]->id);
        $this->assertInstanceOf(Customer::class, $collection[1]);
        $this->assertEquals(2, $collection[1]->id);
        $this->assertInstanceOf(Customer::class, $collection[2]);
        $this->assertEquals(3, $collection[2]->id);

        $models = [
            'one' => new Customer(['id' => 1]),
            'two' => new Customer(['id' => 2]),
            'three' => new Customer(['id' => 3]),
        ];
        $collection = new Collection($models);
        $this->assertInstanceOf(Customer::class, $collection['one']);
        $this->assertEquals(1, $collection['one']->id);
        $this->assertInstanceOf(Customer::class, $collection['two']);
        $this->assertEquals(2, $collection['two']->id);
        $this->assertInstanceOf(Customer::class, $collection['three']);
        $this->assertEquals(3, $collection['three']->id);
    }

    public function testCountable()
    {
        $collection = new Collection([]);
        $this->assertEquals(0, count($collection));
        $this->assertEquals(0, $collection->count());

        $models = [
            new Customer(['id' => 1]),
            new Customer(['id' => 2]),
            new Customer(['id' => 3]),
        ];
        $collection = new Collection($models);
        $this->assertEquals(3, count($collection));
        $this->assertEquals(3, $collection->count());
    }

    public function testIsEmpty()
    {
        $collection = new Collection([]);
        $this->assertTrue($collection->isEmpty());

        $models = [
            new Customer(['id' => 1]),
            new Customer(['id' => 2]),
            new Customer(['id' => 3]),
        ];
        $collection->setData($models);
        $this->assertFalse($collection->isEmpty());

        $collection = new Collection($models);
        $this->assertFalse($collection->isEmpty());
    }

    public function testMap()
    {
        $models = [
            new Customer(['id' => 1]),
            new Customer(['id' => 2]),
            new Customer(['id' => 3]),
        ];
        $collection = new Collection($models);
        $this->assertEquals([1,2,3], $collection->map(function($model) {
            return $model->id;
        })->getData());
    }

    public function testFlatMap()
    {
        $models = [
            new Customer(['id' => 1, 'name' => [1]]),
            new Customer(['id' => 2, 'name' => [2, 3]]),
            new Customer(['id' => 3, 'name' => [4, 5]]),
        ];
        $collection = new Collection($models);
        $this->assertEquals([1,2,3,4,5], $collection->flatMap(function($model) {
            return $model->name;
        })->getData());
    }

    public function testFilter()
    {
        $models = [
            new Customer(['id' => 1]),
            new Customer(['id' => 2]),
            new Customer(['id' => 3]),
        ];
        $collection = new Collection($models);
        $this->assertEquals([1 => 2], $collection->filter(function($model) {
            return $model->id == 2;
        })->map(function($model) {
            return $model->id;
        })->getData());

        $collection = new Collection($models);
        $this->assertEquals([1 => 2, 2 => 3], $collection->filter(function($model, $key) {
            return $model->id == 2 || $key == 2;
        })->map(function($model) {
            return $model->id;
        })->getData());
    }

    public function testReduce()
    {
        $models = [
            new Customer(['id' => 1]),
            new Customer(['id' => 2]),
            new Customer(['id' => 3]),
        ];
        $collection = new Collection($models);
        $this->assertEquals(12, $collection->reduce(function($carry, $model) {
            return $model->id + $carry;
        }, 6));
    }

    public function testSum()
    {
        $collection = new Collection([]);
        $this->assertEquals(0, $collection->sum('id'));
        $this->assertEquals(0, $collection->sum('age'));

        $models = [
            new Customer(['id' => 1, 'age' => -2]),
            new Customer(['id' => 2, 'age' => 2]),
            new Customer(['id' => 3, 'age' => 42]),
        ];
        $collection = new Collection($models);
        $this->assertEquals(6, $collection->sum('id'));
        $this->assertEquals(42, $collection->sum('age'));
    }

    public function testMin()
    {
        $collection = new Collection([]);
        $this->assertEquals(0, $collection->min('id'));
        $this->assertEquals(0, $collection->min('age'));

        $models = [
            new Customer(['id' => 1, 'age' => -2]),
            new Customer(['id' => 2, 'age' => 2]),
            new Customer(['id' => 3, 'age' => 42]),
        ];
        $collection = new Collection($models);
        $this->assertEquals(1, $collection->min('id'));
        $this->assertEquals(-2, $collection->min('age'));
    }

    public function testMax()
    {
        $collection = new Collection([]);
        $this->assertEquals(0, $collection->max('id'));
        $this->assertEquals(0, $collection->max('age'));

        $models = [
            new Customer(['id' => 1, 'age' => -2]),
            new Customer(['id' => 2, 'age' => 2]),
            new Customer(['id' => 3, 'age' => 42]),
        ];
        $collection = new Collection($models);
        $this->assertEquals(3, $collection->max('id'));
        $this->assertEquals(42, $collection->max('age'));
    }

    public function testKeys()
    {
        $data = [
            'a',
            'b' => 'c',
            1 => 'test',
        ];
        $collection = new Collection($data);
        $this->assertSame([0, 'b', 1], $collection->keys()->getData());
    }

    public function testValues()
    {
        $data = [
            'a',
            'b' => 'c',
            1 => 'test',
        ];
        $collection = new Collection($data);
        $this->assertSame(['a', 'c', 'test'], $collection->values()->getData());
    }

    public function testFlip()
    {
        $data = [
            'a',
            'b' => 'c',
            1 => 'test',
        ];
        $collection = new Collection($data);
        $this->assertSame(['a' => 0, 'c' => 'b', 'test' => 1], $collection->flip()->getData());
    }

    public function testReverse()
    {
        $data = [
            'a',
            'b' => 'c',
            1 => 'test',
        ];
        $collection = new Collection($data);
        $this->assertSame([1 => 'test', 'b' => 'c', 0 => 'a'], $collection->reverse()->getData());
    }

    public function testMerge()
    {
        $data1 = ['a', 'b', 'c'];
        $data2 = [1, 2, 3];
        $collection1 = new Collection($data1);
        $collection2 = new Collection($data2);
        $this->assertEquals(['a', 'b', 'c', 1, 2, 3], $collection1->merge($collection2)->getData());
        $this->assertEquals([1, 2, 3, 'a', 'b', 'c'], $collection2->merge($collection1)->getData());
        $this->assertEquals(['a', 'b', 'c', 1, 2, 3], $collection1->merge($data2)->getData());
        $this->assertEquals([1, 2, 3, 'a', 'b', 'c'], $collection2->merge($data1)->getData());
    }

    /**
     * @expectedException \yii\base\InvalidParamException
     */
    public function testMergeWrongType()
    {
        $data1 = ['a', 'b', 'c'];
        $collection1 = new Collection($data1);
        $collection1->merge('string');
    }

    public function testConvert()
    {
        $models = [
            new Customer(['id' => 1, 'age' => -2]),
            new Customer(['id' => 2, 'age' => 2]),
            new Customer(['id' => 3, 'age' => 42]),
        ];
        $collection = new Collection($models);
        $this->assertEquals([1 => -2, 2 => 2, 3 => 42], $collection->convert('id', 'age')->getData());
        $this->assertEquals(['1-2' => -1, '22' => 4, '342' => 45], $collection->convert(
            function($model) { return $model->id . $model->age; },
            function($model) { return $model->id + $model->age; }
        )->getData());
    }

}