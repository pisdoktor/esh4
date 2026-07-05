<?php
declare(strict_types=1);
if (!empty($GLOBALS['eshPatientViewEditModals'])) {
    echo esh_csp_script_src_tag(ASSETS_URL . '/pages/js/patient-edit.js');
}
