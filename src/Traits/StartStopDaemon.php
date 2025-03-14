<?php

namespace Takuya\PhpDaemonize\Traits;


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
        $class = self::class;
        /** @var \Takuya\PhpDaemonize\PhpDaemonize $proc */
        $proc = new $class($name);
        $proc->stop();
        $proc->wait();
        return $proc;
      case 'run':
        $func();
        return null;
    }
    return null;
  }
}