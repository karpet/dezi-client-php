<?php

require_once 'PestJSON.php';
require_once 'Dezi_Doc.php';
require_once 'Dezi_Response.php';
require_once 'Dezi_HTTPResponse.php';

/*

Dezi_Client - interact with a Dezi server

SYNOPSIS

 require_once 'Dezi_Client.php';

 # open a connection
 my $client = new Dezi::Client(array(
    'server'  => 'http://localhost:5000',
 ));

 # add/update a filesystem document to the index
 $client->index( 'path/to/file.html' );

 # add/update an in-memory document to the index
 $client->index( $html_doc, 'foo/bar.html' );

 # add/update a Dezi::Doc to the index
 $client->index( $dezi_doc );

 # remove a document from the index
 $client->delete( '/doc/uri/relative/to/index' );

 # search the index
 my $response = $client->search(array( 'q' => 'foo' ));

 # iterate over results
 foreach ($response->results as $result) {
     printf("--\n uri: %s\n title: %s\n score: %s\n",
        $result->uri, $result->title, $result->score);
 }

 # print stats
 printf("       hits: %d\n", $response->total);
 printf("search time: %s\n", $response->search_time);
 printf(" build time: %s\n", $response->build_time);
 printf("      query: %s\n", $response->query);

*/

class Dezi_Client {

    public $server;
    public $search_uri;
    public $index_uri;
    protected $VERSION = '0.001000';

    /**
     * Constructor.
     *
     * @param unknown $args (optional)
     */
    public function __construct($args=array('server'=>'http://localhost:5000')) {
        $this->server = $args['server'];
        if (array_key_exists('search', $args) && array_key_exists('index', $args)) {
            $this->search_uri = $args['server'] . $args['search'];
            $this->index_uri  = $args['server'] . $args['index'];
        }
        else {

            $pest = new PestJSON($this->server);
            try {
                $resp = $pest->get('/');
            }
            catch (Pest_NotFound $err) {
                // 404
                die(sprintf("404 response from server: %s/\n", $this->server));
            }
            catch (Pest_UnknownResponse $err) {
                die(sprintf("Initial connect failed to server: %s/\n", $this->server));
            }

            //error_log( var_export($resp, true) );
            $this->search_uri = $resp['search'];
            $this->index_uri =  $resp['index'];
        }

    }





    /**
     *
     *
     * @param unknown $doc
     * @param unknown $uri          (optional)
     * @param unknown $content_type (optional)
     * @return unknown
     */
    public function index($doc, $uri=null, $content_type=null) {
        $buf = null;
        if (!is_object($doc) && file_exists($doc)) {
            $buf = file_get_contents($doc);
            if ($uri==null) {
                $uri = $doc;
            }
        }
        elseif (!is_object($doc)) {
            $buf = $doc;
            if ($uri==null) {
                throw new Exception("uri required");
            }
        }
        elseif (is_object($doc) && is_a($doc, 'Dezi_Doc')) {
            $buf = $doc->content;
            if ($uri==null) {
                $uri = $doc->uri;
            }
            if ($content_type==null) {
                $content_type = $doc->mime_type;
            }
        }
        else {
            throw new Exception("doc must be a file, in-memory buffer, or Dezi_Doc object");
        }

        if ($content_type==null) {
            $content_type = Dezi_Doc::get_mime_type($uri);
        }

        $pest = new Pest($this->index_uri);
        $resp = $pest->post("/$uri", $buf, array('Content-Type: '.$content_type));
        $http_resp = new Dezi_HTTPResponse();
        $http_resp->status = $pest->lastStatus();
        $http_resp->content = $resp;

        return $http_resp;

    }



    /**
     *
     */
    public function search() {


    }



    /**
     *
     */
    public function delete() {


    }


}
