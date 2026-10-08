<?php
/**
 * Upgrade to 1.0.2 — repair installations where the hook registered in
 * `ps_hook_module` no longer matches the ASPD_HOOK configuration value.
 *
 * Root cause fixed in this release: installHooks() used to register every
 * hook from getAvailableHooks() regardless of configuration, and saving the
 * config form only updated Configuration without touching the hook
 * registration. On a shop where the configured hook was changed (by editing
 * the default in code, or by hand) without the actual `ps_hook_module` row
 * following it, the buttons rendered nowhere — the configured hook was
 * never fired because it was not the one registered.
 *
 * This script normalizes any such shop to a single, correct registration:
 * the hook currently selected in ASPD_HOOK (falling back to the module's
 * default if the stored value is empty or invalid).
 *
 * @author    Arkadiusz Pielechowski
 * @copyright Arkadiusz Pielechowski
 * @license   MIT - see LICENSE.md
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param apline_simple_pdf_instructions $module
 *
 * @return bool
 */
function upgrade_module_1_0_2($module)
{
    $availableHooks = apline_simple_pdf_instructions::getAvailableHooks();

    $configuredHook = (string) Configuration::get(apline_simple_pdf_instructions::HOOK_KEY);
    if ($configuredHook === '' || !array_key_exists($configuredHook, $availableHooks)) {
        $configuredHook = apline_simple_pdf_instructions::DEFAULT_HOOK;
        Configuration::updateValue(apline_simple_pdf_instructions::HOOK_KEY, $configuredHook);
    }

    $ok = true;

    // Unregister every other candidate hook the module might have picked up
    // from an earlier install() that registered all of them.
    foreach (array_keys($availableHooks) as $hook) {
        if ($hook === $configuredHook) {
            continue;
        }
        if ($module->isRegisteredInHook($hook)) {
            $ok = $module->unregisterHook($hook) && $ok;
        }
    }

    // Make sure the configured hook is actually registered.
    if (!$module->isRegisteredInHook($configuredHook)) {
        $ok = $module->registerHook($configuredHook) && $ok;
    }

    return $ok;
}
