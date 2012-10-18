#!/usr/bin/env php
<?php

require_once 'TestMore.php';
set_include_path('../pest:.');

plan(2);

require_once 'lib/Dezi_Client.php';

diag("Testing Dezi_Client version " . Dezi_Client::$VERSION);
pass("Dezi_Client sanity check");

ok( $client = new Dezi_Client(),
    "new client"
);

//diag_dump( $client );
