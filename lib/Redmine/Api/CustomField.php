<?php

namespace Redmine\Api;

/**
 * Listing custom fields.
 *
 * @see   http://www.redmine.org/projects/redmine/wiki/Rest_CustomFields
 *
 * @author Kevin Saliou <kevin at saliou dot name>
 */
class CustomField extends AbstractApi
{
    private $customFields = [];

    /**
     * List custom fields.
     *
     * @see http://www.redmine.org/projects/redmine/wiki/Rest_CustomFields#GET
     *
     * @param array $params optional parameters to be passed to the api (offset, limit, ...)
     *
     * @return array list of custom fields found
     */
    public function all(array $params = [])
    {
        $this->customFields = $this->retrieveAll('/custom_fields.json', $params);

        return $this->customFields;
    }

    /**
     * Returns an array of custom fields with name/id pairs.
     *
     * @param bool  $forceUpdate to force the update of the custom fields var
     * @param array $params      optional parameters to be passed to the api (offset, limit, ...)
     *
     * @return array list of custom fields (id => name)
     */
    public function listing($forceUpdate = false, array $params = [])
    {
        if (empty($this->customFields) || $forceUpdate) {
            $this->all($params);
        }
        $ret = [];
        foreach ($this->customFields['custom_fields'] as $e) {
            $ret[$e['name']] = (int) $e['id'];
        }

        return $ret;
    }

    /**
     * Get a tracket id given its name.
     *
     * @param string|int $name   customer field name
     * @param array      $params optional parameters to be passed to the api (offset, limit, ...)
     *
     * @return int|false
     */
    public function getIdByName($name, array $params = [])
    {
        $arr = $this->listing(false, $params);
        if (!isset($arr[$name])) {
            return false;
        }

        return $arr[(string) $name];
    }

    /**
     * Update custom fields value's by custom field number. Requires authentication.
     *
     * @param  string           $id     the issue number
     * @param  array            $params
     * @return SimpleXMLElement
     */
    public function update($id, array $params)
    {
        $defaults = array(
            //'id'              => $id,
            //'name'            => null,
            //'customized_type' => null,
            //'field_format'    => null,
            //'regexp'          => null,
            //'min_length'      => null,
            //'max_length'      => null,
            //'is_required'     => null,
            //'is_filter'       => null,
            //'searchable'      => null,
            //'multiple'        => null,
            //'default_value'   => null,
            //'visible'         => null,
            'possible_values' => null
        );
        $params = array_filter(array_merge($defaults, $params));

        $xml = $this->buildXML($params);

        return $this->put('/custom_fields/'.$id.'.xml', $xml->asXML());
    }

    /**
     * Build the XML for a custom field
     * @param  array            $params for the new/updated issue data
     * @return SimpleXMLElement
     */
    private function buildXML(array $params = array())
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0"?><custom_field></custom_field>');

        foreach ($params as $k => $v) {
            if ('possible_values' === $k && is_array($v)) {
                $this->attachPossibleValuesXML($xml, $v);
            } else {
                $xml->addChild($k, $v);
            }
        }

        return $xml;
    }

    /**
     * Attaches Custom Fields possible values
     *
     * @param  \SimpleXMLElement $xml    XML Element the custom fields are attached to
     * @param  array            $fields array of fields to attach, each field needs name, id and value set
     * @return $xml             \SimpleXMLElement
     */
    protected function attachPossibleValuesXML(\SimpleXMLElement $xml, array $fields)
    {
        $_fields = $xml->addChild('possible_values', implode("\r\n",$fields));
        return $xml;
    }
}
