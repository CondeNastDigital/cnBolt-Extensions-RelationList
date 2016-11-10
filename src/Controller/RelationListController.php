<?php

namespace Bolt\Extension\CND\RelationList\Controller;


use Bolt\Application;
use Bolt\Legacy\Content;
use Exception;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RelationListController implements ControllerProviderInterface
{
    const DEFAULT_EXCERPT_LENGTH = 125;

    private $app;
    private $config;

    private $contentTypeConfigs = [
        'default' => 'options',
        'structuredcontentfield' => 'extend'
    ];

    public function __construct (Application $app, array $config)
    {
        $this->app = $app;
        $this->config = $config;
        $this->app['twig.loader.filesystem']->prependPath(__DIR__."/../../templates");
    }

    public function connect(\Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];
        $ctr->get('/finditems/{contenttype}/{field}/{search}', array($this, 'findItems'));
        $ctr->get('/finditems/{contenttype}/{field}/{search}/{subfield}', array($this, 'findItems'));
        $ctr->post('/fetchJsonList', array($this, 'fetchContentElementArray'));

        return $ctr;
    }

    /**
     * Get a list of matching contents per type
     *
     * @param string $contenttype
     * @param string $field
     * @param string $search
     *
     * @return JsonResponse
     */
    public function findItems($contenttype, $field, $search, $subfield=null){
        $contenttype = preg_replace("/[^a-z0-9\\-_]+/i", "", $contenttype);
        $field       = preg_replace("/[^a-z0-9\\-_]+/i", "", $field);
        $subfield    = preg_replace("/[^a-z0-9\\-_]+/i", "", $subfield);

        if(!$this->app["users"]->isValidSession())
            return $this->makeErrorResponse("Insufficient access rights!");

        $config = $this->getFieldConfig($contenttype, $field, $subfield);

        if ( !$config )
            return $this->makeErrorResponse("Missing field configuration! Please make sure, the `options` attribute is defined according to the `README.md` file.");

        $allowedTypes = isset($config["allowed-types"]) ? $config["allowed-types"] : array();
        $results = array();
        $content = $this->app['storage']->searchContent($search, $allowedTypes, null, 100, 0);

        if($content["results"]) {
            foreach ($content["results"] as $entry) {
                /* @var \Bolt\Legacy\Content $entry */
                $results[] = $this->filterElement($entry);
            }
        }
        return $this->makeDataResponse( $results );
    }

    /**
     * Fetch a JSON object list of elements
     *
     * @param Request $request
     * @return Content elements as JSON
     * @throws Exception
     */
    public function fetchContentElementArray( Request $request ) {

        $elements = $request->request->get("elements");

        if( !$this->app["users"]->isValidSession() )
            return $this->makeErrorResponse("Insufficient access rights!");

        if ( !isset( $elements ) || !is_array( $elements ) )
            return $this->makeErrorResponse("Given elements are not in a valid format.");

        $elements = $this->filterIdentifierList( $elements );
        $ordered = $elements;
        $elements = $this->mapContentElementIdList( $elements ); // mapping list -> prep for db queries


        $contentTypes = array_keys( $elements );
        $results = array();

        foreach ( $contentTypes as $contentType ) {
            // Retrieve content objects
            $contentObjects = $this->app["storage"]->getContent( $contentType, $elements[$contentType] );

            if ( !is_array($contentObjects) && get_class($contentObjects) == "Bolt\\Legacy\\Content" ) {
                $newList = array();
                $newList[$contentObjects->id] = $contentObjects;
                $contentObjects = $newList;
            }

            foreach ($contentObjects as $cObject) {
                if ( !get_class($cObject) == "Bolt\\Legacy\\Content" )
                    throw new Exception("Problem parsing getContent results!");

                $item = $this->filterElement($cObject);
                $results[$item["id"]] = $item;
            }
        }
        // Sort items (and add unsortables at bottom. We may have unsortables because of old style names in id's instead of singular_slug)
        $sortedResults = array();
        foreach($ordered as $id) {
            if (isset($results[$id])) {
                $sortedResults[] = $results[$id];
                unset($results[$id]);
            }
        }
        $sortedResults = array_merge($sortedResults, $results);
        $sortedResults = array_values($sortedResults);

        return $this->makeDataResponse( array("results" => $sortedResults) );
    }

    /**
     * Create an JSON response with error message
     *
     * @param string $message
     * @return JsonResponse
     */
    private function makeErrorResponse( $message ) {
        return new JsonResponse(array(
            "status" => "error",
            "message" => $message
        ));
    }

    /**
     * Create an JSON response with error message
     *
     * @param array $data
     * @return JsonResponse
     */
    private function makeDataResponse( $data ) {
        return new JsonResponse(array(
            "status" => "okay",
            "data" => $data
        ));
    }

    protected function filterElement(Content $cObject, $length = self::DEFAULT_EXCERPT_LENGTH){

        $length = $length - mb_strlen($cObject->getTitle()) - mb_strlen($cObject->contenttype["singular_name"]) - 15;

        $obj = array();
        $obj["id"] = $cObject->contenttype["slug"] . "/" . $cObject->id;
        $obj["title"] = $cObject->getTitle();
        $obj["excerpt"] = mb_convert_encoding((string)$cObject->excerpt($length), "utf-8", "utf-8"); // Broken utf-8 characters will break JSON encoder. This filters invalid chars
        $obj["thumbnail"] = $cObject->getImage();

        $dateChanged = new \DateTime( $cObject->get("datechanged") );
        $obj["datechanged"] = $dateChanged->format('Y-m-d');
        $obj["contenttype"] = $cObject->contenttype["singular_name"];
        $obj["link"] = $cObject->editlink();

        return $obj;
    }

    /**
     * Determine the field config from the contenttypes.yml
     * @param string $contenttype
     * @param string $field
     * @return array|false Returns false, if there is no configuration
     */
    protected function getFieldConfig($contenttype, $field, $subfield=null){
        $contenttype = $this->app['storage']->getContentType($contenttype);

        if(!$contenttype)
            return false;

        $fieldDefinition = $contenttype['fields'][$field];

        if(isset($this->contentTypeConfigs[$fieldDefinition['type']]))
            $configPath = $this->contentTypeConfigs[$fieldDefinition['type']];
        else
            $configPath = $this->contentTypeConfigs['default'];

        if($subfield)
            $configPath .= '.'.$subfield;

        $configPath = explode('.', $configPath);

        $config = $fieldDefinition;

        foreach ($configPath as $path)
            if(isset($config[$path]))
                $config = $config[$path];
            else
                $config = false;

        return $config;
    }

    /**
     * Validate id schema within a list of ids
     * Invalid Ids will be deleted
     *
     * Schema: [A-Za-z0-9_]*)\/([0-9]*)
     *
     * @param array $ids - String list of content element Ids in the forma `contenttype/id`
     *
     * @return array - String list of validated ids
     */
    private function filterIdentifierList( $ids ) {
        $result = array();
        $id_pattern = "/([A-Za-z0-9_]*)\\/([0-9]*)/";

        foreach ( $ids as $id ) {
            if ( is_string($id) && preg_match($id_pattern, $id) )
                $result[] = $id;
        }

        return $result;
    }

    /**
     * Maps array in format ["Page/5", "Page/1", "Record/6"] to
     * [
     *   "Page" => ["id" => ["5", "1"]],
     *   "Record" => ["id" => ["6"]]
     * ]
     *
     * @param  array  $elements - Holds a list of content element ids
     *
     * @return array - Nested list of ids, grouped by content type
     */
    public function mapContentElementIdList( array $elements ) {
        $result = array();

        foreach ( $elements as $element ) {
            $element = explode("/", $element);
            $contentType = $element[0];
            $elementId = $element[1];

            $result[$contentType]['id'][] = intval($elementId);
        }

        foreach( array_keys($result) as $key ) {
            $result[$key]['id'] = implode(" || ", $result[$key]['id']);
        }

        return $result;
    }

}
