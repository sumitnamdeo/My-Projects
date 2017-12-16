<?php
$output = shell_exec('php bin/magento indexer:reindex');
echo "<pre>$output</pre>";