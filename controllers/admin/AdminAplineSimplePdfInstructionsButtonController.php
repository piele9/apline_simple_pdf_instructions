<?php
/**
 * APLINE Simple PDF Instructions module for PrestaShop 9.
 *
 * Hidden admin controller for CRUD on `aspd_button`. Reachable from the
 * module configuration page via the "Manage buttons" link.
 *
 * @author    APLINE Arkadiusz Pielechowski
 * @copyright APLINE Arkadiusz Pielechowski
 * @license   Custom Attribution License v1.0 - see LICENSE.md
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'apline_simple_pdf_instructions/classes/AplineSimplePdfInstructionsBtn.php';

class AdminAplineSimplePdfInstructionsBtnController extends ModuleAdminController
{
    const MAX_IMG_BYTES = 2097152; // 2 MB
    const MAX_STRING = 255;
    const MIN_SLOT = 1;
    const MAX_SLOT = 10;
    const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'aspd_button';
        $this->className = 'AplineSimplePdfInstructionsBtn';
        $this->identifier = 'id_aspd_button';
        $this->position_identifier = 'id_aspd_button';
        $this->lang = false;
        $this->allow_export = false;

        parent::__construct();

        $this->fields_list = [
            'id_aspd_button' => [
                'title' => $this->trans('ID', [], 'Admin.Global'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'slot_position' => [
                'title' => $this->trans('Slot', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'callback' => 'printSlot',
            ],
            'icon_image' => [
                'title' => $this->trans('Icon', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'align' => 'center',
                'callback' => 'printIcon',
                'orderby' => false,
                'search' => false,
            ],
            'own_string' => [
                'title' => $this->trans('Label', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'callback' => 'printLabel',
            ],
            'button_color' => [
                'title' => $this->trans('Color', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'align' => 'center',
                'callback' => 'printColor',
                'orderby' => false,
                'search' => false,
            ],
            'active' => [
                'title' => $this->trans('Displayed', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'align' => 'center',
                'active' => 'active',
                'type' => 'bool',
                'orderby' => false,
            ],
            'position' => [
                'title' => $this->trans('Position', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'align' => 'center',
                'position' => 'position',
                'search' => false,
            ],
        ];

        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected', [], 'Admin.Actions'),
                'confirm' => $this->trans('Delete selected buttons?', [], 'Admin.Notifications.Warning'),
            ],
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJqueryUI('ui.sortable');
    }

    /**
     * Module configuration URL (so the user can get back from the button list).
     *
     * @return string
     */
    private function getConfigUrl()
    {
        return $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => 'apline_simple_pdf_instructions',
            'module_name' => 'apline_simple_pdf_instructions',
        ]);
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_btn['back_to_config'] = [
            'href' => $this->getConfigUrl(),
            'desc' => $this->trans('Back to configuration', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
            'icon' => 'process-icon-back',
        ];
    }

    public function renderList()
    {
        $list = parent::renderList();

        // Breadcrumb-style back link + mandatory APLINE attribution under the table.
        $back = '<div style="margin:10px 0;"><a class="btn btn-default" href="'
            . htmlspecialchars($this->getConfigUrl(), ENT_QUOTES)
            . '"><i class="icon-chevron-left"></i> '
            . $this->trans('Back to configuration', [], 'Modules.Aplinesimplepdfinstructions.Admin')
            . '</a></div>';

        $credit = method_exists($this->module, 'renderAplineFooter')
            ? $this->module->renderAplineFooter()
            : '';

        return $back . $list . $credit;
    }

    /**
     * @param string $value slot_position raw value
     *
     * @return string list cell HTML
     */
    public function printSlot($value, $row)
    {
        return '<strong>#' . (int) $value . '</strong>';
    }

    /**
     * @param string $image stored public path
     *
     * @return string list cell HTML
     */
    public function printIcon($image, $row)
    {
        if (!empty($image)) {
            return '<img src="' . htmlspecialchars($image, ENT_QUOTES) . '" style="max-height:32px;max-width:48px;">';
        }
        if (!empty($row['icon_entity'])) {
            $entity = apline_simple_pdf_instructions::normalizeIcon($row['icon_entity']);
            if ($entity !== '') {
                return '<span style="font-size:24px;line-height:1;">' . $entity . '</span>';
            }
        }

        return '<span class="text-muted">&mdash;</span>';
    }

    /**
     * @return string list cell HTML
     */
    public function printLabel($value, $row)
    {
        if (isset($row['label_source']) && $row['label_source'] === 'filename') {
            return '<em class="text-muted">' . $this->trans('attachment file name', [], 'Modules.Aplinesimplepdfinstructions.Admin') . '</em>';
        }

        $parts = [];
        if (!empty($value)) {
            $parts[] = htmlspecialchars((string) $value, ENT_QUOTES);
        }
        if (!empty($row['append_product_name'])) {
            $parts[] = '<em class="text-muted">+ ' . $this->trans('product name', [], 'Modules.Aplinesimplepdfinstructions.Admin') . '</em>';
        }

        return $parts ? implode(' ', $parts) : '<span class="text-muted">&mdash;</span>';
    }

    /**
     * @return string list cell HTML — color swatch
     */
    public function printColor($value, $row)
    {
        $color = htmlspecialchars((string) $value, ENT_QUOTES);

        return '<span style="display:inline-block;width:32px;height:18px;border:1px solid #ccc;background:' . $color . ';vertical-align:middle;"></span>'
            . ' <code style="font-size:11px;">' . $color . '</code>';
    }

    public function renderForm()
    {
        // Build a slot dropdown 1..10.
        $slotOptions = [];
        for ($i = self::MIN_SLOT; $i <= self::MAX_SLOT; ++$i) {
            $slotOptions[] = ['id' => $i, 'name' => '#' . $i];
        }

        $iconPositionOptions = [
            ['id' => 'none', 'name' => $this->trans('None', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
            ['id' => 'left', 'name' => $this->trans('Left', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
            ['id' => 'right', 'name' => $this->trans('Right', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
            ['id' => 'both', 'name' => $this->trans('Both sides', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('PDF button', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'icon' => 'icon-file-pdf-o',
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->trans('Attachment slot', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'slot_position',
                    'required' => true,
                    'options' => ['query' => $slotOptions, 'id' => 'id', 'name' => 'name'],
                    'desc' => $this->trans('Which product attachment this button represents (1 = first attachment, 2 = second, etc.). The same convention should be used across all your products.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'file',
                    'label' => $this->trans('Icon image', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'image_file',
                    'desc' => $this->trans('Optional. Allowed: JPG, PNG, WEBP. Max 2 MB. Leave empty to keep the current image or to use an HTML entity instead.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Remove current image', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'remove_image',
                    'is_bool' => true,
                    'desc' => $this->trans('Turn on and save to delete the current icon image. Ignored when a new image is uploaded above.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'values' => [
                        ['id' => 'remove_image_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                        ['id' => 'remove_image_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Icon entity', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'icon_entity',
                    'desc' => $this->trans('Optional alternative to an icon image: a unicode hex code (e.g. 1F4C4) or an HTML entity (e.g. &#x1F4C4;). Used when no icon image is set.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->trans('Icon position', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'icon_position',
                    'required' => true,
                    'options' => ['query' => $iconPositionOptions, 'id' => 'id', 'name' => 'name'],
                ],
                [
                    'type' => 'radio',
                    'label' => $this->trans('Label source', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'label_source',
                    'required' => true,
                    'class' => 't',
                    'values' => [
                        ['id' => 'label_source_own', 'value' => 'own', 'label' => $this->trans('Custom text (own label, optionally with product name)', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
                        ['id' => 'label_source_filename', 'value' => 'filename', 'label' => $this->trans('Attachment file name (without extension)', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
                    ],
                    'desc' => $this->trans('"Attachment file name" uses the name set on the product attachment (the field "Name" in BO → Catalog → Files), or the storage file name if empty.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Own label text', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'own_string',
                    'desc' => $this->trans('Used only when "Label source" is "Custom text". Max 255 characters.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Append product name', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'append_product_name',
                    'is_bool' => true,
                    'desc' => $this->trans('Used only when "Label source" is "Custom text". Either "Own label text" OR this switch must be set.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'values' => [
                        ['id' => 'append_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                        ['id' => 'append_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                    ],
                ],
                [
                    'type' => 'color',
                    'label' => $this->trans('Button color', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'button_color',
                    'required' => true,
                    'desc' => $this->trans('Background color of the button (hex format #RRGGBB).', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Displayed', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                    ],
                ],
            ],
            'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')],
        ];

        // Preview of the current icon image when editing.
        if (($obj = $this->loadObject(true)) && Validate::isLoadedObject($obj) && !empty($obj->icon_image)) {
            $this->fields_form['input'][1]['image'] =
                '<img src="' . htmlspecialchars($obj->icon_image, ENT_QUOTES) . '" style="max-height:48px;">';
        }

        // Sensible defaults for the "add" form (PrestaShop reuses fields_value).
        if (!Tools::getValue($this->identifier)) {
            $this->fields_value = [
                'icon_position' => 'left',
                'label_source' => 'own',
                'button_color' => '#dc3545',
                'active' => 1,
                'append_product_name' => 1,
            ];
        }

        return parent::renderForm();
    }

    public function postProcess()
    {
        $isAdd = Tools::isSubmit('submitAdd' . $this->table) && !Tools::getValue($this->identifier);
        $isUpdate = Tools::isSubmit('submitAdd' . $this->table) && Tools::getValue($this->identifier);

        if ($isAdd || $isUpdate) {
            $existing = null;
            if ($isUpdate) {
                $existing = new AplineSimplePdfInstructionsBtn((int) Tools::getValue($this->identifier));
                if (!Validate::isLoadedObject($existing)) {
                    $this->errors[] = $this->trans('The button you are trying to edit does not exist.', [], 'Modules.Aplinesimplepdfinstructions.Admin');

                    return false;
                }
            }

            if (!$this->handleSubmission($existing)) {
                // Errors already pushed to $this->errors: abort before any DB
                // write and keep the form open so the user can fix the input.
                $this->display = $isUpdate ? 'edit' : 'add';

                return false;
            }
        }

        return parent::postProcess();
    }

    /**
     * Validate input and the optional uploaded icon, then inject the resulting
     * values into $_POST so the standard ObjectModel save picks them up.
     * On any failure, populate $this->errors and return false (no save happens).
     *
     * @param AplineSimplePdfInstructionsBtn|null $existing
     *
     * @return bool
     */
    private function handleSubmission($existing)
    {
        $slot = (int) Tools::getValue('slot_position');
        $iconEntity = trim((string) Tools::getValue('icon_entity'));
        $iconPosition = (string) Tools::getValue('icon_position');
        $labelSource = (string) Tools::getValue('label_source');
        $ownString = trim((string) Tools::getValue('own_string'));
        $appendProductName = (int) Tools::getValue('append_product_name') ? 1 : 0;
        $buttonColor = trim((string) Tools::getValue('button_color'));
        $active = (int) Tools::getValue('active') ? 1 : 0;

        // 1. Slot range 1..10 (reject anything else — UI also constrains, but
        // POST can be tampered with).
        if ($slot < self::MIN_SLOT || $slot > self::MAX_SLOT) {
            $this->errors[] = $this->trans('Slot must be between %d and %d.', [self::MIN_SLOT, self::MAX_SLOT], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 2. Icon position must be one of the whitelisted values.
        if (!in_array($iconPosition, AplineSimplePdfInstructionsBtn::ICON_POSITIONS, true)) {
            $this->errors[] = $this->trans('Icon position must be one of: none, left, right, both.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 2b. Label source must be one of the whitelisted values.
        if (!in_array($labelSource, AplineSimplePdfInstructionsBtn::LABEL_SOURCES, true)) {
            $this->errors[] = $this->trans('Label source must be one of: own, filename.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 3. Hex color #RRGGBB (reject anything else — even though the color
        // picker emits hex, manual POST can submit garbage).
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $buttonColor)) {
            $this->errors[] = $this->trans('Button color must be a hex code in the form #RRGGBB.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 4. Max length 255 (reject, never truncate).
        foreach (['Own label' => $ownString, 'Icon entity' => $iconEntity] as $label => $value) {
            if (mb_strlen($value) > self::MAX_STRING) {
                $this->errors[] = $this->trans('The field "%s" exceeds the maximum length of 255 characters.', [$label], 'Modules.Aplinesimplepdfinstructions.Admin');
            }
        }

        // 5. Icon entity format: unicode hex or HTML entity.
        if ($iconEntity !== '' && !preg_match('/^(&#x?[0-9A-Fa-f]+;|&[a-zA-Z]+;|[0-9A-Fa-f]{1,6})$/', $iconEntity)) {
            $this->errors[] = $this->trans('Icon entity must be a unicode hex code (e.g. 1F4C4) or an HTML entity (e.g. &#x1F4C4;).', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 6. Label XOR check: only meaningful when the admin chose "own" as
        // the label source. With "filename" the attachment file name is used,
        // so own_string / append_product_name are ignored at render time.
        if ($labelSource === 'own' && $ownString === '' && $appendProductName === 0) {
            $this->errors[] = $this->trans('The button must have a label: set "Own label text" or enable "Append product name".', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 7. Image upload validation (only when a file was actually sent).
        $newImagePath = null;
        $hasUpload = isset($_FILES['image_file'])
            && isset($_FILES['image_file']['error'])
            && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasUpload) {
            $file = $_FILES['image_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors[] = $this->trans('The icon upload failed. Please try again.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, self::ALLOWED_EXT, true)) {
                    $this->errors[] = $this->trans('Invalid icon format. Allowed formats: JPG, PNG, WEBP.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
                } elseif ((int) $file['size'] > self::MAX_IMG_BYTES) {
                    $this->errors[] = $this->trans('The icon is too large. Maximum size is 2 MB.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
                } else {
                    // Inspect real content, not just the extension: blocks an
                    // executable payload renamed with an image extension
                    // (workspace CLAUDE.md §3.3).
                    $info = @getimagesize($file['tmp_name']);
                    $realMime = is_array($info) && isset($info['mime']) ? $info['mime'] : '';
                    $isRealImage = class_exists('ImageManager')
                        ? ImageManager::isRealImage($file['tmp_name'], $file['type'], self::ALLOWED_MIME)
                        : in_array($realMime, self::ALLOWED_MIME, true);

                    if (!$info || !in_array($realMime, self::ALLOWED_MIME, true) || !$isRealImage) {
                        $this->errors[] = $this->trans('The uploaded file is not a valid image.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
                    } else {
                        $fileName = 'aspd_' . uniqid('', true) . '.' . $ext;
                        $dest = $this->module->getUploadDir() . $fileName;

                        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
                            $this->errors[] = $this->trans('Could not save the uploaded icon. Check folder permissions.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
                        } else {
                            @chmod($dest, 0644);
                            $newImagePath = __PS_BASE_URI__ . 'modules/apline_simple_pdf_instructions/views/img/' . $fileName;
                        }
                    }
                }
            }
        }

        // Explicit "remove current image" toggle wins over the silent "keep
        // existing" fallback — but a fresh upload still trumps it.
        $removeImage = (int) Tools::getValue('remove_image') === 1;

        // Resolve the effective icon image after this submission.
        $effectiveImage = $newImagePath;
        if (null === $effectiveImage && !$removeImage && $existing && !empty($existing->icon_image)) {
            $effectiveImage = $existing->icon_image;
        }

        // 8. Icon position vs source consistency check (only when validation
        // hasn't already failed on icon_position itself).
        if (in_array($iconPosition, AplineSimplePdfInstructionsBtn::ICON_POSITIONS, true)
            && $iconPosition !== 'none'
        ) {
            if (empty($effectiveImage) && $iconEntity === '') {
                $this->errors[] = $this->trans('Icon position is set to "%s" but no icon image or icon entity was provided.', [$iconPosition], 'Modules.Aplinesimplepdfinstructions.Admin');
            }
        }

        if (!empty($this->errors)) {
            // Clean up a freshly uploaded file if the rest of validation failed.
            if ($newImagePath) {
                @unlink($this->module->getUploadDir() . basename($newImagePath));
            }

            return false;
        }

        // Remove the previous file when it is being replaced OR when the
        // admin ticked "Remove current image" without uploading a new one.
        $shouldUnlinkOld = $existing
            && !empty($existing->icon_image)
            && ($newImagePath || $removeImage);
        if ($shouldUnlinkOld) {
            $old = $this->module->getUploadDir() . basename($existing->icon_image);
            if (is_file($old)) {
                @unlink($old);
            }
        }

        // Feed validated values into the standard ObjectModel save flow.
        $_POST['slot_position'] = $slot;
        $_POST['icon_image'] = $effectiveImage ? $effectiveImage : '';
        $_POST['icon_entity'] = $iconEntity;
        $_POST['icon_position'] = $iconPosition;
        $_POST['label_source'] = $labelSource;
        $_POST['own_string'] = $ownString;
        $_POST['append_product_name'] = $appendProductName;
        $_POST['button_color'] = $buttonColor;
        $_POST['active'] = $active;

        return true;
    }

    /**
     * Delete the associated icon image file when the row is deleted.
     */
    public function processDelete()
    {
        $obj = $this->loadObject(true);
        if (Validate::isLoadedObject($obj) && !empty($obj->icon_image)) {
            $file = $this->module->getUploadDir() . basename($obj->icon_image);
            if (is_file($file)) {
                @unlink($file);
            }
        }

        return parent::processDelete();
    }

    public function ajaxProcessUpdatePositions()
    {
        $positions = Tools::getValue($this->table);

        if (!is_array($positions)) {
            die(json_encode(['success' => false]));
        }

        // Reindex deterministically from the order posted by the sortable list:
        // the array order is the new visual order, so assign 1..n sequentially.
        $pos = 1;
        foreach ($positions as $value) {
            // Row token looks like "<table>_<id>" or "<table>_<x>_<id>";
            // the object id is always the last numeric segment.
            $parts = explode('_', (string) $value);
            $id = (int) end($parts);
            if (!$id) {
                continue;
            }
            Db::getInstance()->update(
                'aspd_button',
                ['position' => $pos++],
                'id_aspd_button = ' . $id
            );
        }

        die(json_encode(['success' => true]));
    }
}
