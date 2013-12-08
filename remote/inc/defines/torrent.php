<?php
//This order has to match the order in function: 
// -get_full_list($view, $group, $source) in list.fun.php
define('COMPLETE',            0 );
define('COMPLETED_BYTES',     1 );
define('CONNECTION_CURRENT',  2 );
define('DOWN_RATE',           3 );
define('HASH',                4 );
define('MESSAGE',             5 );
define('NAME',                6 );
define('PEERS_COMPLETE',      7 );
define('PEERS_CONNECTED',     8 );
define('PEERS_NOT_CONNECTED', 9 );
define('PRIORITY',            10);
define('RATIO',               11);
define('SIZE_BYTES',          12);
define('UP_RATE',             13);
define('UP_TOTAL',            14);
define('IS_ACTIVE',           15);
define('LEFT_BYTES',          16);
define('GET_DIRECTORY',       17);
define('ADDED',               18);

//Don't yet discovered magic stuff
define('STATUS',              19);
define('ETA',                 20);
define('PERCENT_COMPLETE',    21);
define('DOWN_TOTAL',          19);
define('SIZE_CHUNKS',         20);
?>
