<?php

/*

Dezi_Response - Dezi search server response

SYNOPSIS

 require 'Dezi_Response.php';
 $client = new Dezi_Client(array(
    'server' => 'http://localhost:5000'
 ));

 $response = $client->search(array( 'q' => 'foo' ));
 // $response isa Dezi_Response

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

class Dezi_Response {

    public $results;
    public $total;
    public $search_time;
    public $build_time;
    public $query;
    public $fields;
    public $facets;
    protected $VERSION = '0.001000';

    /**
     * Constructor. Returns Dezi_Reponse object.
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
            foreach ($this->results as $r) {
                $docs[] = new Dezi_Doc($r);
            }
            $this->results = $docs;
        }

    }


}
