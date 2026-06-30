<?php

/**

 * Araç tanımları tablo satırları (tbody içi; yalnızca <tr>…</tr>).

 * @var list<object> $items

 */

$eshAracShowKurumCol = \App\Helpers\AuthHelper::sessionIsSuperAdmin();

$eshAracListColspan = $eshAracShowKurumCol ? 5 : 4;

if (!empty($items)) {

    foreach ($items as $row): ?>

        <tr>

            <td><?= (int) $row->id ?></td>

            <td class="fw-bold font-monospace"><?= htmlspecialchars((string) ($row->plaka ?? ''), ENT_QUOTES, 'UTF-8') ?></td>

            <td><?= htmlspecialchars((string) ($row->arac_bilgisi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>

            <?php if ($eshAracShowKurumCol): ?>

            <td class="small text-muted">

                <?php

                $eshKurumAd = trim((string) ($row->kurum_ad ?? ''));

                $eshKurumKod = trim((string) ($row->kurum_slug ?? ''));

                if ($eshKurumAd !== '') {

                    echo htmlspecialchars($eshKurumAd, ENT_QUOTES, 'UTF-8');

                    if ($eshKurumKod !== '') {

                        echo ' <span class="text-muted">(' . htmlspecialchars($eshKurumKod, ENT_QUOTES, 'UTF-8') . ')</span>';

                    }

                } else {

                    echo '—';

                }

                ?>

            </td>

            <?php endif; ?>

            <?= \App\Helpers\FormHelper::listActionsCell([

                [

                    'href' => esh_url('Arac', 'edit', array (

  'id' => '',

)) . (int) $row->id,

                    'title' => 'Düzenle',

                    'icon' => 'fas fa-edit',

                    'variant' => 'primary',

                ],

                [

                    'action' => esh_url('Arac', 'delete'),

                    'hidden' => ['id' => (int) $row->id],

                    'title' => 'Sil',

                    'icon' => 'fas fa-trash',

                    'variant' => 'danger',

                    'confirm' => 'Bu araç kaydını silmek istediğinize emin misiniz?',

                ],

            ], ['align' => 'center', 'smallTd' => false]) ?>

        </tr>

    <?php endforeach;

} else { ?>

    <tr><td colspan="<?= (int) $eshAracListColspan ?>" class="text-center py-4 text-muted">Kayıtlı araç yok. Tanım ekleyin.</td></tr>

<?php } ?>

