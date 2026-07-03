<?php
$mailboxType = 'sent';
$mailboxTitle = 'Giden Kutusu';
$mailboxDescription = 'Gönderdiğiniz mesajların bulunduğu konuşmalar';
$inboxRowsFetchUrl = esh_url('Mesaj', 'inboxRows', ['kutu' => 'sent']);
include ROOT_PATH . '/views/site/mesaj/mailbox.php';
