<?php

namespace ElggStars;

use Elgg\UnitTestCase;

/**
 * Unit coverage for the elgg_stars_encode_setting() / elgg_stars_decode_setting()
 * helpers introduced in the 3.x migration.
 *
 * These helpers replaced the pre-3.x serialize()/unserialize() of stored plugin
 * settings to avoid PHP object-injection risk. The decoder must accept both the
 * preferred JSON format AND legacy PHP-serialized payloads so existing data
 * keeps working until the EncodeSettingsAsJson upgrade batch runs.
 *
 * Pure-PHP — no Elgg services, no database.
 */
class SettingsEncodingTest extends UnitTestCase {

	public function up() {}

	public function down() {}

	public function testEncodeReturnsJsonForArray(): void {
		$encoded = elgg_stars_encode_setting(['object:blog', 'object:page']);
		$this->assertSame('["object:blog","object:page"]', $encoded);
	}

	public function testEncodeReturnsJsonForAssociativeArray(): void {
		$encoded = elgg_stars_encode_setting([
			'object:blog' => ['accuracy', 'clarity'],
		]);
		$decoded = json_decode($encoded, true);
		$this->assertSame(['object:blog' => ['accuracy', 'clarity']], $decoded);
	}

	public function testEncodeRoundtripsScalar(): void {
		$this->assertSame('5', elgg_stars_encode_setting(5));
		$this->assertSame('"hello"', elgg_stars_encode_setting('hello'));
		$this->assertSame('null', elgg_stars_encode_setting(null));
	}

	public function testDecodeReturnsNullForEmptyOrNonString(): void {
		$this->assertNull(elgg_stars_decode_setting(''));
		$this->assertNull(elgg_stars_decode_setting(null));
	}

	public function testDecodeJsonPayload(): void {
		$payload = json_encode(['object:blog', 'object:page']);
		$this->assertSame(['object:blog', 'object:page'], elgg_stars_decode_setting($payload));
	}

	public function testDecodeJsonNullLiteral(): void {
		// 'null' is a valid JSON document — decoder must distinguish it from
		// unreadable garbage and return null without falling through to the
		// legacy serialized path.
		$this->assertNull(elgg_stars_decode_setting('null'));
	}

	public function testDecodeLegacySerializedPayload(): void {
		$legacy = serialize(['object:blog', 'object:page']);
		$this->assertSame(
			['object:blog', 'object:page'],
			elgg_stars_decode_setting($legacy)
		);
	}

	public function testDecodeLegacySerializedAssociativeArray(): void {
		$legacy = serialize([
			'object:blog' => ['accuracy', 'clarity'],
			'object:page' => ['relevance'],
		]);
		$this->assertSame(
			[
				'object:blog' => ['accuracy', 'clarity'],
				'object:page' => ['relevance'],
			],
			elgg_stars_decode_setting($legacy)
		);
	}

	public function testDecodeLegacySerializedFalse(): void {
		// 'b:0;' is the only legitimate serialize() output that is literally
		// equal to the function's failure sentinel — the decoder must round-trip
		// it as boolean false rather than treating it as garbage.
		$this->assertFalse(elgg_stars_decode_setting('b:0;'));
	}

	public function testDecodeRejectsGarbage(): void {
		// Neither valid JSON nor valid serialize() output → null.
		$this->assertNull(elgg_stars_decode_setting('this is not a payload'));
	}

	public function testDecodeRefusesToInstantiateObjects(): void {
		// Legacy serialized payload referencing an arbitrary class must NOT
		// instantiate it — the decoder passes allowed_classes => false to
		// unserialize. The result is a stub __PHP_Incomplete_Class, not an
		// actual ElggUser etc.
		$legacy = serialize(new \stdClass());
		$decoded = elgg_stars_decode_setting($legacy);
		$this->assertNotInstanceOf(\stdClass::class, $decoded);
	}

	public function testEncodeDecodeRoundtrip(): void {
		$original = [
			'object:blog' => ['accuracy', 'clarity'],
			'object:page' => ['relevance', 'depth'],
		];
		$encoded = elgg_stars_encode_setting($original);
		$decoded = elgg_stars_decode_setting($encoded);
		$this->assertSame($original, $decoded);
	}
}
