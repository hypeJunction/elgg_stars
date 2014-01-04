<?php
/**
 * RateIt StyleSheet with adaptations
 */
$star_url = elgg_normalize_url('mod/elgg_stars/vendors/rateit/star.gif');
$del_url = elgg_normalize_url('mod/elgg_stars/vendors/rateit/delete.gif');
?>

<?php if (false) : ?><style type="text/css"><?php endif; ?>

	.rateit {
		display: -moz-inline-box;
		display: inline-block;
		position: relative;
		-webkit-user-select: none;
		-khtml-user-select: none;
		-moz-user-select: none;
		-o-user-select: none;
		-ms-user-select: none;
		user-select: none;
		-webkit-touch-callout: none;
	}

	.rateit .rateit-range
	{
		position: relative;
		display: -moz-inline-box;
		display: inline-block;
		background: url(<?php echo $star_url ?>);
		height: 16px;
		outline: none;
	}

	.rateit .rateit-range * {
		display:block;
	}

	/* for IE 6 */
	* html .rateit, * html .rateit .rateit-range
	{
		display: inline;
	}

	/* for IE 7 */
	* + html .rateit, * + html .rateit .rateit-range
	{
		display: inline;
	}

	.rateit .rateit-hover, .rateit .rateit-selected
	{
		position: absolute;
		left: 0px;
	}

	.rateit .rateit-hover-rtl, .rateit .rateit-selected-rtl
	{
		left: auto;
		right: 0px;
	}

	.rateit .rateit-hover
	{
		background: url(<?php echo $star_url ?>) left -32px;
	}

	.rateit .rateit-hover-rtl
	{
		background-position: right -32px;
	}

	.rateit .rateit-selected
	{
		background: url(<?php echo $star_url ?>) left -16px;
	}

	.rateit .rateit-selected-rtl
	{
		background-position: right -16px;
	}

	.rateit .rateit-preset
	{
		background: url(<?php echo $star_url ?>) left -48px;
	}

	.rateit .rateit-preset-rtl
	{
		background: url(<?php echo $star_url ?>) left -48px;
	}

	.rateit button.rateit-reset
	{
		background: url(<?php echo $del_url ?>) 0 0;
		width: 16px;
		height: 16px;
		display: -moz-inline-box;
		display: inline-block;
		float: left;
		outline: none;
		border:none;
		padding: 0;
	}

	.rateit button.rateit-reset:hover, .rateit button.rateit-reset:focus
	{
		background-position: 0 -16px;
	}


	// Custom

	.elgg-form-stars-rate {
		margin: 0;
		padding: 0;
	}
	.elgg-form-stars-rate fieldset {
		margin: 0;
		padding: 0;
	}
	.elgg-form-stars-rate fieldset > div {
		margin-bottom: 0;
	}
	.elgg-form-stars-rate input[type="submit"] {
		display: none;
	}

	.elgg-stars-rating-caption {
		width: 100%;
		font-size: 90%;
		text-align: center;
	}
	
	<?php if (false) : ?></style><?php endif; ?>
