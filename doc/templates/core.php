<?php
echo $this->render('navheader');
echo $this->page->isIndex() ? $this->render('toc') : '';
echo '<div class="row">' . $this->html . '</div>';
echo $this->render('navfooter');
?>
