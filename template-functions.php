<?php

// two drpp-specific Template Tags, to be used in the drpp-template Loop.

function the_score() {
	echo get_the_score();
}

function get_the_score() { // returns the score
	global $post;
	$score = $post->score;
	return apply_filters('get_the_score', $score, $d, $gmt);
}
