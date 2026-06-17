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

require_once __DIR__ . '/classes/AplineSimplePdfInstructionsBtn.php';

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
        $this->version = '1.0.1';
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
            `label_source` ENUM(\'own\', \'filename\') NOT NULL DEFAULT \'own\',
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

        if (Tools::isSubmit('submitAspdConfig')) {
            $hook = (string) Tools::getValue(self::HOOK_KEY);

            if (!array_key_exists($hook, self::getAvailableHooks())) {
                $output .= $this->displayError($this->trans('Invalid display hook selected.', [], 'Modules.Aplinesimplepdfinstructions.Admin'));
            } else {
                Configuration::updateValue(self::HOOK_KEY, $hook);
                $output .= $this->displayConfirmation($this->trans('Settings updated.', [], 'Modules.Aplinesimplepdfinstructions.Admin'));
            }
        }

        if (!$this->isUploadDirWritable()) {
            $output .= $this->displayWarning($this->trans('The upload folder is not writable: %s. Icon uploads will fail until you fix its permissions (e.g. chmod 0775).', [$this->getUploadDir()], 'Modules.Aplinesimplepdfinstructions.Admin'));
        }

        $manageUrl = $this->context->link->getAdminLink(self::ADMIN_CONTROLLER);

        $this->context->smarty->assign([
            'aspd_manage_url' => $manageUrl,
        ]);
        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');

        return $output . $this->renderConfigForm() . $this->renderLikeBox() . $this->renderAplineFooter();
    }

    /**
     * @return string
     */
    private function renderConfigForm()
    {
        $hookOptions = [];
        foreach (self::getAvailableHooks() as $hookName => $label) {
            $hookOptions[] = ['id' => $hookName, 'name' => $label];
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Display settings', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->trans('Display location', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                        'name' => self::HOOK_KEY,
                        'options' => ['query' => $hookOptions, 'id' => 'id', 'name' => 'name'],
                        'desc' => $this->trans('You can also display the buttons anywhere with {widget name=\'apline_simple_pdf_instructions\'}.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    ],
                ],
                'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitAspdConfig';
        $helper->fields_value = [
            self::HOOK_KEY => Configuration::get(self::HOOK_KEY),
        ];

        return $helper->generateForm([$fields_form]);
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
        $this->context->controller->registerStylesheet(
            'apline-simple-pdf-instructions',
            'modules/' . $this->name . '/views/css/front.css'
        );
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        return $this->renderForHook('displayProductAdditionalInfo', $params);
    }

    public function hookDisplayLeftColumn($params)
    {
        return $this->renderForHook('displayLeftColumn', $params);
    }

    public function hookDisplayRightColumn($params)
    {
        return $this->renderForHook('displayRightColumn', $params);
    }

    public function hookDisplayFooterProduct($params)
    {
        return $this->renderForHook('displayFooterProduct', $params);
    }

    /**
     * Render the buttons block only on the hook selected in configuration.
     * Wrapped so any failure yields an empty block instead of a 500.
     *
     * @param string $hookName
     * @param array $params PrestaShop hook params (used to resolve the product)
     *
     * @return string
     */
    private function renderForHook($hookName, $params = [])
    {
        try {
            if (Configuration::get(self::HOOK_KEY) !== $hookName) {
                return '';
            }

            $product = $this->resolveProduct($params);
            if (!$product) {
                return '';
            }

            $buttons = $this->buildButtonsForProduct($product);
            if (!$buttons) {
                return '';
            }

            $this->smarty->assign(['buttons' => $buttons]);

            return $this->display(__FILE__, 'views/templates/hook/buttons.tpl');
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('apline_simple_pdf_instructions: ' . $e->getMessage(), 3);

            return '';
        }
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        try {
            $product = $this->resolveProduct($configuration);
            if (!$product) {
                return '';
            }

            $buttons = $this->buildButtonsForProduct($product);
            if (!$buttons) {
                return '';
            }

            $this->smarty->assign(['buttons' => $buttons]);

            return $this->fetch($this->templateFile);
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('apline_simple_pdf_instructions: ' . $e->getMessage(), 3);

            return '';
        }
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        try {
            $product = $this->resolveProduct($configuration);
            if (!$product) {
                return ['buttons' => []];
            }

            return ['buttons' => $this->buildButtonsForProduct($product)];
        } catch (\Throwable $e) {
            return ['buttons' => []];
        }
    }

    /**
     * Try to resolve a Product object from the hook/widget params or the
     * front controller context. Returns null if we are not on a product page.
     *
     * @param array $params
     *
     * @return Product|object|null
     */
    private function resolveProduct(array $params)
    {
        if (isset($params['product']) && is_object($params['product'])) {
            return $params['product'];
        }
        if (isset($params['product']) && is_array($params['product']) && !empty($params['product']['id_product'])) {
            return new Product((int) $params['product']['id_product'], false, (int) $this->context->language->id);
        }
        if (isset($params['id_product'])) {
            return new Product((int) $params['id_product'], false, (int) $this->context->language->id);
        }

        $controller = isset($this->context->controller) ? $this->context->controller : null;
        if ($controller && property_exists($controller, 'product') && is_object($controller->product)) {
            return $controller->product;
        }
        if ($controller && method_exists($controller, 'getProduct')) {
            try {
                $product = $controller->getProduct();
                if (is_object($product)) {
                    return $product;
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        return null;
    }

    /**
     * Build the per-product list of buttons to render. Each active button
     * is matched against the product attachment at `slot_position`
     * (1-indexed). Buttons without a matching attachment are skipped
     * silently — graceful skip is a core requirement (no empty buttons,
     * no errors).
     *
     * @param Product|object $product
     *
     * @return array[] each entry: [url, color, label, icon_left, icon_right]
     */
    private function buildButtonsForProduct($product)
    {
        $idProduct = (int) (isset($product->id) ? $product->id : 0);
        if (!$idProduct) {
            return [];
        }

        $idLang = (int) $this->context->language->id;

        // Read attachments straight from the DB to avoid PS API drift between
        // 8.x and 9.x. ORDER BY id_attachment ASC gives a stable 1-based slot
        // mapping: attachments[0] = slot 1, attachments[1] = slot 2, ...
        // attachment_lang.name is the human-friendly title set by the admin —
        // used as the label when label_source = 'filename'.
        $sql = 'SELECT a.id_attachment, a.file, al.name AS attachment_name
            FROM `' . _DB_PREFIX_ . 'product_attachment` pa
            INNER JOIN `' . _DB_PREFIX_ . 'attachment` a ON a.id_attachment = pa.id_attachment
            LEFT JOIN `' . _DB_PREFIX_ . 'attachment_lang` al
                ON al.id_attachment = a.id_attachment AND al.id_lang = ' . $idLang . '
            WHERE pa.id_product = ' . $idProduct . '
            ORDER BY a.id_attachment ASC';

        $attachments = Db::getInstance()->executeS($sql);
        if (!is_array($attachments)) {
            $attachments = [];
        }

        if (!$attachments) {
            return [];
        }

        $productName = '';
        if (isset($product->name)) {
            $productName = is_array($product->name)
                ? (isset($product->name[$idLang]) ? (string) $product->name[$idLang] : (string) reset($product->name))
                : (string) $product->name;
        }

        $buttons = [];
        foreach (AplineSimplePdfInstructionsBtn::getActiveButtons() as $btn) {
            $slot = (int) $btn['slot_position'];
            if ($slot < 1 || !isset($attachments[$slot - 1])) {
                continue;
            }

            $attachment = $attachments[$slot - 1];
            $idAttachment = (int) $attachment['id_attachment'];

            // PrestaShop's attachment controller serves the file by id.
            $url = $this->context->link->getPageLink(
                'attachment',
                null,
                $idLang,
                ['id_attachment' => $idAttachment]
            );

            // Compose label from the configured source:
            //   'filename' → attachment_lang.name (admin-set title); fallback
            //                to the storage file name without extension.
            //   'own'      → custom text, optionally with product name appended.
            $labelSource = isset($btn['label_source']) ? (string) $btn['label_source'] : 'own';

            if ($labelSource === 'filename') {
                $label = isset($attachment['attachment_name']) ? trim((string) $attachment['attachment_name']) : '';
                if ($label === '' && isset($attachment['file'])) {
                    $label = pathinfo((string) $attachment['file'], PATHINFO_FILENAME);
                }
            } else {
                $own = isset($btn['own_string']) ? (string) $btn['own_string'] : '';
                $appendName = !empty($btn['append_product_name']);

                if ($own !== '' && $appendName) {
                    $label = $own . ' ' . $productName;
                } elseif ($own !== '') {
                    $label = $own;
                } elseif ($appendName) {
                    $label = $productName;
                } else {
                    $label = '';
                }
            }

            // Icon renders as either <img src=image> or an entity. If both
            // fields are populated, the image wins (consistent with the
            // admin list preview).
            $iconRendered = '';
            if (!empty($btn['icon_image'])) {
                $iconRendered = '<img src="' . htmlspecialchars((string) $btn['icon_image'], ENT_QUOTES) . '" alt="">';
            } elseif (!empty($btn['icon_entity'])) {
                $iconRendered = self::normalizeIcon((string) $btn['icon_entity']);
            }

            $position = isset($btn['icon_position']) ? (string) $btn['icon_position'] : 'none';
            $iconLeft = '';
            $iconRight = '';
            if ($iconRendered !== '') {
                if ($position === 'left' || $position === 'both') {
                    $iconLeft = $iconRendered;
                }
                if ($position === 'right' || $position === 'both') {
                    $iconRight = $iconRendered;
                }
            }

            $buttons[] = [
                'url' => $url,
                'color' => isset($btn['button_color']) ? (string) $btn['button_color'] : '#dc3545',
                'label' => $label,
                'icon_left' => $iconLeft,
                'icon_right' => $iconRight,
            ];
        }

        return $buttons;
    }
}
