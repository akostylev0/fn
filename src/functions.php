<?php
declare (strict_types = 1);

namespace func;

use ArrayIterator;
use Iterator;
use IteratorAggregate;

function to_array(iterable $it) : array
{
    return is_array($it) ? $it : iterator_to_array($it);
}

function to_iterator(iterable $it) : Iterator
{
    if (is_array($it)) {
        return new ArrayIterator($it);
    }

    if ($it instanceof Iterator) {
        return $it;
    }

    /** @var IteratorAggregate $it */

    do {
        $it = $it->getIterator();
    } while ($it instanceof IteratorAggregate);

    /** @var Iterator $it */

    return $it;
}

function map(iterable $it, callable $fn) : iterable
{
    foreach ($it as $key => $value) {
        yield $key => $fn($value);
    }
}

function apply(iterable $it, callable $fn) : void
{
    foreach ($it as $key => $value) {
        $fn($value);
    }
}

function filter(iterable $it, callable $predicate) : iterable
{
    foreach ($it as $key => $value) {
        if ($predicate($value)) {
            yield $key => $value;
        }
    }
}

function chain(iterable ... $its) : iterable
{
    foreach ($its as $it) {
        yield from $it;
    }
}

function all(iterable $it, callable $predicate) : bool
{
    foreach ($it as $value)
    {
        if (!$predicate($value)) {
            return false;
        }
    }

    return true;
}

function any(iterable $it, callable $predicate) : bool
{
    foreach ($it as $value) {
        if ($predicate($value)) {
            return true;
        }
    }

    return false;
}

function reduce(iterable $it, $accumulator, callable $fn)
{
    foreach ($it as $item) {
        $accumulator = $fn($item, $accumulator);
    }

    return $accumulator;
}


function zip(iterable ... $iterables) : iterable
{
    if (empty($iterables)) {
        return;
    }

    $iterators = array_map('\func\to_iterator', $iterables);

    for (apply($iterators, static function (Iterator $iterator) {
            $iterator->rewind();
        });

        all($iterators, static function (Iterator $iterator) {
            return $iterator->valid();
        });
        apply($iterators, static function (Iterator $iterator) {
            $iterator->next();
        })
    ) {
        yield array_map(static function (Iterator $iterator) {
            return $iterator->current();
        }, $iterators);
    }
}

function range(int $start, int $end, int $step = 1) : iterable
{
    if ($start === $end) {
        return (static function (int $n) : iterable {
            yield $n;
        })($start);
    } elseif ($start < $end) {
        if ($step <= 0) {
            throw new \InvalidArgumentException('If start < end the step must be greater than 0');
        }

        return (static function (int $start, int $end, int $step) : iterable {
            for (; $start <= $end; $start += $step) {
                yield $start;
            }
        })($start, $end, $step);
    } else {
        if ($step >= 0) {
            throw new \InvalidArgumentException('If start > end the step must be less than 0');
        }

        return (static function (int $start, int $end, int $step) : iterable {
            for (; $start >= $end; $start += $step) {
                yield $start;
            }
        })($start, $end, $step);
    }
}

function flatten(iterable $it, ?int $depth = null) : iterable {
    foreach ($it as $value) {
        if (!is_iterable($value)) {
            yield $value;
        } else {
            if ($depth === null) {
                foreach (flatten($value) as $anotherValue) {
                    yield $anotherValue;
                }
            } elseif ($depth > 0) {
                foreach (flatten($value, $depth - 1) as $anotherValue) {
                    yield $anotherValue;
                }
            } else {
                yield $value;
            }
        }
    }
}

function flatMap(iterable $it, callable $fn) : iterable
{
    return flatten(map($it, $fn), 1);
}

function take(iterable $it, int $count) : iterable
{
    foreach ($it as $value) {
        if ($count > 0) {
            $count--;
            yield $value;
        } else {
            return;
        }
    }
}

function drop(iterable $it, int $count) : iterable
{
    foreach ($it as $value) {
        if ($count > 0) {
            $count--;
        } else {
            yield $value;
        }
    }
}

function slice(iterable $it, int $start, int $end) : iterable
{
    $c = 0;

    foreach ($it as $value) {
        if ($c >= $start && $c <= $end) {
            yield $value;
        }

        $c++;
    }
}

function reject(iterable $it, callable $predicate) : iterable
{
    foreach ($it as $key => $value) {
        if (!$predicate($value)) {
            yield $key => $value;
        }
    }
}

function carry(callable $fn, ... $arguments) {
    $fn = \Closure::fromCallable($fn);

    $reflection = new \ReflectionFunction($fn);

    if ($reflection->getNumberOfParameters() < count($arguments)) {
        throw new \InvalidArgumentException('Too many arguments given');
    }

    return static function (... $args) use ($fn, $arguments) {
        return $fn(... $arguments, ... $args);
    };
}
