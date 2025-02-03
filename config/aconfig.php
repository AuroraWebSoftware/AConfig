<?php

return [
    /* * The database table where dynamic configurations will be stored */
    'table' => 'aconfig',


    'keys' => [
        /*
    *  'app.name'  => 'Laravel',
    *  'app.env'   => 'production',
    */
    ],

    /* * Automatic deletion of records that exist in the database but are not defined * in the aconfig.php file (orphan records). */
    'auto_delete_orphan_keys' => true,
];
