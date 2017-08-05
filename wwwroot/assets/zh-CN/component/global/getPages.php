<?php
echo App\Tools\Auxi::getPages($this->currentCategoryTotal,
    array($this->__ROOT__ . $this->aliasId,
        $this->__ROOT__ . $this->__PACKAGE__ . '/' . $this->rootListDir . '/' . $this->fileName
    ), $this->currentPage, $this->currentPageSize);
?>
