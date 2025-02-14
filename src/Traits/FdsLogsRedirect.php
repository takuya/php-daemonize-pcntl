<?php

namespace Takuya\PhpDaemonize\Traits;

trait FdsLogsRedirect {
  
  protected array $logs = [1 => null, 2 => null];
  
  protected function changeFds() {
    // disable stdin
    fclose(STDIN);
    // remapping stdout
    if( ! empty($this->logs[1]) ) {
      // NOTICE : この順番は変更しないこと
      // fopen -> fclose -> fopen の３つの処理を連続して行うと、STDOUTを変更できる。
      $GLOBALS['STDOUT'] = fopen($this->logs[1], 'a+');
      fclose(STDOUT);
      $GLOBALS['STDOUT'] = fopen($this->logs[1], 'a+');
    }
    if( ! empty($this->logs[2]) ) {
      // for error_report()
      ini_set('error_log', $this->logs[2]);
      // for php://stderr
      $GLOBALS['STDERR'] = fopen($this->logs[2], 'a+');
      fclose(STDERR);
      $GLOBALS['STDERR'] = fopen($this->logs[2], 'a+');
    }
  }
  
  protected function closeFds() {
    // 再マッピング STDIO
    if( ! empty($this->logs[1]) || ! empty($this->logs[2]) ) {
      $this->changeFds();
    }
  }
  
  protected function setLogsFd( int $idx, string $filename ) {
    $this->logs[$idx] = $filename;
  }
  
  public function setLogFile( string $filename ) {
    $this->setLogsFd(1, $filename);
  }
  
  public function setLogError( string $filename ) {
    $this->setLogsFd(2, $filename);
  }
}