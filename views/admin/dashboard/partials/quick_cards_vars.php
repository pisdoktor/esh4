<?php

declare(strict_types=1);



use App\Helpers\AppSettings;

use App\Helpers\AuthHelper;



$quickCards = [];

if (AuthHelper::sessionIsSuperAdmin()) {

    $quickCards[] = ['href' => esh_url('Settings', 'index'), 'title' => 'Uygulama ayarları', 'icon' => 'fa-sliders', 'color' => 'primary'];
    $quickCards[] = ['href' => esh_url('User', 'list'), 'title' => 'Kullanıcı Yönetimi', 'icon' => 'fa-users-gear', 'color' => 'secondary'];

}

if (AppSettings::isModuleEnabled('erapor')) {

    $quickCards[] = ['href' => esh_url('Erapor', 'create'), 'title' => 'Yeni e-Rapor', 'icon' => 'fa-file-circle-plus', 'color' => 'primary'];

}

if (AppSettings::isModuleEnabled('archive')) {

    $quickCards[] = ['href' => esh_url('Archive', 'index'), 'title' => 'Hasta dosya sistemi', 'icon' => 'fa-box-archive', 'color' => 'success'];

}

if (AuthHelper::sessionIsSuperAdmin() && AppSettings::isModuleEnabled('harita')) {

    $quickCards[] = ['href' => esh_url('Harita', 'index'), 'title' => 'Hasta haritası', 'icon' => 'fa-map-marked-alt', 'color' => 'danger'];

}

if (AuthHelper::sessionIsAdmin() && !AuthHelper::sessionIsSuperAdmin()) {
    $quickCards[] = ['href' => esh_url('User', 'list'), 'title' => 'Kullanıcı Yönetimi', 'icon' => 'fa-users-gear', 'color' => 'secondary'];
}

$quickCards = array_merge($quickCards, [

    ['href' => esh_url('Stats', 'operationsPulse'), 'title' => 'Operasyonel Nabız', 'icon' => 'fa-heart-pulse', 'color' => 'danger'],

    ['href' => esh_url('Stats', 'kayitMonths'), 'title' => 'Kayıt ayları', 'icon' => 'fa-calendar-plus', 'color' => 'primary'],

]);

if (AuthHelper::sessionIsSuperAdmin()) {

    $quickCards[] = ['href' => esh_url('Brans', 'index'), 'title' => 'Branş ve Kota Yönetimi', 'icon' => 'fa-tags', 'color' => 'info'];

    $quickCards[] = ['href' => esh_url('DbMaintenance', 'index'), 'title' => 'DB Bakım / Yedek', 'icon' => 'fa-database', 'color' => 'warning'];

}

$quickCards = array_merge($quickCards, [

    ['href' => esh_url('Planning', 'index'), 'title' => 'Mahalle Planlama', 'icon' => 'fa-map-location-dot', 'color' => 'success'],

    ['href' => esh_url('Stats', 'adresPatientFilter'), 'title' => 'Adrese göre hastalar', 'icon' => 'fa-map-pin', 'color' => 'info'],

]);

