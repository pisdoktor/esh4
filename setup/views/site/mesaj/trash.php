<?php
$mailboxType = 'trash';
$mailboxTitle = 'Çöp Kutusu';
$mailboxDescription = 'Sildiğiniz konuşmalar — geri yükleyebilir veya kalıcı silebilirsiniz';
$inboxRowsFetchUrl = esh_url('Mesaj', 'inboxRows', ['kutu' => 'trash']);
include ROOT_PATH . '/views/site/mesaj/mailbox.php';
