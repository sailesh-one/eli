<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_users.php';

$action = $_REQUEST['action'] ?? '';
$module_type = 1;

$users = new Users();

switch ($action) {
    case 'getmodules':
        $result = $users->getmodules($module_type);
        api_response(
            $result ? 200 : 500,
            $result ? 'ok' : 'fail',
            $result ? 'Fetched modules' : 'Failed to fetch modules',
            $result ?: []
        );
        break;

    case 'savemodules':
        // collect inputs with defaults
        $form_action   = $_POST['form_action'] ?? '';
        $name          = trim($_POST['name'] ?? '');
        $url           = trim($_POST['url'] ?? '');
        $module_id     = $_POST['module_id'] ?? '';
        $submodule_id  = $_POST['submodule_id'] ?? '';
        $category_name = trim($_POST['category_name'] ?? '');
        $active        = $_POST['active'] ?? 'y';
        $is_visible    = $_POST['is_visible'] ?? '0';
        $icon          = trim($_POST['icon'] ?? '');

        // ---------- validations ----------
        $errors = [];

        if ($name === '') {
            $errors['name'] = "Name field is required.";
        } elseif (!validate_field_regex("alpha", $name)) {
            $errors['name'] = "Invalid field name.";
        }

        if (in_array($form_action, ['addmodule', 'updatemodule'])) {
            if ($category_name === '') {
                $errors['category_name'] = "Category name is required.";
            } elseif (!validate_field_regex("alpha", $category_name)) {
                $errors['category_name'] = "Invalid category name.";
            }
        }

        if ($url === '') {
            $errors['url'] = "URL/slug is required.";
        } elseif (!validate_field_regex("url", $url)) {
            $errors['url'] = "Invalid URL/slug.";
        }

        if ($is_visible === '') {
            $errors['is_visible'] = "Is visible value is required.";
        }elseif (!in_array($is_visible, ['0', '1'], true)) {
            $errors['is_visible'] = "Invalid is visible value.";
        }

        if ($icon !== '' && !validate_field_regex("alphanumericspecial", $icon)) {
            $errors['icon'] = "Invalid icon value.";
        }

        if ($errors) {
            api_response(400, "fail", "Validation failed.", [], [], $errors);
        }

        // ---------- action handlers ----------
        $responseMap = [
            'addmodule' => [
                'fn'     => fn() => $users->addmodule($name, $url, $category_name, $is_visible, $module_type, $icon),
                'success'=> 'Added module successfully',
                'fail'   => 'Failed to add module.'
            ],
            'updatemodule' => [
                'fn'     => fn() => $users->updatemodule($name, $url, $module_id, $active, $category_name, $is_visible, $module_type, $icon),
                'success'=> 'Updated module successfully',
                'fail'   => 'Failed to update module.'
            ],
            'addsubmodule' => [
                'fn'     => fn() => $users->addsubmodule($name, $url, $module_id, $module_type),
                'success'=> 'Added sub module successfully',
                'fail'   => 'Failed to add sub module.'
            ],
            'updatesubmodule' => [
                'fn'     => fn() => $users->updatesubmodule($name, $url, $module_id, $submodule_id, $active, $module_type),
                'success'=> 'Updated sub module successfully',
                'fail'   => 'Failed to update sub module.'
            ],
        ];

        if (!isset($responseMap[$form_action])) {
            api_response(400, 'fail', 'Invalid form action.');
        }

        $result = $responseMap[$form_action]['fn']();

        api_response(
            $result ? 200 : 500,
            $result ? 'ok' : 'fail',
            $result ? $responseMap[$form_action]['success'] : $responseMap[$form_action]['fail'],
            $result ? [$result] : []
        );
        break;

    default:
        api_response(400, 'fail', 'Invalid action for modules');
        break;
}
