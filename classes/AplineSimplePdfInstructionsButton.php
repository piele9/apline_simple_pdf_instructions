<?php
/**
 * APLINE Simple PDF Instructions module for PrestaShop 9.
 *
 * STUB ObjectModel — checkpoint 01 only.
 * Full implementation (fields validation, getActiveButtons, getNextPosition,
 * add() override) lands in checkpoint 02.
 *
 * @author    APLINE Arkadiusz Pielechowski
 * @copyright APLINE Arkadiusz Pielechowski
 * @license   Custom Attribution License v1.0 - see LICENSE.md
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AplineSimplePdfInstructionsButton extends ObjectModel
{
    /**
     * @see ObjectModel::$definition
     *
     * Minimal shim. Full field set + validate rules are added in
     * checkpoint 02. Keeping `$definition` valid so `require_once` of
     * this file does not break module install during checkpoint 01.
     */
    public static $definition = [
        'table' => 'aspd_button',
        'primary' => 'id_aspd_button',
        'multilang' => false,
        'fields' => [],
    ];
}
