<?php
class PluginMailQueue_create{
  private $settings = null;  
  private $mysql = null;
  private $mail = null;
  function __construct() {
    /*
     * settings
     */
    $this->settings = new PluginWfArray(wfPlugin::getPluginModulesByClass()->get('settings'));
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

    if($this->settings->get('sql')){
      /*
      * sql/users
      */
      $this->settings->set('sql/users', str_replace('[tag]', $this->settings->get('tag'), $this->settings->get('sql/users')));
      /*
      * sql/sum
      * data/sum
      */
      $sum_data = $this->mysql->runSql($this->settings->get('sql/sum'));
      $this->settings->set('data/sum', $sum_data['data'][0]['sum']);
      /*
      * sql/users
      * data/users
      */
      $users_data = $this->mysql->runSql($this->settings->get('sql/users'));
      $this->settings->set('data/users', $users_data['data']);
      /*
      * mail/*
      */
      $this->settings->set('mail/message', str_replace('[sum]', $this->settings->get('data/sum'), $this->settings->get('mail/message')));
      $this->settings->set('mail/body', $this->settings->get('mail/declarment')."\n".$this->settings->get('mail/message'));
      foreach($this->settings->get('data/users') as $k => $v){
        $i = new PluginWfArray($v);
        $this->mail->create(
          $this->settings->get('mail/subject'), 
          $this->settings->get('mail/body'), 
          $i->get('email'), 
          null, 
          null,
          null, 
          null, 
          $i->get('id'), 
          $this->settings->get('tag')
          );
      }
      /**
      * 
      */
      exit($this->settings->get('data/sum').':'.sizeof($this->settings->get('data/users')));
    }elseif($this->settings->get('sql_full')){
      /**
       * sql_full
       */
      $this->settings->set('sql_full', str_replace('[tag]', $this->settings->get('tag'), $this->settings->get('sql_full')));
      $rs = $this->mysql->runSql($this->settings->get('sql_full'));
      if($rs['num_rows']){
        foreach($rs['data'] as $k => $v){
          $rs['data'][$k]['subject'] = $this->settings->get('mail/subject');
          $rs['data'][$k]['body'] = str_replace('[mail_text]', $v['mail_text'], $this->settings->get('mail/message'));
        }
        foreach($rs['data'] as $k => $v){
          $i = new PluginWfArray($v);
          $this->mail->create(
            $i->get('subject'), 
            $i->get('body'), 
            $i->get('email'), 
            null, 
            null,
            null, 
            null, 
            $i->get('id'), 
            $this->settings->get('tag')
            );
        }
      }
      exit($rs['num_rows'].' created');
    }
  }
}