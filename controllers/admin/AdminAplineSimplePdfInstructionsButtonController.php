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

class AdminAplineSimplePdfInstructionsButtonController extends ModuleAdminController
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
                'title' => $this->trans('Pozycja załącznika', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'callback' => 'printSlot',
            ],
            'icon_image' => [
                'title' => $this->trans('Ikona', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'align' => 'center',
                'callback' => 'printIcon',
                'orderby' => false,
                'search' => false,
            ],
            'own_string' => [
                'title' => $this->trans('Etykieta', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'callback' => 'printLabel',
            ],
            'button_color' => [
                'title' => $this->trans('Kolor', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'align' => 'center',
                'callback' => 'printColor',
                'orderby' => false,
                'search' => false,
            ],
            'active' => [
                'title' => $this->trans('Widoczny', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'align' => 'center',
                'active' => 'active',
                'type' => 'bool',
                'orderby' => false,
            ],
            'position' => [
                'title' => $this->trans('Pozycja', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
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
                'text' => $this->trans('Usuń zaznaczone', [], 'Admin.Actions'),
                'confirm' => $this->trans('Usunąć zaznaczone przyciski?', [], 'Admin.Notifications.Warning'),
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
            'desc' => $this->trans('Wróć do konfiguracji', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
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
            . $this->trans('Wróć do konfiguracji', [], 'Modules.Aplinesimplepdfinstructions.Admin')
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
            return '<em class="text-muted">' . $this->trans('nazwa pliku załącznika', [], 'Modules.Aplinesimplepdfinstructions.Admin') . '</em>';
        }

        $parts = [];
        if (!empty($value)) {
            $parts[] = htmlspecialchars((string) $value, ENT_QUOTES);
        }
        if (!empty($row['append_product_name'])) {
            $parts[] = '<em class="text-muted">+ ' . $this->trans('nazwa produktu', [], 'Modules.Aplinesimplepdfinstructions.Admin') . '</em>';
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
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
        // Build a slot dropdown 1..10.
        $slotOptions = [];
        for ($i = self::MIN_SLOT; $i <= self::MAX_SLOT; ++$i) {
            $slotOptions[] = ['id' => $i, 'name' => '#' . $i];
        }

        $iconPositionOptions = [
            ['id' => 'none', 'name' => $this->trans('Brak', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
            ['id' => 'left', 'name' => $this->trans('Po lewej', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
            ['id' => 'right', 'name' => $this->trans('Po prawej', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
            ['id' => 'both', 'name' => $this->trans('Po obu stronach', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('Przycisk PDF', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                'icon' => 'icon-file-pdf-o',
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->trans('Pozycja załącznika', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'slot_position',
                    'required' => true,
                    'options' => ['query' => $slotOptions, 'id' => 'id', 'name' => 'name'],
                    'desc' => $this->trans('Numer załącznika produktu (1 = pierwszy, 2 = drugi itd.). Używaj tej samej kolejności załączników we wszystkich produktach.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'file',
                    'label' => $this->trans('Obraz ikony', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'image_file',
                    'desc' => $this->trans('Opcjonalny. Formaty: JPG, PNG, WEBP. Maks. 2 MB. Pozostaw puste, aby zachować obraz lub użyć encji HTML.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Usuń obecny obraz', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'remove_image',
                    'is_bool' => true,
                    'desc' => $this->trans('Włącz i zapisz, aby usunąć obecną ikonę. Opcja jest pomijana, jeśli przesyłasz nowy obraz powyżej.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'values' => [
                        ['id' => 'remove_image_on', 'value' => 1, 'label' => $this->trans('Tak', [], 'Admin.Global')],
                        ['id' => 'remove_image_off', 'value' => 0, 'label' => $this->trans('Nie', [], 'Admin.Global')],
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Encja ikony', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'icon_entity',
                    'desc' => $this->trans('Alternatywa dla obrazu: szesnastkowy kod Unicode (np. 1F4C4) lub encja HTML (np. &#x1F4C4;). Używana, jeśli nie ma obrazu ikony.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->trans('Położenie ikony', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'icon_position',
                    'required' => true,
                    'options' => ['query' => $iconPositionOptions, 'id' => 'id', 'name' => 'name'],
                ],
                [
                    'type' => 'radio',
                    'label' => $this->trans('Źródło etykiety', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'label_source',
                    'required' => true,
                    'class' => 't',
                    'values' => [
                        ['id' => 'label_source_own', 'value' => 'own', 'label' => $this->trans('Własny tekst (opcjonalnie z nazwą produktu)', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
                        ['id' => 'label_source_filename', 'value' => 'filename', 'label' => $this->trans('Nazwa pliku załącznika (bez rozszerzenia)', [], 'Modules.Aplinesimplepdfinstructions.Admin')],
                    ],
                    'desc' => $this->trans('Nazwa pliku załącznika korzysta z pola Nazwa załącznika w panelu (Katalog → Pliki). Jeśli puste, używa nazwy pliku zapisanej na dysku.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Własny tekst etykiety', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'own_string',
                    'desc' => $this->trans('Używany tylko przy źródle Własny tekst. Maks. 255 znaków.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Dodaj nazwę produktu', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'append_product_name',
                    'is_bool' => true,
                    'desc' => $this->trans('Używane tylko przy źródle Własny tekst. Wpisz własny tekst etykiety lub włącz tę opcję.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'values' => [
                        ['id' => 'append_on', 'value' => 1, 'label' => $this->trans('Tak', [], 'Admin.Global')],
                        ['id' => 'append_off', 'value' => 0, 'label' => $this->trans('Nie', [], 'Admin.Global')],
                    ],
                ],
                [
                    'type' => 'color',
                    'label' => $this->trans('Kolor przycisku', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'button_color',
                    'required' => true,
                    'desc' => $this->trans('Kolor tła przycisku w formacie szesnastkowym #RRGGBB.', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Widoczny', [], 'Modules.Aplinesimplepdfinstructions.Admin'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Tak', [], 'Admin.Global')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('Nie', [], 'Admin.Global')],
                    ],
                ],
            ],
            'submit' => ['class' => 'btn btn-primary btn-lg apline-btn-duzy pull-right', 'title' => $this->trans('Zapisz', [], 'Admin.Actions')],
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
                    $this->errors[] = $this->trans('Przycisk, który próbujesz edytować, nie istnieje.', [], 'Modules.Aplinesimplepdfinstructions.Admin');

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
            $this->errors[] = $this->trans('Pozycja załącznika musi wynosić od %d do %d.', [self::MIN_SLOT, self::MAX_SLOT], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 2. Icon position must be one of the whitelisted values.
        if (!in_array($iconPosition, AplineSimplePdfInstructionsBtn::ICON_POSITIONS, true)) {
            $this->errors[] = $this->trans('Położenie ikony musi być jednym z: Brak, Po lewej, Po prawej, Po obu stronach.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 2b. Label source must be one of the whitelisted values.
        if (!in_array($labelSource, AplineSimplePdfInstructionsBtn::LABEL_SOURCES, true)) {
            $this->errors[] = $this->trans('Wybierz źródło etykiety: Własny tekst lub Nazwa pliku załącznika.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 3. Hex color #RRGGBB (reject anything else — even though the color
        // picker emits hex, manual POST can submit garbage).
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $buttonColor)) {
            $this->errors[] = $this->trans('Kolor przycisku musi mieć format #RRGGBB.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 4. Max length 255 (reject, never truncate).
        foreach (['Własna etykieta' => $ownString, 'Encja ikony' => $iconEntity] as $label => $value) {
            if (mb_strlen($value) > self::MAX_STRING) {
                $this->errors[] = $this->trans('Pole "%s" przekracza limit 255 znaków.', [$label], 'Modules.Aplinesimplepdfinstructions.Admin');
            }
        }

        // 5. Icon entity format: unicode hex or HTML entity.
        if ($iconEntity !== '' && !preg_match('/^(&#x?[0-9A-Fa-f]+;|&[a-zA-Z]+;|[0-9A-Fa-f]{1,6})$/', $iconEntity)) {
            $this->errors[] = $this->trans('Ikona musi być szesnastkowym kodem Unicode (np. 1F4C4) lub encją HTML (np. &#x1F4C4;).', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 6. Label XOR check: only meaningful when the admin chose "own" as
        // the label source. With "filename" the attachment file name is used,
        // so own_string / append_product_name are ignored at render time.
        if ($labelSource === 'own' && $ownString === '' && $appendProductName === 0) {
            $this->errors[] = $this->trans('Przycisk wymaga etykiety: wpisz własny tekst lub włącz opcję Dodaj nazwę produktu.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
        }

        // 7. Image upload validation (only when a file was actually sent).
        $newImagePath = null;
        $hasUpload = isset($_FILES['image_file'])
            && isset($_FILES['image_file']['error'])
            && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasUpload) {
            $file = $_FILES['image_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors[] = $this->trans('Nie udało się przesłać ikony. Spróbuj ponownie.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, self::ALLOWED_EXT, true)) {
                    $this->errors[] = $this->trans('Nieprawidłowy format ikony. Dozwolone: JPG, PNG, WEBP.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
                } elseif ((int) $file['size'] > self::MAX_IMG_BYTES) {
                    $this->errors[] = $this->trans('Ikona jest zbyt duża. Maksymalny rozmiar to 2 MB.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
                } else {
                    // Inspect real content, not just the extension: blocks an
                    // executable payload renamed with an image extension
                    // .
                    $info = @getimagesize($file['tmp_name']);
                    $realMime = is_array($info) && isset($info['mime']) ? $info['mime'] : '';
                    $isRealImage = class_exists('ImageManager')
                        ? ImageManager::isRealImage($file['tmp_name'], $file['type'], self::ALLOWED_MIME)
                        : in_array($realMime, self::ALLOWED_MIME, true);

                    if (!$info || !in_array($realMime, self::ALLOWED_MIME, true) || !$isRealImage) {
                        $this->errors[] = $this->trans('Przesłany plik nie jest poprawnym obrazem.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
                    } else {
                        $fileName = 'aspd_' . uniqid('', true) . '.' . $ext;
                        $dest = $this->module->getUploadDir() . $fileName;

                        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
                            $this->errors[] = $this->trans('Nie udało się zapisać ikony. Sprawdź uprawnienia katalogu.', [], 'Modules.Aplinesimplepdfinstructions.Admin');
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
                $this->errors[] = $this->trans('Wybrano położenie ikony "%s", ale nie podano obrazu ani encji ikony.', [['none' => 'Brak', 'left' => 'Po lewej', 'right' => 'Po prawej', 'both' => 'Po obu stronach'][$iconPosition] ?? $iconPosition], 'Modules.Aplinesimplepdfinstructions.Admin');
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
