<?php // @codingStandardsIgnoreFile
$prev = $this->page->getPrev();
$parent = $this->page->getParent();
$next = $this->page->getNext();
?>

<footer class="row">
    <div class="col-xs-4 col-sm-4">
    <?php if ($prev): ?>
        <button type="button" class="btn btn-default"><?= $this->anchorRaw($prev->getHref(), 'Prev') ?></button>
        <br /><?= $prev->getNumberAndTitle() ?>
    <?php endif; ?>
    </div>
    <div class="col-xs-4 col-sm-4 text-center">
    <?php if ($parent && ($prev || $next)): ?>
        <button type="button" class="btn btn-default"><?= $this->anchorRaw($parent->getHref(), 'Up') ?></button>
        <br /><?= $parent->getNumberAndTitle() ?>
    <?php endif; ?>
    </div>
    <div class="col-xs-4 col-sm-4 text-right">
    <?php if ($next): ?>
        <button type="button" class="btn btn-default"><?= $this->anchorRaw($next->getHref(), 'Next') ?></button>
        <br /><?= $next->getNumberAndTitle() ?>
    <?php endif; ?>
    </div>
</footer>
