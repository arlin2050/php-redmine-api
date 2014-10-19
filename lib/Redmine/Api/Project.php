<?php

namespace Redmine\Api;

/**
 * Listing projects, creating, editing
 *
 * @link   http://www.redmine.org/projects/redmine/wiki/Rest_Projects
 * @author Kevin Saliou <kevin at saliou dot name>
 */
class Project extends AbstractApi
{
    private $projects = array();

    /**
     * List projects
     * @link http://www.redmine.org/projects/redmine/wiki/Rest_Projects
     *
     * @param  array $params optional parameters to be passed to the api (offset, limit, ...)
     * @return array list of projects found
     */
    public function all(array $params = array())
    {
        $this->projects = $this->retrieveAll('/projects.json', $params);

        return $this->projects;
    }

    /**
     * Returns an array of projects with name/id pairs (or id/name if $reserse is false)
     *
     * @param  boolean $forceUpdate to force the update of the projects var
     * @param  boolean $reverse     to return an array indexed by name rather than id
     * @return array   list of projects (id => project name)
     */
    public function listing($forceUpdate = false, $reverse = true)
    {
        if (true === $forceUpdate || empty($this->projects)) {
            $this->all();
        }
        $ret = array();
        foreach ($this->projects['projects'] as $e) {
            $ret[(int) $e['id']] =  $e['name'];
        }

        return $reverse ? array_flip($ret) : $ret;
    }

    /**
     * Get a project id given its name
     * @param  string $name
     * @return int
     */
    public function getIdByName($name)
    {
        $arr = $this->listing();
        if (!isset($arr[$name])) {
            return false;
        }

        return $arr[(string) $name];
    }

    /**
     * Get extended information about a project (including memberships + groups)
     * @link http://www.redmine.org/projects/redmine/wiki/Rest_Projects
     *
     * @param  string $id the project id
     * @return array  information about the project
     */
    public function show($id)
    {
        return $this->get('/projects/'.urlencode($id).'.json?include=trackers,issue_categories,attachments,relations');
    }

    /**
     * Create a new project given an array of $params
     * @link http://www.redmine.org/projects/redmine/wiki/Rest_Projects
     *
     * @param  array            $params the new project data
     * @throws \Exception
     * @return SimpleXMLElement
     */
    public function create(array $params = array())
    {
        $defaults = array(
            'name'        => null,
            'identifier'  => null,
            'description' => null,
        );

        $params = array_filter(
            array_merge($defaults, $params),
            array($this, 'isNotNull')
        );

        if(
            !isset($params['name'])
         || !isset($params['identifier'])
        ) {
            throw new \Exception('Missing mandatory parameters');
        }
		
        $xml = $this->prepareParamsXml($params);

        return $this->post('/projects.xml', $xml->asXML());
    }

    /**
     * Update project's information
     * @link http://www.redmine.org/projects/redmine/wiki/Rest_Projects
     *
     * @param  string           $id     the project id
     * @param  array            $params
     * @return SimpleXMLElement
     */
    public function update($id, array $params)
    {
        $defaults = array(
            'id'          => $id,
            'name'        => null,
            'identifier'  => null,
            'description' => null,
        );
        $params = array_filter(array_merge($defaults, $params));

        $xml = $this->prepareParamsXml($params);

        return $this->put('/projects/'.$id.'.xml', $xml->asXML());
    }

	/**
	 * 
	 * @param array $params
	 * @return \Redmine\Api\SimpleXMLElement
	 */
    protected function prepareParamsXml($params) {
    	$array_params = array(
    		'tracker_ids', 'issue_custom_field_ids'
    	);
    	$array_params_elements = array(
    		'tracker_ids' => 'tracker',
    		'issue_custom_field_ids' => 'issue_custom_field'
    	);
    	
    	$xml = new SimpleXMLElement('<?xml version="1.0"?><project></project>');
    	foreach ($params as $k => $v) {
    		if (in_array($k, $array_params) && is_array($v)) {
    			$array = $xml->addChild($k, '');
    			$array->addAttribute('type', 'array');
    			foreach ($v as $id) {
    				$array->addChild($array_params_elements[$k], $id);
    			}
    		}
    	}
    	return $xml;
    }

    /**
     * Delete a project
     * @link http://www.redmine.org/projects/redmine/wiki/Rest_Projects
     *
     * @param  int  $id id of the project
     * @return void
     */
    public function remove($id)
    {
        return $this->delete('/projects/'.$id.'.xml');
    }
}
