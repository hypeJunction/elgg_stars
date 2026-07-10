<?php

namespace ElggStars;

use Elgg\Database\Clauses\OrderByClause;
use Elgg\Database\QueryBuilder;
use Elgg\IntegrationTestCase;

/**
 * Regression coverage for migration fix 68f91b8.
 *
 * The `highestrating` widget was rewritten to drop the deprecated
 * elgg_get_entities_from_annotation_calculation() API. It now orders entities
 * by AVG(annotation.value) DESC by joining the annotations table inside an
 * OrderByClause closure (joinAnnotationTable). This test replicates the exact
 * query shape the widget view uses and asserts the entities come back sorted
 * by their average rating, highest first — the property the widget depends on.
 */
class HighestRatingOrderTest extends IntegrationTestCase {

	public function up() {}

	public function down() {}

	public function getPluginID(): string {
		return 'elgg_stars';
	}

	public function testEntitiesOrderedByAverageRatingDesc(): void {
		$rater_a = $this->createUser();
		$rater_b = $this->createUser();

		$low = $this->createObject(['subtype' => 'blog']);
		$mid = $this->createObject(['subtype' => 'blog']);
		$high = $this->createObject(['subtype' => 'blog']);

		// Averages: high = 5.0, mid = 3.0, low = 1.0
		create_annotation($high->guid, 'starrating', 5, '', $rater_a->guid, ACCESS_PUBLIC);
		create_annotation($high->guid, 'starrating', 5, '', $rater_b->guid, ACCESS_PUBLIC);
		create_annotation($mid->guid, 'starrating', 4, '', $rater_a->guid, ACCESS_PUBLIC);
		create_annotation($mid->guid, 'starrating', 2, '', $rater_b->guid, ACCESS_PUBLIC);
		create_annotation($low->guid, 'starrating', 1, '', $rater_a->guid, ACCESS_PUBLIC);
		create_annotation($low->guid, 'starrating', 1, '', $rater_b->guid, ACCESS_PUBLIC);

		$annotation_names = ['starrating'];

		// Constrain to exactly these three guids so ordering is deterministic
		// even when other tests seeded rated blogs in the same prefix.
		$entities = elgg_get_entities([
			'guids' => [$low->guid, $mid->guid, $high->guid],
			'limit' => 10,
			'annotation_name_value_pairs' => [
				['name' => $annotation_names],
			],
			'order_by' => [
				new OrderByClause(
					function (QueryBuilder $qb) use ($annotation_names) {
						$alias = $qb->joinAnnotationTable('e', 'guid', $annotation_names, 'inner', 'star_rating');
						return "AVG({$alias}.value)";
					},
					'desc'
				),
			],
			'group_by' => 'e.guid',
		]);

		$ordered_guids = array_map(static fn ($e) => (int) $e->guid, $entities);

		$this->assertSame(
			[(int) $high->guid, (int) $mid->guid, (int) $low->guid],
			$ordered_guids,
			'widget query must return entities ordered by average rating descending'
		);
	}
}
