<?php
/** @var object $user */
/** @var list<object> $kurumlar */
/** @var object|null $currentKurum */
use App\Helpers\AuthHelper;

$targetLevel = AuthHelper::clampLevel((int) ($user->isadmin ?? 0));
$assignableLevels = AuthHelper::assignableAdminLevels();
