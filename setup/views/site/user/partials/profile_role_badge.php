<?php
/**
 * Profil sayfası — oturumdaki kullanıcının rol rozeti.
 * @var object|null $user Kullanıcı modeli (isadmin alanı)
 */
use App\Helpers\AuthHelper;

$eshRoleLevel = isset($user) && isset($user->isadmin)
    ? AuthHelper::clampLevel((int) $user->isadmin)
    : AuthHelper::sessionAdminLevel();
$eshRoleClass = AuthHelper::adminLevelBadgeClass($eshRoleLevel);
?>
<span class="badge <?= htmlspecialchars($eshRoleClass, ENT_QUOTES, 'UTF-8') ?> px-3"><?= htmlspecialchars(AuthHelper::adminLevelLabel($eshRoleLevel), ENT_QUOTES, 'UTF-8') ?></span>
