<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_colors.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

$color = new Color();

switch ($action) {

    /*----------------------------------
      GET MAKES
    -----------------------------------*/
    case 'getmakes':
        try {
            $color->getmakes();
        } catch (Throwable $e) {
            api_response(400, 'fail', 'Get makes error: ' . $e->getMessage(), [], []);
        }
        break;

    /*----------------------------------
      GET INTERIOR COLORS
    -----------------------------------*/
    case 'getinteriorcolors':
        try {
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $perPage = isset($_POST['perPage']) ? intval($_POST['perPage']) : 10;
            $color->getInteriorColors($page, $perPage);
        } catch (Throwable $e) {
            api_response(400, 'fail', 'Interior colors error: ' . $e->getMessage(), [], []);
        }
        break;

    /*----------------------------------
      SAVE INTERIOR COLOR
    -----------------------------------*/
    case 'saveinteriorcolor':
        try {
            $errors = [];

            $make_id = $_POST['make'] ?? '';
            $interior_color = trim($_POST['interior_color'] ?? '');
            $base_color = trim($_POST['base_color'] ?? '');
            $active = $_POST['active'] ?? 'y';

            if ($make_id === '') $errors['make'] = 'Make is required.';
            if ($interior_color === '') $errors['interior_color'] = 'Interior Color is required.';
            if ($base_color === '') $errors['base_color'] = 'Base Color is required.';

            if (!empty($errors)) {
                api_response(422, 'fail', 'Validation errors', ['errors' => $errors], []);
                break;
            }

            // call add function
            $result = $color->addInteriorColor($make_id, $interior_color, $base_color, $active);
            if ($result) {
                api_response(200, 'ok', 'Interior color added successfully.', [$result], []);
            } else {
                api_response(500, 'fail', 'Failed to add interior color.', [], []);
            }
        } catch (Throwable $e) {
            api_response(400, 'fail', 'Interior color save error: ' . $e->getMessage(), [], []);
        }
        break;

    /*----------------------------------
      UPDATE INTERIOR COLOR
    -----------------------------------*/
    case 'updateinteriorcolor':
        try {
            $errors = [];

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $make_id = $_POST['make'] ?? '';
            $interior_color = trim($_POST['interior_color'] ?? '');
            $base_color = trim($_POST['base_color'] ?? '');
            $active = $_POST['active'] ?? 'y';

            if ($id <= 0) $errors['id'] = 'Invalid ID.';
            if ($make_id === '') $errors['make'] = 'Make is required.';
            if ($interior_color === '') $errors['interior_color'] = 'Interior Color is required.';
            if ($base_color === '') $errors['base_color'] = 'Base Color is required.';

            if (!empty($errors)) {
                api_response(422, 'fail', 'Validation errors', ['errors' => $errors], []);
                break;
            }

            $result = $color->updateInteriorColor($id, $make_id, $interior_color, $base_color, $active);
            if ($result) {
                api_response(200, 'ok', 'Interior color updated successfully.', [$result], []);
            } else {
                api_response(500, 'fail', 'Failed to update interior color.', [], []);
            }
        } catch (Throwable $e) {
            api_response(400, 'fail', 'Interior color update error: ' . $e->getMessage(), [], []);
        }
        break;

    /*----------------------------------
      EXTERIOR COLORS
    -----------------------------------*/
    case 'getexteriorcolors':
        try {
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $perPage = isset($_POST['perPage']) ? intval($_POST['perPage']) : 10;
            $color->getExteriorColors($page, $perPage);
        } catch (Throwable $e) {
            api_response(400, 'fail', 'Exterior colors error: ' . $e->getMessage(), [], []);
        }
        break;

    case 'saveexteriorcolor':
        try {
            $errors = [];
            $make_id = $_POST['make'] ?? '';
            $exterior_color = trim($_POST['exterior_color'] ?? '');
            $base_color = trim($_POST['base_color'] ?? '');
            $active = $_POST['active'] ?? 'y';

            if ($make_id === '') $errors['make'] = 'Make is required.';
            if ($exterior_color === '') $errors['exterior_color'] = 'Exterior Color is required.';
            if ($base_color === '') $errors['base_color'] = 'Base Color is required.';

            if (!empty($errors)) {
                api_response(422, 'fail', 'Validation errors', ['errors' => $errors], []);
                break;
            }

            $result = $color->addExteriorColor($make_id, $exterior_color, $base_color, $active);
            if ($result) {
                api_response(200, 'ok', 'Exterior color added successfully.', [$result], []);
            } else {
                api_response(500, 'fail', 'Failed to add exterior color.', [], []);
            }
        } catch (Throwable $e) {
            api_response(400, 'fail', 'Exterior color save error: ' . $e->getMessage(), [], []);
        }
        break;

    case 'updateexteriorcolor':
        try {
            $errors = [];
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $make_id = $_POST['make'] ?? '';
            $exterior_color = trim($_POST['exterior_color'] ?? '');
            $base_color = trim($_POST['base_color'] ?? '');
            $active = $_POST['active'] ?? 'y';

            if ($id <= 0) $errors['id'] = 'Invalid id.';
            if ($make_id === '') $errors['make'] = 'Make is required.';
            if ($exterior_color === '') $errors['exterior_color'] = 'Exterior Color is required.';
            if ($base_color === '') $errors['base_color'] = 'Base Color is required.';

            if (!empty($errors)) {
                api_response(422, 'fail', 'Validation errors', ['errors' => $errors], []);
                break;
            }

            $result = $color->updateExteriorColor($id, $make_id, $exterior_color, $base_color, $active);
            if ($result) {
                api_response(200, 'ok', 'Exterior color updated successfully.', [$result], []);
            } else {
                api_response(500, 'fail', 'Failed to update exterior color.', [], []);
            }
        } catch (Throwable $e) {
            api_response(400, 'fail', 'Exterior color update error: ' . $e->getMessage(), [], []);
        }
        break;

    default:
        api_response(400, 'fail', 'Unknown action', [], []);
        break;
}
?>
