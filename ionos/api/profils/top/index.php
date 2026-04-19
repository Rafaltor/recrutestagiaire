<?php
declare(strict_types=1);

/**
 * GET /api/profils/top — délègue au proxy (même logique que ?action=profil_top).
 * Déployer le dossier ionos/ à la racine du vhost pour que l’URL soit /api/profils/top/
 */
$_GET['action'] = 'profil_top';
require dirname(__DIR__, 3) . '/rs-airtable-proxy.php';
