<?php
declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Helpers\FormHelper;
use App\Helpers\TenantContext;
use App\Models\Kurum;

/** @var object $patient */
if (!AuthHelper::sessionIsSuperAdmin() || !Kurum::tableExists()) {
    return;
}

$eshKurumList = TenantContext::kurumListForScope(true);
if ($eshKurumList === []) {
    return;
}

$eshPatientKurumId = isset($patient->kurum_id) ? (int) $patient->kurum_id : 0;
$eshKurumOptions = [];
foreach ($eshKurumList as $k) {
    $label = (string) ($k->ad ?? '');
    if (!empty($k->kod)) {
        $label .= ' (' . (string) $k->kod . ')';
    }
    $eshKurumOptions[] = FormHelper::makeOption((string) (int) ($k->id ?? 0), $label);
}
$eshKurumHelpHtml = (($eshKurumFieldContext ?? 'edit') === 'bedit')
    ? 'Bekleyen hasta kaydının kurumu. Kayıt sırasında güncellenir.'
    : 'Kaynak kurumda pasif (Başka Kuruma Nakil); hedef kurumda bekleyen nakil kaydı açılır. İzlem geçmişi kaynak kurumda kalır. '
        . '<a href="' . htmlspecialchars(esh_url('Patient', 'changeKurum', ['id' => (string) ($patient->id ?? '')]), ENT_QUOTES, 'UTF-8') . '">Kurum değiştirme sayfası</a>';
?>
<div class="col-12" id="eshPatientKurumFieldWrap">
    <?php
    $eshKurumFieldLabel = AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN);
    echo FormHelper::fieldSelect('kurum_id', 'Kurum (' . $eshKurumFieldLabel . ')', $eshKurumOptions, (string) $eshPatientKurumId, [
        'col' => '',
        'id' => 'eshPatientKurumSelect',
        'labelClass' => 'form-label small fw-bold text-muted',
        'labelHtml' => '<i class="fa-solid fa-building me-1"></i>Kurum (' . htmlspecialchars($eshKurumFieldLabel, ENT_QUOTES, 'UTF-8') . ')',
        'tomSelect' => false,
        'required' => true,
        'afterInput' => '<div class="form-text">' . $eshKurumHelpHtml . '</div>',
    ]);
    ?>
</div>
