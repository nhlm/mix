<?php

namespace apps\daemon\commands;

use mix\console\Controller;
use mix\swoole\Process;

/**
 * 这是一个单进程守护进程的范例
 * @author 刘健 <coder.liu@qq.com>
 */
class SingleController extends Controller
{

    // PID 文件
    const PID_FILE = '/var/run/single.pid';

    // 是否后台运行
    protected $d = false;

    // 启动
    public function actionStart()
    {
        $controllerName = \Mix::app()->request->route('controller');
        // 重复启动处理
        if ($pid = Process::getMasterPid(self::PID_FILE)) {
            return "mix-daemon '{$controllerName}' is running, PID : {$pid}." . PHP_EOL;
        }
        // 启动提示
        echo "mix-daemon '{$controllerName}' start successed." . PHP_EOL;
        // 蜕变为守护进程
        if ($this->d) {
            Process::daemon();
        }
        // 写入 PID 文件
        Process::writePid(self::PID_FILE);
        // 修改进程名称
        Process::setName('mix-daemon: ' . $controllerName);
        // 开始工作
        $this->startWork();
    }

    // 停止
    public function actionStop()
    {
        $controllerName = \Mix::app()->request->route('controller');
        if ($pid = Process::getMasterPid(self::PID_FILE)) {
            Process::kill($pid);
            while (Process::isRunning($pid)) {
                // 等待进程退出
                usleep(100000);
            }
            return "mix-daemon '{$controllerName}' stop completed." . PHP_EOL;
        } else {
            return "mix-daemon '{$controllerName}' is not running." . PHP_EOL;
        }
    }

    // 重启
    public function actionRestart()
    {
        $this->actionStop();
        $this->actionStart();
    }

    // 查看状态
    public function actionStatus()
    {
        $controllerName = \Mix::app()->request->route('controller');
        if ($pid = Process::getMasterPid(self::PID_FILE)) {
            return "mix-daemon '{$controllerName}' is running, PID : {$pid}." . PHP_EOL;
        } else {
            return "mix-daemon '{$controllerName}' is not running." . PHP_EOL;
        }
    }

    // 开始工作
    public function startWork()
    {
        try {
            $this->work();
        } catch (\Exception $e) {
            \Mix::app()->error->exception($e);
            sleep(10); // 休息一会，避免 cpu 出现 100%
            $this->startWork();
        }
    }

    // 执行工作
    public function work()
    {
        // 模型内使用长连接版本的数据库组件，这样组件会自动帮你维护连接不断线
        $tableModel = new \apps\common\models\TableModel();
        // 循环执行任务
        while (true) {
            // 执行业务代码
            // ...
        }
    }

}
