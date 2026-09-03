<?php

/**
 * Guards the fix for "Save changes does nothing".
 *
 * reka-ui unmounts an inactive tab panel by default, so an admin form whose
 * fields span tabs submitted only the visible tab's inputs — the request then
 * failed validation on fields hidden behind another tab and the page looked
 * unchanged. There is no JS test runner here, so these assertions pin the two
 * pieces of the fix in the source.
 */

/** @return list<string> */
function tabbedAdminForms(): array
{
    return [
        'resources/js/pages/admin/tryouts/TryoutForm.vue',
        'resources/js/pages/admin/camps/CampForm.vue',
        'resources/js/pages/admin/products/ProductForm.vue',
        'resources/js/components/admin/coaches/CoachForm.vue',
        'resources/js/components/admin/teams/TeamForm.vue',
    ];
}

test('the shared tabs wrapper keeps hidden panels mounted', function () {
    expect(base_path('resources/js/components/ui/tabs/Tabs.vue'))->toBeFile();

    expect(file_get_contents(base_path('resources/js/components/ui/tabs/Tabs.vue')))
        ->toContain('unmountOnHide: false');
});

test('forms whose fields span tabs use FormTabs so errors are reachable', function (string $form) {
    $source = file_get_contents(base_path($form));

    expect($source)->toContain('<FormTabs')
        ->and($source)->toContain(':errors="errors"')
        ->and($source)->not->toContain('<Tabs ');
})->with(tabbedAdminForms());
