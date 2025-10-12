<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title><?php echo isset($htmlTitle) ? $htmlTitle : 'benjmacaro.dev'; ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <?php if (isset($metaDescription)): ?>
  <meta name="description" content="<?php echo $metaDescription; ?>">
  <?php endif; ?>
  <?php if (isset($metaKeywords)): ?>
  <meta name="keywords" content="<?php echo $metaKeywords; ?>">
  <?php endif; ?>
  <meta name="author" content="Ben Macaro">
  <meta name="robots" content="index, follow">
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <?php include 'includes/sidebar-nav.php'; ?>

  <!-- Main Container -->
  <table class="main-container" width="800" cellpadding="0" cellspacing="0">
    <tr>
      <td class="content-header" align="center" height="100">
        <font face="helvetica">
          <h1 class="page-title">
            <?php echo isset($pageTitle) ? $pageTitle : 'benjmacaro.dev'; ?>
          </h1>
        </font>
      </td>
    </tr>
    <tr>
      <td class="content-wrapper">
        <font face="helvetica">
