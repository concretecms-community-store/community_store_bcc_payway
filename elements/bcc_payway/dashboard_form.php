<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Page\View\PageView $view
 * @var array $vars
 */

extract($vars);

/**
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Package\CommunityStoreBccPayway\CreditCardImages $creditCardImages
 * @var string $environment
 * @var array $environments
 * @var array $servicesURLs
 * @var array $terminalIDs
 * @var array $signatureKeys
 */

$unselectedOpacity = '0.3';
?>

<div class="form-group">
    <?= $form->label('bccPaywayEnvironment', t('Environment to be used')) ?>
    <?= $form->select('bccPaywayEnvironment', $environments, $environments) ?>
</div>

<?php
foreach ($environments as $environmentKey => $environmentName) {
    ?>
    <div id="bccPaywayEnvironment-<?= $environmentKey ?>"<?= $environmentKey === $environment ? '' : ' style="display:none"' ?>>
        <div class="form-group">
            <?= $form->label('bccPaywayServicesURL_' . $environmentKey, t('URL of the bank services (environment: %s)', h($environmentName))) ?>
            <?= $form->text('bccPaywayServicesURL_' . $environmentKey, $servicesURLs[$environmentKey], ['placeholder' => 'https://server.com/UNI_CG_SERVICES/services']) ?>
        </div>
        <div class="form-group">
            <?= $form->label('bccPaywayTerminalID_' . $environmentKey, t('Terminal ID (environment: %s)', h($environmentName))) ?>
            <?= $form->text('bccPaywayTerminalID_' . $environmentKey, $terminalIDs[$environmentKey], ['maxlength' => '16']) ?>
        </div>
        <div class="form-group">
            <?= $form->label('bccPaywaySignatureKey_' . $environmentKey, t('Signature Key (environment: %s)', h($environmentName))) ?>
            <?= $form->password('bccPaywaySignatureKey_' . $environmentKey, $signatureKeys[$environmentKey]) ?>
        </div>
    </div>
    <?php
}
?>
<script>
$(document).ready(function() {

var $environment = $('#bccPaywayEnvironment');
$environment
    .on('change', function () {
        var enviromnent = $environment.val();
        <?= json_encode(array_keys($environments)) ?>.forEach(function (env) {
            $('#bccPaywayEnvironment-' + env).toggle(env === enviromnent);
        });
    })
    .trigger('change')
});
</script>

<div class="form-group">
    <?= $form->label('', t('Credit card images to be displayed')) ?>
    <div class="small text-muted">
        <?= t('Click on the images to disable them, drag them to change the order.') ?>
    </div>
    <div id="bccPaywayCreditCardImages" style="padding: 15px">
        <?php
        foreach ([
            true => $creditCardImages->getWantedImageHandles(),
            false => array_diff($creditCardImages->getAvailableImageHandles(), $creditCardImages->getWantedImageHandles()),
        ] as $selected => $handles) {
            foreach ($handles as $handle) {
                ?>
                <a href="#" data-handle="<?= h($handle) ?>"<?= $selected ? '' : " style=\"opacity: {$unselectedOpacity}\"" ?>>
                    <input type="hidden" name="bccPaywayCreditCardImages[]" value="<?= $selected ? h($handle) : '' ?>" />
                    <?= $creditCardImages->renderImage($handle, null, 30) ?>
                </a>
                <?php
            }
        }
        ?>
    </div>
</div>
<style>
#bccPaywayCreditCardImages >* {
    display: inline-block;
}
#bccPaywayCreditCardImages >.bccPaywayCreditCardImages-placeholder {
    width: 48px;
    height: 30px;
}
</style>
<script>
$(document).ready(function() {

var $container = $('#bccPaywayCreditCardImages').disableSelection();

$container.find('a')
    .on('click', function (e) {
        e.preventDefault();
        var $link = $(this), $input = $link.find('input');
        if ($input.val() === '') {
            $input.val($link.data('handle'));
            $link.css('opacity', 1);
        } else {
            $input.val('');
            $link.css('opacity', <?= $unselectedOpacity ?>);
        }
    })
;
$container.sortable({
    items: '>a',
    containment: 'parent',
    helper: 'clone',
    placeholder: 'bccPaywayCreditCardImages-placeholder',
});


});
</script>
