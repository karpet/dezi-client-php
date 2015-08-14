<?php

/*

Response - Dezi search server response

SYNOPSIS

 require 'Response.php';
 $client = new Client(array(
    'server' => 'http://localhost:5000'
 ));

 $response = $client->search(array( 'q' => 'foo' ));
 // $response isa Response

 // iterate over results
 foreach ($response->results as $doc) {
     printf("--\n uri: %s\n title: %s\n score: %s\n",
        $doc->uri, $doc->title, $doc->score);
 }

 # print stats
 printf("       hits: %d\n", $response->total);
 printf("search time: %s\n", $response->search_time);
 printf(" build time: %s\n", $response->build_time);
 printf("      query: %s\n", $response->query);

*/
namespace Dezi;

class Response {

    public $results;
    public $total;
    public $search_time;
    public $build_time;
    public $query;
    public $fields;
    public $facets;
    public $suggestions;
    protected $VERSION = '0.002003';

    /**
     * Constructor. Returns Reponse object.
     *
     * @param array   $args (optional)
     */
    public function __construct($args=array()) {
        foreach ($args as $k=>$v) {
            $this->$k = $v;
        }

        // turn results into objects
        if ($this->results) {
            $docs = array();
            $doc_fields = array();
            foreach ($this->results as $r) {
                if (!count($doc_fields)) {
                    foreach ($r as $k=>$v) {
                        if (!property_exists($k, 'Doc')) {
                            $doc_fields[] = $k;
                        }
                    }
                }
                //diag_dump($r);
                $d = new Doc($r);
                foreach ($doc_fields as $field) {
                    $d->set_field( $field, $r[$field] );
                }
                $docs[] = $d;
            }
            $this->results = $docs;
        }

    }


}
