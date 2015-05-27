<?php // @codingStandardsIgnoreFile
$parent = $this->page->getParent() ?: $this->page;
?>
<body>
<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="<?= $parent->getHref() ?>"><?= $parent->getTitle() ?></a>
    </div>
  </div>
</nav>

<div class="container-fluid">
    <?php echo $this->render('core'); ?>
</div>
<script>
  (function () {
    var addLineNumbers = function() {
      var codeElements = document.getElementsByTagName('code');
      if (codeElements.length === 0) {
        return;
      }

      var element;
      var parent;
      var attr;
      for (var i = 0 ; i < codeElements.length ; i += 1) {
        element = codeElements.item(i);
        parent = element.parentNode;
        if (! parent) {
          continue;
        }
        if (parent.tagName !== 'PRE') {
          continue;
        }

        attr = parent.getAttribute('class');
        if (! attr) {
          parent.setAttribute('class', 'line-numbers');
          continue;
        }

        parent.setAttribute('class', attr + ' line-numbers');
      }
    };
    document.addEventListener('DOMContentLoaded', addLineNumbers);
  })();
</script>
<script src="http://uploads.mwop.net/prism-zf.js"></script>
</body>
