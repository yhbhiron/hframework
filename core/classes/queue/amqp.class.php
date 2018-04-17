<?php !defined('IN_WEB') && exit('Access Deny!');

/**
 * ampq型队列，需要AMPQ扩展
 * @version 1.0
 * @author Administrator
 */
class  QueueAMQP extends  Queue{
    
    /**AMQP 连接器*/
    protected  static  $connect;
    
    /**交互器名称*/
    protected $exchangeName = 'hiron_queue_ex';
    
    /**消息路由名称*/
    protected $routeName = 'hiron_queue_route';
    
    
    protected  $queueName = 'hiron_queue';
    
    public static $serverName = 'default';
    
    
    public function __construct(){
        $this->isConnected() == false && $this->connect();
    }
    
    
    public function enter($code){
        
        $channel = new AMQPChannel(self::$connect);
        $exchange = new AMQPExchange($channel);
        $exchange->setName($this->exchangeName); 
        return $exchange->publish($code, $this->routeName);
    }
    
    
    public function listen(){
        
        $channel = new AMQPChannel(self::$connect);   
        $exchange = new AMQPExchange($channel);
        $exchange->setName($this->exchangeName);
        $exchange->setType(AMQP_EX_TYPE_DIRECT);
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->declare();
        
        $queue = new AMQPQueue($channel);
        $queue->setName($this->queueName);
        $queue->setFlags(AMQP_DURABLE); //持久化
        $queue->declare();
        $queue->bind($this->exchangeName,$this->routeName);
        
        $this->isConnected() == false && $this->connect();
        $queue->consume(array($this,'consumer'));
        self::$connect->disconnect();
        
        
    }
    
    /**
     * @param AMQPEnvelope $envelope
     * @param AMQPQueue $queue
     * @see Queue::execute()
     */
    public function consumer($envelope,$queue){
        
        $code = $envelope->getBody();
        $queue->ack($envelope->getDeliveryTag(),AMQP_REQUEUE);
        $success = $this->execute($code);
        if(!$success){
            $queue->nack($envelope->getDeliveryTag(),AMQP_REQUEUE);
        }
    }
    
    
    
    public function execute($code){
        
        if($code == ''){
            return;
        }
        
        $success = '';
        try{
            
            $func = create_function('', $code);
            $func();
            $success = 'Success!';
            
        }catch(Exception $e){
            $success = 'Failed!--'.$e->getMessage();
            return false;
        }
        
        StrObj::secho(time::now().':Execute Queue '.$success);
        return true;
        
    }
    
    
    
    /**
     * 是否已连接
     * @return boolean
     */
    protected function isConnected(){
        return self::$connect!=null && self::$connect->isConnected() == false;
    }
    
    
    
    /**
     * 连接到服务器
     * @throws Exception
     */
    protected  function connect(){
        
        
        if(!class_exists('AMQPConnection')){
            throw new Exception('找不到队列类，请先安装amqp扩展');
        }
        
        $config = Website::loadConfig('amqp');
        if($config == null || ArrayObj::getItem($config,array('servers',self::$serverName)) == null){
            throw new Exception('amqp配置文件错误');
        }
        
        $config = $config['servers'][self::$serverName];
        $default = array(
            'host' => '127.0.0.1',
            'port' => '15672',
            'login' => 'guest',
            'password' => 'guest',
            'vhost'=>'/'
        );
        
        $config = $config+$default;
        self::$connect = new AMQPConnection($config);
        
        if(!self::$connect->connect() ){
            throw new Exception('amqp连接失败');
        }
        
    }
    
}
