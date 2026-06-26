<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle??'SFAS') ?> | Smart Farming Advisory</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Sora:wght@400;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/portal.css">
  <script>
    window.BASE_URL          = "<?= BASE_URL ?>";
    window.SESSION_REMAINING = <?= (int)($sessionRemaining??1800) ?>;
    window.SIPIS_USER = {
      id:       <?= (int)($currentUser->user_id??0) ?>,
      name:     "<?= addslashes($userFullName??'') ?>",
      email:    "<?= addslashes($currentUser->email??'') ?>",
      initials: "<?= $userInitials??'FA' ?>",
      role:     "<?= addslashes($currentUser->role_name??'') ?>",
      photo:    "<?= !empty($userPhoto)?addslashes(upload_url('users/'.$userPhoto)):'' ?>"
    };
  </script>
  <?php if (!empty($pageHead)) echo $pageHead; ?>
</head>
<body>
<?php require get_layout('admin-nav'); ?>
