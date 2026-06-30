<?php

use App\Helpers\ValidationHelper;

use App\Models\Uhds;

?>

<h3 class="h6 fw-bold border-bottom pb-2 mb-2">Bu güne kayıtlı randevular</h3>

<div class="table-responsive mb-4">

    <table class="table table-sm align-middle mb-0">

        <thead class="table-light">

            <tr>

                <th>Hasta</th>

                <th>İstek</th>

                <th>Branş</th>

                <th>Zaman</th>

                <th style="min-width: 7.5rem;">Yapıldı mı?</th>

                <th class="text-end">İşlem</th>

            </tr>

        </thead>

        <tbody id="esh-uhds-day-tbody"

               data-esh-fetch-url="<?= htmlspecialchars($dayAppointmentRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <tr class="esh-uhds-day-loading-row">

                <td colspan="6" class="text-center text-muted py-4 small">Liste yükleniyor…</td>

            </tr>

        </tbody>

    </table>

</div>

