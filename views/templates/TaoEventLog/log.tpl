<?php
use oat\tao\helpers\Template;
?>

<link rel="stylesheet" href="<?= Template::css('eventlog.css', 'taoEventLog') ?>"/>

<div class="content" data-is-from-portal="<?= isset($isFromPortal) && $isFromPortal ? '1' : '0' ?>"></div>
