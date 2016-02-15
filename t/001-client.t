#!/usr/bin/env php
<?php

require_once 'TestMore.php';

require 'vendor/autoload.php';

plan(25);

ok( $client = new \Dezi\Client(array('username'=>'foo', 'password'=>'bar')),
    "new client"
);

ok( $client->server_supports_transactions(), "server supports transactions" );

// add/update a filesystem document to the index
ok( $resp = $client->index('t/test.html'), "index t/test.html" );
is( $resp->status, 202, "index fs success" );

// add/update an in-memory document to the index
$html_doc
    = "<html><title>hello world</title><body>foo bar</body></html>";
ok( $resp = $client->index( $html_doc, 'foo/bar.html' ),
    "index \$html_doc" );
is( $resp->status, 202, "index scalar_ref success" );

// add/update a Dezi::Doc to the index
$doc = new \Dezi\Doc(array( 'uri' => 't/test-dezi-doc.xml' ));
$doc->content = file_get_contents( $doc->uri );
ok( $resp = $client->index($doc), "index Doc" );
is( $resp->status, 202, "index Doc success" );

$doc2 = new \Dezi\Doc(array(
        'uri' => 'auto/xml/magic',
    ));
$doc2->set_field('title', 'ima dezi magic');
$doc2->set_field('foo', array('one', 'two'));
$doc2->set_field('body', 'hello world!');
ok( $resp = $client->index( $doc2 ), "index XML magic");
is( $resp->status, 202, "index XML magic 2xx");

// commit changes
ok( $resp = $client->commit(), "/commit changes");
is( $resp->status, '200', "/commit returns 200");

// remove a document from the index
ok( $resp = $client->delete('foo/bar.html'), "delete foo/bar.html" );
is( $resp->status, 202, "delete success" );

// commit changes
ok( $resp = $client->commit(), "/commit changes");
is( $resp->status, '204', "/commit returns 204 on delete (nothing added)");

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
is( $response->total, 3, "got 3 results" );
ok( $response->search_time, "got search_time" );
ok( $response->build_time,  "got build time" );
is( $response->query, "dezi", "round-trip query string" );

// encoding test
$html = "<html><body> TEST </body></html>";
$docargs = array();
$docargs['mime_type'] = 'text/html';
$docargs['uri'] = rawurlencode ("//test.site/téstfrench");
$docargs['mtime'] = time();
$docargs['size' ] = strlen ($html);
$docargs['content'] = $html;

$doc = new \Dezi\Doc($docargs);
ok( $resp = $client->index( $doc ), "index document with utf8 encoded url" );
diag_dump( $resp );
