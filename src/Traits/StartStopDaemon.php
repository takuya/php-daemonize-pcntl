<?php

namespace Takuya\PhpDaemonize\Traits;


use Takuya\PhpDaemonize\Exceptions\ProcessNotFound;

trait StartStopDaemon {
  
  public static function run_func( callable $func=null, string $name = null, string $stdout = null, string $stderr = null ) {
    //
    $subcmds = ['start','stop','run'];
    $argv = $_SERVER['argv'];
    if ( empty($argv[1]) || !preg_match('/^('.implode('|',$subcmds).')$/', $argv[1]) ){
      throw new \InvalidArgumentException('start/stop');
    }
    switch($argv[1]){
      case 'start':
        $class = self::class;
        /** @var \Takuya\PhpDaemonize\PhpDaemonize $proc */
        $proc = new $class($name);
        $stdout && $proc->setLogFile($stdout);
        $stderr && $proc->setLogError($stderr);
        $proc->start($func);
        return $proc;
      case 'stop':
        /** @var \Takuya\PhpDaemonize\PhpDaemonize $proc */
        $class = self::class;
        $proc = new $class($name);
        try {
          $proc->stop();
          $proc->wait();
        }
        catch(ProcessNotFound $e ){
          fwrite(STDERR,$e->getMessage().PHP_EOL);
        }
        finally {
          $func && $func();
        }
        return $proc;

      case 'run':
        $func();
        return null;
    }
    return null;
  }
}
