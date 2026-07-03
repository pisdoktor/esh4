<?php
namespace App\Helpers;

class AlertHelper {
    /**
     * Session'daki mesajları kontrol eder ve Toastr kodunu basar
     */
    public static function display() {
        $script = "";

        // Başarı Mesajı
        if (isset($_SESSION['success'])) {
            $msg = addslashes($_SESSION['success']);
            $script .= "toastr.success('{$msg}', 'Başarılı');";
            unset($_SESSION['success']);
        }

        // Hata Mesajı
        if (isset($_SESSION['error'])) {
            $msg = addslashes($_SESSION['error']);
            $script .= "toastr.error('{$msg}', 'Hata');";
            unset($_SESSION['error']);
        }

        // Bilgi Mesajı
        if (isset($_SESSION['info'])) {
            $msg = addslashes($_SESSION['info']);
            $script .= "toastr.info('{$msg}', 'Bilgi');";
            unset($_SESSION['info']);
        }

        if ($script) {
            echo "<script>
                $(document).ready(function() {
                    toastr.options = {
                        'closeButton': true,
                        'progressBar': true,
                        'positionClass': 'toast-top-right',
                        'timeOut': '5000'
                    };
                    {$script}
                });
            </script>";
        }
    }
}