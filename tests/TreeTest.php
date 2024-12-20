<?php

/* @noinspection PhpUndefinedFieldInspection */
/* @noinspection PhpUndefinedMethodInspection */
/* @noinspection ReturnTypeCanBeDeclaredInspection */

namespace BlueM\Tests;

use BlueM\Tree\Exception\InvalidDatatypeException;
use BlueM\Tree\Exception\InvalidParentException;
use BlueM\Tree;
use BlueM\Tree\Node;
use BlueM\Tree\Serializer\HierarchicalTreeJsonSerializer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BlueM\Tree.
 *
 * These are not really unit tests, as they test the class including
 * BlueM\Tree\Node as a whole.
 *
 * @covers \BlueM\Tree
 */
class TreeTest extends TestCase
{
    public function testAnExceptionIsThrownIfANonScalarValueShouldBeUsedAsRootId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option “rootid” must be scalar or null');

        new Tree([], ['rootId' => []]);
    }

    public function testAnExceptionIsThrownIfANonStringValueShouldBeUsedAsIdFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option “id” must be a string');

        new Tree([], ['id' => 123]);
    }

    public function testAnExceptionIsThrownIfANonStringValueShouldBeUsedAsParentIdFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option “parent” must be a string');

        new Tree([], ['parent' => $this]);
    }

    public function testAnExceptionIsThrownIfANonObjectShouldBeUsedAsSerializer()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option “jsonSerializer” must be an object');

        new Tree([], ['jsonSerializer' => 'not an object']);
    }

    public function testTheSerializerCanBeSetToAnObjectImplementingSerializerinterface()
    {
        $serializer = new HierarchicalTreeJsonSerializer();

        $subject = new Tree([], ['jsonSerializer' => $serializer]);

        $serializerProperty = new \ReflectionProperty($subject, 'jsonSerializer');
        $serializerProperty->setAccessible(true);

        static::assertSame($serializer, $serializerProperty->getValue($subject));
    }

    public function testNullCanBeUsedAsParentId()
    {
        $data = [
            ['id' => 1, 'parent' => null, 'name' => 'Root'],
            ['id' => 2, 'parent' => 1, 'name' => 'Child'],
            ['id' => 3, 'parent' => 2, 'name' => 'Grandchild'],
            ['id' => 4, 'parent' => null, 'name' => 'Root'],
        ];

        $tree = new Tree($data, ['rootId' => null]);

        $nodes = $tree->getNodes();
        static::assertIsArray($nodes);
        static::assertCount(4, $nodes);
        static::assertSame(1, $nodes[0]->getId());
        static::assertSame(2, $nodes[1]->getId());
        static::assertSame(3, $nodes[2]->getId());
        static::assertSame(4, $nodes[3]->getId());

        static::assertSame($nodes[0], $nodes[1]->getParent());
        static::assertNull($nodes[0]->getParent()->getId());
    }

    public function testTheRootNodesCanBeRetrieved()
    {
        $data = self::dataWithNumericKeys();
        $tree = new Tree($data);

        $nodes = $tree->getRootNodes();
        static::assertIsArray($nodes);
        static::assertCount(5, $nodes);

        $expectedOrder = [5, 3, 4, 6, 1];

        for ($i = 0, $ii = \count($nodes); $i < $ii; $i++) {
            static::assertInstanceOf(Node::class, $nodes[$i]);
            static::assertSame($expectedOrder[$i], $nodes[$i]->getId());
        }
    }

    public function testTheRootNodesCanBeRetrievedWhenTheIdsAreStrings()
    {
        $data = self::dataWithStringKeys();
        $tree = new Tree($data, ['rootId' => '']);

        $nodes = $tree->getRootNodes();
        static::assertIsArray($nodes);

        $expectedOrder = ['building', 'vehicle'];

        for ($i = 0, $ii = \count($nodes); $i < $ii; $i++) {
            static::assertInstanceOf(Node::class, $nodes[$i]);
            static::assertSame($expectedOrder[$i], $nodes[$i]->getId());
        }
    }

    public function testTheTreeCanBeRebuiltFromNewData()
    {
        $data = self::dataWithNumericKeys();

        $tree = new Tree($data);
        $originalData = json_encode($tree);

        for ($i = 0; $i < 3; $i++) {
            $tree->rebuildWithData($data);
            static::assertSame($originalData, json_encode($tree));
        }
    }

    public function testAnExceptionIsThrownWhenTryingToCreateATreeFromUnusableData()
    {
        $this->expectException(\BlueM\Tree\Exception\InvalidDatatypeException::class);
        $this->expectExceptionMessage('Data must be an iterable');

        new Tree('a');
    }

    public function testATreeCanBeCreatedFromAnIterable()
    {
        function gen()
        {
            yield ['id' => 1, 'parent' => 0];
            yield ['id' => 2, 'parent' => 0];
            yield ['id' => 3, 'parent' => 2];
            yield ['id' => 4, 'parent' => 0];
        }

        $tree = new Tree(gen());
        static::assertSame('[{"id":1,"parent":0},{"id":2,"parent":0},{"id":3,"parent":2},{"id":4,"parent":0}]', json_encode($tree));
    }

    public function testATreeCanBeCreatedFromAnArrayOfObjectsImplementingIterator()
    {
        function makeIterableInstance($data) {
            return new class($data) implements \Iterator {

                private $data;
                private $pos = 0;
                private $keys;

                public function __construct(array $data)
                {
                    $this->data = $data;
                    $this->keys = array_keys($data);
                }

                #[\ReturnTypeWillChange]
                public function current()
                {
                    return $this->data[$this->keys[$this->pos]];
                }

                #[\ReturnTypeWillChange]
                public function next()
                {
                    ++$this->pos;
                }

                #[\ReturnTypeWillChange]
                public function key()
                {
                    return $this->keys[$this->pos];
                }

                #[\ReturnTypeWillChange]
                public function valid()
                {
                    return isset($this->keys[$this->pos]);
                }

                #[\ReturnTypeWillChange]
                public function rewind()
                {
                    $this->pos = 0;
                }
            };
        }

        $tree = new Tree([
            makeIterableInstance(['id' => 1, 'parent' => 0, 'title' => 'A']),
            makeIterableInstance(['id' => 2, 'parent' => 0, 'title' => 'B']),
            makeIterableInstance(['id' => 3, 'parent' => 2, 'title' => 'B-1']),
            makeIterableInstance(['id' => 4, 'parent' => 0, 'title' => 'D']),
        ]);
        static::assertSame(
            '[{"title":"A","id":1,"parent":0},{"title":"B","id":2,"parent":0},{"title":"B-1","id":3,"parent":2},{"title":"D","id":4,"parent":0}]',
            json_encode($tree)
        );
    }

    public function testTheTreeCanBeSerializedToAJsonRepresentationFromWhichATreeWithTheSameDataCanBeBuiltWhenDecoded()
    {
        $data = self::dataWithNumericKeys();

        $tree1 = new Tree($data);
        $tree1Json = json_encode($tree1);
        $tree1JsonDecoded = json_decode($tree1Json, true);

        static::assertCount(\count($data), $tree1JsonDecoded);
        foreach ($data as $nodeData) {
            ksort($nodeData);
            // Note: static::assertContainsEquals() fails
            /* @noinspection PhpUnitTestsInspection */
            static::assertTrue(in_array($nodeData, $data));
        }

        $tree2 = new Tree($tree1JsonDecoded);
        $tree2Json = json_encode($tree2);

        static::assertSame($tree1Json, $tree2Json);
    }

    public function testAllNodesCanBeRetrieved()
    {
        $data = self::dataWithNumericKeys();
        $tree = new Tree($data);

        $nodes = $tree->getNodes();

        static::assertIsArray($nodes);
        static::assertCount(\count($data), $nodes);

        $expectedOrder = [5, 3, 4, 6, 1, 7, 15, 11, 21, 27, 12, 10, 20];

        for ($i = 0, $ii = \count($nodes); $i < $ii; $i++) {
            static::assertInstanceOf(Node::class, $nodes[$i]);
            static::assertSame($expectedOrder[$i], $nodes[$i]->getId());
        }
    }

    public function testAllNodesCanBeRetrievedWhenNodeIdsAreStrings()
    {
        $data = self::dataWithStringKeys();
        $tree = new Tree($data, ['rootId' => '']);

        $nodes = $tree->getNodes();
        static::assertIsArray($nodes);
        static::assertCount(\count($data), $nodes);

        $expectedOrder = [
            'building', 'library', 'school', 'primary-school', 'vehicle', 'bicycle', 'car',
        ];

        for ($i = 0, $ii = \count($nodes); $i < $ii; $i++) {
            static::assertInstanceOf(Node::class, $nodes[$i]);
            static::assertSame($expectedOrder[$i], $nodes[$i]->getId());
        }
    }

    public function testANodeCanBeAccessedByItsIntegerId()
    {
        $data = self::dataWithNumericKeys();
        $tree = new Tree($data);
        $node = $tree->getNodeById(20);
        static::assertEquals(20, $node->getId());
    }

    public function testANodeCanBeAccessedByItsStringId()
    {
        $data = self::dataWithStringKeys();
        $tree = new Tree($data, ['rootId' => '']);
        $node = $tree->getNodeById('library');
        static::assertEquals('library', $node->getId());
    }

    public function testTryingToGetANodeByItsIdThrowsAnExceptionIfTheIdIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid node primary key 999');

        $data = self::dataWithNumericKeys();
        $tree = new Tree($data);
        $tree->getNodeById(999);
    }

    public function testANodeCanBeAccessedByItsValuePath()
    {
        $data = self::dataWithNumericKeys();
        $tree = new Tree($data);
        static::assertEquals(
            $tree->getNodeById(11),
            $tree->getNodeByValuePath('name', ['Europe', 'Germany', 'Hamburg'])
        );
    }

    public function testTryingToGetANodeByItsValuePathReturnsNullIfNoNodeMatches()
    {
        $data = self::dataWithNumericKeys();
        $tree = new Tree($data);
        static::assertEquals(
            null,
            $tree->getNodeByValuePath('name', ['Europe', 'Germany', 'Frankfurt'])
        );
    }

    public function testInScalarContextTheTreeIsReturnedAsAString()
    {
        $data = self::dataWithNumericKeys();
        $tree = new Tree($data);
        $actual = (string)$tree;
        $expected = <<<'EXPECTED'
- 5
- 3
- 4
- 6
- 1
  - 7
    - 15
    - 11
      - 21
      - 27
    - 12
  - 10
    - 20
EXPECTED;

        static::assertEquals($expected, $actual);
    }

    public function testAnExceptionIsThrownWhenAnInvalidParentIdIsReferenced()
    {
        $this->expectException(\BlueM\Tree\Exception\InvalidParentException::class);
        $this->expectExceptionMessage('123 points to non-existent parent with ID 456');

        new Tree(
            [
                ['id' => 123, 'parent' => 456],
            ]
        );
    }

    public function testACustomBuildWarningCallbackCanBeSpecifiedWhichIsCalledWithNodeAndParentIdAsArgument()
    {
        $invocationCount = 0;
        $buildwarningcallback = function(Node $node, $parentId) use (&$invocationCount) {
            $invocationCount ++;
            static::assertSame(2, $node->getId());
            static::assertSame('', $parentId);
        };

        new Tree(
            [
                ['id' => 1, 'parent' => 0],
                ['id' => 2, 'parent' => ''],
            ],
            [
                'buildwarningcallback' => $buildwarningcallback
            ]
        );

        static::assertSame(1, $invocationCount);
    }

    public function testAnExceptionIsThrownWhenANodeWouldBeItsOwnParent()
    {
        $this->expectException(\BlueM\Tree\Exception\InvalidParentException::class);
        $this->expectExceptionMessage('678 references its own ID as parent');

        new Tree(
            [
                ['id' => 123, 'parent' => 0],
                ['id' => 678, 'parent' => 678],
            ]
        );
    }

    /**
     * @ticket 3
     */
    public function testAnExceptionIsThrownWhenANodeWouldBeItsOwnParentWhenOwnIdAndParentIdHaveDifferentTypes()
    {
        $this->expectException(\BlueM\Tree\Exception\InvalidParentException::class);
        $this->expectExceptionMessage('references its own ID as parent');

        new Tree(
            [
                ['id' => '5', 'parent' => 5],
            ]
        );
    }

    /**
     * @ticket 3
     */
    public function testWhenMixingNumericAndStringIdsNoExceptionIsThrownDueToImplicitTypecasting()
    {
        new Tree([
            ['id' => 'foo', 'parent' => 0],
        ]);
        static::assertTrue(true); // Just to make PHPUnit happy
    }

    public function testClientsCanSupplyDifferingNamesForIdAndParentIdInInputData()
    {
        $data = self::dataWithStringKeys(true, 'id_node', 'id_parent');

        $tree = new Tree($data, ['rootId' => '', 'id' => 'id_node', 'parent' => 'id_parent']);

        $nodes = $tree->getRootNodes();
        static::assertIsArray($nodes);

        $expectedOrder = ['building', 'vehicle'];

        for ($i = 0, $ii = \count($nodes); $i < $ii; $i++) {
            static::assertInstanceOf(Node::class, $nodes[$i]);
            static::assertSame($expectedOrder[$i], $nodes[$i]->getId());
        }
    }

    private static function dataWithNumericKeys(): array
    {
        $data = [
            ['id' => 1, 'name' => 'Europe', 'parent' => 0],
            ['id' => 3, 'name' => 'America', 'parent' => 0],
            ['id' => 4, 'name' => 'Asia', 'parent' => 0],
            ['id' => 5, 'name' => 'Africa', 'parent' => 0],
            ['id' => 6, 'name' => 'Australia', 'parent' => 0],
            // --
            ['id' => 7, 'name' => 'Germany', 'parent' => 1],
            ['id' => 10, 'name' => 'Portugal', 'parent' => 1],
            // --
            ['id' => 11, 'name' => 'Hamburg', 'parent' => 7],
            ['id' => 12, 'name' => 'Munich', 'parent' => 7],
            ['id' => 15, 'name' => 'Berlin', 'parent' => 7],
            // --
            ['id' => 20, 'name' => 'Lisbon', 'parent' => 10],
            // --
            ['id' => 27, 'name' => 'Eimsbüttel', 'parent' => 11],
            ['id' => 21, 'name' => 'Altona', 'parent' => 11],
        ];

        usort(
            $data,
            function ($a, $b) {
                if ($a['name'] < $b['name']) {
                    return -1;
                }
                if ($a['name'] > $b['name']) {
                    return 1;
                }

                return 0;
            }
        );

        return $data;
    }

    private static function dataWithStringKeys(bool $sorted = true, string $idName = 'id', string $parentName = 'parent'): array
    {
        $data = [
            [$idName => 'vehicle', $parentName => ''],
            [$idName => 'bicycle', $parentName => 'vehicle'],
            [$idName => 'car', $parentName => 'vehicle'],
            [$idName => 'building', $parentName => ''],
            [$idName => 'school', $parentName => 'building'],
            [$idName => 'library', $parentName => 'building'],
            [$idName => 'primary-school', $parentName => 'school'],
        ];

        if ($sorted) {
            usort(
                $data,
                function ($a, $b) use ($idName) {
                    if ($a[$idName] < $b[$idName]) {
                        return -1;
                    }
                    if ($a[$idName] > $b[$idName]) {
                        return 1;
                    }

                    return 0;
                }
            );
        }

        return $data;
    }
}
