#!/usr/bin/env php
<?php

require_once 'TestMore.php';

plan(2);

require 'vendor/autoload.php';


diag("Testing Dezi_Client version " . \Dezi\Dezi_Client::$VERSION);
pass("Dezi_Client sanity check");

ok( $client = new \Dezi\Dezi_Client(),
    "new client"
);

//diag_dump( $client );
