<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? '{{APP_NAME}}') ?></title>
  <?= csrf_meta() ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
  <?= view('partials/navbar') ?>
  <main class="container py-4">
    <?= $this->renderSection('content') ?>
  </main>
  <?= view('partials/footer') ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/cookie-consent.js"></script>
  <?= $this->renderSection('scripts') ?>
</body>
</html>