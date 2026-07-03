<?php
/**
 * Profil avatarı — güncelle (kamera) ve isteğe bağlı sil (dosya diskteyse).
 *
 * @var \App\Models\User $user
 * @var string|null $eshAvatarWrapClass
 * @var string|null $eshAvatarContainerClass
 * @var string|null $eshAvatarImgClass
 * @var string|null $eshAvatarImgStyle
 * @var string|null $eshAvatarCamClass
 */
$eshUser = $user ?? null;
if (!$eshUser instanceof \App\Models\User) {
    return;
}

$eshAvatarWrapClass = $eshAvatarWrapClass ?? 'position-relative d-inline-block mb-3 mb-lg-4';
$eshAvatarContainerClass = $eshAvatarContainerClass ?? 'profile-img-container position-relative d-inline-block';
$eshAvatarImgClass = $eshAvatarImgClass ?? 'rounded-circle border border-4 border-white shadow-sm';
$eshAvatarImgStyle = $eshAvatarImgStyle ?? 'width: 150px; height: 150px; object-fit: cover;';
$eshAvatarCamClass = $eshAvatarCamClass ?? 'btn btn-primary position-absolute bottom-0 end-0 rounded-circle shadow-sm btn-sm';

$eshPhotoUrl = $eshUser->profileImageWebUrl();
$eshCanRemovePhoto = $eshUser->hasRemovableProfilePhoto();
?>
<div class="<?= htmlspecialchars($eshAvatarWrapClass, ENT_QUOTES, 'UTF-8') ?>">
    <div class="<?= htmlspecialchars($eshAvatarContainerClass, ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= htmlspecialchars($eshPhotoUrl, ENT_QUOTES, 'UTF-8') ?>"
             class="<?= htmlspecialchars($eshAvatarImgClass, ENT_QUOTES, 'UTF-8') ?>"
             style="<?= htmlspecialchars($eshAvatarImgStyle, ENT_QUOTES, 'UTF-8') ?>"
             width="150"
             height="150"
             alt="Profil fotoğrafı">

        <?php if ($eshCanRemovePhoto): ?>
            <form action="<?= htmlspecialchars(esh_url('User', 'removephoto'), ENT_QUOTES, 'UTF-8') ?>" method="post"
                  class="position-absolute top-0 start-0 m-0"
                  onsubmit="return confirm('Profil fotoğrafınız silinecek. Emin misiniz?');">
                <button type="submit"
                        class="btn btn-danger btn-sm rounded-circle shadow-sm"
                        title="Profil fotoğrafını sil"
                        aria-label="Profil fotoğrafını sil">
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                </button>
            </form>
        <?php endif; ?>

        <a href="<?= htmlspecialchars(esh_url('User', 'image'), ENT_QUOTES, 'UTF-8') ?>"
           class="<?= htmlspecialchars($eshAvatarCamClass, ENT_QUOTES, 'UTF-8') ?>"
           title="Resmi güncelle">
            <i class="fa-solid fa-camera" aria-hidden="true"></i>
        </a>
    </div>
</div>
