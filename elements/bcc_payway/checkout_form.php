<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\View\View $view
 * @var array $vars
 */

extract($vars);

/**
 * @var Concrete\Package\CommunityStoreBccPayway\Service\CreditCardImages $creditCardImages
 */

$images = $creditCardImages->renderWantedImages(48, null, '', ' ');
?>
<div>
    <?php
    if ($images === '') {
        echo t('We accept the most used credit cards.');
    } else {
        echo t('We accept these credit cards:'), '<br /><br /><div>', $images, '</div>';
    }
    ?>
</div>
