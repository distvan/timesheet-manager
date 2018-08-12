<?php
namespace DotLogics\Action;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use DotLogics\DB\ProjectDB;
use DotLogics\DB\UserProjectDB;

class ApiProjectAction
{
    private $_db;
    private $_log;

    public function __construct($db, $log)
    {
        $this->_log = $log;
        $this->_db = $db;
    }

    /* Adding Project
    *
    * input: project name, project description, active, parent, created_by
    * output: error or project Id
    *
    * */
    public function addProject(Request $request, Response $response, $args)
    {
        $params = json_decode(file_get_contents('php://input'));
        $name = isset($params->name) ? $params->name : $request->getParam('name');
        $description = isset($params->description) ? $params->description : $request->getParam('description');
        $active = isset($params->active) ? (int)$params->active : (int)$request->getParam('active');
        $parent = isset($params->parent) ? (int)$params->parent : (int)$request->getParam('parent');
        $createdBy = (int)$request->getAttribute('user_id');    //it comes from the token in middleware
        $project = new ProjectDB($this->_db, $this->_log);
        $project->setActive($active);
        $project->setName($name);
        $project->setDescription($description);
        $project->setParentId($parent);
        $project->setCreatedBy($createdBy);
        $projectId = $project->save();

        if(!is_numeric($projectId) && $projectId instanceof Exception)
        {
            return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(array('code' => $projectId->getCode(), 'error' => $projectId->getMessage())));
        }

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => $projectId)));
    }

    /* Append a user to the project
     *
     * */
    public function attachUser(Request $request, Response $response, $args)
    {
        $params = json_decode(file_get_contents('php://input'));
        $projectId = isset($params->projectid) ? (int)$params->projectid : (int)$request->getParam('projectid');
        $userId = isset($params->userid) ? (int)$params->userid : (int)$request->getParam('userid');
        $isleader = isset($params->isleader) ? (int)$params->isleader : (int)$request->getParam('isleader');

        $userProject = new UserProjectDB($this->_db, $this->_log);
        $userProject->setProjectId($projectId);
        $userProject->setUserId($userId);
        $userProject->setIsLeader($isleader);
        $result = $userProject->save();

        if(!$result && !is_a($result, 'Exception'))
        {
            return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(array('code' => $projectId->getCode(), 'error' => $projectId->getMessage())));
        }

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => $result)));
    }

    /* Remove a user from a project
     *
     * */
    public function detachUser(Request $request, Response $response, $args)
    {
        $params = json_decode(file_get_contents('php://input'));
        $projectId = isset($params->projectid) ? (int)$params->projectid : (int)$request->getParam('projectid');
        $userId = isset($params->userid) ? (int)$params->userid : (int)$request->getParam('userid');

        $userProject = new UserProjectDB($this->_db, $this->_log);
        $userProject->setUserId($userId);
        $userProject->setProjectId($projectId);
        $result = $userProject->delete();

        if(!$result && !is_a($result, 'Exception'))
        {
            return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(array('code' => $projectId->getCode(), 'error' => $projectId->getMessage())));
        }

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => $result)));
    }

    /**
     * Get All project for a current user
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function getAllProjectForUser(Request $request, Response $response, $args)
    {
        $userId = (int)$args['userId'];
        $projectList = array();

        $project = new ProjectDB($this->_db, $this->_log);

        $list = $project->getAllCreatedBy($userId);

        foreach($list as $item)
        {
            array_push($projectList, array(
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'description' => $item->getDescription(),
                    'active' => $item->getActive() == 1 ? true : false
                )
            );
        }

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => $projectList)));
    }

    /**
     * Set project status
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function setProjectStatus(Request $request, Response $response, $args)
    {
        $projectId = (int)$args['id'];
        $active = (int)$args['status'];

        $project = new ProjectDB($this->_db, $this->_log);
        $project->setId($projectId);
        $project->setActive($active);
        $project->modifyActive();

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => 'ok')));
    }

    /**
     * Modify project
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function modifyProject(Request $request, Response $response, $args)
    {
        $projectId = (int)$args['id'];

        $params = json_decode(file_get_contents('php://input'));
        $name = isset($params->name) ? $params->name : $request->getParam('name');
        $description = isset($params->description) ? $params->description : $request->getParam('description');
        $parent = isset($params->parent) ? (int)$params->parent : (int)$request->getParam('parent');

        $project = new ProjectDB($this->_db, $this->_log);
        $project->setId($projectId);
        $project->setName($name);
        $project->setDescription($description);
        $project->setParentId($parent);
        $project->save();

        return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(array('result' => 'ok')));
    }
}
?>