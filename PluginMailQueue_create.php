<?php
class PluginMailQueue_create{
  private $settings = null;  
  private $mysql = null;
  private $mail = null;
  function __construct() {
    /*
     * settings
     */
    $this->settings = new PluginWfArray(wfPlugin::getPluginModulesOne('mail/queue_create')->get('settings'));
    /*
     * mysql
     */
    wfPlugin::includeonce('wf/mysql');
    $this->mysql = new PluginWfMysql();
    $this->mysql->event = false;
    $this->mysql->open($this->settings->get('mysql'));
    /*
     * mail
     */
    wfPlugin::includeonce('mail/queue');
    $this->mail = new PluginMailQueue(true);
  }
  public function page_create(){
    /*
     * tag
     */
    $this->settings->set('tag', str_replace('[date]', date('Y-m-d'), $this->settings->get('tag')));
    /*
     * sql/users
     */
    $this->settings->set('sql/users', str_replace('[tag]', $this->settings->get('tag'), $this->settings->get('sql/users')));
    /*
     * sum
     */
    $sum_data = $this->mysql->runSql($this->settings->get('sql/sum'));
    $this->settings->set('data/sum', $sum_data['data'][0]['sum']);
    /*
     * users
     */
    $users_data = $this->mysql->runSql($this->settings->get('sql/users'));
    $this->settings->set('data/users', $users_data['data']);
    /*
     * mail
     */
    $this->settings->set('mail/message', str_replace('[sum]', $this->settings->get('data/sum'), $this->settings->get('mail/message')));
    $this->settings->set('mail/body', $this->settings->get('mail/declarment')."\n".$this->settings->get('mail/message'));
    foreach($this->settings->get('data/users') as $k => $v){
      $i = new PluginWfArray($v);
      $this->mail->create($this->settings->get('mail/subject'), $this->settings->get('mail/body'), $i->get('email'), $send_id = null, $date_from = null ,$date_to = null, $rank = null, $i->get('id'), $tag = $this->settings->get('tag'), $mail_from = null, $from_name = null, $attachment = array());
    }
    /**
     * 
     */
    exit($this->settings->get('data/sum').':'.sizeof($this->settings->get('data/users')));
  }
}