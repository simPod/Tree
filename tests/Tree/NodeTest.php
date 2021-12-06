<?php

/* @noinspection PhpUndefinedFieldInspection */
/* @noinspection PhpUndefinedMethodInspection */
/* @noinspection ReturnTypeCanBeDeclaredInspection */

namespace BlueM\Tree\Tests;

use BlueM\Tree\Node;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BlueM\Tree\Node
 */
class NodeTest extends TestCase
{
    public function testThePreviousSiblingCanBeRetrieved()
    {
        $node = new Node(123, null);
        $sibling = new Node(456, null);

        $parent = new Node(789, null);
        $childrenProperty = new \ReflectionProperty($parent, 'children');
        $childrenProperty->setAccessible(true);
        $childrenProperty->setValue($parent, [$sibling, $node]);

        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, $parent);

        static::assertSame($sibling, $node->getPrecedingSibling());
    }

    public function testTryingToGetThePreviousSiblingReturnsNullWhenCalledOnTheFirstNode()
    {
        $node = new Node(123, null);
        $parent = new Node(789, null);

        $childrenProperty = new \ReflectionProperty($parent, 'children');
        $childrenProperty->setAccessible(true);
        $childrenProperty->setValue($parent, [$node]);

        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, $parent);

        static::assertNull($node->getPrecedingSibling());
    }

    public function testTheNextSiblingCanBeRetrieved()
    {
        $node = new Node(123, null);
        $sibling = new Node(456, null);

        $parent = new Node(789, null);
        $childrenProperty = new \ReflectionProperty($parent, 'children');
        $childrenProperty->setAccessible(true);
        $childrenProperty->setValue($parent, [$node, $sibling]);

        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, $parent);

        static::assertSame($sibling, $node->getFollowingSibling());
    }

    public function testAllSiblingsCanBeRetrieved()
    {
        $node = new Node(10, null);
        $sibling1 = new Node(20, null);
        $sibling2 = new Node(30, null);

        $parent = new Node(333, null);
        $childrenProperty = new \ReflectionProperty($parent, 'children');
        $childrenProperty->setAccessible(true);
        $childrenProperty->setValue($parent, [$node, $sibling1, $sibling2]);

        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, $parent);

        static::assertSame(
            [$sibling1, $sibling2],
            $node->getSiblings()
        );
    }

    public function testAllSiblingsCanBeRetrievedIncludingTheNodeItself()
    {
        $node = new Node(10, null);
        $sibling1 = new Node(20, null);
        $sibling2 = new Node(30, null);

        $parent = new Node(333, null);
        $childrenProperty = new \ReflectionProperty($parent, 'children');
        $childrenProperty->setAccessible(true);
        $childrenProperty->setValue($parent, [$sibling1, $node, $sibling2]);

        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, $parent);

        static::assertSame(
            [$sibling1, $node, $sibling2],
            $node->getSiblingsAndSelf()
        );
    }

    public function testAllSiblingsCanBeRetrievedWhenMixedDataTypesAreUsedForTheIds()
    {
        $node = new Node(0, null);
        $sibling1 = new Node('a', null);
        $sibling2 = new Node(30, null);

        $parent = new Node('333', null);
        $childrenProperty = new \ReflectionProperty($parent, 'children');
        $childrenProperty->setAccessible(true);
        $childrenProperty->setValue($parent, [$node, $sibling1, $sibling2]);

        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, $parent);

        static::assertSame(
            [$sibling1, $sibling2],
            $node->getSiblings()
        );
    }

    public function testTheChildNodesCanBeRetrieved()
    {
        $node1 = new Node(10, null);
        $node2 = new Node(20, null);
        $node3 = new Node(30, null);

        $parent = new Node(333, null);
        $childrenProperty = new \ReflectionProperty($parent, 'children');
        $childrenProperty->setAccessible(true);
        $childrenProperty->setValue($parent, [$node1, $node2, $node3]);

        static::assertSame([$node1, $node2, $node3], $parent->getChildren());
    }

    public function testWhenTryingToGetTheChildNodesAnEmptyArrayIsReturnedIfThereAreNoChildNodes()
    {
        $parent = new Node(52, null);
        static::assertSame([], $parent->getChildren());
    }

    public function testANodeCanTellHowManyChildrenItHas()
    {
        $node = new Node(10, null);

        $childrenProperty = new \ReflectionProperty($node, 'children');
        $childrenProperty->setAccessible(true);
        $childrenProperty->setValue($node, ['dummy1', 'dummy2']);

        static::assertSame(2, $node->countChildren());
    }

    public function testANodeCanTellIfItHasAnyChildNodes()
    {
        $node = new Node(10, null);

        $childrenProperty = new \ReflectionProperty($node, 'children');
        $childrenProperty->setAccessible(true);
        $childrenProperty->setValue($node, ['dummy1', 'dummy2']);

        static::assertTrue($node->hasChildren());
    }

    public function testTheParentNodeCanBeRetrieved()
    {
        $node = new Node(2, null);
        $parent = new Node(4, null);

        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, $parent);

        static::assertSame($parent, $node->getParent());
    }

    public function testTryingToGetTheParentReturnsNullForTheRootNode()
    {
        $node = new Node(0, null);

        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, null);

        static::assertNull($node->getParent());
    }

    public function testAChildCanBeAttachedToANode()
    {
        $parent = new Node(100, null);
        $child = new Node(200, null);

        $parent->addChild($child);

        $childrenProperty = new \ReflectionProperty($parent, 'children');
        $childrenProperty->setAccessible(true);
        static::assertSame([$child], $childrenProperty->getValue($parent));

        $parentProperty = new \ReflectionProperty($child, 'parent');
        $parentProperty->setAccessible(true);
        static::assertSame($parent, $parentProperty->getValue($child));

        $propertiesProperty = new \ReflectionProperty($child, 'properties');
        $propertiesProperty->setAccessible(true);
        static::assertSame(
            ['id' => 200, 'parent' => 100],
            $propertiesProperty->getValue($child)
        );
    }

    public function testTheNodeIdCanBeRetrieved()
    {
        $node = new Node(16, null);
        static::assertEquals(16, $node->getId());
    }

    public function testANodePropertyCanBeFetchedUsingMethodGet()
    {
        $node = new Node(16, null, ['key' => 'value']);
        static::assertEquals('value', $node->get('key'));
    }

    public function testTryingToGetANonExistentPropertyUsingGetThrowsAnException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined property: foobar (Node ID: 16)');

        $node = new Node(16, null, ['key' => 'value']);
        static::assertEquals('value', $node->get('foobar'));
    }

    public function testANodePropertyCanBeFetchedUsingMagicMethod()
    {
        $node = new Node(16, null, ['key' => 'value']);
        static::assertEquals('value', $node->getKey());
    }

    public function testTryingToGetANonExistentPropertyUsingMagicMethodThrowsAnException()
    {
        $this->expectException(\BadFunctionCallException::class);
        $this->expectExceptionMessage('Invalid method getFoobar()');

        $node = new Node(16, null, ['key' => 'value']);
        static::assertEquals('value', $node->getFoobar());
    }

    public function testTheExistenceOfAPropertyCanBeCheckedUsingIssetFunction()
    {
        $node = new Node(1, null, ['foo' => 'Foo', 'BAR' => 'Bar']);

        static::assertTrue(isset($node->foo));
        static::assertTrue(isset($node->FOO));
        static::assertTrue(isset($node->bar));
        static::assertTrue(isset($node->BAR));
        static::assertTrue(isset($node->children));
        static::assertTrue(isset($node->parent));
    }

    public function testNodePropertiesAreHandledCaseInsensitively()
    {
        $node = new Node(1, null, ['foo' => 'Foo', 'BAR' => 'Bar']);

        static::assertSame('Foo', $node->foo);
        static::assertSame('Foo', $node->get('foo'));
        static::assertSame('Foo', $node->getFoo());
        static::assertSame('Bar', $node->bar);
        static::assertSame('Bar', $node->get('bar'));
        static::assertSame('Bar', $node->getBar());
    }

    public function testThePropertiesCanBeAccessUsingMagicProperties()
    {
        $node = new Node(1, null, ['foo' => 'Foo', 'BAR' => 'Bar']);

        static::assertSame([], $node->children);
        static::assertSame('Foo', $node->foo);
        static::assertNull($node->parent);
    }

    public function testAnExceptionIsThrownWhenAccessingAnInexistentMagicProperty()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Undefined property');

        $node = new Node(1, null);
        $node->nosuchproperty;
    }

    public function testThePropertiesCanBeFetchedAsAnArray()
    {
        $node = new Node('xyz', 456, ['foo' => 'bar', 'gggg' => 123]);
        static::assertEquals(['foo' => 'bar', 'gggg' => 123, 'id' => 'xyz', 'parent' => 456], $node->toArray());
    }

    public function testWhenSerializingANodeToJsonItsArrayRepresentationIsUsed()
    {
        $node = new Node('xyz', 456, ['foo' => 'bar', 'gggg' => 123]);
        static::assertEquals(
            '{"foo":"bar","gggg":123,"id":"xyz","parent":456}',
            json_encode($node)
        );
    }

    public function testInScalarContextTheNodeIsTypecastedToItsId()
    {
        $node = new Node(123, null);
        static::assertEquals('123', (string) $node);
    }

    public function testTheLevelOfARootNodeIs0()
    {
        $node = new Node(0, null);

        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, null);

        static::assertSame(0, $node->getLevel());
    }

    public function testANode2LevelsBelowTheRootNodeHasLevel2()
    {
        $node = new Node(123, null);
        $parent = new Node(789, null);
        $rootNode = new Node(0, null);

        $parentProperty = new \ReflectionProperty(Node::class, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, $parent);
        $parentProperty->setValue($parent, $rootNode);

        static::assertSame(2, $node->getLevel());
    }

    public function testTheRootNodesAncestorsIsAnEmptyArray()
    {
        $node = new Node(0, null);
        $parentProperty = new \ReflectionProperty($node, 'parent');
        $parentProperty->setAccessible(true);
        $parentProperty->setValue($node, null);

        static::assertEquals([], $node->getAncestors());
    }

    public function testANodesAncestorsCanBeRetrieved()
    {
        $parentProperty = new \ReflectionProperty(Node::class, 'parent');
        $parentProperty->setAccessible(true);

        $node = new Node(3, null);
        $parent = new Node(2, null);
        $grandParent = new Node(1, null);
        $rootNode = new Node(0, null);

        $parentProperty->setValue($node, $parent);
        $parentProperty->setValue($parent, $grandParent);
        $parentProperty->setValue($grandParent, $rootNode);

        static::assertSame([$parent, $grandParent], $node->getAncestors());
    }

    public function testANodesAncestorsCanBeRetrievedIncludingTheNodeItself()
    {
        $parentProperty = new \ReflectionProperty(Node::class, 'parent');
        $parentProperty->setAccessible(true);

        $node = new Node(3, null);
        $parent = new Node(2, null);
        $grandParent = new Node(1, null);
        $rootNode = new Node(0, null);

        $parentProperty->setValue($node, $parent);
        $parentProperty->setValue($parent, $grandParent);
        $parentProperty->setValue($grandParent, $rootNode);

        static::assertSame([$node, $parent, $grandParent], $node->getAncestorsAndSelf());
    }

    public function testANodesDescendantsCanBeRetrieved()
    {
        $childrenProperty = new \ReflectionProperty(Node::class, 'children');
        $childrenProperty->setAccessible(true);

        $node = new Node(1, null);
        $child1 = new Node(2, null);
        $child2 = new Node(3, null);
        $grandChild1 = new Node(4, null);
        $grandChild2 = new Node(5, null);

        $childrenProperty->setValue($node, [$child1, $child2]);
        $childrenProperty->setValue($child1, [$grandChild1, $grandChild2]);

        static::assertSame(
            [$child1, $grandChild1, $grandChild2, $child2],
            $node->getDescendants()
        );
    }

    /**
     * At the moment, this is an almost exact copy of test method
     * getANodesDescendantsIncludingTheNodeItself(). This will change when the
     * argument to getDescendants() is removed from the API.
     */
    public function testANodesDescendantsCanBeRetrievedIncludingTheNodeItself()
    {
        $childrenProperty = new \ReflectionProperty(Node::class, 'children');
        $childrenProperty->setAccessible(true);

        $node = new Node(1, null);
        $child1 = new Node(2, null);
        $child2 = new Node(3, null);
        $grandChild1 = new Node(4, null);
        $grandChild2 = new Node(5, null);

        $childrenProperty->setValue($node, [$child1, $child2]);
        $childrenProperty->setValue($child1, [$grandChild1, $grandChild2]);

        static::assertSame(
            [$node, $child1, $grandChild1, $grandChild2, $child2],
            $node->getDescendantsAndSelf()
        );
    }
}
