<?php

$row = $tabs = array();

$row[] = new tabobject('sync',
                       'sisdb.php?op=sync',
                       get_string('sisdb_sync','block_cegep'));

$row[] = new tabobject('prune',
                       'sisdb.php?op=prune',
                       get_string('sisdb_prune','block_cegep'));

$tabs[] = $row;

print_tabs($tabs, $currenttab);

?>
