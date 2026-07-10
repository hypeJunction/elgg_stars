<?php

namespace ElggStars;

use Elgg\UnitTestCase;
use Elgg\Upgrade\AsynchronousUpgrade;
use Elgg\Upgrade\Result;
use ElggStars\Upgrades\EncodeSettingsAsJson;

/**
 * Static contract coverage for the EncodeSettingsAsJson upgrade batch.
 *
 * Guards two migration fixes with pure reflection (no DB, no Elgg services):
 *
 *   f60d0c7 — 5.x turned Batch into an abstract class hierarchy. The batch
 *             must EXTEND \Elgg\Upgrade\AsynchronousUpgrade, not `implements
 *             Batch`. If it reverts to implementing an interface the class
 *             fatals at load on 6.x/7.x.
 *
 *   57d8edf — 6.x's abstract AsynchronousUpgrade fixed the method signatures.
 *             run() must be run(Result, $offset): Result and the accessors
 *             must declare their scalar return types, or PHP throws a
 *             "must be compatible with abstract" fatal at class load.
 */
class UpgradeContractTest extends UnitTestCase {

	public function up() {}

	public function down() {}

	public function testExtendsAsynchronousUpgradeNotInterface(): void {
		$this->assertTrue(
			is_subclass_of(EncodeSettingsAsJson::class, AsynchronousUpgrade::class),
			'EncodeSettingsAsJson must extend \Elgg\Upgrade\AsynchronousUpgrade'
		);

		// The base must be an abstract class (5.x change) — not the pre-5.x interface.
		$base = new \ReflectionClass(AsynchronousUpgrade::class);
		$this->assertFalse(
			$base->isInterface(),
			'AsynchronousUpgrade is an abstract class since 5.x — a plugin that "implements" it fatals'
		);
	}

	public function testRunSignatureMatchesAbstractContract(): void {
		$run = new \ReflectionMethod(EncodeSettingsAsJson::class, 'run');

		$this->assertSame(2, $run->getNumberOfParameters(), 'run() must accept (Result $result, $offset)');

		$params = $run->getParameters();
		$this->assertNotNull($params[0]->getType());
		$this->assertSame(
			Result::class,
			$params[0]->getType()->getName(),
			'run() first parameter must be type-hinted \Elgg\Upgrade\Result'
		);

		$returnType = $run->getReturnType();
		$this->assertNotNull($returnType, 'run() must declare a return type');
		$this->assertSame(
			Result::class,
			$returnType->getName(),
			'run() must return \Elgg\Upgrade\Result'
		);
	}

	public function testAccessorReturnTypesAreScalar(): void {
		$expected = [
			'getVersion' => 'int',
			'countItems' => 'int',
			'needsIncrementOffset' => 'bool',
			'shouldBeSkipped' => 'bool',
		];

		foreach ($expected as $method => $type) {
			$ref = new \ReflectionMethod(EncodeSettingsAsJson::class, $method);
			$rt = $ref->getReturnType();
			$this->assertNotNull($rt, "{$method}() must declare a return type to satisfy the abstract contract");
			$this->assertSame($type, $rt->getName(), "{$method}() must return {$type}");
		}
	}
}
