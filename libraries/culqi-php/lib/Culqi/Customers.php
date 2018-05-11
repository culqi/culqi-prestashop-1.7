<?php

namespace Culqi;

/**
 * Class Customers
 *
 * @package Culqi
 */
class Customers extends Resource {

    const URL_CUSTOMERS = "/customers/";

    /**
     * @param array|null $options
     *
     * @return all Customers.
     */
    public function getList($options = NULL) {
        return $this->request("GET", self::URL_CUSTOMERS, $api_key = $this->culqi->api_key, $options);
    }

    /**
     * @param array|null $options
     *
     * @return create Customer response.
     */
    public function create($options = NULL) {
        return $this->request("POST", self::URL_CUSTOMERS, $api_key = $this->culqi->api_key, $options);
    }

    /**
     * @param string|null $id
     *
     * @return delete a Customer response.
     */
    public function delete($id = NULL) {
       return $this->request("DELETE", self::URL_CUSTOMERS . $id . "/", $api_key = $this->culqi->api_key);
    }

    /**
     * @param string|null $id
     *
     * @return get a Customer.
     */
    public function get($id = NULL) {
        return $this->request("GET", self::URL_CUSTOMERS . $id . "/", $api_key = $this->culqi->api_key, $options);
    }

}
