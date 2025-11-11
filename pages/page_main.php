<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UCDMS</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#ffffff">
  <meta name="description" content="User Cars Dealer Management System (UCDMS)">
  <link rel="shortcut icon" href="/assets/images/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="<?= $config['base_url'] ?>/assets/bootstrap/bootstrap.min.css?v=1">
  <link rel="stylesheet" href="<?= $config['base_url'] ?>/assets/bootstrap/bootstrap-icons.min.css?v=1">
  <link rel="stylesheet" href="<?= $config['base_url'] ?>/assets/css/styles.css?v=<?= $ver ?>">
  <!-- <link rel="stylesheet" href="/assets/bootstrap/datepicker.css?v=1"> -->
  <script src="<?= $config['base_url'] ?>/assets/js/jquery.min.js?v=<?= $ver ?>"></script>
</head>
<body>
  <div id="app"></div>
  <script src="<?= $config['base_url'] ?>/assets/js/vue.global<?= $config['env_server'] === 'prod' ? '.prod' : '' ?>.js?v=<?= $ver ?>"></script>
  <script src="<?= $config['base_url'] ?>/assets/js/vue-router.global<?= $config['env_server'] === 'prod' ? '.prod' : '' ?>.js?v=<?= $ver ?>"></script>
  <?php if ($config['env_server'] !== 'prod'): ?>
    <script src="<?= $config['base_url'] ?>/assets/js/index.iife.js?v=<?= $ver ?>"></script>
  <?php endif; ?>
  <script src="<?= $config['base_url'] ?>/assets/js/pinia.iife<?= $config['env_server'] === 'prod' ? '.prod' : '' ?>.js?v=<?= $ver ?>"></script>
  <script 
    type="module" 
    defer 
    src="<?= $config['base_url'] ?>/pages/lib/main-app.js?v=<?= $ver ?>"
    data-init="main-app"
    data-ver="<?= $ver ?>"
    data-base-url="<?= $config['base_url'] ?>"
    data-base-url-api="<?= $config['base_url_api'] ?>"
    data-base-service-url="<?= $config['service_base_url'] ?>"
    data-env-stage="<?= $config['env_server'] ?>"
  ></script>
  <script src="<?= $config['base_url'] ?>/assets/bootstrap/bootstrap.bundle.min.js?v=1"></script>
  <!-- <script src="/assets/bootstrap/datepicker.js?v=1"></script> -->
</body>
</html>
