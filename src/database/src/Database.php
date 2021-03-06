<?php

namespace Mix\Database;

use Mix\Database\Pool\ConnectionPool;
use Mix\Database\Pool\Dialer;
use Mix\Database\Query\Expression;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Class Database
 * @package Mix\Database
 */
class Database
{

    /**
     * 数据源格式
     * @var string
     */
    protected $dsn = '';

    /**
     * 数据库用户名
     * @var string
     */
    protected $username = 'root';

    /**
     * 数据库密码
     * @var string
     */
    protected $password = '';

    /**
     * 驱动连接选项
     * @var array
     */
    protected $options = [];

    /**
     * 最大连接数
     * @var int
     * @deprecated 废弃，使用 maxOpen 取代
     */
    public $maxActive = -1;

    /**
     * 最大活跃数
     * "0" 为不限制，默认等于cpu数量
     * @var int
     * @deprecated 应该设置为 protected，为了向下兼容而保留 public
     */
    public $maxOpen = -1;

    /**
     * 最多可空闲连接数
     * 默认等于cpu数量
     * @var int
     * @deprecated 应该设置为 protected，为了向下兼容而保留 public
     */
    public $maxIdle = -1;

    /**
     * 连接可复用的最长时间
     * "0" 为不限制
     * @var int
     * @deprecated 应该设置为 protected，为了向下兼容而保留 public
     */
    public $maxLifetime = 0;

    /**
     * 等待新连接超时时间
     * "0" 为不限制
     * @var float
     * @deprecated 应该设置为 protected，为了向下兼容而保留 public
     */
    public $waitTimeout = 0.0;

    /**
     * 事件调度器
     * @var EventDispatcherInterface
     */
    public $dispatcher;

    /**
     * 连接池
     * @var ConnectionPool
     */
    protected $pool;

    /**
     * Database constructor.
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @param int $maxOpen
     * @param int $maxIdle
     * @param int $maxLifetime
     * @param float $waitTimeout
     * @throws \PhpDocReader\AnnotationException
     * @throws \ReflectionException
     */
    public function __construct(string $dsn, string $username, string $password, array $options = [],
                                int $maxOpen = -1, int $maxIdle = -1, int $maxLifetime = 0, float $waitTimeout = 0.0)
    {
        $this->dsn         = $dsn;
        $this->username    = $username;
        $this->password    = $password;
        $this->options     = $options;
        $this->maxOpen     = $maxOpen;
        $this->maxIdle     = $maxIdle;
        $this->maxLifetime = $maxLifetime;
        $this->waitTimeout = $waitTimeout;
        $this->pool        = $this->createPool();
    }

    /**
     * @return ConnectionPool
     * @throws \PhpDocReader\AnnotationException
     * @throws \ReflectionException
     */
    protected function createPool()
    {
        $pool             = new ConnectionPool(
            new Dialer([
                'dsn'      => $this->dsn,
                'username' => $this->username,
                'password' => $this->password,
                'options'  => $this->options,
            ]),
            $this->maxOpen,
            $this->maxIdle,
            $this->maxLifetime,
            $this->waitTimeout
        );
        $pool->dispatcher = &$this->dispatcher;
        return $pool;
    }

    /**
     * @param int $maxOpen
     * @throws \PhpDocReader\AnnotationException
     * @throws \ReflectionException
     */
    public function setMaxOpen(int $maxOpen)
    {
        $this->maxOpen = $maxOpen;
        $this->pool    = $this->createPool();
    }

    /**
     * @param int $maxIdle
     * @throws \PhpDocReader\AnnotationException
     * @throws \ReflectionException
     */
    public function setMaxIdle(int $maxIdle)
    {
        $this->maxIdle = $maxIdle;
        $this->pool    = $this->createPool();
    }

    /**
     * @param int $maxLifetime
     * @throws \PhpDocReader\AnnotationException
     * @throws \ReflectionException
     */
    public function setMaxLifetime(int $maxLifetime)
    {
        $this->maxLifetime = $maxLifetime;
        $this->pool        = $this->createPool();
    }

    /**
     * @param float $waitTimeout
     * @throws \PhpDocReader\AnnotationException
     * @throws \ReflectionException
     */
    public function setWaitTimeout(float $waitTimeout)
    {
        $this->waitTimeout = $waitTimeout;
        $this->pool        = $this->createPool();
    }

    /**
     * Borrow connection
     * @return Connection
     */
    public function borrow(): Connection
    {
        $driver           = $this->pool->borrow();
        $conn             = new Connection($driver);
        $conn->dispatcher = $this->dispatcher;
        return $conn;
    }

    /**
     * 准备执行语句
     * @param string|array $sql
     * @return Connection
     */
    public function prepare($sql): Connection
    {
        return $this->borrow()->prepare($sql);
    }

    /**
     * 插入
     * @param string $table
     * @param array $data
     * @return Connection
     */
    public function insert(string $table, array $data): Connection
    {
        return $this->borrow()->insert($table, $data);
    }

    /**
     * 批量插入
     * @param string $table
     * @param array $data
     * @return Connection
     */
    public function batchInsert(string $table, array $data): Connection
    {
        return $this->borrow()->batchInsert($table, $data);
    }

    /**
     * 更新
     * @param string $table
     * @param array $data
     * @param array $where
     * @return Connection
     */
    public function update(string $table, array $data, array $where): Connection
    {
        return $this->borrow()->update($table, $data, $where);
    }

    /**
     * 删除
     * @param string $table
     * @param array $where
     * @return Connection
     */
    public function delete(string $table, array $where): Connection
    {
        return $this->borrow()->delete($table, $where);
    }

    /**
     * 自动事务
     * @param \Closure $closure
     * @throws \Throwable
     */
    public function transaction(\Closure $closure)
    {
        return $this->borrow()->transaction($closure);
    }

    /**
     * 开始事务
     * @return Connection
     */
    public function beginTransaction(): Connection
    {
        return $this->borrow()->beginTransaction();
    }

    /**
     * 启动查询生成器
     * @param string $table
     * @return QueryBuilder
     */
    public function table(string $table): QueryBuilder
    {
        return $this->borrow()->table($table);
    }

    /**
     * 返回一个RawQuery对象，对象的值将不经过参数绑定，直接解释为SQL的一部分，适合传递数据库原生函数
     * @param string $value
     * @return Expression
     */
    public static function raw(string $value): Expression
    {
        return new Expression($value);
    }

}
