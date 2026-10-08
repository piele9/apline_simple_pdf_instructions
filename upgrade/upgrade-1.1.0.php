<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    $id = (int) Tab::getIdFromClassName($module::ADMIN_CONTROLLER);
    if (!$id) {
        return false;
    }
    $tab = new Tab($id);
    foreach (Language::getLanguages(false) as $language) {
        $tab->name[$language['id_lang']] = 'Instrukcje PDF APLINE';
    }

    return (bool) $tab->update();
}
