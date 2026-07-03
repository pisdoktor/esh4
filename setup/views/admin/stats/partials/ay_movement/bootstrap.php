<?php
/** @var object $g */
/** @var int $year */
/** @var int $month */
/** @var string $period_label */
/** @var array<int, string> $turkce_aylar */
/** @var bool $filterExpanded */
$gen = $g->general ?? (object) [];
$new = $g->new ?? (object) [];
$ex = $g->exit ?? (object) [];
$newT = (int) ($new->new_male ?? 0) + (int) ($new->new_female ?? 0);
$exT = (int) ($ex->exit_male ?? 0) + (int) ($ex->exit_female ?? 0);
$filterExpanded = !empty($filterExpanded);
$yearMax = (int) date('Y');
$yearMin = 2010;
?>