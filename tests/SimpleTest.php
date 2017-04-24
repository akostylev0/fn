<?php
declare (strict_types=1);

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function test_to_array_Array()
    {
        $in = [1, 2, 3];

        $this->assertEquals($in, \func\to_array($in));
    }

    public function test_to_array_Iterator()
    {
        $in = [1, 2, 3];

        $this->assertEquals($in, \func\to_array(new ArrayIterator($in)));
    }

    public function test_to_array_EmptyIterator()
    {
        $this->assertEquals([], \func\to_array(new EmptyIterator()));
    }

    public function test_to_iterator_Array()
    {
        $in = [1, 2, 3];

        $iterator = \func\to_iterator($in);

        $this->assertThat($iterator, $this->isInstanceOf(Iterator::class));
        $this->assertEquals(iterator_to_array($iterator), $in);
    }

    public function test_to_iterator_Iterator()
    {
        $in = [1, 2, 3];

        $iterator = \func\to_iterator(new ArrayIterator($in));

        $this->assertThat($iterator, $this->isInstanceOf(Iterator::class));
        $this->assertEquals(iterator_to_array($iterator), $in);
    }

    public function test_to_iterator_IteratorAggregate()
    {
        $in = [1, 2, 3];

        $iterator = \func\to_iterator(
            $this->iteratorAggregateForTraversable(new ArrayIterator($in))
        );

        $this->assertThat($iterator, $this->isInstanceOf(Iterator::class));
        $this->assertEquals(iterator_to_array($iterator), $in);
    }

    public function test_to_iterator_NestedIteratorAggregate()
    {
        $in = [1, 2, 3];

        $iterator = \func\to_iterator(
            $this->iteratorAggregateForTraversable(
                $this->iteratorAggregateForTraversable(new ArrayIterator($in))
            )
        );

        $this->assertThat($iterator, $this->isInstanceOf(Iterator::class));
        $this->assertEquals(iterator_to_array($iterator), $in);
    }

    public function test_map_EmptyIterable()
    {
        $in = [];

        $actual = \func\map($in, static function () {
        });

        $this->assertTrue(is_iterable($actual));
        $this->assertEquals([], \func\to_array($actual));
    }

    public function test_map_Iterable()
    {
        $in = [1, 2, 3];

        $actual = \func\map($in, static function (int $n): int {
            return $n ** 2;
        });

        $this->assertTrue(is_iterable($actual));
        $this->assertEquals([1, 4, 9], \func\to_array($actual));
    }

    public function test_map_IterableWithKeys()
    {
        $in = ['one' => 1];

        $actual = \func\map($in, static function (int $n): int {
            return $n * 2;
        });

        $this->assertTrue(is_iterable($actual));
        $this->assertEquals(['one' => 2], \func\to_array($actual));
    }

    public function test_apply_CallbackCalled()
    {
        $in = [3];

        $called = false;
        $actual = func\apply($in, function (int $n) use (&$called) : void {
            $called = true;
        });

        $this->assertNull($actual);
        $this->assertTrue($called);
    }

    public function test_reduce_Sum()
    {
        $in = [2, 3, 4];

        $actual = \func\reduce($in, 1, static function (int $n, int $accumulator): int {
            return $n + $accumulator;
        });

        $this->assertEquals(1 + 2 + 3 + 4, $actual);
    }

    public function test_filter_DivisionBy2()
    {
        $in = range(1, 8);

        $actual = \func\filter($in, static function (int $n): bool {
            return $n % 2 === 0;
        });

        $this->assertEquals([
            1 => 2,
            3 => 4,
            5 => 6,
            7 => 8
        ], \func\to_array($actual));
    }

    public function test_chain_EmptyIteratables()
    {
        $actual = \func\chain([], new EmptyIterator(), $this->iteratorAggregateForTraversable(new EmptyIterator()));

        $this->assertEquals([], \func\to_array($actual));
    }

    public function test_chain_Iteratables()
    {
        $actual = \func\chain(
            ['o' => 1],
            new ArrayIterator(['a' => 3]),
            $this->iteratorAggregateForTraversable(new ArrayIterator(['z' => 4]))
        );

        $this->assertEquals([
            'o' => 1,
            'a' => 3,
            'z' => 4
        ], \func\to_array($actual));
    }

    public function test_all_GreaterThanZero_True()
    {
        $this->assertTrue(\func\all([1, 2, 3], static function (int $n) : bool {
            return $n > 0;
        }));
    }

    public function test_all_GreaterThanZero_False()
    {
        $this->assertFalse(\func\all([1, -2, 3], static function (int $n) : bool {
            return $n > 0;
        }));
    }

    public function test_any_GreaterThanZero_True()
    {
        $this->assertTrue(\func\any([-1, 2, -3], static function (int $n) : bool {
            return $n > 0;
        }));
    }

    public function test_any_GreaterThanZero_False()
    {
        $this->assertFalse(\func\any([-1, -2, -3], static function (int $n) : bool {
            return $n > 0;
        }));
    }

    public function test_zip_Empty()
    {
        $actual = \func\zip(
            [],
            new EmptyIterator(),
            $this->iteratorAggregateForTraversable(new EmptyIterator())
        );

        $this->assertEmpty(\func\to_array($actual));
    }

    public function test_zip_MinSize()
    {
        $actual = \func\zip(
            [1, 2, 3],
            new ArrayIterator([1, 2]),
            $this->iteratorAggregateForTraversable(new EmptyIterator())
        );

        $this->assertEmpty(\func\to_array($actual));
    }

    public function test_zip_Zipped()
    {
        $actual = \func\zip(
            [1, 2, 3],
            new ArrayIterator([4, 5]),
            $this->iteratorAggregateForTraversable(new ArrayIterator([6, 7]))
        );

        $this->assertEquals([[1, 4, 6], [2, 5, 7]], \func\to_array($actual));
    }

    public function test_range_Equals()
    {
        $this->assertEquals([5], \func\to_array(\func\range(5, 5)));
    }

    public function test_range_Increment()
    {
        $this->assertEquals([1, 3, 5], \func\to_array(\func\range(1, 5, 2)));
    }

    public function test_range_IncrementGreaterThan0()
    {
        $this->expectException(InvalidArgumentException::class);

        \func\range(1, 5, -2);
    }

    public function test_range_Decrement()
    {
        $this->assertEquals([5, 3, 1], \func\to_array(\func\range(5, 1, -2)));
    }

    public function test_flatten_simple()
    {
        $this->assertEquals([1, 2, 3], \func\to_array(\func\flatten([1, 2, 3])));
    }

    public function test_flatten_DepthIsZero()
    {
        $this->assertEquals([1, [2], 3], \func\to_array(\func\flatten([1, [2], 3], 0)));
    }

    public function test_flatten_DepthIsTwo()
    {
        $this->assertEquals([1, 2, 3], \func\to_array(\func\flatten([1, [2], [[3]]], 2)));
    }

    public function test_flatMap_Simple()
    {
        $this->assertEquals(
            ['q', 'w', 'e', 'z'],
            \func\to_array(\func\flatMap(['q w', 'e z'], static function (string $str) : array {
                return explode(' ', $str);
            }))
        );
    }

    private function iteratorAggregateForTraversable(Traversable $traversable)
    {
        return new class($traversable) implements IteratorAggregate
        {
            /** @var Traversable */
            private $iterator;

            public function __construct(Traversable $traversable)
            {
                $this->iterator = $traversable;
            }

            public function getIterator()
            {
                return $this->iterator;
            }
        };
    }
}
