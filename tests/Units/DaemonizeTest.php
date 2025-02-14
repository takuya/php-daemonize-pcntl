<?php

namespace Tests\PhpDaemonize\Units;

use Tests\PhpDaemonize\TestCase;
use Takuya\PhpDaemonize\PhpDaemonize;
use function Takuya\Helpers\str_rand;


class DaemonizeTest extends TestCase {
  
  protected string $tmp_name;
  protected string $tmpLogFile;
  protected string $tmpLogErr;
  
  protected function tearDown():void {
    parent::tearDown();
    foreach ([$this->tmpLogErr, $this->tmpLogFile] as $f) {
      file_exists($f) && unlink($f);
    }
  }
  
  protected function setUp():void {
    parent::setUp();
    $this->tmp_name = sys_get_temp_dir().DIRECTORY_SEPARATOR.str_rand(10);
    $this->tmpLogFile = $this->tmp_name.'log';
    $this->tmpLogErr = $this->tmp_name.'err';
  }
  protected function getProcessInfo($pid):array{
    $command = "ps -o ppid,pid,sid,uid,gid,tty,cmd $pid";
    $output = shell_exec($command);
    $lines = explode("\n", trim($output));
    $headers = preg_split('/\s+/', array_shift($lines));
    $body = $lines[0];
    $processes =array_combine($headers, preg_split('/\s+/', ltrim($body), count($headers)));
    return $processes;
  }
  
  public function test_function_01() {
    $m = new PhpDaemonize();
    $m->start(function () { usleep(1000*1000); });
    $this->assertTrue($m->daemon_pid > 0);
    $this->assertEquals($m->daemon_pid, file_get_contents($m->pidFile()));
    //
    $m->wait();
    $this->assertFileDoesNotExist($m->pidFile());
  }
  
  public function test_process_is_daemonized() {
    $m = new PhpDaemonize();
    $m->start(function () { usleep(1000*1000*10); });
    //
    $pid = $m->daemon_pid;
    $sid = $m->daemon_sid;
    $proc_info = $this->getProcessInfo($pid);
    $m->stop();
    $m->wait();
    $this->assertEquals('?', $proc_info['TT']);
    $this->assertEquals('1', $proc_info['PPID']);
    $this->assertEquals($sid, $proc_info['SID']);
    $this->assertEquals($pid, $proc_info['PID']);
  }
  
  public function test_daemonPidFileRemoved() {
    $m = new PhpDaemonize();
    $m->start(function () { usleep(10); });
    $this->assertFileDoesNotExist($m->pidFile());
  }
  
  public function test_stopDaemon() {
    $m = new PhpDaemonize();
    $m->start(function () { sleep(10); });
    $this->assertTrue($m->isAlive());
    $this->assertFalse($m->isKilled());
    $m->stop();
    $this->assertFalse($m->isAlive());
    $this->assertTrue($m->isKilled());
  }
  
  public function test_daemonPidFileExists() {
    $m = new PhpDaemonize();
    $m->start(function () { sleep(10); });
    $this->assertFileExists($m->pidFile());
  }
  
  public function test_stdout_to_logfile() {
    $tmp = $this->tmpLogFile;
    $key = str_rand(10);
    $m = new PhpDaemonize();
    $m->setLogFile($tmp);
    $m->start(fn() => printf("%s", str_repeat($key.PHP_EOL, 10)));
    $m->wait();
    $cnt = substr_count(file_get_contents($tmp), $key, 0);
    $this->assertEquals(10, $cnt);
  }
  
  public function test_error_report_to_logfile() {
    $tmp = $this->tmpLogErr;
    $key = str_rand(10);
    $m = new PhpDaemonize();
    $m->setLogError($tmp);
    $m->start(fn() => array_map(fn() => error_log('SAMPLE ERROR: '.$key), range(1, 10)));
    $m->wait();
    $cnt = substr_count(file_get_contents($tmp), $key, 0);
    $this->assertEquals(10, $cnt);
  }
  
  public function test_stderr_to_logfile() {
    $tmp = $this->tmpLogErr;
    $key = str_rand(10);
    $m = new PhpDaemonize();
    $m->setLogError($tmp);
    $m->start(fn() => array_map(fn() => fwrite(fopen('php://stderr', 'a'), $key."\n"), range(1, 10)));
    $m->wait();
    $cnt = substr_count(file_get_contents($tmp), $key, 0);
    $this->assertEquals(10, $cnt);
  }
  
  public function test_execute_command(){
    $m = new PhpDaemonize();
    $m->start(function(){
      pcntl_exec('/usr/bin/sleep',[10]);
    });
    $proc = $this->getProcessInfo($m->daemon_pid);
    $m->stop();
    $m->wait();
    $this->assertStringContainsString('/usr/bin/sleep',$proc['CMD']);
  }
  public function test_start_stop_command_line(){
    //
    $_SERVER['argv']= [
      'sample.php',
      'start'
    ];
    $m1 = PhpDaemonize::run_func(fn()=>sleep(10),$name=str_rand(20));
    //
    $_SERVER['argv']= [
      'sample.php',
      'stop'
    ];
    $m2 = PhpDaemonize::run_func(null,$name);
  
    $this->assertFalse($m1->isAlive());
    $this->assertFalse($m2->isAlive());
  }
  public function test_setuid_guid(){
    if ( posix_geteuid() !== 0 ){
      $this->assertTrue(true);
      return ;
    }
    $m = new PhpDaemonize();
    $nobody= $nogroup =65534;
    $m->setDaemonUid($nobody);
    $m->setDaemongid($nogroup);
    $m->start(function(){
      pcntl_exec('/usr/bin/sleep',[10]);
    });
    $proc = $this->getProcessInfo($m->daemon_pid);
    
  
  
  }
}
