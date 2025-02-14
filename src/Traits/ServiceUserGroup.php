<?php

namespace Takuya\PhpDaemonize\Traits;

trait ServiceUserGroup {
  public ?int $daemon_uid = null;
  public ?int $daemon_gid = null;
  
  public function setUserGroup(string $user, string $group=null){
    //
    $u = posix_getpwnam($user);
    if( empty($u) ){
      throw new \InvalidArgumentException('user is not exists.');
    }
    $uid = $u['uid'];
    //
    $groups = array_map(fn($e)=>posix_getgrgid($e),posix_getgroups());
    $groups = array_values(array_filter($groups,fn($e)=>$e['name']==$group));
    if(empty($groups)){
      throw new \Exception('group not found.');
    }
    $gid = $groups[0]['gid'];
    //
    $this->daemon_uid = $uid;
    $this->daemon_gid = $gid;
  }
  protected function changeUidGid(){
    $this->daemon_uid && posix_seteuid($this->daemon_uid);
    $this->daemon_gid && posix_setegid($this->daemon_gid);
  }
  
  public function setDaemonUid( ?int $daemon_uid ):void {
    $this->daemon_uid = $daemon_uid;
  }
  
  public function setDaemonGid( ?int $daemon_gid ):void {
    $this->daemon_gid = $daemon_gid;
  }

  
}