<?php
/**
 * APLINE Simple PDF Instructions module for PrestaShop 9.
 *
 * Configurable PDF-download buttons on the product page, each mapped to
 * a product-attachment slot. Reads native PrestaShop attachments — does
 * NOT manage PDF files itself.
 *
 * @author    APLINE Arkadiusz Pielechowski
 * @copyright APLINE Arkadiusz Pielechowski
 * @license   Custom Attribution License v1.0 - see LICENSE.md
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/AplineSimplePdfInstructionsButton.php';

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class apline_simple_pdf_instructions extends Module implements WidgetInterface
{
    const HOOK_KEY = 'ASPD_HOOK';

    const ADMIN_CONTROLLER = 'AdminAplineSimplePdfInstructionsButton';

    /** @var string */
    private $templateFile = 'module:apline_simple_pdf_instructions/views/templates/hook/buttons.tpl';

    /**
     * Hooks the block may be displayed on. Key = hook name, value = admin label.
     *
     * @return array
     */
    public static function getAvailableHooks()
    {
        return [
            'displayProductAdditionalInfo' => 'Product page (reassurance area)',
            'displayLeftColumn' => 'Left column',
            'displayRightColumn' => 'Right column',
            'displayFooterProduct' => 'Product page footer',
        ];
    }

    public function __construct()
    {
        $this->name = 'apline_simple_pdf_instructions';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'APLINE Arkadiusz Pielechowski';
        $this->need_instance = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('APLINE Simple PDF Instructions for PrestaShop 9', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        $this->description = $this->trans('Configurable PDF-download buttons on the product page, each mapped to a product-attachment slot.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall this module? All button definitions will be deleted.', [], 'Modules.Aplinesimplepdfinstructions.Admin');

        $this->ps_versions_compliancy = ['min' => '9.0', 'max' => _PS_VERSION_];
    }

    /**
     * @return string absolute path to the upload directory
     */
    public function getUploadDir()
    {
        return _PS_MODULE_DIR_ . $this->name . '/views/img/';
    }

    /**
     * @return bool whether the upload directory is writable
     */
    public function isUploadDirWritable()
    {
        $dir = $this->getUploadDir();

        return is_dir($dir) && is_writable($dir);
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->installDb()
            || !$this->installConfiguration()
            || !$this->installHooks()
            || !$this->installTab()
        ) {
            // Roll back to a clean state so the shop is never left half-installed.
            $this->uninstall();
            $this->_errors[] = $this->trans('Installation failed and was rolled back. Please check folder permissions and try again.', [], 'Modules.Aplinesimplepdfinstructions.Admin');

            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // Each step is idempotent; uninstall must not fail because something is already gone.
        $this->uninstallTab();
        $this->deleteUploadedFiles();

        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'aspd_button`');

        Configuration::deleteByName(self::HOOK_KEY);

        return parent::uninstall();
    }

    /**
     * @return bool
     */
    private function installDb()
    {
        // No demo seed — buttons without matching product attachments would be ghost
        // buttons on the front. The first real button is configured by the admin.
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aspd_button` (
            `id_aspd_button` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `slot_position` INT(10) UNSIGNED NOT NULL,
            `icon_image` VARCHAR(255) DEFAULT NULL,
            `icon_entity` VARCHAR(255) DEFAULT NULL,
            `icon_position` ENUM(\'none\', \'left\', \'right\', \'both\') NOT NULL DEFAULT \'left\',
            `own_string` VARCHAR(255) DEFAULT NULL,
            `append_product_name` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            `button_color` VARCHAR(7) NOT NULL DEFAULT \'#dc3545\',
            `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            `position` INT(10) UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_aspd_button`),
            KEY `idx_active_position` (`active`, `position`),
            KEY `idx_slot` (`slot_position`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return (bool) Db::getInstance()->execute($sql);
    }

    /**
     * @return bool
     */
    private function installConfiguration()
    {
        return Configuration::updateValue(self::HOOK_KEY, 'displayProductAdditionalInfo');
    }

    /**
     * @return bool
     */
    private function installHooks()
    {
        $ok = $this->registerHook('actionFrontControllerSetMedia');
        foreach (array_keys(self::getAvailableHooks()) as $hook) {
            $ok = $ok && $this->registerHook($hook);
        }

        return $ok;
    }

    /**
     * @return bool
     */
    private function installTab()
    {
        if (Tab::getIdFromClassName(self::ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = self::ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = 1;
        // Hidden tab (no visible parent): managed from the module configuration page.
        $tab->id_parent = -1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Simple PDF Instructions';
        }

        return (bool) $tab->add();
    }

    /**
     * @return bool
     */
    private function uninstallTab()
    {
        $id = (int) Tab::getIdFromClassName(self::ADMIN_CONTROLLER);
        if (!$id) {
            return true;
        }

        try {
            $tab = new Tab($id);

            return (bool) $tab->delete();
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Remove uploaded icons. Only ever touches files inside the module folder.
     */
    private function deleteUploadedFiles()
    {
        $dir = $this->getUploadDir();
        if (!is_dir($dir)) {
            return;
        }

        foreach ((array) glob($dir . 'aspd_*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Normalize the icon field to a renderable HTML entity.
     * Accepts a bare unicode hex (e.g. "1F4C4") or an already-formed entity.
     *
     * @param string $icon
     *
     * @return string
     */
    public static function normalizeIcon($icon)
    {
        $icon = trim((string) $icon);
        if ($icon === '') {
            return '';
        }
        if (preg_match('/^[0-9A-Fa-f]{1,6}$/', $icon)) {
            return '&#x' . strtoupper($icon) . ';';
        }
        // Already an entity: keep as-is (validated by a whitelist on save).
        if (preg_match('/^(&#x?[0-9A-Fa-f]+;|&[a-zA-Z]+;)$/', $icon)) {
            return $icon;
        }

        return '';
    }

    public function getContent()
    {
        $output = '';

        if (!$this->isUploadDirWritable()) {
            $output .= $this->displayWarning($this->trans('The upload folder is not writable: %s. Icon uploads will fail until you fix its permissions (e.g. chmod 0775).', [$this->getUploadDir()], 'Modules.Aplinesimplepdfinstructions.Admin'));
        }

        // Render the "Manage buttons" entry panel (configure.tpl). The
        // ASPD_HOOK selector form lands in checkpoint 04 — for now the
        // configuration page exposes only the button-list link plus the
        // mandatory APLINE attribution.
        $manageUrl = $this->context->link->getAdminLink(self::ADMIN_CONTROLLER);

        $this->context->smarty->assign([
            'aspd_manage_url' => $manageUrl,
        ]);
        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');

        return $output . $this->renderLikeBox() . $this->renderAplineFooter();
    }

    /**
     * APLINE attribution block. Required by the module license to stay visible
     * on the configuration page with a working link to https://apline.pl.
     * Rendered server-side as a standalone component (not CSS-only) so it
     * cannot be trivially stripped.
     *
     * @return string
     */
    public function renderAplineFooter()
    {
        return '
        <style>
            .apline-credit { margin-top: 24px; font-size: 12px; opacity: 0.9; }
            .apline-credit a { font-weight: 600; }
        </style>
        <div class="apline-credit">
            ' . $this->trans('Module created by', [], 'Modules.Aplinesimplepdfinstructions.Admin') . '
            <a href="https://apline.pl" target="_blank" rel="noopener noreferrer">APLINE</a>
        </div>';
    }

    /**
     * Subtle "need custom development?" box shown on the configuration page.
     *
     * @return string
     */
    public function renderLikeBox()
    {
        return '
        <div class="panel">
            <h3>&#9749; ' . $this->trans('Like this module?', [], 'Modules.Aplinesimplepdfinstructions.Admin') . '</h3>
            <p>' . $this->trans('Need custom PrestaShop development, performance optimization or integrations?', [], 'Modules.Aplinesimplepdfinstructions.Admin') . '</p>
            <a class="btn btn-default" href="https://apline.pl" target="_blank" rel="noopener noreferrer">&#8594; APLINE.PL</a>
        </div>';
    }

    public function hookActionFrontControllerSetMedia()
    {
        // Stub for checkpoint 01 — actual stylesheet registration arrives in checkpoint 04
        // (along with views/css/front.css and the front-end render).
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        return $this->renderForHook('displayProductAdditionalInfo');
    }

    public function hookDisplayLeftColumn($params)
    {
        return $this->renderForHook('displayLeftColumn');
    }

    public function hookDisplayRightColumn($params)
    {
        return $this->renderForHook('displayRightColumn');
    }

    public function hookDisplayFooterProduct($params)
    {
        return $this->renderForHook('displayFooterProduct');
    }

    /**
     * Render the buttons block only on the hook selected in configuration.
     * Wrapped so any failure yields an empty block instead of a 500.
     *
     * Stub for checkpoint 01 — always returns '' because there is no Smarty
     * template yet. Real rendering arrives in checkpoint 04.
     *
     * @param string $hookName
     *
     * @return string
     */
    private function renderForHook($hookName)
    {
        try {
            if (Configuration::get(self::HOOK_KEY) !== $hookName) {
                return '';
            }

            // Checkpoint 04 will populate variables and render
            // views/templates/hook/buttons.tpl here.
            return '';
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('apline_simple_pdf_instructions: ' . $e->getMessage(), 3);

            return '';
        }
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        try {
            // Checkpoint 04 will populate Smarty variables and fetch
            // $this->templateFile here.
            return '';
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('apline_simple_pdf_instructions: ' . $e->getMessage(), 3);

            return '';
        }
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        // Checkpoint 04 will return ['buttons' => [...]] here based on the
        // current product context.
        return [];
    }
}
