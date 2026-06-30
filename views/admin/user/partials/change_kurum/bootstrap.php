<?php
/** @var object $user */
/** @var list<object> $kurumlar */
/** @var object|null $currentKurum */
use App\Helpers\AuthHelper;

$targetLevel = max(0, min(2, (int) ($user->isadmin ?? 0)));
$assignableLevels = AuthHelper::assignableAdminLevels();
