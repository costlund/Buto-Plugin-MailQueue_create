<?php
class PluginMailQueue_create{
  private $settings = null;  
  private $mysql = null;
  private $mail = null;
  private $validate = null;
  private $i18n = null;
  function __construct() {
    /*
     * settings
     */
    $this->settings = new PluginWfArray(wfPlugin::getPluginModulesByClass()->get('settings'));
    /**
     * i18n
     */
    wfPlugin::includeonce('i18n/translate_v1');
    $this->i18n = new PluginI18nTranslate_v1();
    $this->settings->set('mail/subject', $this->i18n->translateFromTheme($this->settings->get('mail/subject')));
    /**
     * validate
     */
    wfPlugin::includeonce('wf/yml');
    $this->validate = new PluginWfYml('/plugin/mail/queue_create/data/validate.yml');
    wfPlugin::validateParams(__CLASS__, __FUNCTION__, $this->validate->get('construct'), $this->settings->get());
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
    $dry = wfRequest::get('dry');
    $localhost = wfServer::isHost('localhost');
    /*
     * tag
     */
    $this->settings->set('tag', wfPhpfunc::str_replace('[date]', date('Y-m-d'), $this->settings->get('tag')));
    $this->settings->set('tag', wfPhpfunc::str_replace('[date_hour]', date('Y-m-d_H'), $this->settings->get('tag')));
    /**
     */
    if($this->settings->get('sql')){
      /**
       * validate
       */
      wfPlugin::validateParams(__CLASS__, __FUNCTION__, $this->validate->get('sql'), $this->settings->get());
      /*
      * sql/users
      */
      $this->settings->set('sql/users', wfPhpfunc::str_replace('[tag]', $this->settings->get('tag'), $this->settings->get('sql/users')));
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
      $this->settings->set('mail/message', wfPhpfunc::str_replace('[sum]', $this->settings->get('data/sum'), $this->settings->get('mail/message')));
      $this->settings->set('mail/body', $this->settings->get('mail/declarment')."\n".$this->settings->get('mail/message'));
      if(!$dry){
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
      }
      /**
      * 
      */
      if($dry && (wfUser::hasRole('webmaster') || $localhost)){
        wfHelp::print($this->settings, true);
      }else{
        exit($this->settings->get('data/sum').':'.sizeof($this->settings->get('data/users')));
      }
    }elseif($this->settings->get('sql_full')){
      /**
       * sql_full
       */
      $this->settings->set('sql_full', wfPhpfunc::str_replace('[tag]', $this->settings->get('tag'), $this->settings->get('sql_full')));
      $rs = $this->mysql->runSql($this->settings->get('sql_full'), null);
      if($rs['num_rows']){
        foreach($rs['data'] as $k => $v){
          $rs['mail'][$k]['subject'] = $this->settings->get('mail/subject');
          $rs['mail'][$k]['email'] = $v['email'];
          $rs['mail'][$k]['id'] = $v['id'];
          $message = $this->settings->get('mail/message');
          if(is_array($message)){
            $message = new PluginWfArray($message);
            $message->setByTag($v);
            $rs['mail'][$k]['body'] = $message->get();
          }else{
            $message = wfPhpfunc::str_replace('[mail_text]', $v['mail_text'], $message);
            $rs['mail'][$k]['body'] = $message;
          }
        }
        /**
         */
        foreach($rs['mail'] as $k => $v){
          $tag = $this->settings->get('tag');
          $subject = $v['subject'];
          foreach($rs['data'][$k] as $k2 => $v2){
            $subject = wfPhpfunc::str_replace("[$k2]", $v2, $subject);
            $tag = wfPhpfunc::str_replace("[$k2]", $v2, $tag);
          }
          $rs['mail'][$k]['subject'] = $subject;
          $rs['mail'][$k]['tag'] = $tag;
        }
        if(!$dry){
          foreach($rs['mail'] as $k => $v){
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
              $i->get('tag') 
              );
          }
        }
      }
      if($dry && (wfUser::hasRole('webmaster') || $localhost)){
        wfHelp::print($this->settings);
        wfHelp::print($rs, true);
      }else{
        exit($rs['num_rows'].' created');
      }
    }else{
      exit(__CLASS__.'.'.__FUNCTION__.' says: Param sql or sql_full is not set!');
    }
  }
}