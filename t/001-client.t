#!/usr/bin/env php
<?php

require_once 'TestMore.php';
set_include_path('../pest:.');

plan(19);

require_once 'lib/Dezi_Client.php';

ok( $client = new Dezi_Client(),
    "new client"
);

// add/update a filesystem document to the index
ok( $resp = $client->index('t/test.html'), "index t/test.html" );
is( $resp->status, 200, "index fs success" );

// add/update an in-memory document to the index
$html_doc
    = "<html><title>hello world</title><body>foo bar</body></html>";
ok( $resp = $client->index( $html_doc, 'foo/bar.html' ),
    "index \$html_doc" );
is( $resp->status, 200, "index scalar_ref success" );

// add/update a Dezi::Doc to the index
$dezi_doc = new Dezi_Doc(array( 'uri' => 't/test-dezi-doc.xml' ));
$dezi_doc->content = file_get_contents( $dezi_doc->uri );
ok( $resp = $client->index($dezi_doc), "index Dezi_Doc" );
is( $resp->status, 200, "index Dezi_Doc success" );

$doc2 = new Dezi_Doc(array(
        'uri' => 'auto/xml/magic',
    ));
$doc2->set_field('title', 'ima dezi magic');
$doc2->set_field('foo', array('one', 'two'));
$doc2->set_field('body', 'hello world!');
ok( $resp = $client->index( $doc2 ), "index XML magic");
is( $resp->status, 200, "index XML magic 200");


// remove a document from the index
ok( $resp = $client->delete('foo/bar.html'), "delete foo/bar.html" );
is( $resp->status, 204, "delete success" );

// search the index
ok( $response = $client->search(array( 'q' => 'dezi' )), "search" );

//diag_dump( $response );

// iterate over results
foreach ($response->results as $result ) {

    //diag_dump( $result );
    ok( $result->uri, "get result uri" );
    diag(
        sprintf(
            "--\n uri: %s\n title: %s\n score: %s\n swishmime: %s\n",
            $result->uri, $result->title, $result->score, 
            array_pop($result->get_field('swishmime'))
        )
    );
}

// print stats
is( $response->total, 2, "got 2 results" );
ok( $response->search_time, "got search_time" );
ok( $response->build_time,  "got build time" );
is( $response->query, "dezi", "round-trip query string" );
