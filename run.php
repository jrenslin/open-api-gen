<?PHP
/**
 * Pre-generates the description of an OpenAPI definition for the response of an API.
 *
 * @author Joshua Ramon Enslin<jenslin@freies-deutsches-hochstift.de>
 */
declare(strict_types=1);

class OPEN_API_DESCRIBER {

    /** @var array<mixed> */
    private readonly array $_responseProperties;

    /**
     * Forms the response format for an array (either array or list).
     *
     * @param array<mixed>   $value Value to describe.
     * @param integer|string $key   Array key for error msgs.
     *
     * @return array<mixed>
     */
    public function categorizeArray(array $value, string|int $key):array {


        if (array_is_list($value)) {

            try {
                return [
                    'type' => 'array',
                    'items' => $this->parseList($value),
                ];
            }
            catch (Exception $e) {
                return [
                    'type' => 'array',
                    'items' => 'TODO TODO TODO',
                ];
            }

        }
        else {

            $output = [
                'type' => 'object',
                'properties' => $this->parse($value),
            ];
            $output['required'] = array_keys($output['properties']);
            return $output;

        }

    }

    /**
     * Checks a list respose value and returns a description.
     * Assumes that all elements in the list are formed the same way.
     *
     * @param array $list List to describe.
     *
     * @return array<mixed>
     */
    public function parseList(array $list):array {

        if (empty($list) || empty($list[0])) {
            throw new Exception("Cannot describe empty list");
        }

        return $this->matchTypeToProperty($list[0], 0);

    }

    /**
     * Formulates the right response for a given (single) element.
     *
     * @param mixed          $value Element to formulate output for.
     * @param string|integer $key   Key currently matched (for debugging).
     *
     * @return array<mixed>
     */
    private function matchTypeToProperty(mixed $value, string|int $key):array {

        return match(gettype($value)) {
            "string" => [
                'type' => 'string',
                'example' => $value,
                'description' => 'TODO',
            ],
            'integer' => [
                'type' => 'integer',
                'example' => $value,
                'description' => 'TODO',
            ],
            'float' => [
                'type' => 'number',
                'example' => $value,
                'description' => 'TODO',
            ],
            'boolean' => [
                'type' => 'boolean',
                'example' => $value,
                'description' => 'TODO',
            ],
            'array' => $this->categorizeArray($value, $key),
            default => throw new Exception("Unknown type encountered for element " . $key)
        };

    }

    /**
     * Parses API output.
     *
     * @param array<mixed> $elements Elements to describe.
     *
     * @return array<mixed>
     */
    public function parse(array $elements):array {

        $output = [];

        foreach ($elements as $key => $value) {

            $output[$key] = $this->matchTypeToProperty($value, $key);

        }

        return $output;

    }

    /**
     * Returns the description of an API.
     *
     * @return array<mixed>
     */
    public function describe():array {

        return [
            '200' => [
                'description' => 'Returns a list of translations',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => $this->_responseProperties,
                        ],
                    ],
                ],
            ],
        ];

    }

    /**
     * Constructor. Takes the URL and parses its JSON.
     *
     * @param string $url URL.
     *
     * @return void
     */
    public function __construct(string $url) {

        $response = file_get_contents($url); // TODO: Replace with curl later.
        if (($json = json_decode($response, true)) === false) {
            throw new Exception("Failed to parse JSON output");
        }
        $this->_responseProperties = $this->parse($json);

    }

}

if (($url = filter_var($argv[1], FILTER_VALIDATE_URL)) === false) {
    throw new Exception("First argument needs to be an absolute URL pointing to an API");
}

$run = new OPEN_API_DESCRIBER($url);

if (in_array('php', $argv, true)) {
    echo var_export($run->describe(), true);
}
else {
    echo json_encode($run->describe(), JSON_PRETTY_PRINT);
}

