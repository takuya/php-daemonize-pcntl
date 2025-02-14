<?php

namespace Takuya\PhpDaemonize\Traits;

trait PIDFile {
  
  protected function getPidFilePath( $dirs = ['/run', '/var/run/', '/tmp'], $ext = '.pid' ):string {
    $d = array_values(array_filter($dirs, fn( $e ) => is_dir($e) && is_writable($e),));
    if( empty($d) ) {
      throw new \RuntimeException('not writable '.implode(',', $dirs));
    }
    
    return $d[0].DIRECTORY_SEPARATOR.$this->name.$ext;
  }
  
  protected function register_shutdown_pid_remove_pid_file() {
    $pid_file = $this->getPidFilePath();
    register_shutdown_function(function () use ( $pid_file ) {
      file_exists($pid_file)
      && is_writable($pid_file)
      && unlink($pid_file);
    });
  }
  
  protected function savePidIntoFile( $pid ) {
    file_put_contents($this->getPidFilePath(), $pid);
  }
}