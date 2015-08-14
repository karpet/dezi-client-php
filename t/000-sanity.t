#!/usr/bin/env php
<?php

require_once 'TestMore.php';

plan(2);

require 'vendor/autoload.php';


diag("Testing \Dezi\Client version " . \Dezi\Client::$VERSION);
pass("\Dezi\Client sanity check");

ok( $client = new \Dezi\Client(),
    "new client"
);

//diag_dump( $client );
