<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\View\View $view
 * @var array $vars
 */

extract($vars);

/**
 * @var Concrete\Package\CommunityStoreBccPayway\CreditCardImages $creditCardImages
 * @var string $environment
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
<?php
if ($environment === 'sandbox') {
    ?>
    <div class="alert alert-info">
        <?= h(t('This payment method is currently in "test" mode.')) ?><br />
        <?= t('That means that even if you provide your credit card details, you will not actually be charged anything.') ?><br />
        <?= t('In order to test the payment method you can use any credit card number, for example: %s', '<code>4005519200000004</code>') ?>
    </div>
    <?php
}
