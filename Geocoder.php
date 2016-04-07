<?php
class Geocoder
{
    public static $geo_url = 'https://maps.googleapis.com/maps/api/geocode/';
    //camelCase!!!!
    public static $nearbysearch_url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/';
    public static $distancematrix_url = 'https://maps.googleapis.com/maps/api/distancematrix/';
    const SUCCESS = "OK";
    const ZERO_RESULTS = "ZERO_RESULTS";
    const OVER_QUERY_LIMIT = "OVER_QUERY_LIMIT";
    const REQUEST_DENIED = "REQUEST_DENIED";
    const INVALID_REQUEST = "INVALID_REQUEST";
    const UNKNOWN_ERROR = "UNKNOWN_ERROR";
    protected $_apiKey;
    protected $_type;
    protected $_radius;
    protected $_city;
    protected $_travelMode;
    protected $_output;

    public function __construct($key, $type = 'subway_station',$radius = 2000, $city = 'Санкт-Петербург',$travelMode = 'walking', $output = 'json')
    {
        $this->_apiKey = $key;
        $this->_type = $type;
        $this->_radius = $radius;
        $this->_city = $city;
        $this->_travelMode = $travelMode;
        $this->_output = $output;
    }

    public function performRequest($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        $jsonResponse = json_decode($response);
        $status = $this->checkStatus($jsonResponse->status);
        if($status)
        {
            return $jsonResponse;
        }
        else return $status;
    }

    public function checkStatus($status)
    {
        switch ($status)
        {
            case self::SUCCESS:
                return 1;              
            case self::ZERO_RESULTS:
                return 0;
            default:
                 throw new Exception(sprintf("Google Geo error %d occurred", $status));
        }
    }

    public function getCoordinates($place)
    {
        return sprintf("%s,%s",$place->geometry->location->lat,$place->geometry->location->lng);
        
    }

     public function getInitialPlace($search)
    {
        $address = sprintf("%s, %s", $this->_city,$search);
        $url = sprintf("%s%s?address=%s&key=%s", self::$geo_url, $this->_output, urlencode($address), urlencode($this->_apiKey));
        $response = $this->performRequest($url);
        //нужна проверка
        if (1)
        {          
            $place = $response->results[0];
            return $place;
        }
        
    }

    public function getEndPlaces($initialCoordinates)
    {
        $url = sprintf("%s%s?location=%s&radius=%s&type=%s&rankBy=%s&key=%s", self::$nearbysearch_url, $this->_output, urlencode($initialCoordinates),urlencode($this->_radius),urlencode($this->_type),urlencode('distance'), $this->_apiKey);
        $response = $this->performRequest($url);
        print("ok");
        //нужна проверка
        if (1)
        {
            $places = array();
            foreach($response->results as $num => $place)
            {
                $places[$num] = $place;
            }
        }
        return $places;
    }

    public function getDistances($origins, $destinations)
    {
        $url = sprintf("%s%s?origins=%s&destinations=%s&transit_mode=%s&key=%s", self::$distancematrix_url,$this->_output, urlencode($origins), urlencode($destinations), urlencode($this->_travelMode), urlencode($this->_apiKey));
        $response = $this->performRequest($url);
        print("ok");
        if (1)
        {
            $distances = array();
            $times = array();
            foreach ($response->rows[0]->elements as $num => $info) 
            {
                //нужна проверка
                if(1)
                {
                    $distances[$num] = $info->distance->value;
                    $times[$num] = $info->duration->value;
                }

            }
            arsort($distances);
            $distancesInfo = array();
            foreach ($distances as $key => $value) 
            {
                $distancesInfo[$key] = sprintf("distance: %s; time: %s", $value, $times[$key]);
            }
            return $distancesInfo;
        }
 
    }

    public function lookup($search)
    {
        $initialPlace = $this->getInitialPlace($search);    
        $initialCoordinates = $this->getCoordinates($initialPlace);
        $endPlaces = $this->getEndPlaces($initialCoordinates);
        $endCoordinates = array();
        foreach ($endPlaces as $num => $place) 
        {
            $endCoordinates[$num] = $this->getCoordinates($place);
        }
        $validDestinations = implode("|",$endCoordinates); 
        $distances = $this->getDistances($initialCoordinates,$validDestinations);
        foreach ($endPlaces as $num => $place) 
        {
            $str = sprintf("Метро: %s; %s\n", $place->name,$distances[$num]);
            print($str);
        }
        return 0;
    }

}
$obj = new Geocoder('AIzaSyCwipAvGva2u0FjGwQ1CiXbj-WcA84o6Ik');
print $obj->lookup("Кубинская, 22");
?>

