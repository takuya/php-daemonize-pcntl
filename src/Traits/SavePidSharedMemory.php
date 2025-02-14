<?php

namespace Takuya\PhpDaemonize\Traits;

use function Takuya\Helpers\str_rand;

trait SavePidSharedMemory {
  
  protected int $shm_size = 4;
  
  protected function saveDaemonPid( \Shmop $shm, int $pid ):int {
    return shmop_write($shm, pack('N', $pid), 0);
  }
  
  protected function readDaemonPid( \Shmop $shm ):int {
    $ret = unpack('N', shmop_read($shm, 0, $this->shm_size));
    
    return $ret[1];
  }
  
  protected function freeSharedMemory( \Shmop $shm ):bool {
    return shmop_delete($shm);
  }
  
  protected function ftokey():int {
    return $this->ftok ??= ftok(__FILE__, str_rand(1));
  }
  
  protected function sharedMemory():bool|\Shmop {
    return shmop_open($this->ftokey(), 'c', 0666, $this->shm_size);
  }
}