<?php

namespace Takuya\PhpDaemonize;

use Takuya\PhpDaemonize\Traits\PIDFile;
use Takuya\PhpDaemonize\Traits\ForkCallback;
use Takuya\PhpDaemonize\Traits\FdsLogsRedirect;
use Takuya\PhpDaemonize\Traits\StartStopDaemon;
use Takuya\PhpDaemonize\Traits\ServiceUserGroup;
use Takuya\PhpDaemonize\Traits\SavePidSharedMemory;
use function Takuya\Helpers\str_rand;

class PhpDaemonize {
  
  use ForkCallback;
  use PIDFile;
  use SavePidSharedMemory;
  use FdsLogsRedirect;
  use StartStopDaemon;
  use ServiceUserGroup;
  
  public ?int $daemon_pid = null;
  public ?int $daemon_sid = null;
  
  public function __construct( protected $name = null ) {
    $this->name ??= strtolower(
                      preg_replace(
                        '/[A-Z]/',
                        '_$0',
                        lcfirst(
                          array_slice(explode('\\', self::class), -1)[0]))).'-'.str_rand(20);
    $this->ftokey();
  }
  
  public function pidFile():string {
    return $this->getPidFilePath();
  }
  
  protected function double_fork( $func ):int {
    $sid = $this->fork(function ( $pid ) use ( $func ) {
      // 新しいセッショングループになる
      $sid = posix_setsid();
      if( $sid !== $pid ) {
        throw new \RuntimeException('set sid failed.');
      }
      $this->closeFds();
      $this->changeUidGid();
      $cpid = $this->fork(function () use ( $func ) {
        $this->register_shutdown_pid_remove_pid_file();
        $this->savePidIntoFile(posix_getpid());
        chdir($this->working_dir ?? getcwd());
        call_user_func($func);
        exit(0);
      });
      $this->saveDaemonPid($this->sharedMemory(), $cpid);
      exit(0);
    });
    // ensure double fork;
    pcntl_wait($st);
    // read shared memory
    $this->daemon_pid = $this->readDaemonPid($this->sharedMemory());
    $this->freeSharedMemory($this->sharedMemory());
    $this->daemon_sid = $sid;
    
    return $this->daemon_pid;
  }
  
  public function start( $func ) {
    return $this->double_fork($func);
  }
  
  public function stop():bool {
    $this->wait(function () {
      posix_kill($this->daemon_pid, SIGINT);
    });
    
    return $this->isKilled();
  }
  
  public function isAlive():bool {
    return ! $this->isKilled();
  }
  
  public function isKilled():bool {
    $fname = $this->pidFile();
    $this->daemon_pid ??= file_exists($fname) ? file_get_contents($fname):null;
    if(empty($this->daemon_pid)){
      throw new \RuntimeException('process not found');
    }
    return posix_kill($this->daemon_pid, 0) === false;
  }
  
  public function wait( ?callable $func = null ) {
    while($this->isAlive()) {
      usleep(1000*100);
      $func && $func();
    };
  }
}
