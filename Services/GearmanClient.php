<?php
namespace Cimus\GearmanBundle\Services;


/**
 * Description of GearmanClient
 *
 * @author Sergey Ageev (Cimus <s_ageev@mail.ru>)
 */
class GearmanClient
{
    
    /**
     * Gearman method do
     *
     * The GearmanClient::do() method is deprecated as of pecl/gearman 1.0.0.
     * Use GearmanClient::doNormal().
     *
     * @see http://www.php.net/manual/en/gearmanclient.do.php
     *
     * @var string
     */
    const GEARMAN_METHOD_DO = 'do';
    /**
     * Gearman method doNormal
     *
     * @see http://www.php.net/manual/en/gearmanclient.donormal.php
     *
     * @var string
     */
    const GEARMAN_METHOD_DONORMAL = 'doNormal';
    /**
     * Gearman method doLow
     *
     * @see http://www.php.net/manual/en/gearmanclient.dolow.php
     *
     * @var string
     */
    const GEARMAN_METHOD_DOLOW = 'doLow';
    /**
     * Gearman method doHigh
     *
     * @see http://www.php.net/manual/en/gearmanclient.dohigh.php
     *
     * @var string
     */
    const GEARMAN_METHOD_DOHIGH = 'doHigh';
    /**
     * Gearman method doBackground
     *
     * @see http://php.net/manual/en/gearmanclient.dobackground.php
     *
     * @var string
     */
    const GEARMAN_METHOD_DOBACKGROUND = 'doBackground';
    /**
     * Gearman method doLowBackgound
     *
     * @see http://php.net/manual/en/gearmanclient.dolowbackground.php
     *
     * @var string
     */
    const GEARMAN_METHOD_DOLOWBACKGROUND = 'doLowBackground';
    /**
     * Gearman method doHighBackground
     *
     * @see http://php.net/manual/en/gearmanclient.dohighbackground.php
     *
     * @var string
     */
    const GEARMAN_METHOD_DOHIGHBACKGROUND = 'doHighBackground';
    /**
     * Tasks methods
     */
    /**
     * Gearman method addTask
     *
     * @see http://www.php.net/manual/en/gearmanclient.addtask.php
     *
     * @var string
     */
    const GEARMAN_METHOD_ADDTASK = 'addTask';
    /**
     * Gearman method addTaskLow
     *
     * @see http://www.php.net/manual/en/gearmanclient.addtasklow.php
     *
     * @var string
     */
    const GEARMAN_METHOD_ADDTASKLOW = 'addTaskLow';
    /**
     * Gearman method addTaskHigh
     *
     * @see http://www.php.net/manual/en/gearmanclient.addtaskhigh.php
     *
     * @var string
     */
    const GEARMAN_METHOD_ADDTASKHIGH = 'addTaskHigh';
    /**
     * Gearman method addTaskBackground
     *
     * @see http://www.php.net/manual/en/gearmanclient.addtaskbackground.php
     *
     * @var string
     */
    const GEARMAN_METHOD_ADDTASKBACKGROUND = 'addTaskBackground';
    /**
     * Gearman method addTaskLowBackground
     *
     * @see http://www.php.net/manual/en/gearmanclient.addtasklowbackground.php
     *
     * @var string
     */
    const GEARMAN_METHOD_ADDTASKLOWBACKGROUND = 'addTaskLowBackground';
    /**
     * Gearman method addTaskHighBackground
     *
     * @see http://www.php.net/manual/en/gearmanclient.addtaskhighbackground.php
     *
     * @var string
     */
    const GEARMAN_METHOD_ADDTASKHIGHBACKGROUND = 'addTaskHighBackground';
    
    
    /**
     *
     * @var \GearmanClient 
     */
    private $gearmanClient;

    private $tasks = [];



    public function __construct(\GearmanClient $gearmanClient)
    {
        $this->gearmanClient = $gearmanClient;
    }
    /**
     * 
     * @param string $name
     * @param mixed $params
     * @param null|string $unique
     * @return \Cimus\GearmanBundle\Services\GearmanClient
     */
    public function doBackground($name, $params = '', $unique = null)
    {
        $this->setJob($name, $params, $unique, self::GEARMAN_METHOD_DOBACKGROUND);
        return $this;
    }
    
    /**
     * Get the last Gearman return code
     *
     * @return int
     * @link http://docs.php.net/manual/en/gearmanclient.returncode.php
     */
    public function returnCode()
    {
        return $this->gearmanClient->returnCode();
    }

        protected function setJob($name, $params, $unique, $method)
    {
        $params = serialize($params);
        $unique = $this->generateUniqueKey($name, $params, $unique, $method);
        
        
        return $this->gearmanClient->$method($name,$params,$unique);
    }

    
    

//    protected function createTask($name, $params, $unique, $method)
//    {
//        $params = serialize($params);
//        
//        $this->tasks[] = [
//            'name'    => $name,
//            'params'  => $params,
//            'unique'  => $this->uniqueJobIdentifierGenerator->generateUniqueKey($name, $params, $unique, $method),
//            'method'  => $method,
//        ];
//
//        return $this;
//    }
    
    protected function generateUniqueKey($name, $params, $unique, $method)
    {
        if($unique)
            return $unique;
        
        return crc32($name . $params . $unique . $method);
    }
    
//    public function runTasks()
//    {
//        foreach($this->tasks as $task)
//        {
//            $this->gearmanClient->{$task['method']}(
//                        $task['name'],
//                        $task['params'],
//                        $task['unique']
//                    );
//        }
//        
//        $this->tasks = [];
//    }
    
}
