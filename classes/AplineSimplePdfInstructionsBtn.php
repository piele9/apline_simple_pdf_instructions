<?php
/**
 * APLINE Simple PDF Instructions module for PrestaShop 9.
 *
 * ObjectModel for the `aspd_button` table.
 *
 * Each row represents one button definition that maps to a specific
 * product-attachment slot. The actual PDF file lives in PrestaShop's
 * native `ps_product_attachment`; this table only stores how to render
 * the button (icon, label, color, slot index).
 *
 * @author    APLINE Arkadiusz Pielechowski
 * @copyright APLINE Arkadiusz Pielechowski
 * @license   Custom Attribution License v1.0 - see LICENSE.md
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AplineSimplePdfInstructionsBtn extends ObjectModel
{
    /** @var int slot index in ps_product_attachment (1-based) */
    public $slot_position;
    /** @var string|null relative public path to the uploaded icon image */
    public $icon_image;
    /** @var string|null unicode hex or HTML entity for the icon */
    public $icon_entity;
    /** @var string icon position relative to the label: none|left|right|both */
    public $icon_position;
    /** @var string source of the label text: own|filename */
    public $label_source;
    /** @var string|null custom label text (255 chars max) */
    public $own_string;
    /** @var bool whether to append the product name to the label */
    public $append_product_name;
    /** @var string hex color of the button background (#RRGGBB) */
    public $button_color;
    /** @var bool */
    public $active;
    /** @var int */
    public $position;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'aspd_button',
        'primary' => 'id_aspd_button',
        'multilang' => false,
        'fields' => [
            'slot_position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'icon_image' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255],
            'icon_entity' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255],
            'icon_position' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 8, 'required' => true],
            'label_source' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 8, 'required' => true],
            'own_string' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255],
            'append_product_name' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'button_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7, 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * Whitelist of valid `icon_position` values. Enforced by the
     * AdminController in checkpoint 03; documented here as the source
     * of truth.
     *
     * @var string[]
     */
    const ICON_POSITIONS = ['none', 'left', 'right', 'both'];

    /**
     * Whitelist of valid `label_source` values. Enforced by the
     * AdminController; documented here as the source of truth.
     *
     * @var string[]
     */
    const LABEL_SOURCES = ['own', 'filename'];

    /**
     * Active buttons ordered by position, for front rendering.
     * Guarded so a missing or corrupted table never breaks the shop front
     * .
     *
     * @return array
     */
    public static function getActiveButtons()
    {
        try {
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'aspd_button`
                WHERE `active` = 1
                ORDER BY `position` ASC, `id_aspd_button` ASC';

            $result = Db::getInstance()->executeS($sql);

            return is_array($result) ? $result : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Next free position value, used to default new rows to the end of
     * the list.
     *
     * @return int
     */
    public static function getNextPosition()
    {
        try {
            $max = (int) Db::getInstance()->getValue(
                'SELECT MAX(`position`) FROM `' . _DB_PREFIX_ . 'aspd_button`'
            );

            return $max + 1;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    /**
     * @see ObjectModel::add()
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (empty($this->position)) {
            $this->position = self::getNextPosition();
        }

        return parent::add($auto_date, $null_values);
    }
}
