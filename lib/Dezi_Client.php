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
    public $last_response;
    public static $VERSION = '0.001000';

    /**
     * Constructor. Returns a new Client.
     *
     * @param array   $args (optional)
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
     * index() - add or update a document in the index
     *
     * Expects either a string file, string in-memory document,
     * or a Dezi_Doc object.
     *
     * @param string_or_object $doc
     * @param string  $uri          (optional)
     * @param string  $content_type (optional)
     * @return Dezi_HTTPResponse $resp
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

        //error_log("content_type=$content_type");
        $pest = new Pest($this->index_uri);
        $resp = $pest->post("/$uri", $buf, array('Content-Type: '.$content_type));
        $http_resp = new Dezi_HTTPResponse();
        $http_resp->status = $pest->lastStatus();
        $http_resp->content = $resp;

        return $http_resp;

    }



    /**
     * search() - Fetch search results from a Dezi server.
     *
     * $params may be any key/value pair as described in Search::OpenSearch.
     *
     * Returns a Dezi_Response on success and 0 on failure. Check
     * $client->last_response on failure.
     *
     * @param array   $params
     * @return Dezi_Response object
     */
    public function search($params) {
        $pest = new PestJSON($this->search_uri);
        $params['format'] = 'json';  // force response type
        $query = http_build_query($params);
        $resp = $pest->get("?$query");
        $http_resp = new Dezi_HTTPResponse();
        $http_resp->status = $pest->lastStatus();
        $http_resp->content = $resp;
        $this->last_response = $http_resp;
        if ($pest->lastStatus() != '200') {
            return 0;
        }
        $dezi_response = new Dezi_Response($resp);
        return $dezi_response;
    }



    /**
     * delete() - remove a document from the index.
     *
     * $uri should be the document URI.
     *
     * @param string  $uri
     * @return Dezi_HTTPResponse $resp
     */
    public function delete($uri) {
        $pest = new Pest($this->index_uri);
        $resp = $pest->delete("/$uri");
        $http_resp = new Dezi_HTTPResponse();
        $http_resp->status = $pest->lastStatus();
        $http_resp->content = $resp;
        return $http_resp;
    }


}
